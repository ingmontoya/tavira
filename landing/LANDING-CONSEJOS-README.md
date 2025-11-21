# Landing Page - Consejos

Landing page profesional para propietarios y miembros del consejo interesados en Tavira, con integración directa al CRM Perfex.

## 📋 Características

### Contenido del Landing
Basado en la presentación de ventas enfocada en **TRANSPARENCIA y TRANQUILIDAD** para propietarios/consejo:

- ✅ **10 Slides interactivos** con navegación fluida
- ✅ **Formulario de contacto** con validación completa
- ✅ **Integración directa con Perfex CRM** para captura de leads
- ✅ **Diseño responsive** optimizado para móviles
- ✅ **Animaciones suaves** y transiciones profesionales
- ✅ **SEO optimizado** con meta tags apropiados

### Slides Incluidos

1. **¿Su Consejo Tiene Control Real?** - Introducción al problema
2. **La Realidad Actual** - Puntos de dolor actuales
3. **Transparencia Total** - Dashboard y features principales
4. **Control de Costos** - Comparador de proveedores
5. **La Red Tavira** - Sistema de alertas compartidas
6. **Beneficios Principales** - 4 beneficios clave
7. **FAQs** - Preguntas frecuentes
8. **Formulario de Contacto** - Captura de leads
9. **CTA Final** - Llamado a la acción
10. **Footer** - Información de contacto

## 🚀 Implementación

### 1. Configuración de Variables de Entorno

Crear/editar el archivo `.env` en el directorio `landing/`:

```bash
# Perfex CRM API Configuration
PERFEX_BASE_URL=https://perfexcrm.themesic.com
PERFEX_API_USER=precontactos
PERFEX_API_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoicHJlY29udGFjdG9zIiwibmFtZSI6InByZWNvbnRhY3RvcyIsIkFQSV9USU1FIjoxNzYzNDk0MzU0fQ.4sLVBW3OECCabwnCuG1FprVigdHmoNzTnGxpaVvOdF4
```

### 2. Ejecutar el Landing

```bash
cd landing
npm install  # Si es primera vez
npm run dev
```

### 3. Acceder al Landing

Abrir en el navegador:
```
http://localhost:3000/consejos
```

## 🔧 Estructura de Archivos

```
landing/
├── pages/
│   └── consejos.vue                    # Página principal del landing
├── server/
│   └── api/
│       └── leads/
│           └── create.post.ts          # API endpoint para crear leads
├── .env                                 # Variables de entorno (NO commitear)
├── .env.example                         # Ejemplo de variables de entorno
└── nuxt.config.ts                      # Configuración actualizada con Perfex
```

## 📊 Integración con Perfex CRM

### Campos que se envían al CRM:

**Campos Estándar:**
- `name` - Nombre completo del contacto
- `email` - Email del contacto
- `phonenumber` - Teléfono de contacto
- `company` - Nombre del conjunto
- `title` - Cargo en el consejo
- `description` - Descripción detallada con toda la información
- `country` - Colombia (por defecto)
- `tags` - "Consejo,Landing Page,Prospecto"
- `source` - "Website - Landing Consejos"

**Campos Personalizados:**
- `custom_field_conjunto_name` - Nombre del conjunto
- `custom_field_num_units` - Número de unidades
- `custom_field_role` - Cargo en el consejo

### Configuración del CRM:

1. **Token API**: Ya configurado con el usuario `precontactos`
2. **Permisos**: El token debe tener permiso para crear leads
3. **Campos Personalizados**: Si deseas usar los campos custom, debes crearlos en Perfex CRM:
   - Ve a Setup > Custom Fields > Leads
   - Crea: `conjunto_name`, `num_units`, `role`

## 🎨 Personalización

### Cambiar Contenido de Slides

Editar el archivo `landing/pages/consejos.vue`, sección `slides`:

```typescript
const slides = [
  {
    id: 1,
    title: 'Tu título aquí',
    subtitle: 'Tu subtítulo',
    // ... más propiedades
  }
];
```

### Cambiar Beneficios

Editar la sección `benefits`:

```typescript
const benefits = [
  {
    icon: '✅',
    title: 'Tu beneficio',
    description: 'Descripción del beneficio'
  }
];
```

### Cambiar FAQs

Editar la sección `faqs`:

```typescript
const faqs = [
  {
    question: '¿Tu pregunta?',
    answer: 'Tu respuesta'
  }
];
```

## 🔐 Seguridad

- ✅ El token de API de Perfex **NO se expone al cliente**
- ✅ Las variables de entorno se manejan server-side en Nuxt
- ✅ Validación de formulario en cliente y servidor
- ✅ Sanitización de datos antes de enviar al CRM
- ✅ Manejo de errores sin exponer detalles internos

## 📱 Responsive Design

El landing está optimizado para:
- 📱 **Móviles** (320px+)
- 📱 **Tablets** (768px+)
- 💻 **Desktop** (1024px+)
- 🖥️ **Large Desktop** (1280px+)

## 🎯 Conversión y Analytics

### Eventos importantes para trackear:

1. **Visualización de slide** - `currentSlide` cambia
2. **Click en "Solicitar Demo"** - `openContactForm()`
3. **Envío de formulario** - `submitForm()`
4. **Lead creado exitosamente** - `showSuccessMessage = true`

### Integrar Google Analytics (opcional):

```vue
<script setup>
// En consejos.vue
const trackEvent = (category: string, action: string, label?: string) => {
  // @ts-ignore
  if (window.gtag) {
    window.gtag('event', action, {
      event_category: category,
      event_label: label
    });
  }
};

// Usar en eventos:
const nextSlide = () => {
  trackEvent('Slide Navigation', 'Next Slide', `Slide ${currentSlide.value + 1}`);
  // ... resto del código
};
</script>
```

## 🚀 Deployment

### Build para Producción

```bash
cd landing
npm run build
npm run preview  # Ver preview local
```

### Archivos Generados

```
landing/.output/
├── public/          # Archivos estáticos
└── server/          # Código del servidor
```

## 🧪 Testing

### Probar Integración con Perfex

1. Llenar el formulario en `/consejos`
2. Verificar en la consola del navegador que no hay errores
3. Verificar en Perfex CRM que el lead se creó:
   - Ve a Sales > Leads
   - Busca por el email enviado
   - Verifica que todos los campos estén correctos

### Errores Comunes

**Error: "Missing required field"**
- Verificar que todos los campos requeridos estén llenos

**Error: "Invalid email format"**
- Verificar formato del email

**Error: "Error processing your request"**
- Verificar token de Perfex en `.env`
- Verificar que la URL base de Perfex sea correcta
- Ver logs del servidor: `npm run dev` en terminal

## 📞 Contacto

Para soporte o preguntas:
- 📧 Email: consejo@tavira.com.co
- 📱 WhatsApp: +57 300 123 4567

## 📝 Notas

- El primer mes es GRATIS para los primeros 50 conjuntos
- Sin compromisos, se puede cancelar cuando quieran
- Incluye capacitación exclusiva para el consejo
- Acceso prioritario a la Red Tavira

---

**Creado por:** Tavira - Control y Transparencia para Residenciales
**Versión:** 1.0.0
**Última actualización:** Noviembre 2025
