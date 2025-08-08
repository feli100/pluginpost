<?php
/**
 * Configuración completa para Doguify Comparador
 * Archivo: includes/config.php
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Configuración por defecto de la página de espera
define('DOGUIFY_WAITING_PAGE_CONFIG', array(
    // URLs de imágenes
    'images' => array(
        'logo' => 'https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-scaled-e1750429316951.png',
        'pets_left' => 'https://doguify.com/wp-content/uploads/2025/07/perro-gato-1-e1751989375681.png',
        'pets_right' => 'https://doguify.com/wp-content/uploads/2025/07/perros-web-1-e1751989411921.png'
    ),
    
    // Configuración de colores
    'colors' => array(
        'primary_gradient_start' => '#4A90E2',
        'primary_gradient_middle' => '#357ABD',
        'primary_gradient_end' => '#2E6DA4',
        'wave_color' => '#ECF3FD',
        'text_color' => '#FFFFFF',
        'progress_bar_bg' => 'rgba(255, 255, 255, 0.2)',
        'progress_bar_fill' => '#FFFFFF'
    ),
    
    // Configuración de textos dinámicos
    'loading_texts' => array(
        'trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar',
        'analizando las mejores opciones<br>para tu mascota',
        'comparando precios y coberturas<br>en tiempo real',
        'finalizando tu comparativa<br>personalizada',
        'verificando disponibilidad<br>de productos especializados',
        'consultando bases de datos<br>de seguros veterinarios'
    ),
    
    // Configuración de progreso
    'progress' => array(
        'initial_delay' => 500,
        'text_change_interval' => 4000,
        'min_duration' => 8000,
        'max_duration' => 15000,
        'phases' => array(
            'phase1' => array('end' => 30, 'speed_min' => 1, 'speed_max' => 4),
            'phase2' => array('end' => 70, 'speed_min' => 0.5, 'speed_max' => 2.5),
            'phase3' => array('end' => 90, 'speed_min' => 0.2, 'speed_max' => 1.2),
            'phase4' => array('end' => 100, 'speed_min' => 0.1, 'speed_max' => 0.6)
        )
    ),
    
    // Configuración responsive
    'breakpoints' => array(
        'mobile' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'large' => 1200
    ),
    
    // Configuración de rendimiento
    'performance' => array(
        'lazy_load_images' => true,
        'preload_next_page' => true,
        'compress_output' => true,
        'cache_duration' => 3600
    ),
    
    // Configuración de analytics
    'analytics' => array(
        'track_page_view' => true,
        'track_progress_milestones' => array(25, 50, 75, 90, 100),
        'track_time_on_page' => true,
        'track_exit_intent' => true
    ),
    
    // Configuración de accesibilidad
    'accessibility' => array(
        'high_contrast_mode' => true,
        'reduced_motion_support' => true,
        'screen_reader_support' => true,
        'keyboard_navigation' => true
    ),
    
    // Configuración de SEO
    'seo' => array(
        'noindex' => true,
        'nofollow' => true,
        'no_sitemap' => true,
        'meta_description' => 'Procesando comparativa de seguros para mascotas - Doguify'
    )
));

// Configuración por defecto del plugin
define('DOGUIFY_DEFAULT_CONFIG', array(
    // Integración Petplan
    'petplan_enabled' => true,
    'petplan_timeout' => 30,
    'cache_duration' => 60,
    
    // Notificaciones
    'email_notifications' => false,
    'admin_email' => get_option('admin_email'),
    'user_confirmation_email' => false,
    
    // Personalización
    'results_page_title' => 'Tu comparativa está lista',
    'results_page_subtitle' => 'Hemos encontrado las mejores opciones para tu mascota',
    'waiting_page_message' => 'trabajamos con los mejores proveedores para que puedas comparar planes y precios en un solo lugar',
    
    // Razas disponibles
    'available_breeds' => "beagle\nlabrador\ngolden_retriever\npastor_aleman\nbulldog_frances\nchihuahua\nyorkshire\nboxer\ncocker_spaniel\nmestizo\notro",
    'allow_custom_breed' => true,
    
    // Configuración avanzada
    'debug_mode' => false,
    'gdpr_compliance' => true,
    'data_retention_days' => 730,
    
    // Integraciones
    'google_analytics_id' => '',
    'facebook_pixel_id' => '',
    'webhook_url' => '',
    
    // Rate limiting
    'rate_limit_enabled' => true,
    'max_attempts_per_hour' => 5,
    'rate_limit_window' => 300,
    
    // Seguridad
    'honeypot_enabled' => true,
    'require_ssl' => false,
    'block_tor' => false,
    
    // Internacionalización
    'default_language' => 'es_ES',
    'enable_multilang' => false
));

/**
 * Función para obtener configuración
 * Soporta notación de puntos para claves anidadas
 */
