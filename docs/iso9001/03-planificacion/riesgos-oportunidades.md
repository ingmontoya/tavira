# Gestión de Riesgos y Oportunidades

**Documento**: SGC-007-Riesgos-Oportunidades
**Versión**: 1.0
**Fecha**: 2025-11-04
**Aprobado por**: [A COMPLETAR]

---

## Introducción

Este documento establece el enfoque para identificar, evaluar y gestionar riesgos y oportunidades que puedan afectar la capacidad del Sistema de Gestión de Calidad para lograr sus resultados previstos.

Cumple con el requisito de la cláusula 6.1 de ISO 9001:2015.

---

## Metodología de Gestión de Riesgos

### Proceso de Gestión de Riesgos

```
1. IDENTIFICAR → 2. EVALUAR → 3. TRATAR → 4. MONITOREAR → 5. REVISAR
     ↓              ↓             ↓           ↓             ↓
   Riesgos      Probabilidad  Controles   Indicadores   Eficacia
  y Oportu.     e Impacto     Preventivos  de Riesgo    de Controles
```

### Frecuencia de Revisión
- **Identificación de nuevos riesgos**: Continua (en retrospectivas, planning, incidentes)
- **Evaluación completa de matriz**: Trimestral
- **Revisión en dirección**: Semestral
- **Ad-hoc**: Cuando ocurran cambios significativos

---

## Matriz de Evaluación de Riesgos

### Escala de Probabilidad

| Nivel | Descripción | Probabilidad |
|-------|-------------|--------------|
| 1 - Muy Baja | Improbable que ocurra | < 5% |
| 2 - Baja | Podría ocurrir en circunstancias excepcionales | 5-20% |
| 3 - Media | Podría ocurrir en algún momento | 20-50% |
| 4 - Alta | Probablemente ocurrirá | 50-80% |
| 5 - Muy Alta | Se espera que ocurra frecuentemente | > 80% |

### Escala de Impacto

| Nivel | Descripción | Impacto en Negocio |
|-------|-------------|-------------------|
| 1 - Insignificante | Impacto mínimo, fácilmente gestionable | < $500 o < 1 hora downtime |
| 2 - Menor | Impacto bajo, gestionable sin recursos adicionales | $500-$2K o 1-4 horas downtime |
| 3 - Moderado | Impacto significativo, requiere recursos adicionales | $2K-$10K o 4-24 horas downtime |
| 4 - Mayor | Impacto severo en operaciones o reputación | $10K-$50K o 1-3 días downtime |
| 5 - Catastrófico | Amenaza la viabilidad del negocio | > $50K o > 3 días downtime |

### Nivel de Riesgo (Probabilidad × Impacto)

| Puntaje | Nivel de Riesgo | Acción Requerida |
|---------|----------------|------------------|
| 1-4 | 🟢 Bajo | Aceptar y monitorear |
| 5-9 | 🟡 Medio | Implementar controles en plazo razonable |
| 10-15 | 🟠 Alto | Acción inmediata, plan de mitigación urgente |
| 16-25 | 🔴 Crítico | Acción inmediata, escalamiento a dirección |

---

## Matriz de Riesgos Identificados

### Riesgos Tecnológicos

