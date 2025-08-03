# 📍 NAVEGACIÓN - PANEL DE MAPEO CONTABLE

## ✅ UBICACIÓN EN EL MENÚ

### **Ruta de Acceso:**
```
Settings → Mapeo Contable
/settings/payment-concept-mapping
```

### **Navegación Visual:**
```
🏠 Habitta
├── Dashboard
├── Apartamentos  
├── Residentes
├── Pagos
├── Contabilidad
└── ⚙️ Settings
    ├── 👤 Perfil
    ├── 🔐 Contraseña
    ├── 🛡️ Seguridad
    ├── 🎨 Apariencia
    ├── 💳 Pagos
    ├── 👥 Permisos
    └── 📊 Mapeo Contable ← **NUEVA FUNCIONALIDAD**
```

---

## 🔐 CONTROL DE ACCESO

### **Permiso Requerido:** `manage_accounting`

### **Roles con Acceso:**
- ✅ **Superadmin** - Acceso completo
- ✅ **Admin Conjunto** - Gestión completa del mapeo
- ✅ **Consejo** - Configuración de cuentas contables
- ❌ **Propietario** - Sin acceso
- ❌ **Residente** - Sin acceso  
- ❌ **Portería** - Sin acceso

---

## 🎯 FUNCIONALIDADES DEL PANEL

### 1. **Vista Principal**
- **Tabla de Mapeos Configurados**
- **Estado Activo/Inactivo** por mapeo
- **Filtros y Búsqueda** por concepto
- **Alertas** para conceptos sin mapeo

### 2. **Crear Nuevo Mapeo**
```
┌─────────────────────────────────────┐
│ Nuevo Mapeo de Cuentas             │
├─────────────────────────────────────┤
│ Concepto de Pago:  [Dropdown ▼]    │
│ Cuenta Ingresos:   [Dropdown ▼]    │  
│ Cuenta por Cobrar: [Dropdown ▼]    │
│ Notas: [Textarea]                   │
├─────────────────────────────────────┤
│ [Cancelar] [Crear Mapeo]           │
└─────────────────────────────────────┘
```

### 3. **Editar Mapeo Existente**
```
┌─────────────────────────────────────┐
│ Editar: "Cuotas de Administración" │
├─────────────────────────────────────┤
│ Cuenta Ingresos:   [413501 ▼]      │  
│ Cuenta por Cobrar: [130501 ▼]      │
│ Mapeo Activo:      [Toggle ON]     │
│ Notas: [Textarea]                   │
├─────────────────────────────────────┤
│ [Cancelar] [Actualizar]            │
└─────────────────────────────────────┘
```

### 4. **Mapeos por Defecto**
```
Botón: "Crear Mapeos por Defecto"
→ Genera automáticamente mapeos para conceptos sin configurar
→ Basado en el tipo de concepto (administración, mora, multas, etc.)
```

---

## 📊 EJEMPLO DE USO

### **Conjunto "Torres de Villa Campestre"**

#### Paso 1: Acceder al Panel
```
1. Login como Admin Conjunto
2. Ir a Settings → Mapeo Contable
3. Ver conceptos sin mapeo (si existen)
```

#### Paso 2: Configurar Mapeos
```
Concepto: "Cuotas de Administración"
├── Cuenta Ingresos: 413501 - Cuotas de Administración  
└── Cuenta por Cobrar: 130501 - Cartera Administración

Concepto: "Intereses de Mora"  
├── Cuenta Ingresos: 413506 - Intereses de Mora
└── Cuenta por Cobrar: 130503 - Cartera Intereses Mora

Concepto: "Parqueaderos"
├── Cuenta Ingresos: 413503 - Parqueaderos
└── Cuenta por Cobrar: 130501 - Cartera Administración
```

#### Paso 3: Resultado
```
✅ Facturación automática usa cuentas específicas
✅ Reportes contables separados por concepto  
✅ Control detallado de cartera por tipo
```

---

## 🔧 CARACTERÍSTICAS TÉCNICAS

### **Frontend (Vue.js)**
- **Componente:** `settings/PaymentConceptMapping/Index.vue`
- **Ruta:** `/settings/payment-concept-mapping`  
- **Layout:** `settings/Layout.vue`
- **UI:** shadcn/ui components + TypeScript

### **Backend (Laravel)**
- **Controlador:** `PaymentConceptMappingController`
- **Middleware:** `can:manage_accounting`
- **Modelo:** `PaymentConceptAccountMapping`
- **Rutas:** 6 endpoints RESTful + utilidades

### **Base de Datos**
- **Tabla:** `payment_concept_account_mappings`
- **Vista:** Unión con `payment_concepts` y `chart_of_accounts`
- **Índices:** Optimizados para consultas frecuentes

---

## 🚀 FLUJO DE TRABAJO

### **Para Admin Conjunto:**
```
1. Recibe conjunto nuevo
2. Configura plan de cuentas (si no existe)
3. Va a Settings → Mapeo Contable  
4. Crea mapeos automáticos o manuales
5. Activa/desactiva según necesidad
6. Sistema funciona automáticamente
```

### **Para Consejo:**
```
1. Revisa mapeos existentes
2. Modifica según políticas del conjunto
3. Aprueba configuración contable
4. Supervisa reportes generados
```

---

## ✅ VERIFICACIONES REALIZADAS

- ✅ **Rutas registradas** correctamente
- ✅ **Permisos creados** en base de datos  
- ✅ **Menú de navegación** actualizado
- ✅ **Compilación exitosa** sin errores
- ✅ **TypeScript** tipado completamente
- ✅ **Responsive design** implementado

---

## 📋 PRÓXIMOS PASOS

1. **Testing en Staging**
   - Probar con usuario admin_conjunto
   - Verificar permisos por rol
   - Validar flujo completo

2. **Capacitación**
   - Manual de usuario para administradores
   - Video tutorial del panel
   - Casos de uso comunes

3. **Monitoreo**
   - Logs de uso del panel
   - Errores de configuración
   - Performance de consultas

---

**✅ PANEL DE MAPEO CONTABLE COMPLETAMENTE INTEGRADO AL SISTEMA DE NAVEGACIÓN**

**Ubicación:** `Settings → Mapeo Contable`  
**Estado:** Listo para uso en producción  
**Fecha:** 2025-08-03