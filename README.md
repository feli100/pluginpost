# Doguify Comparador de Seguros

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0+-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0+-red.svg)

Plugin completo de WordPress para comparar seguros de mascotas con integración a Petplan.

## 📋 Descripción

Doguify Comparador es un plugin profesional que permite a los usuarios comparar seguros para sus mascotas de forma rápida y sencilla. El plugin incluye un formulario avanzado, integración con la API de Petplan, páginas de progreso personalizadas y un completo panel de administración.

## ✨ Características Principales

### 🚀 Frontend
- **Formulario avanzado**: Radio buttons, campos de fecha inteligentes, validación en tiempo real
- **Página de espera**: Barra de progreso animada con pasos del proceso
- **Página de resultados**: Diseño profesional con precios y información de la mascota
- **Responsive**: Compatible con todos los dispositivos
- **Validaciones**: Validación tanto en frontend como backend
- **Rate limiting**: Protección contra spam y ataques

### 🔧 Backend
- **Panel de administración completo**: Gestión de comparativas, estadísticas y configuración
- **Integración Petplan**: Consulta automática de precios via API
- **Sistema de logs**: Logging completo para debugging y monitoreo
- **Exportación de datos**: CSV con filtros avanzados
- **Estadísticas**: Gráficos y métricas detalladas
- **GDPR**: Cumplimiento completo con exportación y eliminación de datos

### 🛡️ Seguridad
- **Nonces**: Protección CSRF en todas las acciones
- **Sanitización**: Limpieza completa de datos de entrada
- **Rate limiting**: Protección contra ataques de fuerza bruta
- **Validación**: Múltiples capas de validación
- **Logs de seguridad**: Registro de eventos sospechosos

## 🔧 Requisitos del Sistema

- **WordPress**: 5.0 o superior
- **PHP**: 7.4 o superior
- **MySQL**: 5.6 o superior
- **Extensiones PHP**: cURL, JSON
- **Permisos**: Escritura en wp-content

## 📦 Instalación

### Instalación Manual

1. **Descargar el plugin**:
   ```bash
   git clone https://github.com/tu-usuario/doguify-comparador.git
   ```

2. **Subir al directorio de plugins**:
   ```
   /wp-content/plugins/doguify-comparador/
   ```

3. **Activar el plugin**:
   - Ve a WordPress Admin → Plugins
   - Busca "Doguify Comparador"
   - Haz clic en "Activar"

### Instalación via ZIP

1. Descarga el archivo ZIP del plugin
2. Ve a WordPress Admin → Plugins → Añadir nuevo
3. Haz clic en "Subir plugin"
4. Selecciona el archivo ZIP y haz clic en "Instalar ahora"
5. Activa el plugin

## ⚙️ Configuración

### Configuración Inicial

1. **Accede al panel de configuración**:
   ```
   WordPress Admin → Doguify Comparador → Configuración
   ```

2. **Configura las opciones básicas**:
   - ✅ Habilitar consultas a Petplan
   - 📧 Email del administrador
   - 🎨 Personalizar títulos y mensajes

3. **Configura las razas disponibles**:
   ```
   beagle
   labrador
   golden_retriever
   pastor_aleman
   bulldog_frances
   chihuahua
   yorkshire
   boxer
   cocker_spaniel
   mestizo
   otro
   ```

### Opciones Avanzadas

- **Cache**: Duración del cache para consultas Petplan (60 minutos por defecto)
- **GDPR**: Retención de datos (730 días por defecto)
- **Debug**: Activar logs detallados para desarrollo
- **Integraciones**: Google Analytics, Facebook Pixel, Webhooks

## 🎯 Uso del Plugin

### Shortcode Principal

```php
[doguify_formulario titulo="Compara seguros para tu mascota"]
```

**Parámetros disponibles**:
- `titulo`: Título del formulario (opcional)

### Ejemplo de Uso

```php
// En una página o post
[doguify_formulario]

// En un template PHP
echo do_shortcode('[doguify_formulario titulo="Obtén tu comparativa"]');

// En un widget de texto
[doguify_formulario titulo="Seguro para tu mascota"]
```

### URLs Personalizadas

El plugin crea automáticamente estas URLs:

- **Página de espera**: `tudominio.com/doguify-espera/`
- **Página de resultados**: `tudominio.com/doguify-resultado/`

## 📁 Estructura de Archivos

```
doguify-comparador/
├── doguify-comparador.php          # Archivo principal del plugin
├── README.md                       # Esta documentación
├── includes/
│   ├── installer.php              # Instalación y activación
│   ├── ajax-handlers.php          # Manejadores AJAX
│   ├── utilities.php              # Funciones auxiliares
│   └── logger.php                 # Sistema de logs
├── admin/
│   ├── admin-panel.php            # Panel de administración
│   ├── admin.css                  # Estilos del admin
│   ├── admin.js                   # JavaScript del admin
│   └── views/
│       ├── admin-main.php         # Vista principal
│       ├── config.php             # Vista de configuración
│       └── stats.php              # Vista de estadísticas
├── assets/
│   ├── doguify-comparador.js      # JavaScript frontend
│   └── doguify-comparador.css     # CSS frontend
└── templates/
    ├── formulario.php             # Template del formulario
    ├── pagina-espera.php          # Template página de espera
    └── pagina-resultado.php       # Template página de resultados
```