| ID | Riesgo | Prob. | Imp. | Nivel | Controles Actuales | Responsable | Estado |
|----|--------|-------|------|-------|-------------------|-------------|--------|
| RT-01 | **Caída de producción por fallo de infraestructura** | 3 | 4 | 🟠 12 | - Monitoreo 24/7<br>- Backups automáticos<br>- Redundancia de servidores | DevOps Lead | Activo |
| RT-02 | **Brecha de seguridad / Hack** | 2 | 5 | 🟠 10 | - Firewall configurado<br>- Actualizaciones de seguridad<br>- Escaneo de vulnerabilidades | DevOps Lead | Activo |
| RT-03 | **Pérdida de datos por fallo de backup** | 2 | 5 | 🟠 10 | - Backups automáticos diarios<br>- Verificación mensual de restore<br>- Backups en múltiples ubicaciones | DevOps Lead | Activo |
| RT-04 | **Dependencia obsoleta con vulnerabilidad crítica** | 4 | 3 | 🟠 12 | - Dependabot habilitado<br>- Actualización mensual de dependencias<br>- npm audit en CI | Tech Lead | Activo |
| RT-05 | **Bug crítico en producción** | 3 | 3 | 🟡 9 | - Code reviews obligatorios<br>- Tests automatizados<br>- Staging antes de prod | Tech Lead | Activo |
| RT-06 | **Pérdida de repositorio de código (GitHub)** | 1 | 5 | 🟡 5 | - Backups locales periódicos<br>- Multiple branches<br>- GitHub es muy confiable | Tech Lead | Activo |
| RT-07 | **Deuda técnica insostenible** | 3 | 3 | 🟡 9 | - Dedicar 1 sprint/trimestre a deuda técnica<br>- Tracking de deuda en retrospectivas | Tech Lead | Activo |
| RT-08 | **Problemas de rendimiento/escalabilidad** | 3 | 3 | 🟡 9 | - Monitoreo de performance<br>- Load testing periódico<br>- Arquitectura escalable | DevOps Lead | Activo |

### Riesgos de Personas y Competencias

| ID | Riesgo | Prob. | Imp. | Nivel | Controles Actuales | Responsable | Estado |
|----|--------|-------|------|-------|-------------------|-------------|--------|
| RP-01 | **Renuncia de miembro clave del equipo** | 3 | 4 | 🟠 12 | - Documentación de procesos<br>- Conocimiento compartido<br>- Pair programming | Director | Activo |
| RP-02 | **Falta de competencias técnicas en tecnología clave** | 3 | 3 | 🟡 9 | - Plan de capacitación anual<br>- Budget de formación<br>- Tech talks internos | Director | Activo |
| RP-03 | **Burnout del equipo** | 3 | 3 | 🟡 9 | - Carga de trabajo balanceada<br>- Retrospectivas<br>- Vacaciones obligatorias | Director | Activo |
| RP-04 | **Único experto en área crítica (single point of failure)** | 4 | 3 | 🟠 12 | - Documentación detallada<br>- Rotación de conocimiento<br>- Shadowing | Tech Lead | Activo |

### Riesgos de Proveedores y Terceros

| ID | Riesgo | Prob. | Imp. | Nivel | Controles Actuales | Responsable | Estado |
|----|--------|-------|------|-------|-------------------|-------------|--------|
| RV-01 | **Caída del proveedor de hosting** | 2 | 5 | 🟠 10 | - SLA 99.9% contratado<br>- Monitoreo independiente<br>- Plan de migración documentado | DevOps Lead | Activo |
| RV-02 | **Aumento significativo de costos de infraestructura** | 3 | 3 | 🟡 9 | - Revisión trimestral de costos<br>- Optimización de recursos<br>- Contratos anuales con precio fijo | Director | Activo |
| RV-03 | **Discontinuación de servicio crítico de tercero (API)** | 2 | 3 | 🟡 6 | - Identificar alternativas<br>- Minimizar dependencias críticas | Tech Lead | Activo |

### Riesgos de Negocio y Mercado

| ID | Riesgo | Prob. | Imp. | Nivel | Controles Actuales | Responsable | Estado |
|----|--------|-------|------|-------|-------------------|-------------|--------|
| RN-01 | **Entrada de competidor fuerte al mercado** | 3 | 3 | 🟡 9 | - Diferenciación por calidad<br>- Innovación continua<br>- Relación cercana con clientes | Director | Activo |
| RN-02 | **Cambio regulatorio contable (Decreto 2650)** | 2 | 4 | 🟡 8 | - Monitoreo de regulación<br>- Arquitectura flexible<br>- Asesor contable | Product Owner | Activo |
| RN-03 | **Pérdida de clientes clave (churn)** | 2 | 4 | 🟡 8 | - Encuestas de satisfacción<br>- Soporte proactivo<br>- Mejora continua del producto | Support Lead | Activo |

