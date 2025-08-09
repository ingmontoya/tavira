# Menú de Conciliación Jelpit - Documentación

## Ubicación en la Navegación

El módulo de **Conciliación Jelpit** se encuentra ubicado en:

```
Finanzas → Pagos y Cobros → Conciliación Jelpit
```

## Configuración del Menú

### Archivo: `resources/js/composables/useNavigation.ts`

```typescript
{
    title: 'Conciliación Jelpit',
    href: '/finance/jelpit-reconciliation',
    icon: FileSpreadsheet,
    tourId: 'nav-jelpit-reconciliation',
    visible: hasPermission('view_payments'),
}
```

### Características del Menú

- **Título**: "Conciliación Jelpit"
- **Ruta**: `/finance/jelpit-reconciliation` 
- **Icono**: `FileSpreadsheet` (representativo para archivos Excel)
- **Permiso requerido**: `view_payments`
- **Tour ID**: `nav-jelpit-reconciliation` (para tours guiados)

## Estructura Jerárquica

```
📁 Finanzas (Wallet icon)
  └── 📁 Pagos y Cobros (CreditCard icon)
      ├── 💳 Pagos (CreditCard icon)
      ├── 🧾 Facturas (Receipt icon)  
      ├── 📧 Envío de Facturas (Mail icon)
      ├── ⚙️  Conceptos de Pago (Settings icon)
      ├── 📄 Acuerdos de Pago (FileText icon)
      └── 📊 Conciliación Jelpit (FileSpreadsheet icon) ← NUEVO
```

## Funcionalidad

Al hacer clic en "Conciliación Jelpit", el usuario navegará a:
- **URL**: `/finance/jelpit-reconciliation`
- **Controlador**: `JelpitReconciliationController@index`
- **Vista Vue**: `Finance/JelpitReconciliation/Index.vue`

## Permisos

El menú solo será visible para usuarios que tengan el permiso:
- `view_payments`

## Iconografía

Se utiliza el icono `FileSpreadsheet` de Lucide Icons que representa:
- 📊 Archivos de hoja de cálculo (Excel)
- 🔄 Conciliación de datos
- 📤 Importación de archivos

## Estado de Implementación

✅ **Completado**:
- Menú agregado a la navegación
- Icono apropiado seleccionado
- Permisos configurados
- Ruta funcionando
- Build exitoso

## Acceso Rápido

Para acceder al módulo:
1. Iniciar sesión con permisos de `view_payments`
2. Navegar a **Finanzas** en el menú lateral
3. Expandir **Pagos y Cobros**  
4. Hacer clic en **Conciliación Jelpit**

El menú está completamente funcional y listo para uso en producción.