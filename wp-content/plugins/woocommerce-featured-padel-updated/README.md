# WooCommerce Featured Padel Products - Actualización

## ✅ Cambios Realizados

### Problema Original
El plugin mostraba el **mismo texto** para las 3 palas destacadas porque usaba un único campo de texto a nivel de categoría (`campo_entre_titulo_resena`).

### Solución Implementada
Ahora **cada pala tiene su propio campo de texto personalizado**, permitiendo diferentes descripciones para cada producto destacado.

---

## 📋 Archivos Modificados

### 1. `includes/category-fields.php`
**Cambios principales:**
- ❌ **Eliminado**: Campo único `campo_entre_titulo_resena` (texto global)
- ✅ **Añadido**: 3 campos de texto individuales `_wfpp_textos_palas[]`
- Cada producto destacado ahora tiene su propio campo de texto debajo del selector de producto
- Los textos se guardan en un array de 3 posiciones

**Nuevos campos en WordPress:**
```
Producto Destacado 1:
  └─ [Selector de producto]
  └─ [Textarea: Texto personalizado para esta pala]

Producto Destacado 2:
  └─ [Selector de producto]
  └─ [Textarea: Texto personalizado para esta pala]

Producto Destacado 3:
  └─ [Selector de producto]
  └─ [Textarea: Texto personalizado para esta pala]
```

### 2. `includes/shortcode.php`
**Cambios principales:**
- Ahora lee el array `_wfpp_textos_palas` en lugar de `campo_entre_titulo_resena`
- Usa un índice (`$product_index`) para asignar el texto correcto a cada pala
- Cada producto muestra su texto específico en lugar del texto global

**Código relevante:**
```php
// Obtener textos individuales
$textos_palas = get_term_meta($category->term_id, '_wfpp_textos_palas', true);

// Dentro del loop de productos
$product_index = 0;
foreach ($products as $product_post): 
    $texto_pala = isset($textos_palas[$product_index]) ? $textos_palas[$product_index] : '';
    $product_index++;
    
    // Mostrar el texto de esta pala específica
    if (!empty($texto_pala)): 
        echo '<p class="wfpp-campo-entre-titulo-resena">' . esc_html($texto_pala) . '</p>';
    endif;
```

---

## 🚀 Cómo Usar

### Instalación
1. Desactiva el plugin actual en WordPress
2. Elimina la versión antigua del plugin
3. Sube esta nueva versión
4. Activa el plugin

### Configuración
1. Ve a **Productos → Categorías** en WordPress
2. Edita la categoría de palas (por ejemplo, "Padelschläger")
3. Verás 3 selectores de productos, cada uno con su campo de texto personalizado
4. Selecciona cada producto y escribe su texto específico
5. Guarda los cambios

### Ejemplo de uso:
```
Producto Destacado 1: Adidas Adipower Carbon Ctrl 3.4 2025
Texto: "Perfecta para jugadores avanzados que buscan control máximo"

Producto Destacado 2: Adidas Arrow Hit CTRL 2026  
Texto: "Ideal para jugadores intermedios con buen equilibrio entre potencia y control"

Producto Destacado 3: Adidas Adipower Carbon Light 3.4 2025
Texto: "Excelente opción para principiantes gracias a su ligereza"
```

---

## 🔄 Compatibilidad con Versiones Anteriores

El plugin mantiene compatibilidad con el campo antiguo `campo_entre_titulo_resena` por si necesitas recuperar datos antiguos, pero este campo ya no se utiliza en el frontend.

Si tenías textos en el campo antiguo, tendrás que copiarlos manualmente a los nuevos campos individuales de cada pala.

---

## ⚡ Ventajas de la Actualización

✅ **Textos personalizados** - Cada pala puede tener su propia descripción
✅ **Más flexibilidad** - Destaca características específicas de cada producto
✅ **Mejor SEO** - Contenido único para cada producto
✅ **Interfaz intuitiva** - Los campos de texto están junto a cada selector de producto

---

## 📝 Notas Técnicas

- Los textos se guardan en el meta de la categoría como `_wfpp_textos_palas`
- Es un array de 3 posiciones que corresponde a los 3 productos destacados
- El orden es importante: textos_palas[0] corresponde al featured_products[0]
- Los textos se sanitizan con `sanitize_textarea_field()` antes de guardarse

---

## 🐛 Resolución de Problemas

**Problema**: No veo los campos de texto nuevos
- Solución: Asegúrate de haber desactivado y reactivado el plugin después de actualizar

**Problema**: Los textos no se guardan
- Solución: Verifica los permisos de escritura de WordPress y que no haya conflictos con otros plugins

**Problema**: Aparece el mismo texto en todas las palas
- Solución: Asegúrate de haber guardado textos diferentes en cada campo individual

---

## 💡 Soporte

Si tienes alguna pregunta o problema con la actualización, contacta con el desarrollador del plugin.

**Versión**: 1.1.0
**Actualizado**: Diciembre 2024