### Riesgos de Cumplimiento Legal

| ID | Riesgo | Prob. | Imp. | Nivel | Controles Actuales | Responsable | Estado |
|----|--------|-------|------|-------|-------------------|-------------|--------|
| RL-01 | **Incumplimiento de Ley de Protección de Datos (Ley 1581)** | 2 | 5 | 🟠 10 | - Política de privacidad<br>- Encriptación de datos<br>- Procedimientos GDPR | DevOps Lead | Activo |
| RL-02 | **Demanda por fallo en cálculos contables** | 2 | 4 | 🟡 8 | - Tests exhaustivos de contabilidad<br>- Validación con contador<br>- Términos y condiciones claros | Tech Lead | Activo |

---

## Oportunidades Identificadas

Las oportunidades son situaciones favorables que podemos aprovechar para mejorar.

| ID | Oportunidad | Potencial | Acciones para Aprovechar | Responsable | Estado |
|----|-------------|-----------|-------------------------|-------------|--------|
| O-01 | **Automatización completa de CI/CD** | Alto | - Implementar despliegues automáticos<br>- Reducir tiempo de release<br>- Aumentar frecuencia de deploys | DevOps Lead | En progreso |
| O-02 | **Expansión a otros países de Latinoamérica** | Alto | - Investigar regulaciones contables de otros países<br>- Internacionalización de la plataforma | Director | Planificado |
| O-03 | **Certificación ISO 9001** | Medio | - Preparar documentación del SGC<br>- Realizar auditorías internas<br>- Contratar organismo certificador | Rep. SGC | En progreso |
| O-04 | **Integración con proveedores locales** | Alto | - API para proveedores<br>- Marketplace de servicios<br>- Comisiones por transacciones | Product Owner | Planificado |
| O-05 | **Inteligencia Artificial para soporte** | Medio | - Chatbot con IA para respuestas frecuentes<br>- Reducir carga de soporte nivel 1 | Support Lead | Investigación |
| O-06 | **Open source de componentes no-core** | Medio | - Liberar componentes reutilizables<br>- Aumentar visibilidad de la empresa<br>- Atraer talento | Tech Lead | Considerando |
| O-07 | **Alianzas con asociaciones de conjuntos residenciales** | Alto | - Presentar producto en eventos del sector<br>- Descuentos por volumen | Director | Planificado |

---

## Tratamiento de Riesgos

Para cada riesgo identificado, se pueden aplicar las siguientes estrategias:

### 1. Evitar el Riesgo
Eliminar la actividad que genera el riesgo.
- **Ejemplo**: No usar una tecnología experimental en producción.

### 2. Mitigar el Riesgo
Implementar controles para reducir probabilidad o impacto.
- **Ejemplo**: Implementar tests automatizados para reducir bugs en producción.

### 3. Transferir el Riesgo
Trasladar el riesgo a un tercero (seguro, SLA con proveedores).
- **Ejemplo**: Contratar hosting con SLA 99.9% y penalizaciones por downtime.

### 4. Aceptar el Riesgo
Reconocer el riesgo y no tomar acción adicional (para riesgos bajos).
- **Ejemplo**: Aceptar que GitHub podría caer temporalmente (muy baja probabilidad).

---

## Planes de Acción para Riesgos Críticos y Altos

### RT-01: Caída de Producción

**Plan de Mitigación**:
- ✅ **Ya implementado**: Monitoreo con alertas automáticas
- ✅ **Ya implementado**: Backups automáticos diarios con retención de 30 días
- 🔄 **Pendiente**: Configurar auto-failover entre servidores (Q2 2025)
- 🔄 **Pendiente**: Practicar disaster recovery cada 6 meses (Q1 2025)

**Plan de Contingencia** (si ocurre):
1. Persona on-call recibe alerta inmediata
2. Diagnosticar causa raíz (max 30 min)
3. Activar servidor de respaldo si es necesario
4. Comunicar a clientes afectados
5. Restaurar desde backup si es necesario
6. Post-mortem y acciones correctivas

