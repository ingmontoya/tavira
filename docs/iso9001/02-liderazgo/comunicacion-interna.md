# Comunicación Interna

**Documento**: SGC-006-Comunicación-Interna
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este documento establece los mecanismos de comunicación interna dentro de [Nombre de la Empresa] para asegurar que la información relevante sobre el Sistema de Gestión de Calidad y las operaciones fluye efectivamente entre todos los niveles de la organización.

Cumple con el requisito de la cláusula 7.4 de ISO 9001:2015.

---

## Objetivos de la Comunicación Interna

1. **Asegurar** que todos los miembros del equipo estén informados sobre:
   - Política y objetivos de calidad
   - Su rol y responsabilidades en el SGC
   - Cambios que afecten al SGC o al producto
   - Desempeño del SGC y oportunidades de mejora

2. **Promover** la participación activa del equipo en:
   - Identificación de problemas y oportunidades
   - Mejora continua de procesos
   - Cumplimiento de requisitos del cliente

3. **Facilitar** la coordinación eficaz entre:
   - Diferentes roles y responsabilidades
   - Procesos operacionales
   - Equipos de trabajo

---

## Canales de Comunicación

### 1. Herramientas de Comunicación Digital

| Herramienta | Propósito | Frecuencia de Uso |
|-------------|-----------|-------------------|
| **[Slack/Discord/Teams]** | Comunicación diaria del equipo, coordinación de tareas, dudas rápidas | Continua |
| **Email** | Comunicaciones formales, documentos importantes, comunicados oficiales | Según necesidad |
| **GitHub** | Code reviews, issues, pull requests, documentación técnica | Continua |
| **[Jira/Linear/Trello]** | Gestión de tareas, sprints, backlog | Continua |
| **[Notion/Confluence]** | Documentación técnica, wikis, procedimientos | Según necesidad |
| **[Google Meet/Zoom]** | Reuniones virtuales, pair programming, daily standups | Diaria/Semanal |

**Herramienta principal de comunicación instantánea**: [A COMPLETAR - ej: Slack]

**Estructura de canales sugerida** (ejemplo para Slack):
- `#general` - Anuncios generales de la empresa
- `#dev` - Discusiones técnicas de desarrollo
- `#support` - Coordinación de soporte al cliente
- `#releases` - Notificaciones de despliegues y releases
- `#incidents` - Alertas y gestión de incidentes
- `#sgc-calidad` - Temas relacionados con el SGC (auditorías, métricas, mejoras)
- `#random` - Temas no laborales, cultura de equipo

---

## Tipos de Comunicación y Frecuencia

### 2. Reuniones Regulares

#### Daily Standup (Reunión Diaria)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Sincronizar al equipo sobre el progreso diario, identificar bloqueos |
| **Participantes** | Todo el equipo técnico (developers, QA, Tech Lead) |
| **Frecuencia** | Diaria (excepto fines de semana) |
| **Duración** | Máximo 15 minutos |
| **Horario** | [A COMPLETAR - ej: 9:30 AM] |
| **Formato** | Cada persona responde:<br>1. ¿Qué hice ayer?<br>2. ¿Qué haré hoy?<br>3. ¿Tengo algún bloqueo? |
| **Herramienta** | [Video call / Slack thread - A COMPLETAR] |
| **Responsable** | Tech Lead / Scrum Master |

**Temas que NO se discuten en daily** (se agenda reunión aparte): diseño técnico detallado, debugging de problemas complejos, temas administrativos.

---

#### Sprint Planning (Planificación de Sprint)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Planificar el trabajo del próximo sprint (2 semanas) |
| **Participantes** | Product Owner, Tech Lead, Developers, QA |
| **Frecuencia** | Cada 2 semanas (al inicio del sprint) |
| **Duración** | 2-3 horas |
| **Horario** | [A COMPLETAR - ej: Lunes 10:00 AM] |
| **Formato** | 1. Product Owner presenta prioridades<br>2. Equipo estima user stories<br>3. Se seleccionan stories para el sprint<br>4. Se crean tasks técnicas |
| **Herramienta** | Video call + herramienta de gestión de proyectos |
| **Responsable** | Product Owner + Tech Lead |

