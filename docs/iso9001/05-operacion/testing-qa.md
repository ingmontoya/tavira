# Testing y Aseguramiento de Calidad

**Documento**: SGC-013-Testing-QA
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Estrategia de Testing

### Pirámide de Testing

```
         /\
        /E2E\       ← Pocos, lentos, alto valor (10%)
       /──────\
      /  API   \    ← Moderados, rápidos (30%)
     /──────────\
    /   UNIT     \  ← Muchos, muy rápidos (60%)
   /──────────────\
```

---

## 1. Tests Unitarios

**Herramientas**: PHPUnit / Pest (backend), Jest/Vitest (frontend si aplica)

**Qué probar**:
- Lógica de negocio en modelos y servicios
- Cálculos (ej: contabilidad, totales)
- Validaciones
- Helpers y utilities

**Ejemplo** (Pest):
```php
test('calcula correctamente el saldo de un apartamento', function () {
    $apartment = Apartment::factory()->create();
    Invoice::factory()->create(['apartment_id' => $apartment->id, 'amount' => 100]);
    Payment::factory()->create(['apartment_id' => $apartment->id, 'amount' => 60]);

    expect($apartment->balance())->toBe(40);
});
```

**Ejecutar**:
```bash
php artisan test
```

**Coverage objetivo**: ≥ 80% en módulos críticos (contabilidad, autenticación)

---

## 2. Tests de Integración

**Qué probar**:
- Endpoints de API
- Interacción entre componentes
- Flujos completos backend

**Ejemplo**:
```php
test('usuario puede crear una factura', function () {
    $user = User::factory()->admin()->create();
    $apartment = Apartment::factory()->create();

    $this->actingAs($user)
        ->post('/api/invoices', [
            'apartment_id' => $apartment->id,
            'amount' => 100,
            'due_date' => now()->addDays(30),
        ])
        ->assertStatus(201)
        ->assertJson(['amount' => 100]);

    $this->assertDatabaseHas('invoices', ['apartment_id' => $apartment->id]);
});
```

---

## 3. Tests End-to-End (E2E)

**Herramienta**: Playwright

**Qué probar**:
- Flujos críticos de usuario
- Casos de uso principales
- Navegación completa

**Ejemplo**:
```typescript
test('admin puede crear un residente', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'admin@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');

  await page.click('text=Residentes');
  await page.click('text=Crear Residente');
  await page.fill('input[name="name"]', 'Juan Pérez');
  await page.fill('input[name="email"]', 'juan@example.com');
  await page.click('button:has-text("Guardar")');

  await expect(page.locator('text=Juan Pérez')).toBeVisible();
});
```

**Ejecutar**:
```bash
npm run test:e2e
```

**Frecuencia**: Antes de cada deploy a staging/producción

---

## 4. Tests Manuales

**Cuándo**:
- Exploratory testing
- Usabilidad y UX
- Funcionalidades complejas difíciles de automatizar

**Checklist de testing manual**:
- [ ] Funcionalidad básica funciona
- [ ] UI se ve correctamente (responsive)
- [ ] Validaciones funcionan
- [ ] Mensajes de error claros
- [ ] Permisos por rol funcionan

---

## Proceso de QA

### Pre-deployment (Staging)

1. **Developer** completa feature y tests pasan
2. **Deploy a staging** (automático vía CI/CD)
3. **QA Lead** valida en staging:
   - Ejecuta tests E2E
   - Realiza smoke tests manuales
   - Verifica criterios de aceptación de la user story
4. **QA Lead** aprueba o rechaza:
   - ✅ **Aprobado**: Listo para producción
   - ❌ **Rechazado**: Crea issue con bugs encontrados, regresa a desarrollo

---

## Ambientes de Testing

| Ambiente | Propósito | Deploy | Datos |
|----------|-----------|--------|-------|
| **Local** | Desarrollo | Manual | Seeders de prueba |
| **Staging** | QA pre-producción | Automático (CI/CD) | Copia de producción (anonimizada) o seeders |
| **Production** | Usuarios reales | Manual (aprobado por QA) | Datos reales |

---

## Cobertura de Tests

**Medición**:
```bash
php artisan test --coverage
```

**Objetivo**:
- Módulos críticos (contabilidad, autenticación, pagos): ≥ 80%
- Módulos no críticos: ≥ 60%

**Responsable**: Tech Lead monitorea semanalmente

---

## Bug Tracking

**Cuando se encuentra un bug**:
1. **Crear issue** en GitHub con label "bug" y prioridad
2. **Descripción clara**:
   - Pasos para reproducir
   - Comportamiento esperado vs actual
   - Screenshots/videos si es útil
   - Severidad (crítico, alto, medio, bajo)
3. **Asignar** a developer
4. **Resolver** y validar fix
5. **Cerrar** issue una vez validado

**Severidades**:
- **Crítico**: Bloquea funcionalidad core, pérdida de datos, caída de sistema
- **Alto**: Funcionalidad importante no funciona, workaround disponible
- **Medio**: Funcionalidad menor no funciona
- **Bajo**: Problema cosmético, no afecta funcionalidad

**SLA de resolución** (ver [Objetivos de Calidad](../01-sistema-gestion-calidad/objetivos-calidad.md)):
- Crítico: ≤ 24 horas
- Alto: ≤ 1 semana
- Medio/Bajo: Según priorización del backlog

---

## Regression Testing

**Cada release**:
- ✅ Ejecutar suite completa de tests automatizados
- ✅ Smoke tests manuales de funcionalidades principales

**Prevenir regresiones**:
- Tests automatizados para bugs encontrados
- CI ejecuta tests en cada PR

---

## Performance Testing

**Periodicidad**: Trimestral o cuando se sospeche problema de performance

**Qué probar**:
- Tiempo de carga de páginas principales
- Tiempo de respuesta de APIs
- Queries lentas (usar Laravel Debugbar / Telescope)

**Herramientas**:
- Laravel Telescope (desarrollo)
- APM (Application Performance Monitoring) en producción
- Lighthouse (performance de frontend)

---

## Security Testing

**Checklist de seguridad** (revisar en QA):
- [ ] Inputs validados y sanitizados
- [ ] Autenticación y autorización correctas
- [ ] No hay inyección SQL (usar Eloquent ORM)
- [ ] No hay XSS (templates escapean HTML automáticamente)
- [ ] CSRF protection habilitado
- [ ] Datos sensibles encriptados

**Security audit**: Trimestral (ver [Gestión de Riesgos](../03-planificacion/riesgos-oportunidades.md))

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| QA Lead | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