function doguify_get_config($key = null, $default = null) {
    // Obtener configuración guardada
    $saved_config = get_option('doguify_config', array());
    
    // Merger con configuración por defecto
    $config = array_merge(DOGUIFY_DEFAULT_CONFIG, $saved_config);
    
    // Merger configuración de la página de espera
    $config = array_merge($config, DOGUIFY_WAITING_PAGE_CONFIG);
    
    if ($key === null) {
        return $config;
    }
    
    // Soportar notación de puntos para claves anidadas
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }
    
    return $value;
}

/**
 * Función para actualizar configuración
 * Soporta notación de puntos para claves anidadas
 */
function doguify_update_config($key, $value) {
    $config = get_option('doguify_config', array());
    
    // Soportar notación de puntos
    $keys = explode('.', $key);
    $temp = &$config;
    
    foreach ($keys as $k) {
        if (!isset($temp[$k]) || !is_array($temp[$k])) {
            $temp[$k] = array();
        }
        $temp = &$temp[$k];
    }
    
    $temp = $value;
    
    return update_option('doguify_config', $config);
}

/**
 * Función para generar CSS dinámico basado en configuración
 */
function doguify_generate_dynamic_css() {
    $colors = doguify_get_config('colors');
    $breakpoints = doguify_get_config('breakpoints');
    
    ob_start();
    ?>
    <style id="doguify-dynamic-css">
    :root {
        --doguify-primary-start: <?php echo esc_attr($colors['primary_gradient_start']); ?>;
        --doguify-primary-middle: <?php echo esc_attr($colors['primary_gradient_middle']); ?>;
        --doguify-primary-end: <?php echo esc_attr($colors['primary_gradient_end']); ?>;
        --doguify-wave-color: <?php echo esc_attr($colors['wave_color']); ?>;
        --doguify-text-color: <?php echo esc_attr($colors['text_color']); ?>;
        --doguify-progress-bg: <?php echo esc_attr($colors['progress_bar_bg']); ?>;
        --doguify-progress-fill: <?php echo esc_attr($colors['progress_bar_fill']); ?>;
        --doguify-mobile: <?php echo intval($breakpoints['mobile']); ?>px;
        --doguify-tablet: <?php echo intval($breakpoints['tablet']); ?>px;
        --doguify-desktop: <?php echo intval($breakpoints['desktop']); ?>px;
    }

    .doguify-waiting-page-wrapper {
        background: linear-gradient(135deg, 
            var(--doguify-primary-start) 0%, 
            var(--doguify-primary-middle) 50%, 
            var(--doguify-primary-end) 100%) !important;
    }
    
    .doguify-wave {
        background: var(--doguify-wave-color);
    }
    
    .doguify-wave:nth-child(2) {
        background: <?php echo esc_attr($colors['wave_color']); ?>cc; /* 80% opacity */
    }
    
    .doguify-wave:nth-child(3) {
        background: <?php echo esc_attr($colors['wave_color']); ?>99; /* 60% opacity */
    }
    
    .doguify-progress-container {
        background: var(--doguify-progress-bg);
    }
    
    .doguify-progress-bar {
        background: var(--doguify-progress-fill);
    }
    
    .doguify-waiting-page-wrapper h1,
    .doguify-waiting-page-wrapper p,
    .doguify-waiting-page-wrapper div {
        color: var(--doguify-text-color) !important;
    }
    
    /* Responsive breakpoints dinámicos */
    @media (max-width: <?php echo intval($breakpoints['tablet']); ?>px) {
        .doguify-side-image {
            display: none;
        }
        
        .doguify-content-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: <?php echo intval($breakpoints['mobile']); ?>px) {
        .doguify-main-title {
            font-size: 1.5rem !important;
        }
        
        .doguify-logo img {
            max-width: 200px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Función para registrar hooks de configuración
 */
function doguify_register_config_hooks() {
    // Agregar CSS dinámico en la página de espera
    add_action('wp_head', function() {
        if (get_query_var('doguify_page') === 'espera') {
            echo doguify_generate_dynamic_css();
        }
    }, 20);
    
    // Agregar configuración JavaScript
    add_action('wp_footer', function() {
        if (get_query_var('doguify_page') === 'espera') {
            $js_config = array(
                'loadingTexts' => doguify_get_config('loading_texts'),
                'progress' => doguify_get_config('progress'),
                'analytics' => doguify_get_config('analytics'),
                'performance' => doguify_get_config('performance'),
                'accessibility' => doguify_get_config('accessibility')
            );
            ?>
            <script id="doguify-config-js">
            if (typeof window.DoguifyConfig === 'undefined') {
                window.DoguifyConfig = <?php echo wp_json_encode($js_config); ?>;
            }
            
            // Configurar analytics si está habilitado
            if (window.DoguifyConfig.analytics.track_page_view && typeof gtag !== 'undefined') {
                gtag('event', 'page_view', {
                    page_title: 'Doguify - Página de Espera',
                    page_location: window.location.href
                });
            }
            
            // Configurar Facebook Pixel si está habilitado
            <?php if (!empty(doguify_get_config('facebook_pixel_id'))): ?>
            if (typeof fbq !== 'undefined') {
                fbq('track', 'ViewContent', {
                    content_name: 'Waiting Page',
                    content_category: 'Insurance Comparison'
                });
            }
            <?php endif; ?>
            </script>
            <?php
        }
    });
    
    // Precargar recursos críticos
    add_action('wp_head', function() {
        if (get_query_var('doguify_page') === 'espera') {
            $images = doguify_get_config('images');
            foreach ($images as $image_url) {
                echo '<link rel="preload" as="image" href="' . esc_url($image_url) . '">';
            }
        }
    }, 5);
}

/**
 * Función para validar configuración
 */
function doguify_validate_config($config = null) {
    if ($config === null) {
        $config = doguify_get_config();
    }
    
    $errors = array();
    
    // Validar URLs de imágenes
    if (isset($config['images']) && is_array($config['images'])) {
        foreach ($config['images'] as $key => $url) {
            if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "URL de imagen inválida para '{$key}': {$url}";
            }
        }
    }
    
    // Validar colores (formato hex o rgba)
    if (isset($config['colors']) && is_array($config['colors'])) {
        foreach ($config['colors'] as $key => $color) {
            if (!empty($color)) {
                $is_hex = preg_match('/^#[a-fA-F0-9]{6}$/', $color);
                $is_rgba = strpos($color, 'rgba(') === 0;
                
                if (!$is_hex && !$is_rgba) {
                    $errors[] = "Color inválido para '{$key}': {$color}";
                }
            }
        }
    }
    
    // Validar breakpoints
    if (isset($config['breakpoints']) && is_array($config['breakpoints'])) {
        foreach ($config['breakpoints'] as $key => $value) {
            if (!is_numeric($value) || $value < 0) {
                $errors[] = "Breakpoint inválido para '{$key}': {$value}";
            }
        }
    }
    
    // Validar emails
    if (isset($config['admin_email']) && !empty($config['admin_email'])) {
        if (!is_email($config['admin_email'])) {
            $errors[] = "Email de administrador inválido: {$config['admin_email']}";
        }
    }
    
    // Validar URLs de webhook
    if (isset($config['webhook_url']) && !empty($config['webhook_url'])) {
        if (!filter_var($config['webhook_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "URL de webhook inválida: {$config['webhook_url']}";
        }
    }
    
    // Validar timeouts y duraciones
    $numeric_fields = array(
        'petplan_timeout' => array('min' => 5, 'max' => 120),
        'cache_duration' => array('min' => 1, 'max' => 1440),
        'data_retention_days' => array('min' => 30, 'max' => 3650),
        'max_attempts_per_hour' => array('min' => 1, 'max' => 100),
        'rate_limit_window' => array('min' => 60, 'max' => 3600)
    );
    
    foreach ($numeric_fields as $field => $limits) {
        if (isset($config[$field])) {
            $value = intval($config[$field]);
            if ($value < $limits['min'] || $value > $limits['max']) {
                $errors[] = "Valor inválido para '{$field}': debe estar entre {$limits['min']} y {$limits['max']}";
            }
        }
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Función para resetear configuración a valores por defecto
 */
function doguify_reset_config() {
    delete_option('doguify_config');
    
    // Log del reseteo
    if (function_exists('doguify_log')) {
        doguify_log('info', 'Configuración reseteada a valores por defecto');
    }
    
    return true;
}

/**
 * Función para exportar configuración
 */
function doguify_export_config() {
    $config = get_option('doguify_config', array());
    $export_data = array(
        'version' => DOGUIFY_PLUGIN_VERSION,
        'export_date' => current_time('c'),
        'config' => $config
    );
    
    return json_encode($export_data, JSON_PRETTY_PRINT);
}

/**
 * Función para importar configuración
 */
function doguify_import_config($json_data) {
    $import_data = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array('success' => false, 'message' => 'JSON inválido');
    }
    
    if (!isset($import_data['config'])) {
        return array('success' => false, 'message' => 'Formato de importación inválido');
    }
    
    // Validar configuración importada
    $validation = doguify_validate_config($import_data['config']);
    if ($validation !== true) {
        return array('success' => false, 'message' => 'Configuración inválida: ' . implode(', ', $validation));
    }
    
    // Guardar configuración
    $result = update_option('doguify_config', $import_data['config']);
    
    if ($result) {
        if (function_exists('doguify_log')) {
            doguify_log('info', 'Configuración importada desde archivo');
        }
        return array('success' => true, 'message' => 'Configuración importada correctamente');
    } else {
        return array('success' => false, 'message' => 'Error al guardar la configuración');
    }
}

/**
 * Función para obtener configuración para un idioma específico
 */
function doguify_get_localized_config($lang = null) {
    if ($lang === null) {
        $lang = get_locale();
    }
    
    $config = doguify_get_config();
    
    // Configuración específica por idioma
    $localized_texts = array(
        'es_ES' => array(
            'loading_texts' => array(
                'trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar',
                'analizando las mejores opciones<br>para tu mascota',
                'comparando precios y coberturas<br>en tiempo real',
                'finalizando tu comparativa<br>personalizada'
            ),
            'waiting_message' => 'Por favor no cierres esta página',
            'main_title' => 'Estamos obteniendo<br>tus resultados!'
        ),
        'en_US' => array(
            'loading_texts' => array(
                'working with the best providers<br>so you can compare plans<br>and prices in one place',
                'analyzing the best options<br>for your pet',
                'comparing prices and coverage<br>in real time',
                'finalizing your personalized<br>comparison'
            ),
            'waiting_message' => 'Please do not close this page',
            'main_title' => 'We are getting<br>your results!'
        )
    );
    
    if (isset($localized_texts[$lang])) {
        $config = array_merge($config, $localized_texts[$lang]);
    }
    
    return $config;
}

// Hook para validar configuración al guardar
add_filter('pre_update_option_doguify_config', function($new_value, $old_value) {
    $validation = doguify_validate_config($new_value);
    
    if ($validation !== true) {
        // Log errores de validación
        if (function_exists('doguify_log')) {
            doguify_log('error', 'Errores de validación en configuración: ' . implode(', ', $validation));
        }
        
        // Mantener valor anterior si hay errores críticos
        $critical_errors = array_filter($validation, function($error) {
            return strpos($error, 'inválido') !== false;
        });
        
        if (!empty($critical_errors)) {
            add_action('admin_notices', function() use ($validation) {
                echo '<div class="notice notice-error"><p><strong>Error en configuración:</strong> ' . implode('<br>', $validation) . '</p></div>';
            });
            return $old_value;
        }
    }
    
    return $new_value;
}, 10, 2);

// Registrar hooks de configuración al inicializar
add_action('init', 'doguify_register_config_hooks');

// Hook para limpiar configuración al desinstalar
register_uninstall_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', function() {
    delete_option('doguify_config');
});

/**
 * Función de utilidad para obtener textos localizados
 */
function doguify_get_text($key, $default = '', $lang = null) {
    $config = doguify_get_localized_config($lang);
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Función para registrar configuración personalizada desde otros plugins/temas
 */
function doguify_register_custom_config($key, $value) {
    $custom_config = get_option('doguify_custom_config', array());
    $custom_config[$key] = $value;
    return update_option('doguify_custom_config', $custom_config);
}

/**
 * Función para obtener configuración personalizada
 */
function doguify_get_custom_config($key, $default = null) {
    $custom_config = get_option('doguify_custom_config', array());
    return isset($custom_config[$key]) ? $custom_config[$key] : $default;
}
?>