**Salida**: Sprint backlog definido, commitment del equipo, objetivos claros del sprint.

---

#### Sprint Review (Demostración)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Demostrar funcionalidades completadas en el sprint, obtener feedback |
| **Participantes** | Todo el equipo + stakeholders clave (si aplica) |
| **Frecuencia** | Cada 2 semanas (al final del sprint) |
| **Duración** | 1 hora |
| **Horario** | [A COMPLETAR - ej: Viernes 3:00 PM] |
| **Formato** | 1. Demo de funcionalidades completadas<br>2. Feedback de stakeholders<br>3. Actualización del roadmap |
| **Herramienta** | Video call con pantalla compartida |
| **Responsable** | Product Owner |

**Salida**: Feedback recogido, aprobación de funcionalidades, ajustes al backlog.

---

#### Sprint Retrospective (Retrospectiva)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Reflexionar sobre el sprint, identificar mejoras en procesos y herramientas |
| **Participantes** | Todo el equipo |
| **Frecuencia** | Cada 2 semanas (al final del sprint, después del review) |
| **Duración** | 1 hora |
| **Horario** | [A COMPLETAR - ej: Viernes 4:00 PM] |
| **Formato** | 1. ¿Qué salió bien?<br>2. ¿Qué salió mal?<br>3. ¿Qué vamos a mejorar?<br>4. Acuerdos de mejora para el siguiente sprint |
| **Herramienta** | Video call + Miro / Retrium / Google Docs |
| **Responsable** | Tech Lead / Scrum Master |

**Salida**: Acciones de mejora documentadas, compromisos del equipo.

**Nota SGC**: Las retrospectivas son clave para la **mejora continua** (cláusula 10 de ISO 9001). Los acuerdos se documentan y se hace seguimiento en la siguiente retrospectiva.

---

#### Weekly Team Meeting (Reunión Semanal de Equipo)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Alineación semanal, actualización sobre temas importantes, coordinación |
| **Participantes** | Todo el equipo |
| **Frecuencia** | Semanal |
| **Duración** | 30-45 minutos |
| **Horario** | [A COMPLETAR - ej: Miércoles 2:00 PM] |
| **Formato** | 1. Actualizaciones de cada área (dev, support, producto)<br>2. Temas importantes de la semana<br>3. Decisiones que requieren consenso<br>4. Celebración de logros |
| **Herramienta** | Video call |
| **Responsable** | Director General / Team Lead |

---

#### Reunión de Revisión por la Dirección

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Revisar el desempeño del SGC y tomar decisiones estratégicas (requisito ISO 9001 cláusula 9.3) |
| **Participantes** | Director General, Representante SGC, Tech Lead, Product Owner, Support Lead |
| **Frecuencia** | Semestral (mínimo 2 veces al año) |
| **Duración** | 2-3 horas |
| **Horario** | [A COMPLETAR - ej: Junio y Diciembre] |
| **Formato** | Según agenda definida en [Revisión por la Dirección](../06-evaluacion-desempeno/revision-direccion.md) |
| **Herramienta** | Presencial o video call |
| **Responsable** | Director General |

**Salida**: Acta de revisión con decisiones, recursos asignados, actualizaciones al SGC.

---

#### Reunión de Auditoría Interna (Kick-off y Cierre)

| Elemento | Descripción |
|----------|-------------|
| **Objetivo** | Planificar y presentar resultados de auditorías internas del SGC |
| **Participantes** | Director General, Representante SGC, Auditor Interno, áreas auditadas |
| **Frecuencia** | Semestral (con cada auditoría interna) |
| **Duración** | 1-2 horas |
| **Formato** | Kick-off: Plan de auditoría<br>Cierre: Presentación de hallazgos y no conformidades |
| **Herramienta** | Presencial o video call |
| **Responsable** | Representante SGC |

---

### 3. Comunicaciones Ad-Hoc

#### Incidentes Críticos

**Cuando**: Incidente de producción que afecta a clientes (downtime, bug crítico, brecha de seguridad).

