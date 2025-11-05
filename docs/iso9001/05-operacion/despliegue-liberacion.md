# Despliegue y Liberación

**Documento**: SGC-014-Despliegue
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Pipeline CI/CD

```
COMMIT → CI (Tests) → Build → Deploy Staging → QA → Aprobación → Deploy Producción → Monitoring
```

---

## Integración Continua (CI)

**Herramienta**: GitHub Actions

**Triggers**: Cada push a cualquier branch, cada PR

**Pasos del CI**:
1. **Install dependencies** (composer, npm)
2. **Lint code** (Pint, ESLint, Prettier)
3. **Static analysis** (PHPStan, TypeScript check)
4. **Run tests** (PHPUnit, Playwright)
5. **Build assets** (Vite)
6. **Report coverage**

**Ejemplo `.github/workflows/ci.yml`** (simplificado):
```yaml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: ./vendor/bin/pint --test
      - run: php artisan test
```

**Resultado**: ✅ CI Pass = Código listo para merge

---

## Despliegue a Staging

**Trigger**: Merge a branch `develop`

**Proceso** (automático):
1. CI ejecuta y pasa
2. Deploy script ejecuta:
   ```bash
   ssh user@staging-server << 'EOF'
   cd /var/www/tavira-staging
   git pull origin develop
   composer install --no-dev
   npm ci && npm run build
   php artisan migrate --force
   php artisan optimize
   sudo systemctl reload php-fpm
   EOF
   ```
3. Smoke tests automáticos post-deploy
4. Notificación en Slack con resultado

**Staging URL**: [A COMPLETAR - ej: https://staging.tavira.com.co]

---

## Validación en Staging (QA)

**Responsable**: QA Lead

**Checklist**:
- [ ] Deploy exitoso
- [ ] Tests E2E pasan
- [ ] Smoke tests manuales OK
- [ ] Nueva funcionalidad funciona según criterios de aceptación
- [ ] No hay regresiones evidentes
- [ ] Performance aceptable

**Resultado**:
- ✅ **Aprobado**: Listo para producción
- ❌ **Rechazado**: Crear issues con bugs y no deployar

---

## Despliegue a Producción

### Pre-requisitos
- ✅ QA aprobó en staging
- ✅ Product Owner aprobó (para features mayores)
- ✅ Todos los tests pasando
- ✅ Release notes preparados

### Timing
- **Horario preferido**: Martes-Jueves, 10:00 AM - 2:00 PM (horario Colombia)
- **Evitar**: Viernes tarde, fines de semana, vísperas de festivos
- **Downtime esperado**: < 2 minutos (deploy con zero-downtime como objetivo)

### Proceso

**1. Pre-deploy Checklist**
- [ ] Backup de base de datos
- [ ] Rollback plan listo
- [ ] Team notificado
- [ ] Clientes notificados (si hay downtime esperado)

**2. Deploy** (semi-automático, trigger manual):
```bash
# Conectar a servidor de producción
ssh user@prod-server

# Activar modo mantenimiento (si es necesario)
php artisan down --secret="deploy-token"

# Obtener último código
git pull origin main

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Ejecutar migraciones
php artisan migrate --force

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Reiniciar servicios
sudo systemctl reload php-fpm
sudo systemctl restart queue-worker

# Desactivar modo mantenimiento
php artisan up
```

**3. Post-deploy Checklist**
- [ ] Smoke tests (endpoints principales, login, páginas core)
- [ ] Monitoreo de errores (primeros 30 minutos)
- [ ] Verificar logs por errores
- [ ] Confirmación de equipo que todo funciona

**4. Comunicación**
- Notificar en Slack: "✅ Deploy exitoso v1.2.3"
- Actualizar release notes públicas

---

## Rollback

**Cuándo hacer rollback**:
- Bugs críticos en producción
- Errores que afectan funcionalidad core
- Performance degradada significativamente

**Proceso** (< 5 minutos):
```bash
ssh user@prod-server
git revert HEAD
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback
php artisan optimize:clear && php artisan optimize
sudo systemctl reload php-fpm
```

**Post-rollback**:
1. Investigar causa raíz
2. Fix en develop
3. Re-test completamente
4. Deploy fix cuando esté listo

---

## Versionado Semántico

**Formato**: `MAJOR.MINOR.PATCH` (ej: `1.4.2`)

- **MAJOR**: Cambios incompatibles (breaking changes)
- **MINOR**: Nueva funcionalidad (backwards compatible)
- **PATCH**: Bug fixes

**Git Tags**:
```bash
git tag -a v1.4.2 -m "Release v1.4.2: Reporte de cartera"
git push origin v1.4.2
```

---

## Release Notes

**Para cada release, documentar**:
- **Versión** y fecha
- **Nuevas funcionalidades** (con links a PRs/issues)
- **Bug fixes**
- **Cambios menores** (mejoras de performance, refactorización)
- **Breaking changes** (si aplica)
- **Instrucciones de migración** (si aplica)

**Ejemplo**:
```markdown
# Release v1.4.0 - 2025-01-15

## Nuevas Funcionalidades
- **Reporte de Cartera**: Muestra apartamentos con cuotas pendientes (#145)
- **Export a PDF**: Todos los reportes ahora se pueden exportar (#152)

## Bug Fixes
- Fix: Cálculo incorrecto de intereses de mora (#149)
- Fix: Permisos de visualización de reportes (#150)

## Mejoras
- Performance mejorada en dashboard (carga 30% más rápida)
- UI: Mejor contraste en botones principales

## Notas de Deploy
- Ejecutar: `php artisan migrate` (1 nueva migración)
- No requiere cambios de configuración
```

**Ubicación**: `CHANGELOG.md` en el repositorio

---

## Monitoreo Post-Deploy

**Primeros 30 minutos** (crítico):
- Monitorear dashboard de errores
- Verificar métricas de performance
- Revisar logs por errores inesperados

**Primeras 24 horas**:
- Monitorear tickets de soporte
- Verificar métricas de uso

**Herramientas**:
- UptimeRobot / Pingdom (uptime)
- Sentry / Bugsnag (error tracking)
- Laravel Telescope (debugging)
- New Relic / Datadog (APM)

---

## Feature Flags

**Para features grandes/riesgosas**: Implementar feature flags para activar/desactivar sin deploy.

**Beneficios**:
- Deploy code sin activar feature
- Test en producción con usuarios seleccionados
- Rollback instantáneo sin re-deploy

**Herramientas**: Laravel Pennant o similar

---

## Ambientes

| Ambiente | URL | Branch | Deploy | Propósito |
|----------|-----|--------|--------|-----------|
| **Local** | localhost | feature/* | Manual | Desarrollo |
| **Staging** | staging.tavira.com.co | develop | Auto | QA |
| **Production** | tavira.com.co | main | Manual | Usuarios finales |

---

## Métricas de Despliegue

| Métrica | Objetivo | Medición |
|---------|----------|----------|
| Tiempo de deploy | ≤ 30 minutos | Por deploy |
| Frecuencia de deploy | ≥ 1 por semana | Semanal |
| Tasa de éxito de deploys | ≥ 95% | Mensual |
| Rollbacks necesarios | ≤ 1 cada 10 deploys | Mensual |

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| DevOps Lead | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
