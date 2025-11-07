# Implementación de Sistema de Retenciones en la Fuente

## Estado Actual

El sistema ya tiene soporte básico para retenciones:
- ✅ Campo `tax_amount` en modelo `Expense`
- ✅ Campo `tax_account_id` para especificar la cuenta contable (2365xx)
- ✅ Lógica contable correcta que registra la retención

## Requerimientos Pendientes

### 1. Filtros en el Índice de Gastos

**Ubicación:** `app/Http/Controllers/ExpenseController.php` método `index()`

**Agregar filtros:**

```php
// Filtro por tipo de retención (basado en tax_account_id)
if ($request->filled('retention_type')) {
    $query->where('tax_account_id', $request->retention_type);
}

// Filtro por proveedor
if ($request->filled('provider_id')) {
    $query->where('provider_id', $request->provider_id);
}

// Filtro por nombre de proveedor (búsqueda parcial)
if ($request->filled('provider_search')) {
    $query->where(function ($q) use ($request) {
        $q->where('vendor_name', 'LIKE', '%' . $request->provider_search . '%')
          ->orWhereHas('provider', function ($providerQuery) use ($request) {
              $providerQuery->where('name', 'LIKE', '%' . $request->provider_search . '%');
          });
    });
}
```

**Frontend:** Agregar selectores en `resources/js/pages/Expenses/Index.vue`

---

### 2. Reporte de Retenciones a Pagar

**Crear nuevo controlador:** `app/Http/Controllers/WithholdingTaxReportController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithholdingTaxReportController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::first();

        // Filtros de fecha
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        // Obtener todos los gastos con retención en el período
        $expenses = Expense::forConjunto($conjunto->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->with(['provider', 'taxAccount', 'expenseCategory'])
            ->get();

        // Agrupar por cuenta de retención (subcuentas de 2365)
        $retentionsByAccount = $expenses->groupBy('tax_account_id')
            ->map(function ($group) {
                $account = $group->first()->taxAccount;
                return [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'count' => $group->count(),
                    'total_retained' => $group->sum('tax_amount'),
                    'expenses' => $group->map(function ($expense) {
                        return [
                            'id' => $expense->id,
                            'expense_number' => $expense->expense_number,
                            'vendor_name' => $expense->vendor_name ?? $expense->provider?->name,
                            'expense_date' => $expense->expense_date,
                            'subtotal' => $expense->subtotal,
                            'tax_amount' => $expense->tax_amount,
                            'category' => $expense->expenseCategory?->name,
                        ];
                    })
                ];
            });

        // Total general de retenciones (cuenta 2365)
        $totalRetentions = $expenses->sum('tax_amount');

        // Obtener cuenta principal 2365
        $mainAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', '2365')
            ->first();

        return Inertia::render('Reports/WithholdingTaxReport', [
            'retentionsByAccount' => $retentionsByAccount,
            'totalRetentions' => $totalRetentions,
            'mainAccount' => $mainAccount,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_expenses_with_retention' => $expenses->count(),
                'total_providers' => $expenses->pluck('provider_id')->unique()->count(),
                'average_retention_rate' => $expenses->avg(function ($expense) {
                    return ($expense->tax_amount / $expense->subtotal) * 100;
                }),
            ],
        ]);
    }

    public function export(Request $request)
    {
        // Implementar exportación a Excel/PDF
    }
}
```

**Ruta:** `routes/modules/finance.php`

```php
Route::prefix('retenciones')->group(function () {
    Route::get('/', [WithholdingTaxReportController::class, 'index'])
        ->name('retenciones.index');
    Route::get('/export', [WithholdingTaxReportController::class, 'export'])
        ->name('retenciones.export');
});
```

---

### 3. Certificados de Retención Automáticos

**Crear modelo:** `app/Models/WithholdingCertificate.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingCertificate extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'provider_id',
        'year',
        'certificate_number',
        'total_base', // Total pagado antes de retención
        'total_retained', // Total retenido
        'issued_at',
        'issued_by',
        'pdf_path',
    ];

    protected $casts = [
        'total_base' => 'decimal:2',
        'total_retained' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function details()
    {
        return $this->hasMany(WithholdingCertificateDetail::class);
    }
}
```

**Crear modelo:** `app/Models/WithholdingCertificateDetail.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingCertificateDetail extends Model
{
    protected $fillable = [
        'withholding_certificate_id',
        'expense_id',
        'retention_concept', // Ej: "Honorarios", "Servicios"
        'base_amount',
        'retention_percentage',
        'retained_amount',
        'retention_account_code', // 2365xx
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'retained_amount' => 'decimal:2',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
```

**Migración:**

```bash
php artisan make:migration create_withholding_certificates_tables
```

```php
Schema::create('withholding_certificates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conjunto_config_id')->constrained();
    $table->foreignId('provider_id')->constrained();
    $table->integer('year');
    $table->string('certificate_number')->unique();
    $table->decimal('total_base', 15, 2);
    $table->decimal('total_retained', 15, 2);
    $table->timestamp('issued_at');
    $table->foreignId('issued_by')->constrained('users');
    $table->string('pdf_path')->nullable();
    $table->timestamps();

    $table->unique(['provider_id', 'year']);
});

Schema::create('withholding_certificate_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('withholding_certificate_id')->constrained()->onDelete('cascade');
    $table->foreignId('expense_id')->constrained();
    $table->string('retention_concept');
    $table->decimal('base_amount', 15, 2);
    $table->decimal('retention_percentage', 5, 2);
    $table->decimal('retained_amount', 15, 2);
    $table->string('retention_account_code');
    $table->timestamps();
});
```

