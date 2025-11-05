# Proceso de Desarrollo de Software

**Documento**: SGC-012-Desarrollo
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este documento describe el proceso de desarrollo de software para la plataforma Tavira, desde la concepción de una funcionalidad hasta su implementación y entrega.

Cumple con los requisitos de las cláusulas 8.1, 8.2, 8.3, 8.5 y 8.6 de ISO 9001:2015.

---

## Metodología Ágil - Scrum

Utilizamos **Scrum** con sprints de **2 semanas**.

### Roles Scrum
- **Product Owner**: Define qué construir y prioriza backlog
- **Scrum Master** (puede ser Tech Lead): Facilita el proceso, elimina impedimentos
- **Development Team**: Developers, QA, DevOps

### Eventos Scrum
- **Sprint Planning**: Cada 2 semanas, 2-3 horas
- **Daily Standup**: Diario, 15 minutos
- **Sprint Review**: Cada 2 semanas, 1 hora
- **Sprint Retrospective**: Cada 2 semanas, 1 hora

---

## Flujo de Desarrollo

```
IDEA/REQUISITO → USER STORY → PLANIFICACIÓN → DESARROLLO → CODE REVIEW → QA → DEPLOY
```

---

## 1. Requisitos y User Stories

### Origen de Requisitos
- Solicitudes de clientes
- Roadmap del producto
- Bugs reportados
- Mejoras internas
- Requisitos regulatorios

### Documentación de User Stories

**Formato**:
```
Como [tipo de usuario]
Quiero [funcionalidad]
Para [beneficio/objetivo]

Criterios de Aceptación:
- [ ] Criterio 1
- [ ] Criterio 2
- [ ] Criterio 3
```

**Ejemplo**:
```
Como administrador de conjunto
Quiero generar un reporte de cartera
Para identificar apartamentos con cuotas pendientes

Criterios de Aceptación:
- [ ] Muestra listado de apartamentos con saldo pendiente
- [ ] Incluye monto y fecha de última cuota pagada
- [ ] Permite exportar a PDF
- [ ] Respeta permisos por rol
```

**Herramienta**: [Jira / Linear / GitHub Projects - A COMPLETAR]

---

## 2. Planificación de Sprint

### Sprint Planning Meeting

**Participantes**: Product Owner, Tech Lead, Developers

**Agenda**:
1. **Product Owner** presenta user stories priorizadas del backlog
2. **Equipo** hace preguntas de clarificación
3. **Equipo** estima esfuerzo (story points o horas)
4. **Equipo** selecciona stories que caben en el sprint (según velocity histórica)
5. **Equipo** descompone stories en tasks técnicas

**Salida**:
- Sprint Backlog definido
- Sprint Goal claro
- Commitment del equipo

---

## 3. Diseño Técnico

Para features complejas, se requiere diseño técnico previo:

**Incluye**:
- Diagrama de arquitectura (si afecta arquitectura)
- Modelo de base de datos (si crea/modifica tablas)
- API endpoints (si crea nuevas APIs)
- Consideraciones de seguridad
- Consideraciones de performance
- Estimación de esfuerzo técnico

**Aprobación**: Tech Lead

**Herramienta**: Documento en Notion / Google Docs, o ADR (Architecture Decision Record) en repositorio

---

## 4. Desarrollo (Programación)

### Stack Tecnológico

**Backend**:
- PHP 8.2+
- Laravel 12
- PostgreSQL / MySQL
- Redis (cache)

**Frontend**:
- Vue.js 3 (Composition API)
- TypeScript
- Inertia.js
- Tailwind CSS
- shadcn/ui components

**Testing**:
- PHPUnit / Pest (backend)
- Playwright (E2E)

### Estándares de Código

**PHP**:
- **Laravel Pint** para formateo automático
- **PSR-12** coding style
- **PHPStan** para análisis estático (nivel 5+ recomendado)

**JavaScript/TypeScript**:
- **ESLint** para linting
- **Prettier** para formateo
- **TypeScript strict mode** habilitado

**Ejecutar antes de commit**:
```bash
# Backend
./vendor/bin/pint
php artisan test

# Frontend
npm run lint
npm run format
vue-tsc --noEmit
```

### Convenciones de Código

**Nombres**:
- **Clases**: PascalCase (`UserController`, `InvoiceService`)
- **Métodos/Funciones**: camelCase (`getUserById`, `calculateTotal`)
- **Variables**: camelCase (`$userId`, `apartmentNumber`)
- **Constantes**: UPPER_SNAKE_CASE (`MAX_RETRIES`, `API_VERSION`)

**Comentarios**:
- Código debe ser auto-explicativo (nombres descriptivos)
- Comentarios solo cuando lógica sea compleja
- **Docblocks** en métodos públicos de clases y APIs

---

## 5. Control de Versiones (Git)

### Git Flow

**Branches principales**:
- `main`: Código en producción (protegido)
- `develop`: Código de desarrollo (protegido)

**Branches de trabajo**:
- `feature/nombre-feature`: Nuevas funcionalidades
- `bugfix/nombre-bug`: Corrección de bugs
- `hotfix/nombre-hotfix`: Fixes urgentes en producción

### Commits

**Formato de commit message**:
```
tipo(ámbito): descripción corta

Descripción detallada (opcional)

Closes #123
```