**Cómo**:
1. Alerta automática en canal `#incidents` (desde monitoreo)
2. Persona on-call crea thread en el canal describiendo el problema
3. Se convoca a personas relevantes para resolución
4. Se mantiene comunicación constante sobre progreso
5. Post-mortem documentado después de resolución

**Responsable**: DevOps Lead (on-call rotation)

---

#### Cambios en Requisitos o Prioridades

**Cuando**: Cliente solicita cambio urgente, se identifica riesgo importante, cambio regulatorio.

**Cómo**:
1. Product Owner documenta el cambio
2. Se comunica en daily standup o se agenda reunión urgente si es crítico
3. Se re-prioriza el backlog según sea necesario

**Responsable**: Product Owner

---

#### Despliegues a Producción

**Cuando**: Antes y después de cada despliegue a producción.

**Cómo**:
1. **Pre-despliegue**: Notificación en `#releases` con:
   - Fecha/hora del despliegue
   - Funcionalidades incluidas
   - Posibles riesgos
   - Rollback plan
2. **Post-despliegue**: Confirmación de éxito + smoke tests
3. Si hay downtime programado: notificar a clientes con 48h de anticipación

**Responsable**: DevOps Lead

---

#### Comunicaciones sobre el SGC

**Cuando**: Cambios en política/objetivos de calidad, resultados de auditorías, nuevos procedimientos.

**Cómo**:
1. Representante SGC redacta comunicado
2. Se publica en canal `#sgc-calidad` y/o email
3. Se agenda capacitación si es necesario

**Responsable**: Representante SGC

---

## Comunicación de la Política y Objetivos de Calidad

La **Política de Calidad** y **Objetivos de Calidad** deben ser comunicados a:
- Todo el equipo actual
- Cada nuevo miembro durante onboarding
- Partes interesadas externas (cuando sea relevante)

**Mecanismos de comunicación**:
1. **Onboarding de nuevos miembros**: Sesión dedicada a revisar política, objetivos y SGC
2. **Anualmente**: Reunión de equipo para revisar y reafirmar compromiso
3. **Disponibilidad permanente**: Documentos accesibles en repositorio (`docs/iso9001/`)
4. **Visibilidad de métricas**: Dashboard de objetivos de calidad accesible al equipo

**Responsable**: Representante del SGC

---

## Comunicación de Roles y Responsabilidades

Cada miembro del equipo debe conocer:
- Su rol en el SGC (ver [Roles y Responsabilidades](./roles-responsabilidades.md))
- Sus responsabilidades específicas
- A quién reporta
- Quién depende de su trabajo

**Mecanismos**:
1. Descripción de puesto formal al momento de contratación
2. Revisión en onboarding
3. Actualización cuando cambien roles o responsabilidades

**Responsable**: Director General + Representante SGC

---

## Comunicación Ascendente (Equipo → Dirección)

El equipo debe tener mecanismos claros para comunicar hacia arriba:

### Canales Formales:
- **Retrospectivas**: Identificar problemas de procesos o herramientas
- **Revisión por la dirección**: Representantes del equipo participan
- **Auditorías internas**: Entrevistas con auditores
- **Reportes de métricas**: Cualquier miembro puede reportar desviaciones

### Canales Informales:
- **1-on-1s**: Reuniones individuales entre miembro del equipo y lead (recomendado mensual)
- **Open door policy**: Cualquier miembro puede agendar reunión con Director General
- **Sugerencias anónimas**: [A IMPLEMENTAR - buzón de sugerencias si se desea]

**Compromiso de la dirección**: Toda comunicación ascendente será escuchada y respondida con acciones cuando sea apropiado.

---

## Comunicación Externa Relacionada con el SGC

### Comunicación con Clientes

| Tipo de Comunicación | Responsable | Canal | Frecuencia |
|----------------------|-------------|-------|------------|
| Soporte técnico | Support Lead | Email, Chat, Teléfono | Según tickets |
| Notificaciones de mantenimiento | DevOps Lead | Email masivo | Según necesidad |
| Release notes | Product Owner | Email + blog | Con cada release |
| Encuestas de satisfacción | Support Lead | Email | Trimestral |
| Webinars y capacitaciones | Support Lead | Video call | Mensual |

