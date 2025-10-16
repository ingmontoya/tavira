# Ejemplos Prácticos de Sincronización de Proveedores

## Ejemplos de Código Real

### 1. Crear Proveedor Global desde Panel de Administración Central

```php
<?php

namespace App\Http\Controllers\Central;

use App\Models\Central\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'document_number' => 'nullable|unique:providers,document_number',
            // ... más validaciones
        ]);

        // Crear proveedor en base central
        $provider = Provider::create($validated);

        // El evento SyncedResourceSaved se dispara automáticamente
        // y sincroniza a todos los tenants en background (usando cola)

        return redirect()
            ->route('central.providers.index')
            ->with('success', 'Proveedor creado y sincronizado a todos los conjuntos.');
    }

    public function update(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            // ... más validaciones
        ]);

        // Actualizar proveedor central
        $provider->update($validated);

        // Los cambios se propagan automáticamente

        return redirect()
            ->route('central.providers.show', $provider)
            ->with('success', 'Proveedor actualizado en todos los conjuntos.');
    }

    public function destroy(Provider $provider)
    {
        // Soft delete en central
        $provider->delete();

        // Se elimina (soft delete) automáticamente en todos los tenants

        return redirect()
            ->route('central.providers.index')
            ->with('success', 'Proveedor eliminado de todos los conjuntos.');
    }
}
```

### 2. Consultar Proveedores desde un Tenant

```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = Provider::query()
            ->with('createdBy') // Usuario que creó proveedores locales
            ->orderBy('name');

        // Filtrar por categoría
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filtrar solo activos
        if ($request->boolean('only_active')) {
            $query->active();
        }

        // Filtrar solo proveedores globales o locales
        if ($request->filled('type')) {
            if ($request->type === 'global') {
                $query->whereNotNull('global_provider_id');
            } elseif ($request->type === 'local') {
                $query->whereNull('global_provider_id');
            }
        }

        $providers = $query->paginate(20);

        return Inertia::render('Providers/Index', [
            'providers' => $providers,
            'filters' => $request->only(['category', 'only_active', 'type']),
        ]);
    }

    public function show(Provider $provider)
    {
        $provider->load(['expenses', 'maintenanceRequests']);

        return Inertia::render('Providers/Show', [
            'provider' => $provider,
            'isGlobal' => $provider->isSynced(),
            'statistics' => [
                'total_expenses' => $provider->expenses->count(),
                'total_amount' => $provider->expenses->sum('total_amount'),
                'maintenance_requests' => $provider->maintenanceRequests->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // Solo permitir crear proveedores locales desde tenant
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            // ... más validaciones
        ]);

        $provider = Provider::create([
            ...$validated,
            'created_by' => auth()->id(), // Marcar quién lo creó
            // No se especifica global_provider_id, es local
        ]);

        return redirect()
            ->route('providers.show', $provider)
            ->with('success', 'Proveedor local creado exitosamente.');
    }

    public function update(Request $request, Provider $provider)
    {
        // Verificar que no sea un proveedor sincronizado
        if ($provider->isSynced()) {
            return back()->with('error',
                'No se puede modificar un proveedor global. Los cambios deben hacerse desde la administración central.'
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            // ... más validaciones
        ]);

        $provider->update($validated);

        return redirect()
            ->route('providers.show', $provider)
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Provider $provider)
    {
        // Verificar que no sea sincronizado
        if ($provider->isSynced()) {
            return back()->with('error',
                'No se puede eliminar un proveedor global desde el tenant.'
            );
        }

        // Verificar que no tenga registros relacionados
        if (!$provider->canBeDeleted()) {
            return back()->with('error',
                'No se puede eliminar el proveedor porque tiene gastos o solicitudes de mantenimiento asociadas.'
            );
        }

        $provider->delete();

        return redirect()
            ->route('providers.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }
}
```

### 3. Componente Vue para Mostrar Badge de Proveedor

```vue
<!-- resources/js/components/ProviderBadge.vue -->
<script setup lang="ts">
interface Provider {
  id: number
  name: string
  global_provider_id: number | null
  is_active: boolean
}

interface Props {
  provider: Provider
  showType?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showType: true,
})

const isGlobal = computed(() => props.provider.global_provider_id !== null)
const badgeColor = computed(() => {
  if (!props.provider.is_active) return 'gray'
  return isGlobal.value ? 'blue' : 'green'
})
</script>

<template>
  <div class="flex items-center gap-2">
    <span class="font-medium">{{ provider.name }}</span>

    <Badge v-if="showType" :variant="badgeColor">
      {{ isGlobal ? 'Global' : 'Local' }}
    </Badge>

    <Badge v-if="!provider.is_active" variant="destructive">
      Inactivo
    </Badge>
  </div>
</template>
```

