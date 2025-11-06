# Control de Registros

**Documento**: SGC-011-Control-Registros
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este procedimiento establece los controles para la identificación, almacenamiento, protección, recuperación, retención y disposición de los registros del Sistema de Gestión de Calidad.

Los registros proporcionan evidencia de conformidad con los requisitos y de la operación eficaz del SGC.

Cumple con el requisito de la cláusula 7.5 de ISO 9001:2015.

---

## Tipos de Registros del SGC

| Tipo de Registro | Ejemplos | Requisito ISO 9001 |
|------------------|----------|-------------------|
| **Competencia del personal** | Certificaciones, registros de capacitación, evaluaciones | 7.2 |
| **Revisión por la dirección** | Actas de revisión con decisiones | 9.3 |
| **Auditorías internas** | Informes de auditoría, no conformidades | 9.2 |
| **No conformidades** | Registros de problemas, análisis de causa raíz, acciones correctivas | 10.2 |
| **Seguimiento de métricas** | Reportes de KPIs, dashboards | 9.1 |
| **Incidentes** | Post-mortems, reportes de downtime | 8.5 |
| **Riesgos** | Matriz de riesgos actualizada | 6.1 |
| **Satisfacción del cliente** | Encuestas NPS, CSAT, tickets resueltos | 9.1.2 |
| **Desempeño de proveedores** | Evaluaciones de hosting, SLA tracking | 8.4 |
| **Cambios en el SGC** | Historial de cambios en documentos | 7.5 |

---

## Identificación de Registros

Cada registro debe ser claramente identificable con:
- **Nombre/Título** descriptivo
- **Fecha** de creación
- **Responsable** que generó el registro
- **Versión** (si aplica)
- **Código** (para registros formales, ej: AUDIT-2025-01)

---

## Almacenamiento de Registros

### Ubicación Central

| Tipo de Registro | Ubicación | Formato |
|------------------|-----------|---------|
| Registros de auditorías | [Carpeta en Drive / Notion - A COMPLETAR] | PDF/MD |
| Registros de capacitación | [Sistema HRIS / Spreadsheet - A COMPLETAR] | Excel/Google Sheets |
| Actas de revisión por dirección | [Carpeta en Drive - A COMPLETAR] | PDF/MD |
| Registros de incidentes | [GitHub Issues / PagerDuty - A COMPLETAR] | Digital |
| Métricas y KPIs | [Dashboard / Grafana / Spreadsheet - A COMPLETAR] | Digital |
| No conformidades | [GitHub Issues / Spreadsheet - A COMPLETAR] | Digital |
| Encuestas de satisfacción | [TypeForm / Google Forms - A COMPLETAR] | Digital |

**Principio**: Usar herramientas que ya usa el equipo, evitar duplicidad.

---

## Protección de Registros

### Contra Pérdida

- ✅ **Backup automático** de sistemas digitales (Google Drive, GitHub, etc.)
- ✅ **Redundancia**: Copias en múltiples ubicaciones
- ✅ **Cloud storage** confiable con SLA alto

### Contra Deterioro

- ✅ **Formato digital** preferido sobre físico
- ✅ **Formatos estándar** (PDF, Markdown, Excel) que no se vuelven obsoletos

### Contra Acceso No Autorizado

- ✅ **Control de acceso**: Solo personas autorizadas pueden leer/modificar
- ✅ **Logs de acceso**: En sistemas críticos
- ✅ **Encriptación**: Para registros sensibles (datos personales)

### Confidencialidad

- Datos de clientes protegidos según Ley 1581 (Colombia) / GDPR
- Registros internos no se comparten sin autorización
- Acuerdos de confidencialidad firmados por el equipo

---

## Recuperación de Registros

**Requisito**: Los registros deben ser recuperables cuando se necesiten (ej: durante auditorías).

**Método**:
- **Nomenclatura clara** de archivos y carpetas
- **Índice o listado** de registros importantes
- **Búsqueda**: Herramientas de búsqueda en Drive, GitHub, etc.

**Tiempo de recuperación esperado**: < 15 minutos para cualquier registro de los últimos 3 años

---

## Retención de Registros

| Tipo de Registro | Período de Retención | Justificación |
|------------------|----------------------|---------------|
| Auditorías internas | 5 años | Requisito típico de certificación |
| Revisiones por la dirección | 5 años | Requisito típico de certificación |
| No conformidades y acciones correctivas | 5 años | Evidencia de mejora continua |
| Capacitaciones | Durante empleo + 3 años | Evidencia de competencia |
| Incidentes de producción | 3 años | Análisis de tendencias |
| Satisfacción del cliente | 3 años | Análisis de tendencias |
| Métricas y KPIs | 3 años | Análisis de tendencias |
| Evaluación de proveedores | Durante vigencia del contrato + 1 año | Gestión de proveedores |

**Nota**: Ajustar según requisitos legales específicos de Colombia.

---

## Disposición de Registros

**Al finalizar el período de retención**:

### Registros Digitales
- **Archivar** en carpeta de "Histórico" o "Archivo"
- **Eliminar** después de 1 año adicional en archivo
- Registros con datos personales: **Eliminar de forma segura** cumpliendo Ley 1581

### Registros Físicos (si existen)
- **Destruir** mediante trituración
- **Registrar** la destrucción (qué se destruyó, cuándo, por quién)

---

## Registros Requeridos por ISO 9001:2015

ISO 9001:2015 no prescribe registros específicos, pero requiere "información documentada como evidencia". Los siguientes son necesarios:

✅ **7.2**: Evidencia de competencia del personal
✅ **9.2.2**: Programa de auditoría y resultados
✅ **9.3.3**: Salidas de la revisión por la dirección
✅ **10.2.2**: Naturaleza de no conformidades, acciones tomadas, resultados

**Adicionales útiles** (no obligatorios pero recomendados):
- Registros de capacitación
- Registros de satisfacción del cliente
- Registros de seguimiento de métricas
- Registros de incidentes y post-mortems

---

## Formatos de Registros

Se proporcionan templates en [/templates](../templates/):
- Registro de revisión por la dirección
- Registro de auditoría interna
- Registro de capacitación
- Registro de incidencia/bug
- Registro de acción correctiva

**Usar estos templates** asegura consistencia y completitud.

---

## Responsabilidades

| Rol | Responsabilidad |
|-----|-----------------|
| **Representante del SGC** | Coordinar el sistema de registros, asegurar cumplimiento de retención |
| **Responsables de procesos** | Generar y mantener registros de sus procesos |
| **Director General** | Aprobar política de retención |
| **Todo el equipo** | Generar registros cuando sea requerido, proteger confidencialidad |

---

## Auditoría de Registros

Durante auditorías internas, se verifica:
- ✅ Registros requeridos existen y están completos
- ✅ Registros son legibles y recuperables
- ✅ Retención cumple con política establecida
- ✅ Registros están protegidos adecuadamente

---

## Mejora del Sistema de Registros

Si se identifican problemas:
- Registros difíciles de encontrar → Mejorar nomenclatura/organización
- Registros incompletos → Mejorar templates o capacitación
- Registros perdidos → Mejorar backups
- Sobrecarga de registros → Simplificar, eliminar registros innecesarios

---

## Revisión

Este procedimiento debe revisarse anualmente.

**Próxima revisión**: [Fecha +12 meses - A COMPLETAR]

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Representante del SGC | [A COMPLETAR] | | 2025-11-04 |
| Director General | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del procedimiento | [A COMPLETAR] |
