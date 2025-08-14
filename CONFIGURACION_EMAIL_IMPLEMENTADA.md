# Configuración de Correo Electrónico - Habitta

## Descripción
Se ha implementado un sistema completo de configuración de correo electrónico a través de settings, permitiendo configurar las cuentas de correo para administración y concejo desde la interfaz web.

## ✨ Características Principales

### 🔧 Configuración Completa
- **Servidor SMTP**: Configuración completa del servidor de correo saliente
- **Cuentas separadas**: Configuración independiente para administración y concejo
- **Presets predefinidos**: Gmail, Outlook, Mailpit y configuración personalizada
- **Pruebas integradas**: Test de conexión SMTP y envío de correos de prueba

### 📧 Gestión de Cuentas
- **Administración**: Cuenta específica para correos administrativos
- **Concejo**: Cuenta específica para comunicaciones del concejo
- **Firmas personalizadas**: Configuración de firmas para cada cuenta
- **Nombres para mostrar**: Personalización del nombre que aparece en los correos

### ⚙️ Configuración Avanzada
- **Rate limiting**: Control de límites de envío por hora y día
- **Cola de correos**: Envío asíncrono para mejor rendimiento
- **Reintentos automáticos**: Configuración de intentos fallidos
- **Plantillas HTML**: Soporte para correos con formato

## 🏗️ Arquitectura Implementada

### Backend
```
📁 app/Settings/
├── EmailSettings.php              # Clase principal de settings
📁 app/Http/Controllers/Settings/
├── EmailSettingsController.php    # Controlador de configuración
📁 database/migrations/
├── 2025_08_14_000238_create_email_settings_table.php
📁 database/seeders/
├── EmailSettingsSeeder.php        # Seeder con valores por defecto
```

### Frontend
```
📁 resources/js/pages/settings/
├── Email.vue                      # Vista principal de configuración
📁 resources/js/layouts/settings/
├── Layout.vue                     # Menú de settings actualizado
```

### Rutas
```
GET  /settings/email                    # Vista de configuración
POST /settings/email                    # Actualizar configuración
POST /settings/email/test-connection    # Probar conexión SMTP
POST /settings/email/test-email         # Enviar correo de prueba
POST /settings/email/apply-preset       # Aplicar preset
POST /settings/email/reset-defaults     # Restablecer por defecto
```

## 🔐 Seguridad y Permisos

### Acceso Restringido
- **Permiso requerido**: `edit_conjunto_config`
- **Roles con acceso**: `superadmin`, `admin_conjunto`
- **Encriptación**: Las contraseñas SMTP se encriptan antes de almacenar

### Validaciones
- **Campos requeridos**: Host SMTP, puerto, credenciales
- **Validación de email**: Direcciones de correo válidas
- **Rangos numéricos**: Puertos, timeouts, límites dentro de rangos válidos

## 📋 Configuraciones Disponibles

### 1. Servidor SMTP
```php
- smtp_host: string             // Servidor SMTP
- smtp_port: int               // Puerto (1-65535)
- smtp_username: string        // Usuario SMTP
- smtp_password: string        // Contraseña (encriptada)
- smtp_encryption: string      // tls, ssl o null
- smtp_timeout: int           // Timeout en segundos
```

### 2. Cuentas de Correo
```php
// Administración
- admin_email_address: string    // email@admin.com
- admin_email_name: string       // Nombre para mostrar
- admin_email_signature: string // Firma personalizada

// Concejo
- council_email_address: string  // email@concejo.com
- council_email_name: string     // Nombre para mostrar
- council_email_signature: string // Firma personalizada
```

### 3. Configuración General
```php
- email_enabled: bool           // Habilitar envío
- email_queue_enabled: bool     // Usar cola
- email_retry_attempts: int     // Intentos de reenvío
- email_retry_delay: int        // Demora entre intentos
- rate_limiting_enabled: bool   // Límites de velocidad
```

### 4. Integración Mailpit
```php
- mailpit_enabled: bool              // Habilitar Mailpit
- mailpit_url: string               // URL de Mailpit
- use_mailpit_in_development: bool  // Usar en desarrollo
```

## 🎯 Presets Disponibles