### 4. Policy para Controlar Permisos

```php
<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    /**
     * Determine if the user can update the provider.
     */
    public function update(User $user, Provider $provider): bool
    {
        // No se pueden editar proveedores globales desde tenant
        if ($provider->isSynced()) {
            return false;
        }

        // Verificar permisos del usuario
        return $user->can('providers.edit');
    }

    /**
     * Determine if the user can delete the provider.
     */
    public function delete(User $user, Provider $provider): bool
    {
        // No se pueden eliminar proveedores globales desde tenant
        if ($provider->isSynced()) {
            return false;
        }

        // Solo puede eliminar si no tiene registros relacionados
        if (!$provider->canBeDeleted()) {
            return false;
        }

        return $user->can('providers.delete');
    }

    /**
     * Determine if the user can create providers.
     */
    public function create(User $user): bool
    {
        // Los tenants pueden crear proveedores locales
        return $user->can('providers.create');
    }
}
```

### 5. Job para Sincronización Asíncrona Avanzada

```php
<?php

namespace App\Jobs;

use App\Models\Central\Provider as CentralProvider;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProviderToTenants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $centralProviderId,
        private ?array $tenantIds = null
    ) {}

    public function handle(): void
    {
        $centralProvider = CentralProvider::on('central')->find($this->centralProviderId);

        if (!$centralProvider) {
            Log::warning("Central provider {$this->centralProviderId} not found");
            return;
        }

        // Obtener tenants específicos o todos
        $tenants = $this->tenantIds
            ? Tenant::whereIn('id', $this->tenantIds)->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($centralProvider) {
                    \App\Models\Provider::updateOrCreate(
                        ['global_provider_id' => $centralProvider->id],
                        [
                            'name' => $centralProvider->name,
                            'category' => $centralProvider->category,
                            'phone' => $centralProvider->phone,
                            'email' => $centralProvider->email,
                            'address' => $centralProvider->address,
                            'document_type' => $centralProvider->document_type,
                            'document_number' => $centralProvider->document_number,
                            'city' => $centralProvider->city,
                            'country' => $centralProvider->country,
                            'contact_name' => $centralProvider->contact_name,
                            'contact_phone' => $centralProvider->contact_phone,
                            'contact_email' => $centralProvider->contact_email,
                            'notes' => $centralProvider->notes,
                            'tax_regime' => $centralProvider->tax_regime,
                            'is_active' => $centralProvider->is_active,
                        ]
                    );
                });

                Log::info("Provider {$centralProvider->id} synced to tenant {$tenant->id}");
            } catch (\Exception $e) {
                Log::error("Failed to sync provider to tenant {$tenant->id}: {$e->getMessage()}");
            }
        }
    }
}
```

### 6. Test Unitario

```php
<?php

namespace Tests\Feature;

use App\Models\Central\Provider as CentralProvider;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_provider_syncs_to_tenants()
    {
        // Crear tenants de prueba
        $tenant1 = Tenant::create(['id' => 'test-tenant-1']);
        $tenant2 = Tenant::create(['id' => 'test-tenant-2']);

        // Crear proveedor central
        $centralProvider = CentralProvider::create([
            'name' => 'Test Provider',
            'category' => 'Testing',
            'email' => 'test@example.com',
        ]);

        // Verificar que se sincronizó a ambos tenants
        $tenant1->run(function () use ($centralProvider) {
            $this->assertDatabaseHas('providers', [
                'global_provider_id' => $centralProvider->id,
                'name' => 'Test Provider',
            ]);
        });

        $tenant2->run(function () use ($centralProvider) {
            $this->assertDatabaseHas('providers', [
                'global_provider_id' => $centralProvider->id,
                'name' => 'Test Provider',
            ]);
        });
    }

    public function test_updating_central_provider_updates_tenants()
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        $centralProvider = CentralProvider::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        // Actualizar proveedor central
        $centralProvider->update(['name' => 'Updated Name']);

        // Verificar actualización en tenant
        $tenant->run(function () {
            $this->assertDatabaseHas('providers', [
                'name' => 'Updated Name',
            ]);
        });
    }

    public function test_local_provider_is_not_synced()
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);

        $tenant->run(function () {
            // Crear proveedor local
            $localProvider = Provider::create([
                'name' => 'Local Provider',
                'email' => 'local@example.com',
                // No tiene global_provider_id
            ]);

            $this->assertFalse($localProvider->isSynced());
            $this->assertNull($localProvider->global_provider_id);
        });
    }
}
```