**Tipos**:
- `feat`: Nueva funcionalidad
- `fix`: Corrección de bug
- `refactor`: Refactorización sin cambio de funcionalidad
- `test`: Agregar o modificar tests
- `docs`: Cambios en documentación
- `style`: Cambios de formato (sin cambio de lógica)
- `chore`: Tareas de mantenimiento

**Ejemplo**:
```
feat(contabilidad): agregar reporte de cartera

Implementa reporte que muestra apartamentos con cuotas pendientes,
incluyendo monto y última fecha de pago.

Closes #145
```

### Pull Requests (PRs)

**Requerimientos**:
- ✅ Título descriptivo
- ✅ Descripción de cambios
- ✅ Tests pasando (CI verde)
- ✅ Code review aprobado (mínimo 1 reviewer)
- ✅ Sin conflictos con branch destino

**Template de PR**:
```markdown
## Descripción
Breve descripción de los cambios

## Tipo de cambio
- [ ] Bug fix
- [ ] Nueva funcionalidad
- [ ] Breaking change
- [ ] Refactorización

## ¿Cómo probarlo?
Pasos para probar la funcionalidad

## Checklist
- [ ] Tests agregados/actualizados
- [ ] Documentación actualizada (si aplica)
- [ ] Sin warnings de linter
- [ ] Revisé mi propio código
```

---

## 6. Code Review

**Objetivo**: Asegurar calidad, detectar bugs, compartir conocimiento, mantener consistencia.

### Proceso

1. **Developer** crea PR cuando feature está lista
2. **Reviewer(s)** asignados (generalmente Tech Lead + 1 developer)
3. **Reviewers** revisan código en < 4 horas (objetivo)
4. **Reviewers** dejan comentarios constructivos
5. **Developer** responde a comentarios y hace ajustes
6. **Reviewer** aprueba cuando está satisfecho
7. **Developer** hace merge (o automatic merge si está configurado)

### Qué Revisar

**Funcionalidad**:
- ✅ Cumple requisitos de la user story
- ✅ Criterios de aceptación satisfechos
- ✅ Edge cases manejados

**Calidad de Código**:
- ✅ Código legible y mantenible
- ✅ Nombres descriptivos
- ✅ No hay código duplicado innecesario
- ✅ Cumple estándares de código

**Seguridad**:
- ✅ No hay vulnerabilidades obvias (SQL injection, XSS, etc.)
- ✅ Validación de inputs
- ✅ Autorización correcta

**Performance**:
- ✅ No hay queries N+1
- ✅ Uso eficiente de recursos
- ✅ Caching cuando corresponda

**Tests**:
- ✅ Tests incluidos para nueva funcionalidad
- ✅ Tests existentes no rompieron
- ✅ Cobertura adecuada

---

## 7. Testing durante Desarrollo

Ver documento completo en [Testing y QA](./testing-qa.md).

**Tipos de tests**:
- **Unit tests**: Cada developer escribe tests para su código
- **Integration tests**: Tests de interacción entre componentes
- **E2E tests**: Tests de flujos completos de usuario (Playwright)

**Requisito**: **Cobertura mínima 80% en código crítico** (contabilidad, autenticación, pagos).

---

## 8. Documentación

**Documentación técnica requerida**:
- README.md actualizado (cómo levantar proyecto localmente)
- Docblocks en clases y métodos públicos
- ADRs para decisiones arquitectónicas importantes
- API documentation (si hay APIs públicas)

**Documentación de usuario**:
- Support Lead crea/actualiza documentación cuando es feature user-facing
- Screenshots y videos cuando sea útil

---

## 9. Definición de "Done"

Una user story se considera "Done" cuando:
- ✅ Código programado y cumple estándares
- ✅ Tests escritos y pasando
- ✅ Code review aprobado
- ✅ QA validó en staging
- ✅ Documentación actualizada (si aplica)
- ✅ Merged a branch develop
- ✅ Product Owner acepta (cumple criterios de aceptación)

---

## 10. Gestión de Deuda Técnica

**Identificación**:
- Durante code review
- Durante retrospectivas
- Análisis de métricas de código

**Registro**: GitHub Issues con label "tech-debt"

**Tratamiento**:
- **1 sprint por trimestre** dedicado prioritariamente a deuda técnica
- Evaluar impacto (deuda crítica vs. deuda tolerable)
- Incluir en planning cuando sea necesario

---

## Métricas de Desarrollo

| Métrica | Objetivo | Frecuencia de Medición |
|---------|----------|------------------------|
| Velocity del equipo (story points) | Estable ±10% | Por sprint |
| Cobertura de tests | ≥ 80% en código crítico | Semanal (CI) |
| Tiempo de code review | ≤ 4 horas | Semanal |
| Bugs encontrados en QA vs producción | Ratio 10:1 | Mensual |
| Deuda técnica | ≤ 10% del backlog | Trimestral |

**Responsable**: Tech Lead reporta en revisión de objetivos

---

## Revisión y Mejora

Este documento debe revisarse anualmente o cuando cambien metodología/stack.

**Próxima revisión**: [Fecha +12 meses - A COMPLETAR]

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Tech Lead | [A COMPLETAR] | | 2025-11-04 |
| Product Owner | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