### Gmail
```
Host: smtp.gmail.com
Puerto: 587
Encriptación: TLS
```

### Outlook/Hotmail
```
Host: smtp-mail.outlook.com
Puerto: 587
Encriptación: TLS
```

### Mailpit (Desarrollo)
```
Host: localhost
Puerto: 1025
Sin encriptación
```

### Personalizada
```
Configuración manual completa
```

## 🧪 Funciones de Prueba

### Test de Conexión SMTP
- Valida credenciales y conectividad
- Timeout de 10 segundos
- Respuesta inmediata con resultado

### Envío de Correo de Prueba
- Selección de cuenta (admin/concejo)
- Dirección de destino personalizable
- Verificación de configuración completa

## 🔄 Integración con EmailController

### Uso de Settings
```php
// El EmailController ahora usa EmailSettings
protected EmailSettings $emailSettings;

// Configuración dinámica de Mailpit
if ($this->emailSettings->shouldUseMailpit()) {
    // Usar Mailpit
} else {
    // Usar datos mock
}

// Datos de cuentas configuradas
$adminConfig = $this->emailSettings->getAdminEmailConfig();
$councilConfig = $this->emailSettings->getCouncilEmailConfig();
```

### Datos Mock Dinámicos
Los datos de ejemplo ahora usan las direcciones y nombres configurados en settings en lugar de valores fijos.

## 📱 Interfaz de Usuario

### Pestañas Organizadas
1. **Servidor SMTP**: Configuración de conexión
2. **Cuentas de Correo**: Administración y Concejo
3. **Configuración General**: Opciones básicas
4. **Avanzado**: Rate limiting y configuraciones técnicas

### Características de UI
- **Indicadores de estado**: Configuración completa/incompleta
- **Estado de Mailpit**: Disponibilidad en tiempo real
- **Pruebas integradas**: Botones para test de conexión y envío
- **Presets rápidos**: Aplicación de configuraciones predefinidas
- **Validación en tiempo real**: Errores mostrados inmediatamente

### Elementos Visuales
- **Iconos descriptivos**: Identificación visual de cada sección
- **Estados de carga**: Indicadores durante pruebas
- **Resultados inmediatos**: Feedback de pruebas y guardado
- **Encriptación visual**: Campo de contraseña con toggle

## 🚀 Uso

### Configuración Inicial
1. **Acceder**: Menú Sistema → Configuración → Correo Electrónico
2. **Seleccionar preset**: Elegir Gmail, Outlook o personalizado
3. **Configurar SMTP**: Completar credenciales del servidor
4. **Probar conexión**: Verificar conectividad
5. **Configurar cuentas**: Direcciones para admin y concejo
6. **Enviar prueba**: Verificar envío completo

### Configuración de Producción
```
1. Usar preset "Personalizada"
2. Configurar servidor SMTP corporativo
3. Establecer cuentas reales:
   - admin@miconjunto.com
   - concejo@miconjunto.com
4. Configurar firmas institucionales
5. Habilitar rate limiting
6. Desactivar Mailpit
```

### Configuración de Desarrollo
```
1. Usar preset "Mailpit"
2. Verificar que Mailpit esté corriendo
3. Mantener cuentas de prueba
4. Habilitar todas las funciones de debug
```

## 🔮 Próximas Mejoras

### Funcionalidades Pendientes
- [ ] Envío real de correos (implementar mailer)
- [ ] Plantillas de correo personalizables
- [ ] Programación de envíos
- [ ] Listas de distribución
- [ ] Estadísticas de envío
- [ ] Integración con webhooks
- [ ] Backup automático de configuración

### Integraciones Futuras
- [ ] Múltiples proveedores SMTP
- [ ] Autenticación OAuth2
- [ ] Filtros anti-spam avanzados
- [ ] Métricas de deliverability
- [ ] Integración con marketing automation

## 📝 Mantenimiento

### Logs
Todas las acciones se registran en logs con:
- Usuario que realiza la acción
- Settings modificados
- Resultados de pruebas
- Errores de conexión

### Backup
Las configuraciones se almacenan en la tabla `settings` y se respaldan automáticamente con el resto de la base de datos.

El sistema está completamente funcional y listo para configurar las cuentas de correo del conjunto residencial.