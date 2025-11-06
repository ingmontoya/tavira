# Control de Documentos

**Documento**: SGC-010-Control-Documentos
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este procedimiento establece los controles necesarios para crear, revisar, aprobar, distribuir y mantener la documentación del Sistema de Gestión de Calidad.

Cumple con el requisito de la cláusula 7.5 de ISO 9001:2015.

---

## Tipos de Documentos del SGC

### Documentos Estratégicos (Nivel 1)
- Política de Calidad
- Objetivos de Calidad
- Mapa de Procesos

**Aprobación**: Director General

### Documentos Operacionales (Nivel 2)
- Procedimientos documentados (este documento y similares)
- Descripciones de procesos
- Matriz de riesgos

**Aprobación**: Representante del SGC

### Documentos de Trabajo (Nivel 3)
- Instructivos técnicos
- Templates y formatos
- Guías y manuales

**Aprobación**: Responsable del área

### Registros (Nivel 4)
- Evidencia de actividades realizadas (ver [Control de Registros](./control-registros.md))

---

## Ciclo de Vida de los Documentos

```
CREAR → REVISAR → APROBAR → PUBLICAR → USAR → REVISAR PERIÓDICAMENTE → ACTUALIZAR/ARCHIVAR
```

---

## Proceso de Control de Documentos

### 1. Creación de Documentos

**Responsable**: Autor designado (según área)

**Pasos**:
1. Identificar necesidad de nuevo documento
2. Usar template estandarizado (encabezado con: título, código, versión, fecha, aprobador)
3. Redactar contenido claro y conciso
4. Incluir fecha y versión
5. Solicitar revisión

**Formato**: Markdown (.md) para documentos del SGC en repositorio

### 2. Revisión de Documentos

**Responsable**: Representante del SGC o designado

**Qué se revisa**:
- ✅ Claridad y comprensión
- ✅ Alineación con requisitos de ISO 9001:2015
- ✅ Coherencia con otros documentos del SGC
- ✅ Aplicabilidad práctica

**Resultado**: Aprobado / Aprobado con cambios / Rechazado

### 3. Aprobación de Documentos

**Responsables** (según nivel):
- Nivel 1: Director General
- Nivel 2: Representante del SGC
- Nivel 3: Lead del área

**Evidencia**: Firma/nombre en tabla de aprobación del documento

### 4. Publicación y Distribución

**Ubicación central**: Repositorio Git (`/docs/iso9001/`)

**Acceso**: Todo el equipo tiene acceso de lectura

**Comunicación**: Anuncio en canal de Slack/Discord cuando se publique documento nuevo o actualización importante

### 5. Uso de Documentos

- Todos los miembros del equipo deben usar las **versiones vigentes**
- Documentos obsoletos deben ser claramente marcados como "OBSOLETO"

### 6. Revisión Periódica

**Frecuencia**: Indicada en cada documento (generalmente anual)

**Responsable**: Representante del SGC (coordina)

**Resultado**: Mantener vigente / Actualizar / Archivar como obsoleto

### 7. Actualización de Documentos

**Cuando actualizar**:
- Revisión periódica programada
- Cambios en requisitos o regulaciones
- Identificación de mejoras
- Cambios en procesos o estructura

**Proceso**:
1. Modificar documento (nueva versión)
2. Revisar y aprobar cambios
3. Actualizar historial de cambios
4. Publicar nueva versión
5. Marcar versión anterior como obsoleta
6. Comunicar cambios al equipo

### 8. Archivo de Documentos Obsoletos

- Documentos obsoletos se archivan (no se eliminan)
- Se marca claramente como "OBSOLETO - NO USAR"
- Se mantiene por [3 años - A DEFINIR] para referencia histórica

---

## Identificación de Documentos

### Código de Documentos

Formato: `SGC-###-NombreCorto`

Ejemplo:
- `SGC-001-Alcance` = Documento 001: Alcance del SGC
- `SGC-010-Control-Documentos` = Documento 010: Este documento

### Control de Versiones

**Formato**: `Major.Minor`
- **Major** (1.0, 2.0): Cambios significativos
- **Minor** (1.1, 1.2): Cambios menores o correcciones

**Git como sistema de control de versiones**: Los commits de Git proporcionan trazabilidad completa de cambios.

---

## Listado Maestro de Documentos

Se mantiene un listado maestro de todos los documentos del SGC con:
- Código del documento
- Nombre del documento
- Versión actual
- Fecha de última actualización
- Próxima fecha de revisión
- Responsable
- Estado (Vigente / Obsoleto)

**Ubicación**: [docs/iso9001/README.md](../README.md) incluye la estructura completa

**Responsable**: Representante del SGC

---

## Documentos de Origen Externo

**Ejemplos**: ISO 9001:2015, Decreto 2650, leyes colombianas, documentación de proveedores.

**Control**:
- Identificar documentos externos críticos
- Mantener copias actualizadas
- Revisar periódicamente si hay actualizaciones
- Comunicar cambios relevantes al equipo

**Ubicación**: [A COMPLETAR - carpeta de documentos externos]

---

## Documentos Electrónicos vs Físicos

**Preferencia**: Documentos electrónicos (Markdown en repositorio Git)

**Ventajas**:
- Control de versiones automático (Git)
- Accesibilidad remota
- Búsqueda rápida
- Backup automático
- Colaboración eficiente

**Documentos físicos** (si existen): Deben tener sello de "CONTROLADO" y fecha de impresión. Se revisa periódicamente que sigan vigentes.

---

## Protección de Documentos

**Backup**: Repositorio Git en GitHub con backups adicionales

**Acceso**:
- **Lectura**: Todo el equipo
- **Escritura**: Solo personas autorizadas (Rep. SGC + leads)
- **Aprobación**: Según nivel del documento

**Confidencialidad**: Documentos internos del SGC no se comparten externamente sin autorización.

---

## Cambios Urgentes

En caso de cambio urgente necesario (ej: cambio regulatorio crítico):
1. Representante del SGC autoriza cambio temporal
2. Se comunica inmediatamente al equipo
3. Documento se actualiza formalmente dentro de 5 días hábiles
4. Se documenta la excepción

---

## Revisión y Mejora

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
