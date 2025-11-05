# Atención al Cliente

**Documento**: SGC-015-Atencion-Cliente
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Canales de Soporte

| Canal | Disponibilidad | Uso | Tiempo de Respuesta Objetivo |
|-------|----------------|-----|------------------------------|
| **Email** | 24/7 (respuesta en horario laboral) | Consultas generales, bugs no urgentes | ≤ 4 horas laborales |
| **Chat en vivo** | Lunes-Viernes 8AM-6PM | Soporte inmediato | ≤ 5 minutos |
| **Teléfono** | Lunes-Viernes 8AM-6PM | Issues críticos | Inmediato |
| **Sistema de tickets** | 24/7 | Rastreo de incidencias | Según prioridad |

**Email de soporte**: [soporte@tavira.com.co - A COMPLETAR]
**Teléfono**: [+57 XXX XXX XXXX - A COMPLETAR]

---

## Sistema de Tickets

**Herramienta**: [Zendesk / Freshdesk / Linear - A COMPLETAR]

### Estados de Tickets
- **Nuevo**: Recién creado, no asignado
- **En Progreso**: Asignado y siendo trabajado
- **Esperando Respuesta del Cliente**: Necesita información adicional
- **Resuelto**: Solución provista, esperando confirmación
- **Cerrado**: Cliente confirmó resolución

### Prioridades

| Prioridad | Descripción | SLA Tiempo de Respuesta | SLA Resolución |
|-----------|-------------|------------------------|----------------|
| **Crítico** | Sistema caído, pérdida de datos, imposible trabajar | 1 hora | 4 horas |
| **Alto** | Funcionalidad importante no funciona, workaround disponible | 4 horas | 1 día laboral |
| **Medio** | Funcionalidad menor no funciona, no bloquea trabajo | 1 día laboral | 3 días laborales |
| **Bajo** | Consulta, mejora sugerida, cosmético | 2 días laborales | Best effort |

---

## Proceso de Gestión de Tickets

### 1. Recepción

**Cuando llega un ticket** (email, chat, teléfono):
1. **Registrar** en sistema de tickets
2. **Clasificar**:
   - Tipo: Bug / Consulta / Solicitud de feature / Capacitación
   - Prioridad: Crítico / Alto / Medio / Bajo
   - Área: Técnico / Funcional / Contable
3. **Asignar** a responsable:
   - Nivel 1 (Support Lead): Consultas, capacitación
   - Nivel 2 (Developer): Bugs técnicos
   - Nivel 3 (DevOps): Incidentes de infraestructura

### 2. Diagnóstico

**Support Lead**:
- Hace preguntas clarificadoras
- Intenta reproducir el problema
- Revisa documentación existente
- Busca soluciones previas

**Si es bug técnico**: Escalar a developer con:
- Pasos para reproducir
- Comportamiento esperado vs actual
- Screenshots / videos
- Logs relevantes

### 3. Resolución

**Según tipo**:
- **Consulta**: Responder con información, link a documentación
- **Capacitación**: Agendar sesión, enviar video tutorial
- **Bug**: Developer investiga, reproduce, corrige (según prioridad)
- **Feature request**: Registrar en backlog para Product Owner

### 4. Comunicación

**Mantener informado al cliente**:
- Confirmación de recepción (inmediata)
- Actualizaciones de progreso (cada 24 horas para tickets críticos/altos)
- Solución propuesta
- Confirmación de resolución

**Tono**: Profesional, empático, proactivo

### 5. Cierre

**Antes de cerrar**:
- Confirmar que el cliente está satisfecho
- Preguntar si necesita algo más
- Enviar encuesta de satisfacción (CSAT)

---

## Plantillas de Respuesta

### Primera Respuesta
```
Hola [Nombre],

Gracias por contactarnos. Hemos recibido tu solicitud [#TICKET_ID] y estamos trabajando en ella.

[Confirmación de lo que entendimos del problema]

[Estimación de tiempo de resolución / próximos pasos]

Estaremos en contacto pronto.

Saludos,
[Nombre]
Equipo de Soporte - Tavira
```

### Solicitud de Información
```
Hola [Nombre],

Para ayudarte mejor con [problema], necesito algunos datos adicionales:

- [Pregunta 1]
- [Pregunta 2]

Gracias por tu colaboración.

Saludos,
[Nombre]
```

### Resolución
```
Hola [Nombre],

Buenas noticias - hemos resuelto tu solicitud [#TICKET_ID].

[Explicación de la solución / pasos que tomamos]

Por favor confirma que ahora todo funciona correctamente.

¿Hay algo más en lo que pueda ayudarte?

Saludos,
[Nombre]
```

---

## Base de Conocimiento (FAQ)

**Objetivo**: Reducir tickets repetitivos mediante auto-servicio.

