# Seguimiento y Medición del Desempeño

**Documento**: SGC-016-Seguimiento
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Dashboard de Métricas

**Ubicación**: [A COMPLETAR - URL del dashboard]

**Actualización**: Automática (métricas técnicas) + Manual mensual (métricas de negocio)

**Responsable**: Representante del SGC consolida, cada responsable provee sus métricas

---

## Métricas Monitoreadas

Ver [Objetivos de Calidad](../01-sistema-gestion-calidad/objetivos-calidad.md) para objetivos específicos.

### 1. Disponibilidad y Confiabilidad

- **Uptime mensual** (%)
- **Tiempo de respuesta promedio** (ms)
- **Incidentes críticos** (cantidad)
- **MTTR** (Mean Time To Recovery)

**Fuente**: UptimeRobot, APM, PagerDuty

### 2. Calidad del Código

- **Cobertura de tests** (%)
- **Bugs en producción** (cantidad)
- **Tiempo de resolución bugs críticos** (horas)
- **Deuda técnica estimada** (% del backlog)

**Fuente**: CI coverage reports, GitHub Issues

### 3. Satisfacción del Cliente

- **NPS** (trimestral)
- **CSAT** (promedio post-ticket)
- **Tasa de renovación** (%)
- **Churn rate** (%)
- **Tickets resueltos en SLA** (%)

**Fuente**: Encuestas, CRM, sistema de tickets

### 4. Velocidad de Desarrollo

- **Velocity del equipo** (story points/sprint)
- **Tiempo de despliegue** (minutos)
- **Frecuencia de despliegue** (deploys/semana)
- **Lead time** (sprints)

**Fuente**: Jira/Linear, GitHub, CI/CD logs

### 5. Seguridad

- **Vulnerabilidades sin parchear** (cantidad)
- **Incidentes de seguridad** (cantidad)
- **Backups fallidos** (cantidad)

**Fuente**: Dependabot, npm audit, backup logs

---

## Reportes

**Reporte Mensual**:
- Métricas del mes vs objetivo
- Alertas de desviaciones
- Acciones correctivas en curso

**Reporte Trimestral**:
- Consolidado de 3 meses
- Tendencias
- Análisis de causas raíz
- Propuestas de mejora

**Presentado en**: Revisión por la Dirección

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Representante del SGC | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