## 🔌 Hooks y Filtros

### Actions Disponibles

```php
// Después de guardar una comparativa
do_action('doguify_after_save_comparison', $data, $comparison_id);

// Después de consultar Petplan
do_action('doguify_after_petplan_query', $session_id, $price, $data);

// Antes de mostrar resultados
do_action('doguify_before_show_results', $session_id, $data);

// Limpieza diaria
do_action('doguify_daily_cleanup');
```

### Filtros Disponibles

```php
// Modificar datos antes de guardar
$data = apply_filters('doguify_before_save_data', $data);

// Modificar URL de Petplan
$url = apply_filters('doguify_petplan_url', $url, $params);

// Modificar precio recibido
$price = apply_filters('doguify_petplan_price', $price, $raw_response);

// Modificar template de email
$template = apply_filters('doguify_email_template', $template, $type, $data);
```

### Ejemplos de Uso

```php
// Añadir campos personalizados
add_filter('doguify_before_save_data', function($data) {
    $data['custom_field'] = 'valor_personalizado';
    return $data;
});

// Modificar precio
add_filter('doguify_petplan_price', function($price, $response) {
    // Aplicar descuento del 10%
    return $price * 0.9;
}, 10, 2);

// Enviar notificación personalizada
add_action('doguify_after_save_comparison', function($data, $id) {
    // Enviar a Slack, Discord, etc.
    custom_send_notification($data);
}, 10, 2);
```

## 📊 Base de Datos

### Tabla Principal: `wp_doguify_comparativas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único auto-incremental |
| `session_id` | VARCHAR(50) | ID único de sesión |
| `tipo_mascota` | VARCHAR(20) | perro/gato |
| `nombre` | VARCHAR(100) | Nombre de la mascota |
| `email` | VARCHAR(255) | Email del propietario |
| `codigo_postal` | VARCHAR(5) | Código postal |
| `edad_dia` | INT | Día de nacimiento |
| `edad_mes` | INT | Mes de nacimiento |
| `edad_año` | INT | Año de nacimiento |
| `raza` | VARCHAR(100) | Raza de la mascota |
| `precio_petplan` | DECIMAL(10,2) | Precio de Petplan |
| `estado` | VARCHAR(20) | pendiente/completado |
| `ip_address` | VARCHAR(45) | IP del usuario |
| `user_agent` | TEXT | User agent del navegador |
| `fecha_registro` | DATETIME | Fecha de registro |
| `fecha_consulta` | DATETIME | Fecha de consulta Petplan |

### Tabla de Logs: `wp_doguify_logs`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | ID único |
| `session_id` | VARCHAR(50) | ID de sesión relacionado |
| `level` | VARCHAR(20) | Nivel del log |
| `message` | TEXT | Mensaje del log |
| `context` | TEXT | Contexto adicional (JSON) |
| `fecha` | DATETIME | Fecha del log |

## 🔧 API de Petplan

### Endpoint

```
GET https://ws.petplan.es/pricing
```

### Parámetros

| Parámetro | Descripción | Ejemplo |
|-----------|-------------|---------|
| `postalcode` | Código postal | 28001 |
| `age` | Fecha nacimiento | 11/01/2019 |
| `column` | Columna (siempre 2) | 2 |
| `breed` | Raza | beagle |

### Respuesta

```json
{
  "Precio": "562.00"
}
```

## 🚨 Troubleshooting

### Problemas Comunes

#### 1. El formulario no envía datos

**Síntomas**: El botón "Obtener comparativa" no hace nada

**Soluciones**:
```php
// Verificar que jQuery está cargado
wp_enqueue_script('jquery');

// Verificar nonce en consola del navegador
console.log(window.doguify_ajax.nonce);

// Verificar AJAX URL
console.log(window.doguify_ajax.ajax_url);
```

#### 2. Error 404 en páginas de espera/resultado

**Síntomas**: Las URLs `/doguify-espera/` devuelven 404

**Soluciones**:
```php
// Limpiar permalinks
flush_rewrite_rules();

// Verificar configuración
add_action('init', function() {
    add_rewrite_rule('^doguify-espera/?$', 'index.php?doguify_page=espera', 'top');
    add_rewrite_rule('^doguify-resultado/?$', 'index.php?doguify_page=resultado', 'top');
});
```

#### 3. Consultas a Petplan fallan

**Síntomas**: No se obtienen precios, estado siempre "pendiente"