### 7. Seeder para Proveedores Globales

```php
<?php

namespace Database\Seeders;

use App\Models\Central\Provider;
use Illuminate\Database\Seeder;

class CentralProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Ferretería Nacional',
                'category' => 'Construcción',
                'phone' => '6012345678',
                'email' => 'ventas@ferreterianacional.com',
                'document_type' => 'NIT',
                'document_number' => '900123456-7',
                'city' => 'Bogotá',
                'tax_regime' => 'Responsable IVA',
            ],
            [
                'name' => 'Pinturas y Acabados S.A.',
                'category' => 'Pintura',
                'phone' => '6018765432',
                'email' => 'contacto@pinturasacabados.com',
                'document_type' => 'NIT',
                'document_number' => '800987654-3',
                'city' => 'Medellín',
                'tax_regime' => 'Responsable IVA',
            ],
            [
                'name' => 'Servicios de Jardinería Integral',
                'category' => 'Jardinería',
                'phone' => '3001234567',
                'email' => 'info@jardineriaintegral.com',
                'document_type' => 'NIT',
                'document_number' => '900555444-2',
                'city' => 'Cali',
                'tax_regime' => 'Régimen Simplificado',
            ],
        ];

        foreach ($providers as $providerData) {
            Provider::create($providerData);
        }

        $this->command->info('Proveedores centrales creados: ' . count($providers));
    }
}
```

```bash
# Ejecutar seeder en base central
php artisan db:seed --class=CentralProviderSeeder --database=central

# Los proveedores se sincronizarán automáticamente a todos los tenants
```

### 8. Comando Artisan para Auditar Sincronización

```php
<?php

namespace App\Console\Commands;

use App\Models\Central\Provider as CentralProvider;
use App\Models\Tenant;
use Illuminate\Console\Command;

class AuditProviderSync extends Command
{
    protected $signature = 'sync:audit-providers';
    protected $description = 'Audit provider synchronization across tenants';

    public function handle(): int
    {
        $this->info('Auditing provider synchronization...');

        $centralProviders = CentralProvider::on('central')->count();
        $this->info("Central providers: {$centralProviders}");

        $tenants = Tenant::all();
        $results = [];

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, &$results, $centralProviders) {
                $syncedCount = \App\Models\Provider::whereNotNull('global_provider_id')->count();
                $localCount = \App\Models\Provider::whereNull('global_provider_id')->count();
                $totalCount = \App\Models\Provider::count();

                $results[] = [
                    $tenant->id,
                    $syncedCount,
                    $localCount,
                    $totalCount,
                    $syncedCount === $centralProviders ? '✓' : '✗',
                ];
            });
        }

        $this->table(
            ['Tenant', 'Synced', 'Local', 'Total', 'Status'],
            $results
        );

        return self::SUCCESS;
    }
}
```

## Casos de Uso Reales

### Caso 1: Agregar Categorías de Proveedores

```php
// En la migración central
Schema::table('providers', function (Blueprint $table) {
    $table->enum('category', [
        'Construcción',
        'Pintura',
        'Jardinería',
        'Plomería',
        'Electricidad',
        'Limpieza',
        'Seguridad',
        'Otro'
    ])->nullable()->change();
});

// Actualizar modelos existentes
Provider::whereNull('category')->update(['category' => 'Otro']);
```

### Caso 2: Dashboard de Proveedores Globales

```php
public function dashboard()
{
    $stats = [
        'total_providers' => CentralProvider::count(),
        'active_providers' => CentralProvider::active()->count(),
        'by_category' => CentralProvider::select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get(),
        'total_tenants' => Tenant::count(),
        'recent_providers' => CentralProvider::latest()->take(5)->get(),
    ];

    return Inertia::render('Central/Providers/Dashboard', [
        'stats' => $stats,
    ]);
}
```

Esta guía proporciona ejemplos completos y funcionales que puedes adaptar a tus necesidades específicas.