### Comunicación con Proveedores

| Tipo de Comunicación | Responsable | Canal | Frecuencia |
|----------------------|-------------|-------|------------|
| Evaluación de desempeño | DevOps Lead | Email | Anual |
| Reporte de incidentes | DevOps Lead | Sistema del proveedor | Según necesidad |
| Renovación de contratos | Director General | Email, reunión | Anual |

### Comunicación con Auditores/Certificadores

| Tipo de Comunicación | Responsable | Canal | Frecuencia |
|----------------------|-------------|-------|------------|
| Coordinación de auditorías | Representante SGC | Email, reunión | Según auditoría |
| Entrega de evidencias | Representante SGC | Email, plataforma | Según solicitud |

---

## Registro de Comunicaciones Importantes

Las siguientes comunicaciones deben ser **registradas y archivadas**:

✅ Actas de revisión por la dirección
✅ Actas de auditorías internas
✅ Comunicados sobre cambios en política o objetivos de calidad
✅ Comunicaciones de incidentes críticos (post-mortems)
✅ Acuerdos de retrospectivas
✅ Comunicaciones formales con clientes sobre problemas críticos

**Ubicación de archivo**: [A COMPLETAR - ej: carpeta compartida en Google Drive, repositorio]

**Responsable**: Representante del SGC

---

## Comunicación en Trabajo Remoto

Dado que el equipo puede trabajar de forma remota, es esencial:

**Buenas prácticas**:
1. **Documentar decisiones importantes**: No confiar solo en conversaciones sincrónicas
2. **Usar herramientas asincrónicas**: Threads en Slack, comentarios en GitHub, documentos colaborativos
3. **Respetar zonas horarias**: Si hay equipo distribuido, rotar horarios de reuniones
4. **Video on por defecto**: En reuniones importantes, promover video para mejor conexión
5. **Over-communicate**: En remoto, es mejor comunicar de más que de menos

**Evitar**:
- Tomar decisiones importantes sin documentarlas
- Conversaciones privadas que deberían ser en canales públicos del equipo
- Asumir que todos vieron un mensaje (usar @mentions cuando sea importante)

---

## Evaluación de la Efectividad de la Comunicación

**Indicadores de comunicación efectiva**:
- Equipo reporta estar bien informado sobre objetivos y cambios (encuesta anual)
- Decisiones se toman con participación del equipo relevante
- Problemas se identifican y escalan rápidamente
- Reuniones empiezan y terminan a tiempo, con objetivos claros

**Problemas que indican comunicación deficiente**:
- Mismo problema se repite sin que el equipo aprenda
- Sorpresas frecuentes ("no sabía que eso era así")
- Decisiones que afectan a alguien se toman sin consultarle
- Reuniones sin agenda clara o sin seguimiento de acuerdos

**Revisión**: En cada retrospectiva, el equipo puede proponer mejoras a la comunicación.

---

## Confidencialidad

Cierta información es confidencial y no debe ser compartida externamente sin autorización:

**Información confidencial**:
- Datos de clientes (protegidos por GDPR / Ley 1581 de Colombia)
- Estrategia de producto no pública
- Información financiera de la empresa
- Código fuente (repositorio privado)
- Vulnerabilidades de seguridad no parcheadas
- Resultados de auditorías internas (antes de acciones correctivas)

**Todo el equipo debe firmar** acuerdos de confidencialidad (NDA) al momento de contratación.

---

## Idioma de Comunicación

**Idioma oficial**: Español (Colombia)

**Excepción**: Documentación técnica y código pueden estar en inglés si eso es más natural para el equipo.

---

## Revisión de la Comunicación Interna

Este documento debe revisarse:
- Anualmente
- Cuando cambien herramientas de comunicación
- Cuando el equipo crezca significativamente
- Cuando se identifiquen problemas de comunicación

**Próxima revisión**: [Fecha +12 meses - A COMPLETAR]

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Director General | [A COMPLETAR] | | 2025-11-04 |
| Representante del SGC | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