**Soluciones**:
```php
// Verificar conectividad
$response = wp_remote_get('https://ws.petplan.es/pricing?postalcode=28001&age=01/01/2020&column=2&breed=beagle');
if (is_wp_error($response)) {
    echo $response->get_error_message();
}

// Verificar timeout
add_filter('http_request_timeout', function() {
    return 30; // 30 segundos
});

// Habilitar logs
update_option('doguify_config', array_merge(
    get_option('doguify_config', []),
    ['debug_mode' => true]
));
```

#### 4. Estilos no se cargan correctamente

**Síntomas**: El formulario se ve sin estilos

**Soluciones**:
```php
// Verificar enqueue de estilos
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('doguify-comparador', 
        plugin_dir_url(__FILE__) . 'assets/doguify-comparador.css');
});

// Limpiar cache
wp_cache_flush();

// Verificar permisos de archivos
chmod 644 wp-content/plugins/doguify-comparador/assets/doguify-comparador.css
```

### Logs de Debug

```php
// Activar logs detallados
update_option('doguify_config', array_merge(
    get_option('doguify_config', []),
    ['debug_mode' => true]
));

// Ver logs en tiempo real
tail -f wp-content/debug.log | grep DOGUIFY

// Verificar logs en base de datos
SELECT * FROM wp_doguify_logs ORDER BY fecha DESC LIMIT 20;
```

## 🔄 Actualizaciones

### Migraciones Automáticas

El plugin incluye un sistema de migraciones automáticas que se ejecuta al actualizar:

```php
// Verificar versión actual
$current_version = get_option('doguify_plugin_version', '0.0.0');

// Las migraciones se ejecutan automáticamente
// Ver includes/installer.php para detalles
```

### Backup Antes de Actualizar

```sql
-- Backup de datos
CREATE TABLE wp_doguify_comparativas_backup AS 
SELECT * FROM wp_doguify_comparativas;

CREATE TABLE wp_doguify_logs_backup AS 
SELECT * FROM wp_doguify_logs;
```

## 📈 Optimización

### Performance

```php
// Cache de consultas Petplan (configurar en admin)
$cache_duration = 60; // minutos

// Optimizar consultas de base de datos
add_action('init', function() {
    // Índices automáticos creados en instalación
});

// Lazy loading de assets
add_action('wp_enqueue_scripts', function() {
    // Solo cargar en páginas que lo necesiten
    if (has_shortcode(get_post()->post_content, 'doguify_formulario')) {
        wp_enqueue_style('doguify-comparador');
        wp_enqueue_script('doguify-comparador');
    }
});
```

### Seguridad

```php
// Rate limiting personalizado
add_filter('doguify_rate_limit_attempts', function($limit) {
    return 3; // 3 intentos por hora
});

// Validación adicional
add_filter('doguify_before_save_data', function($data) {
    // Validaciones personalizadas
    if (!custom_validate_data($data)) {
        wp_die('Datos inválidos');
    }
    return $data;
});
```

## 📝 Changelog

### v1.0.0 (2025-01-08)
- ✨ Lanzamiento inicial
- 🚀 Formulario completo con validación avanzada
- 🔌 Integración completa con API Petplan
- 📊 Panel de administración con estadísticas
- 🛡️ Sistema de logs y seguridad
- 📱 Diseño responsive
- 🌍 Cumplimiento GDPR
- 📧 Sistema de notificaciones por email
- 🎨 Páginas de progreso animadas
- 📈 Exportación de datos CSV
- 🔧 Sistema de configuración avanzado

## 🤝 Contribuir

### Desarrollo Local

```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/doguify-comparador.git

# Instalar dependencias (si las hay)
npm install

# Configurar entorno de desarrollo
cp wp-config-sample.php wp-config.php
```

### Estándares de Código

- **WordPress Coding Standards**
- **PHPDoc** para toda función pública
- **ESLint** para JavaScript
- **Responsive design** obligatorio
- **Accesibilidad** WCAG 2.1 AA

### Reportar Issues

1. Usar el template de issue en GitHub
2. Incluir versión de WordPress y PHP
3. Adjuntar logs relevantes
4. Pasos detallados para reproducir

## 📄 Licencia

Este plugin está licenciado bajo GPL-2.0+. Ver archivo LICENSE para más detalles.

## 📞 Soporte

- **Email**: soporte@doguify.com
- **GitHub Issues**: [github.com/tu-usuario/doguify-comparador/issues](https://github.com/tu-usuario/doguify-comparador/issues)
- **Documentación**: [docs.doguify.com](https://docs.doguify.com)

## 🙏 Créditos

- **Desarrollado por**: Equipo Doguify
- **API Petplan**: Integración oficial
- **Iconos**: Font Awesome y Emoji nativo
- **Framework CSS**: Tailwind CSS (utilidades)

---

**🐕 ¡Hecho con amor para las mascotas! 🐱**
**🐕 ¡Hecho con amor para las mascotas! 🐱**