**Servicio de generación:** `app/Services/WithholdingCertificateService.php`

```php
<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Provider;
use App\Models\WithholdingCertificate;
use Barryvdh\DomPDF\Facade\Pdf;

class WithholdingCertificateService
{
    public function generateForProvider(Provider $provider, int $year)
    {
        // Obtener todos los gastos con retención del proveedor en el año
        $expenses = Expense::where('provider_id', $provider->id)
            ->whereYear('expense_date', $year)
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->with(['taxAccount', 'expenseCategory'])
            ->get();

        if ($expenses->isEmpty()) {
            return null;
        }

        // Crear certificado
        $certificate = WithholdingCertificate::create([
            'conjunto_config_id' => $provider->conjunto_config_id,
            'provider_id' => $provider->id,
            'year' => $year,
            'certificate_number' => $this->generateCertificateNumber($year),
            'total_base' => $expenses->sum('subtotal'),
            'total_retained' => $expenses->sum('tax_amount'),
            'issued_at' => now(),
            'issued_by' => auth()->id(),
        ]);

        // Crear detalles
        foreach ($expenses as $expense) {
            $certificate->details()->create([
                'expense_id' => $expense->id,
                'retention_concept' => $expense->expenseCategory->name ?? 'Servicio',
                'base_amount' => $expense->subtotal,
                'retention_percentage' => ($expense->tax_amount / $expense->subtotal) * 100,
                'retained_amount' => $expense->tax_amount,
                'retention_account_code' => $expense->taxAccount->code,
            ]);
        }

        // Generar PDF
        $pdf = $this->generatePDF($certificate);

        return $certificate;
    }

    private function generateCertificateNumber(int $year): string
    {
        $lastCertificate = WithholdingCertificate::whereYear('issued_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCertificate ? intval(substr($lastCertificate->certificate_number, -4)) + 1 : 1;

        return sprintf('CERT-RET-%d-%04d', $year, $sequence);
    }

    private function generatePDF(WithholdingCertificate $certificate)
    {
        $pdf = Pdf::loadView('pdfs.withholding-certificate', [
            'certificate' => $certificate->load(['provider', 'details.expense'])
        ]);

        $path = "certificates/retenciones/{$certificate->year}/{$certificate->certificate_number}.pdf";
        Storage::put($path, $pdf->output());

        $certificate->update(['pdf_path' => $path]);

        return $pdf;
    }
}
```

---

### 4. Estado de Resultados - Agregar Cuenta 417005

**Ubicación:** `app/Http/Controllers/FinancialReportController.php` o `AccountingReportsController.php`

En el método que genera el estado de resultados, asegurarse de incluir todas las cuentas de ingreso tipo 41xxxx:

```php
// Ingresos
$incomeAccounts = ChartOfAccounts::forConjunto($conjuntoId)
    ->where('code', 'LIKE', '41%') // Todas las cuentas de ingreso
    ->where('accepts_posting', true)
    ->where('is_active', true)
    ->orderBy('code')
    ->get();
```

Si la cuenta 417005 no aparece, verificar:
1. Que exista en `chart_of_accounts`
2. Que `accepts_posting` = true
3. Que `is_active` = true
4. Que el tipo sea 'income'

---

## Archivos a Crear

1. `app/Http/Controllers/WithholdingTaxReportController.php`
2. `app/Models/WithholdingCertificate.php`
3. `app/Models/WithholdingCertificateDetail.php`
4. `app/Services/WithholdingCertificateService.php`
5. `database/migrations/xxxx_create_withholding_certificates_tables.php`
6. `resources/views/pdfs/withholding-certificate.blade.php`
7. `resources/js/pages/Reports/WithholdingTaxReport.vue`

## Comandos Artisan

```bash
# Generar certificados automáticamente al final del año
php artisan make:command GenerateYearlyWithholdingCertificates

# Dentro del comando:
public function handle()
{
    $year = $this->argument('year') ?? now()->subYear()->year;
    $service = new WithholdingCertificateService();

    $providers = Provider::whereHas('expenses', function ($query) use ($year) {
        $query->whereYear('expense_date', $year)
              ->where('tax_amount', '>', 0);
    })->get();

    foreach ($providers as $provider) {
        $certificate = $service->generateForProvider($provider, $year);
        $this->info("Certificado generado para: {$provider->name}");
    }
}
```

## Testing

```php
// tests/Feature/WithholdingCertificateTest.php
public function test_generates_certificate_with_correct_totals()
{
    $provider = Provider::factory()->create();

    // Crear gastos con retención
    Expense::factory()->count(3)->create([
        'provider_id' => $provider->id,
        'subtotal' => 1000000,
        'tax_amount' => 100000, // 10% retención
        'tax_account_id' => $this->retentionAccount->id,
    ]);

    $service = new WithholdingCertificateService();
    $certificate = $service->generateForProvider($provider, now()->year);

    $this->assertEquals(3000000, $certificate->total_base);
    $this->assertEquals(300000, $certificate->total_retained);
    $this->assertNotNull($certificate->pdf_path);
}
```

---

## Próximos Pasos

1. ✅ Crear las migraciones
2. ✅ Implementar modelos
3. ✅ Crear servicio de certificados
4. ✅ Agregar rutas y controlador
5. ✅ Crear vistas Vue
6. ✅ Agregar filtros al índice de gastos
7. ✅ Verificar cuenta 417005 en estado de resultados
8. ✅ Testing

¿Por dónde quieres empezar?