**Ubicación**: [help.tavira.com.co - A COMPLETAR]

**Secciones**:
1. **Primeros Pasos**
   - Cómo crear mi primera factura
   - Cómo registrar un pago
   - Cómo configurar mi conjunto

2. **Contabilidad**
   - Entender el plan de cuentas
   - Generar reportes financieros
   - Presupuestos y ejecución presupuestal

3. **Residentes y Apartamentos**
   - Agregar residentes
   - Asignar apartamentos
   - Gestionar propietarios vs inquilinos

4. **Problemas Comunes**
   - No puedo iniciar sesión
   - Error al generar reporte
   - Cálculos contables incorrectos

**Responsable**: Support Lead crea/actualiza artículos

---

## Capacitación de Clientes

### Onboarding de Nuevos Clientes

**Semana 1**: Sesión inicial (1-2 horas)
- Tour de la plataforma
- Configuración inicial del conjunto
- Creación de apartamentos y tipos
- Carga de residentes

**Semana 2**: Seguimiento (30 minutos)
- Revisión de dudas
- Facturación y pagos
- Reportes básicos

**Semana 3**: Avanzado (30 minutos - opcional)
- Contabilidad y cierre mensual
- Presupuestos
- Reportes avanzados

### Webinars Mensuales

**Frecuencia**: 1 vez al mes
**Duración**: 1 hora
**Temas** (rotativo):
- Novedades y nuevas funcionalidades
- Tips y trucos
- Mejores prácticas de gestión
- Q&A abierto

**Grabaciones**: Disponibles en base de conocimiento

---

## Satisfacción del Cliente

### Encuesta CSAT (Post-Ticket)

Enviar automáticamente al cerrar ticket:

```
¿Qué tan satisfecho estás con la resolución de tu solicitud?

😞 1 - Muy insatisfecho
😐 2 - Insatisfecho
😊 3 - Neutral
😄 4 - Satisfecho
🤩 5 - Muy satisfecho

¿Comentarios adicionales? [Campo de texto opcional]
```

**Objetivo**: ≥ 4.5/5 promedio

### Encuesta NPS (Trimestral)

```
En una escala de 0 a 10, ¿qué tan probable es que recomiendes Tavira a otro conjunto residencial?

0 1 2 3 4 5 6 7 8 9 10
[─────────────────────────]

¿Por qué diste esta calificación?
```

**Clasificación**:
- 0-6: Detractors
- 7-8: Passives
- 9-10: Promoters

**NPS Score** = % Promoters - % Detractors

**Objetivo**: NPS ≥ 50

---

## Escalamiento

### Cuándo Escalar

**A Developer (Nivel 2)**:
- Bug técnico confirmado
- Error de código
- Problema que requiere investigación técnica

**A DevOps (Nivel 3)**:
- Problemas de infraestructura
- Performance degradado
- Incidente de producción

**A Product Owner**:
- Feature request importante
- Feedback crítico sobre funcionalidad
- Solicitud de priorización

**A Director General**:
- Cliente muy insatisfecho (churn risk)
- Problema legal o contractual
- Escalamiento de cliente importante

---

## Comunicación Proactiva

**Notificar a clientes sobre**:
- **Mantenimientos programados**: 48 horas de anticipación
- **Nuevas funcionalidades**: Release notes por email
- **Incidentes**: Actualizaciones cada hora hasta resolución
- **Mejoras solicitadas**: Cuando se implementan

**Status Page**: [status.tavira.com.co - opcional - A IMPLEMENTAR]

---

## Métricas de Atención al Cliente

| Métrica | Objetivo | Medición |
|---------|----------|----------|
| Tiempo de primera respuesta | ≤ 4 horas laborales | Por ticket |
| Tasa de resolución en primer contacto | ≥ 60% | Mensual |
| Satisfacción del cliente (CSAT) | ≥ 4.5/5 | Post-ticket |
| NPS | ≥ 50 | Trimestral |
| Tickets resueltos dentro de SLA | ≥ 95% | Mensual |
| Churn rate | ≤ 5% anual | Trimestral |

**Responsable**: Support Lead reporta mensualmente

---

## Mejora Continua

**Revisar mensualmente**:
- Tickets más frecuentes → Mejorar producto o documentación
- Tiempo de resolución alto → Optimizar procesos o capacitación
- Satisfacción baja → Identificar causas raíz

**Retrospectivas trimestrales** con equipo de soporte para identificar mejoras.

---

## Aprobación

| Rol | Nombre | Firma | Fecha |
|-----|--------|-------|-------|
| Support Lead | [A COMPLETAR] | | 2025-11-04 |

---

**Historial de Cambios:**

| Versión | Fecha | Descripción del Cambio | Autor |
|---------|-------|------------------------|-------|
| 1.0 | 2025-11-04 | Creación inicial del documento | [A COMPLETAR] |
