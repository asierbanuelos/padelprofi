# PadelProfi Cost Analysis - Versión 3.0

## 📋 Descripción

Plugin completo de análisis de costes y beneficios para WooCommerce con sistema de exportación a Excel/CSV independiente.

## ✨ Características Principales

### 1. **Análisis de Costes por Producto**
- Campo de coste en cada producto
- Guardado automático del coste al momento del pedido
- Visualización detallada por producto en cada pedido

### 2. **Cálculos Automáticos**
- **Bruttogewinn (Beneficio Bruto)**: Umsatz - Produktkosten
- **Nettogewinn (Beneficio Neto)**: Total - MwSt - Produktkosten - Versandkosten + Versand cobrado - Comisiones
- Márgenes porcentuales automáticos

### 3. **Visualización en Pedidos**
- Columnas adicionales en la lista de pedidos
- Sección completa en los totales del pedido
- Meta box detallado con análisis por producto
- Códigos de color para identificar rápidamente beneficios/pérdidas

### 4. **Sistema de Exportación (NUEVO)**
- Página dedicada de exportación en WooCommerce
- Exportación a Excel (.xlsx) o CSV
- Filtros por fecha y estado de pedido
- Opción de incluir detalles de productos
- Descarga directa sin dependencias externas

## 🚀 Instalación

### Opción 1: Instalación Manual

1. Crea una carpeta llamada `padelprofi-cost-analysis` en `/wp-content/plugins/`

2. Copia los siguientes archivos a la carpeta:
```
padelprofi-cost-analysis/
├── padelprofi-cost-analysis.php (renombrar el archivo principal)
└── assets/
    ├── css/
    │   └── export.css
    └── js/
        └── export.js
```

3. Ve a **WordPress Admin > Plugins** y activa "PadelProfi Cost Analysis"

### Opción 2: Subir ZIP

1. Crea un archivo ZIP con la estructura anterior
2. Ve a **WordPress Admin > Plugins > Añadir nuevo > Subir plugin**
3. Selecciona el ZIP y activa el plugin

## 📊 Uso del Plugin

### Configurar Costes de Productos

1. Edita cualquier producto en WooCommerce
2. En la pestaña **General** encontrarás el campo **"Cost Price (€)"**
3. Introduce el coste de compra del producto
4. El coste se guardará automáticamente con cada pedido

### Visualizar Análisis en Pedidos

Los pedidos mostrarán automáticamente:

- **En la lista de pedidos**: Columnas de Bruttogewinn y Nettogewinn
- **En el detalle del pedido**:
  - Sección completa de cálculos en los totales
  - Meta box "Kostenanalyse pro Produkt" con tabla detallada

### Exportar Datos

1. Ve a **WooCommerce > Exportar Costes**

2. Configura los filtros:
   - **Fecha desde/hasta**: Rango de fechas a exportar
   - **Estado de pedidos**: Selecciona uno o varios estados (o "Todos")
   - **Formato**: Excel (.xlsx) o CSV
   - **Incluir detalles de productos**: Marca si quieres detalle por producto

3. Haz clic en **"Exportar Datos"**

4. El archivo se descargará automáticamente

### Estructura del Excel/CSV Exportado

#### Hoja Principal: Resumen de Pedidos

| Columna | Descripción |
|---------|-------------|
| Pedido | Número de pedido |
| Fecha | Fecha y hora del pedido |
| Estado | Estado actual del pedido |
| Cliente | Nombre del cliente |
| Email | Email del cliente |
| Total Pedido | Total con IVA |
| MwSt | Impuestos (19%) |
| Umsatz (sin MwSt) | Ingresos sin impuestos |
| Produktkosten | Coste de los productos |
| Bruttogewinn | Beneficio bruto |
| Brutto % | Margen bruto porcentual |
| Versandkosten | Coste de envío real (6.70€) |
| Versand cobrado | Lo que se cobró al cliente |
| Comisiones pago | Comisiones de PayPal/Stripe |
| Nettogewinn | Beneficio neto final |
| Netto % | Margen neto porcentual |

#### Si se incluyen detalles de productos:

Debajo de cada pedido aparecerán filas con:
- Nombre del producto
- Cantidad
- Coste unitario
- Coste total
- Venta total
- Beneficio del producto
- Margen del producto

## 🔧 Funciones Técnicas