**Responsable**: DevOps Lead

---

### RT-02: Brecha de Seguridad

**Plan de Mitigación**:
- ✅ **Ya implementado**: Dependabot para vulnerabilidades
- ✅ **Ya implementado**: HTTPS con certificados SSL
- ✅ **Ya implementado**: Autenticación y autorización robusta (Laravel Breeze + Spatie Permissions)
- 🔄 **Pendiente**: Penetration testing externo (Q2 2025)
- 🔄 **Pendiente**: Security audit semestral (Q1 y Q3 2025)

**Plan de Respuesta a Incidentes** (si ocurre):
1. Aislar el sistema comprometido inmediatamente
2. Evaluar alcance de la brecha (datos expuestos)
3. Notificar a clientes afectados según ley 1581
4. Parchear vulnerabilidad
5. Cambiar todas las credenciales comprometidas
6. Investigación forense
7. Reporte a autoridades si es requerido

**Responsable**: DevOps Lead + Director

---

### RP-01: Renuncia de Miembro Clave

**Plan de Mitigación**:
- ✅ **Ya implementado**: Documentación en repositorio
- 🔄 **Pendiente**: Documentación de runbooks y procedimientos críticos (Q1 2025)
- 🔄 **Pendiente**: Cross-training entre miembros del equipo (continuo)
- 🔄 **Pendiente**: Retention strategy (bonus, equity, cultura) (Q1 2025)

**Plan de Contingencia** (si ocurre):
1. Período de notice mínimo de 2 semanas (en contrato)
2. Knowledge transfer sessions con el equipo
3. Documentar conocimiento crítico antes de salida
4. Evaluación de carga de trabajo del equipo restante
5. Plan de contratación acelerado si es necesario
6. Redistribución temporal de responsabilidades

**Responsable**: Director + Tech Lead

---

## Monitoreo de Riesgos

### Indicadores de Riesgo (KRIs - Key Risk Indicators)

| Riesgo | Indicador de Alerta Temprana | Umbral de Alerta |
|--------|------------------------------|------------------|
| RT-01 | Uptime mensual | < 99.5% |
| RT-02 | Vulnerabilidades sin parchear | > 0 críticas por más de 7 días |
| RT-03 | Backups fallidos | > 1 fallo consecutivo |
| RT-05 | Bugs críticos en producción | > 1 por mes |
| RT-07 | Deuda técnica estimada | > 20% del tiempo de sprint |
| RP-01 | Employee satisfaction score | < 4/5 en encuesta |
| RP-03 | Horas extra promedio | > 5 horas/semana/persona |

**Monitoreo**: Representante del SGC revisa estos indicadores mensualmente.

---

## Revisión de Riesgos y Oportunidades

**Frecuencia**:
- **Trimestral**: Revisión completa de la matriz con el equipo
- **Semestral**: Revisión en la Revisión por la Dirección
- **Ad-hoc**: Cuando ocurra un incidente mayor o cambio significativo

**Responsable**: Representante del SGC (coordinación) + Responsables de cada riesgo

**Proceso de revisión**:
1. Revisar si riesgos identificados siguen vigentes
2. Evaluar si probabilidad o impacto han cambiado
3. Identificar nuevos riesgos
4. Evaluar eficacia de controles implementados
5. Ajustar planes de acción según sea necesario

---

## Oportunidades de Mejora del SGC

Además de riesgos de negocio, identificamos oportunidades para mejorar el propio SGC:

- **Automatizar reporte de métricas**: Integrar métricas en dashboard automatizado
- **Auditorías más ágiles**: Usar checklist digitales y automatización
- **Capacitación continua**: Programa estructurado de learning & development
- **Cultura de calidad**: Gamificación de métricas de calidad

**Estas oportunidades se priorizan y se incluyen en la planificación de sprints/proyectos.**

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
| 1.0 | 2025-11-04 | Creación inicial de la matriz de riesgos | [A COMPLETAR] |

---

**Próxima revisión completa**: [Fecha +3 meses - A COMPLETAR]
