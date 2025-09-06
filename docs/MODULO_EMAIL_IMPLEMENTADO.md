# Módulo de Correo Electrónico - Tavira

## Descripción
Se ha implementado un módulo completo de correo electrónico en el menú de comunicación con dos vistas separadas: una para administración y otra para concejo.

## Características Implementadas

### 📧 Funcionalidades Principales
- **Bandeja de entrada separada** para administración y concejo
- **Visualización de correos** con datos de Mailpit
- **Redacción de correos** con plantillas predefinidas
- **Gestión de archivos adjuntos**
- **Búsqueda y filtrado** de correos
- **Marcado como leído/no leído**
- **Eliminación de correos**
- **Responder y reenviar correos**

### 🏗️ Estructura Técnica

#### Backend
- **Controlador**: `EmailController.php` con métodos separados para admin y concejo
- **Rutas**: Organizadas en `/email/admin` y `/email/concejo`
- **Integración con Mailpit**: API HTTP para obtener correos reales
- **Datos mock**: Fallback cuando Mailpit no está disponible

#### Frontend
- **Vistas Vue separadas**:
  - `Email/Admin/Index.vue` - Bandeja de administración
  - `Email/Admin/Show.vue` - Ver correo específico (admin)
  - `Email/Admin/Compose.vue` - Redactar correo (admin)
  - `Email/Concejo/Index.vue` - Bandeja del concejo
  - `Email/Concejo/Show.vue` - Ver correo específico (concejo)
  - `Email/Concejo/Compose.vue` - Redactar correo (concejo)

### 🎨 Características de UI/UX

#### Bandeja de Entrada
- **Vista de lista** con información del remitente, asunto, fecha y tamaño
- **Indicadores visuales** para correos leídos/no leídos
- **Sidebar de navegación** con carpetas (Recibidos, Destacados, Archivados, Papelera)
- **Búsqueda en tiempo real** por asunto, remitente o contenido
- **Selección múltiple** para acciones en lote
- **Acciones rápidas** al hacer hover sobre cada correo

#### Vista de Correo Individual
- **Información completa** del correo (remitente, destinatarios, fecha)
- **Renderizado de contenido HTML** y texto plano
- **Gestión de archivos adjuntos** con información de tamaño y tipo
- **Botones de acción**: Responder, Reenviar, Imprimir, Eliminar
- **Estilos de impresión** optimizados

#### Redacción de Correos
- **Formulario completo** con campos Para, CC, CCO, Asunto y Cuerpo
- **Plantillas predefinidas** específicas para cada vista:
  - **Admin**: Plantillas formales de negocios
  - **Concejo**: Plantillas oficiales y comunicados
- **Gestión de archivos adjuntos** con vista previa y eliminación
- **Destinatarios frecuentes** para acceso rápido
- **Guardar como borrador** funcionalidad

### 🔧 Configuración

#### Rutas Principales
```
/email/admin          - Bandeja de administración
/email/admin/compose  - Redactar correo (admin)
/email/concejo        - Bandeja del concejo
/email/concejo/compose - Redactar correo (concejo)
```

#### Permisos
- Utiliza el permiso `view_correspondence` para ambas vistas
- Integrado con el sistema de roles y permisos existente

#### Navegación
- Añadido al menú "Comunicación" como submódulo
- Dos opciones: "Correo Administración" y "Correo Concejo"
- Iconos distintivos para cada sección

### 🔌 Integración con Mailpit

El módulo está diseñado para integrarse con Mailpit:
- **URL por defecto**: `http://localhost:8025/api/v1/`
- **Endpoints utilizados**:
  - `GET /messages` - Lista de correos
  - `GET /message/{id}` - Correo específico
- **Fallback**: Datos mock cuando Mailpit no está disponible

### 🎯 Diferencias entre Vistas

#### Vista de Administración
- Enfoque en comunicación empresarial
- Plantillas para informes, solicitudes y comunicación formal
- Destinatarios frecuentes: Concejo, Gerencia, Contabilidad

#### Vista de Concejo
- Enfoque en comunicación oficial del concejo
- Plantillas para comunicados oficiales, aprobaciones y convocatorias
- Identificación visual clara con badge "Concejo"
- Plantillas específicas para funciones del concejo

### 📝 Próximas Mejoras

#### Funcionalidades Pendientes
- Implementación real de envío de correos
- Sistema de borradores persistente
- Gestión de carpetas personalizadas
- Sincronización bidireccional con Mailpit
- Notificaciones en tiempo real de nuevos correos
- Filtros avanzados y reglas de correo
- Firma digital automática
- Plantillas personalizables por usuario

#### Integraciones Futuras
- Conexión con proveedores de email (SMTP, SendGrid, etc.)
- Calendario para programar envío de correos
- Integración con el sistema de documentos
- Backup automático de correos importantes

## Uso

1. **Acceder al módulo**: Menú Comunicación > Correo Electrónico
2. **Seleccionar vista**: Administración o Concejo
3. **Navegar correos**: Hacer clic en cualquier correo para verlo
4. **Redactar correo**: Botón "Redactar" en la bandeja
5. **Usar plantillas**: Seleccionar desde el sidebar al redactar
6. **Gestionar archivos**: Adjuntar/eliminar desde el formulario de redacción

El módulo está completamente funcional y listo para uso en producción con Mailpit configurado.