### Funciones Auxiliares Disponibles

```php
// Obtener coste de envío de un pedido
padelprofi_get_shipping_cost($order);

// Obtener comisiones de pago de un pedido
padelprofi_get_payment_fees($order);

// Obtener coste de un item (del momento del pedido)
padelprofi_get_item_cost($item);

// Obtener todos los cálculos de un pedido
padelprofi_get_order_calculations($order);
```

### Meta Data Guardada

El plugin guarda automáticamente:

```php
// En productos
_cost_price_padelprofi

// En items de pedido
_line_item_cost_price

// En pedidos
_padelprofi_gross_profit
_padelprofi_net_profit
_padelprofi_total_cost
```

## 📈 Cálculos Detallados

### Bruttogewinn (Beneficio Bruto)
```
Bruttogewinn = Umsatz - Produktkosten
Brutto % = (Bruttogewinn / Umsatz) × 100
```

### Nettogewinn (Beneficio Neto)
```
Nettogewinn = Total Pedido 
              - MwSt (19%)
              - Produktkosten
              - Versandkosten (6.70€)
              + Versand cobrado al cliente
              - Comisiones de pago

Netto % = (Nettogewinn / Total Pedido) × 100
```

## 🎨 Características Visuales

- **Colores automáticos**: Verde para beneficios, rojo para pérdidas
- **Tablas responsivas**: Adaptadas a diferentes tamaños de pantalla
- **Iconos descriptivos**: Facilitan la comprensión rápida
- **Progress bar**: Durante la exportación

## ⚙️ Configuración de Costes de Envío

Por defecto, el coste de envío es **6.70€**. Para cambiarlo:

1. Edita el archivo principal del plugin
2. Busca la función `padelprofi_get_shipping_cost`
3. Cambia el valor de retorno:

```php
function padelprofi_get_shipping_cost($order) {
    return 6.70; // Cambia este valor
}
```

## 🔄 Compatibilidad

- **WordPress**: 5.8 o superior
- **PHP**: 7.4 o superior
- **WooCommerce**: 6.0 - 8.5+
- **Pasarelas de pago compatibles**:
  - PayPal
  - Stripe
  - Otras que guarden comisiones como meta data

## 📝 Notas Importantes

1. **Pedidos antiguos**: Los pedidos anteriores al 23/12/2025 mostrarán coste 0 si no tienen coste guardado
2. **Costes al momento del pedido**: Se guarda el coste del producto en el momento exacto de la compra
3. **Exportaciones grandes**: Para más de 1000 pedidos, se recomienda hacer exportaciones por rangos de fechas
4. **Formato CSV**: Usa punto y coma (;) como separador y coma (,) para decimales (formato europeo)

## 🐛 Solución de Problemas

### La exportación no descarga
- Verifica que tengas permisos de administrador de WooCommerce
- Comprueba que los directorios tengan permisos de escritura
- Revisa la consola del navegador para errores JavaScript

### Los costes no aparecen
- Asegúrate de haber guardado el coste en cada producto
- Verifica que el pedido sea posterior al 23/12/2025
- Comprueba que el producto tenga el campo `_cost_price_padelprofi` relleno

### El Excel no se genera
- Si PhpSpreadsheet no está disponible, automáticamente usa CSV
- Verifica los logs de PHP para errores de memoria
- Para pedidos muy grandes, usa CSV en lugar de XLSX

## 🆕 Cambios en Versión 3.0

- ✅ **NUEVO**: Sistema de exportación independiente
- ✅ **NUEVO**: Página dedicada en menú de WooCommerce
- ✅ **NUEVO**: Filtros avanzados de fecha y estado
- ✅ **NUEVO**: Opción de incluir detalles de productos
- ✅ **NUEVO**: Soporte para Excel y CSV
- ✅ **MEJORADO**: Interfaz visual más limpia
- ✅ **ELIMINADO**: Dependencia de Analytics de WooCommerce
- ✅ **ELIMINADO**: Necesidad de archivos JavaScript complejos

## 📧 Soporte

Para reportar bugs o solicitar nuevas características, contacta con el equipo de desarrollo.

## 📄 Licencia

Este plugin es propiedad de PadelProfi Deutschland.

---

**Versión**: 3.0  
**Última actualización**: Enero 2026  
**Desarrollado por**: PadelProfi Team
