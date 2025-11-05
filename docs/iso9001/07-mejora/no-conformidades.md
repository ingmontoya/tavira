# Gestión de No Conformidades y Acciones Correctivas

**Documento**: SGC-019-No-Conformidades
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Definición

**No Conformidad**: Incumplimiento de un requisito (de ISO 9001, del SGC, del cliente, o interno).

**Ejemplos**:
- Bug crítico en producción que afecta a clientes
- Documento del SGC desactualizado
- Objetivo de calidad no cumplido sin acción correctiva
- Proceso no seguido según lo documentado
- Hallazgo de auditoría

---

## Proceso de Gestión

```
DETECTAR → REGISTRAR → ANALIZAR CAUSA RAÍZ → ACCIÓN CORRECTIVA → IMPLEMENTAR → VERIFICAR EFICACIA → CERRAR
```

---

## 1. Detección

**Fuentes de no conformidades**:
- Auditorías internas
- Quejas de clientes
- Bugs críticos en producción
- Incidentes de producción
- Incumplimiento de objetivos de calidad
- Observación directa del equipo

---

## 2. Registro

**Herramienta**: GitHub Issues con label "non-conformity" o sistema de calidad

**Template**: [Registro de No Conformidad](../templates/registro-accion-correctiva.md)

**Información mínima**:
- Fecha de detección
- Descripción de la no conformidad
- Evidencias
- Proceso/área afectada
- Severidad (Crítica / Alta / Media / Baja)
- Responsable asignado

---

## 3. Análisis de Causa Raíz

**Métodos**:

**5 Whys** (para problemas simples):
```
Problema: Bug crítico en producción
¿Por qué? → No se detectó en QA
¿Por qué? → Falta de tests E2E para ese flujo
¿Por qué? → No está en la estrategia de testing
¿Por qué? → Proceso de testing no cubre flujos críticos completamente
¿Por qué? → Falta identificación formal de flujos críticos

→ Causa raíz: No tenemos identificados formalmente los flujos críticos a testear
```

**Diagrama de Ishikawa** (Espina de Pescado) - para problemas complejos:
```
Categorías: Personas, Procesos, Herramientas, Ambiente, etc.
```

---

## 4. Acción Correctiva

**Tipos de acciones**:

**Corrección** (inmediata):
- Soluciona el problema específico
- Ejemplo: Parchear el bug en producción

**Acción Correctiva** (prevención):
- Elimina la causa raíz para que no se repita
- Ejemplo: Documentar flujos críticos y crear tests E2E para todos

**Plan de acción incluye**:
- Descripción de la acción
- Responsable
- Plazo de implementación
- Recursos necesarios

---

## 5. Implementación

- Responsable ejecuta la acción correctiva
- Documenta evidencias de implementación
- Actualiza estado en sistema

---

## 6. Verificación de Eficacia

**Después de 1-3 meses** (según caso):
- ¿Se implementó la acción correctiva?
- ¿La no conformidad dejó de ocurrir?
- ¿Los resultados mejoraron?

**Métodos**:
- Revisión de métricas
- Nueva auditoría
- Seguimiento en retrospectivas

**Si NO es eficaz**: Analizar nuevamente y ajustar acción correctiva

---

## 7. Cierre

Una vez verificada la eficacia:
- Actualizar registro como "Cerrado"
- Documentar lecciones aprendidas
- Comunicar al equipo (si es relevante)

---

## Responsabilidades

| Rol | Responsabilidad |
|-----|-----------------|
| **Cualquier miembro del equipo** | Detectar y reportar no conformidades |
| **Responsable del proceso** | Analizar causa raíz, definir acción correctiva, implementar |
| **Representante del SGC** | Coordinar, hacer seguimiento, verificar eficacia |
| **Director General** | Aprobar acciones correctivas que requieran recursos significativos |

---

## Métricas

- **No conformidades abiertas** (cantidad)
- **Acciones correctivas cerradas en plazo** (% ≥ 90%)
- **No conformidades recurrentes** (cantidad - objetivo: 0)
- **Tiempo promedio de cierre** (días)

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
