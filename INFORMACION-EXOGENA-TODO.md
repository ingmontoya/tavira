# TODO: Implementación Módulo Información Exógena DIAN

## Análisis y Planificación
- [x] Revisar estructura actual de contabilidad (ChartOfAccounts, AccountingTransaction) ✅
- [x] Identificar formatos DIAN aplicables a conjuntos residenciales ✅ (1001, 1003, 1005, 1647)
- [x] Definir configuración de umbrales y montos mínimos ✅ ($100M default)
- [x] Investigar formatos XML/archivo plano requeridos por DIAN ✅ (XML, TXT, Excel)

## Base de Datos
- [x] Crear migración para tabla `exogenous_reports` (reportes generados) ✅
- [x] Crear migración para tabla `exogenous_report_items` (items del reporte) ✅
- [x] Crear migración para tabla `exogenous_report_configurations` (configuración de umbrales) ✅
- [x] Crear índices apropiados para consultas de agregación ✅

## Modelos Backend
- [x] Crear modelo `ExogenousReport` con relaciones ✅
- [x] Crear modelo `ExogenousReportItem` con validaciones ✅
- [x] Crear modelo `ExogenousReportConfiguration` para settings ✅
- [x] Implementar scopes para filtrado por período y tipo ✅

## Servicios de Negocio
- [x] Crear servicio `ExogenousReportGenerator` para generar reportes ✅
- [x] Implementar cálculo de retenciones en la fuente ✅
- [x] Implementar cálculo de pagos a terceros superiores a umbrales ✅
- [x] Implementar cálculo de ingresos recibidos ✅ (parcial, 1005 menos relevante)
- [x] Crear servicio `ExogenousReportExporter` para exportar formatos DIAN ✅

## Controladores y Rutas
- [x] Crear `ExogenousReportController` con métodos CRUD ✅
- [x] Implementar endpoint `POST /api/exogenous-reports/generate` (generar reporte) ✅
- [x] Implementar endpoint `GET /api/exogenous-reports` (listar reportes) ✅
- [x] Implementar endpoint `GET /api/exogenous-reports/{id}` (ver detalle) ✅
- [x] Implementar endpoint `GET /api/exogenous-reports/{id}/export` (exportar) ✅
- [x] Implementar endpoint `DELETE /api/exogenous-reports/{id}` (eliminar) ✅
- [x] Añadir rutas a `routes/modules/accounting.php` ✅

## Frontend - Componentes Base
- [ ] Crear página `resources/js/pages/Accounting/ExogenousReports/Index.vue`
- [ ] Crear página `resources/js/pages/Accounting/ExogenousReports/Create.vue`
- [ ] Crear página `resources/js/pages/Accounting/ExogenousReports/Show.vue`
- [ ] Crear componente `ExogenousReportCard.vue` para vista de tarjetas
- [ ] Crear componente `ExogenousReportFilters.vue` para filtros

## Frontend - Funcionalidad
- [ ] Implementar formulario de generación con selección de período
- [ ] Implementar tabla de items del reporte con paginación
- [ ] Implementar preview del reporte antes de generar
- [ ] Implementar descarga de archivo en formato DIAN
- [ ] Implementar validaciones de formulario
- [ ] Añadir breadcrumbs y navegación

## Formatos de Exportación
- [ ] Investigar especificación técnica formatos DIAN (XML/TXT)
- [ ] Implementar exportador formato 1001 (Pagos o abonos en cuenta)
- [ ] Implementar exportador formato 1003 (Retenciones en la fuente)
- [ ] Implementar exportador formato 1005 (Ingresos recibidos)
- [ ] Implementar validador de estructura de archivos
- [ ] Añadir opción de descarga en Excel para revisión interna

## Configuración
- [ ] Crear seeder para configuraciones por defecto de umbrales
- [ ] Implementar pantalla de configuración de umbrales por año
- [ ] Añadir validación de NIT y datos del conjunto
- [ ] Configurar responsables de firma electrónica

## Validaciones y Seguridad
- [ ] Validar períodos fiscales válidos
- [ ] Validar montos y cálculos
- [ ] Implementar permisos para generar reportes exógenos
- [ ] Implementar audit log para generación de reportes
- [ ] Validar integridad de datos antes de exportar

## Testing
- [ ] Tests unitarios para cálculos de umbrales
- [ ] Tests unitarios para generación de items
- [ ] Tests de integración para generación completa
- [ ] Tests para validación de formatos exportados
- [ ] Tests E2E para flujo completo de usuario

## Documentación
- [ ] Documentar endpoints en comentarios del controlador
- [ ] Crear guía de usuario para generar reportes
- [ ] Documentar configuración de umbrales
- [ ] Añadir ejemplos de archivos generados
- [ ] Documentar calendario de vencimientos DIAN

## Mejoras Futuras (Opcional)
- [ ] Integración directa con API DIAN (si existe)
- [ ] Notificaciones automáticas de fechas límite
- [ ] Dashboard de cumplimiento tributario
- [ ] Histórico de reportes presentados
- [ ] Validador automático contra muisca DIAN

---

**Notas Importantes:**
- Umbrales 2025: Ingresos > $100 millones para personas jurídicas
- Fechas límite: Abril 25 - Junio 13 según NIT
- Formatos principales para conjuntos: 1001, 1003, 1005
- Conservar respaldos mínimo 5 años
- Sanciones por no presentar: 1% de valores omitidos
