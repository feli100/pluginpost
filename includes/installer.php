<?php
/**
 * Instalador del plugin Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyInstaller {
    
    public static function install() {
        // Verificar requisitos antes de instalar
        $errors = self::check_requirements();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                self::display_admin_notice($error, 'error');
            }
            return false;
        }
        
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        self::create_capabilities();
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Marcar como instalado
        update_option('doguify_plugin_version', DOGUIFY_PLUGIN_VERSION);
        update_option('doguify_db_version', '1.0.0');
        update_option('doguify_installation_date', current_time('mysql'));
        
        // Log de instalación exitosa
        if (function_exists('doguify_log')) {
            doguify_log('info', 'Plugin instalado correctamente - Versión: ' . DOGUIFY_PLUGIN_VERSION);
        }
        
        return true;
    }
    
    public static function uninstall() {
        // Solo ejecutar en desinstalación real
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        self::remove_tables();
        self::remove_pages();
        self::remove_options();
        self::remove_capabilities();
        self::unschedule_events();
        self::cleanup_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log de desinstalación
        if (function_exists('doguify_log')) {
            doguify_log('info', 'Plugin desinstalado completamente');
        }
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $tabla_comparativas = $wpdb->prefix . 'doguify_comparativas';
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla principal de comparativas
        $sql_comparativas = "CREATE TABLE $tabla_comparativas (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            tipo_mascota varchar(20) NOT NULL,
            nombre varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            codigo_postal varchar(5) NOT NULL,
            edad_dia int NOT NULL,
            edad_mes int NOT NULL,
            edad_año int NOT NULL,
            raza varchar(100) NOT NULL,
            precio_petplan decimal(10,2) DEFAULT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            datos_adicionales text DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            fecha_registro datetime NOT NULL,
            fecha_consulta datetime DEFAULT NULL,
            fecha_actualizacion timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY email (email),
            KEY fecha_registro (fecha_registro),
            KEY estado (estado),
            KEY codigo_postal (codigo_postal),
            KEY tipo_mascota (tipo_mascota),
            KEY raza (raza),
            KEY precio_petplan (precio_petplan)
        ) $charset_collate;";
        
        // Tabla de logs
        $sql_logs = "CREATE TABLE $tabla_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) DEFAULT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context text DEFAULT NULL,
            fecha datetime NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY level (level),
            KEY fecha (fecha)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $result1 = dbDelta($sql_comparativas);
        $result2 = dbDelta($sql_logs);
        
        // Crear índices adicionales si es necesario
        self::create_indexes();
        
        // Verificar que las tablas se crearon correctamente
        $tables_created = array();
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_comparativas'") == $tabla_comparativas) {
            $tables_created[] = 'doguify_comparativas';
        }
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_logs'") == $tabla_logs) {
            $tables_created[] = 'doguify_logs';
        }
        
        // Log de creación de tablas
        if (function_exists('doguify_log')) {
            doguify_log('info', 'Tablas creadas: ' . implode(', ', $tables_created));
        }
        
        return $tables_created;
    }
    
    public static function create_indexes() {
        global $wpdb;
        
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        // Verificar si los índices ya existen antes de crearlos
        $indexes = array(
            'idx_estado_fecha' => "CREATE INDEX idx_estado_fecha ON $tabla (estado, fecha_registro)",
            'idx_tipo_codigo' => "CREATE INDEX idx_tipo_codigo ON $tabla (tipo_mascota, codigo_postal)",
            'idx_email_fecha' => "CREATE INDEX idx_email_fecha ON $tabla (email, fecha_registro)"
        );
        
        foreach ($indexes as $index_name => $sql) {
            // Verificar si el índice existe
            $index_exists = $wpdb->get_var("SHOW INDEX FROM $tabla WHERE Key_name = '$index_name'");
            
            if (!$index_exists) {
                $wpdb->query($sql);
            }
        }
    }
    
    public static function create_pages() {
        // Crear página de ejemplo como borrador
        $page_content = '[doguify_formulario titulo="Compara seguros para tu mascota"]';
        
        $page_data = array(
            'post_title'    => 'Comparador de Seguros para Mascotas',
            'post_content'  => $page_content,
            'post_status'   => 'draft',
            'post_type'     => 'page',
            'post_name'     => 'comparador-seguros-mascotas',
            'post_excerpt'  => 'Página de ejemplo del comparador de seguros Doguify'
        );
        
        // Verificar si ya existe una página similar
        $existing_page = get_page_by_path('comparador-seguros-mascotas');
        
        if (!$existing_page) {
            $page_id = wp_insert_post($page_data);
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('doguify_example_page_id', $page_id);
                
                // Agregar meta información
                update_post_meta($page_id, '_doguify_example_page', true);
                
                if (function_exists('doguify_log')) {
                    doguify_log('info', "Página de ejemplo creada con ID: $page_id");
                }
            }
        }
    }
    
    public static function set_default_options() {
        $default_config = array(
            // Configuración de Petplan
            'petplan_enabled' => true,
            'petplan_timeout' => 30,
            'cache_duration' => 60,
            
            // Notificaciones
            'email_notifications' => true,
            'admin_email' => get_option('admin_email'),
            'user_confirmation_email' => false,
            
            // Personalización de páginas
            'results_page_title' => '¡Tu comparativa está lista!',
            'results_page_subtitle' => 'Hemos encontrado la mejor opción para tu mascota',
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
            
            // Configuración de la página de espera (integrada desde config.php)
            'loading_texts' => array(
                'trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar',
                'analizando las mejores opciones<br>para tu mascota',
                'comparando precios y coberturas<br>en tiempo real',
                'finalizando tu comparativa<br>personalizada'
            ),
            'progress' => array(
                'initial_delay' => 500,
                'text_change_interval' => 4000,
                'phases' => array(
                    array('end' => 30, 'speed_min' => 1, 'speed_max' => 4),
                    array('end' => 70, 'speed_min' => 0.5, 'speed_max' => 2.5),
                    array('end' => 90, 'speed_min' => 0.2, 'speed_max' => 1.2),
                    array('end' => 100, 'speed_min' => 0.1, 'speed_max' => 0.6)
                )
            ),
            'colors' => array(
                'primary_gradient_start' => '#4A90E2',
                'primary_gradient_middle' => '#357ABD',
                'primary_gradient_end' => '#2E6DA4',
                'wave_color' => '#ECF3FD',
                'text_color' => '#FFFFFF'
            ),
            'images' => array(
                'logo' => 'https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-scaled-e1750429316951.png',
                'pets_left' => 'https://doguify.com/wp-content/uploads/2025/07/perro-gato-1-e1751989375681.png',
                'pets_right' => 'https://doguify.com/wp-content/uploads/2025/07/perros-web-1-e1751989411921.png'
            )
        );
        
        // Solo añadir configuración por defecto si no existe
        if (!get_option('doguify_config')) {
            add_option('doguify_config', $default_config);
        }
        
        // Otras opciones
        add_option('doguify_stats_cache', array());
        add_option('doguify_last_cleanup', current_time('mysql'));
        add_option('doguify_installation_stats', array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => $GLOBALS['wpdb']->db_version()
        ));
    }
    
    public static function create_capabilities() {
        // Añadir capacidades específicas del plugin
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_doguify_comparator');
            $admin_role->add_cap('view_doguify_stats');
            $admin_role->add_cap('export_doguify_data');
            $admin_role->add_cap('configure_doguify');
        }
        
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('view_doguify_stats');
        }
    }
    
    public static function schedule_events() {
        // Limpiar eventos existentes primero
        wp_clear_scheduled_hook('doguify_daily_cleanup');
        wp_clear_scheduled_hook('doguify_weekly_report');
        wp_clear_scheduled_hook('doguify_monthly_backup');
        
        // Programar limpieza automática diaria
        if (!wp_next_scheduled('doguify_daily_cleanup')) {
            wp_schedule_event(time() + 3600, 'daily', 'doguify_daily_cleanup'); // Empezar en 1 hora
        }
        
        // Programar envío de reportes semanales (opcional)
        if (!wp_next_scheduled('doguify_weekly_report')) {
            wp_schedule_event(time() + 86400, 'weekly', 'doguify_weekly_report'); // Empezar en 24 horas
        }
        
        // Programar backup de datos mensual (opcional)
        if (!wp_next_scheduled('doguify_monthly_backup')) {
            wp_schedule_event(time() + 604800, 'monthly', 'doguify_monthly_backup'); // Empezar en 1 semana
        }
    }
    
    public static function remove_tables() {
        global $wpdb;
        
        $tabla_comparativas = $wpdb->prefix . 'doguify_comparativas';
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        // Crear backup antes de eliminar (opcional)
        $backup_data = array(
            'comparativas' => $wpdb->get_results("SELECT * FROM $tabla_comparativas", ARRAY_A),
            'logs' => $wpdb->get_results("SELECT * FROM $tabla_logs LIMIT 1000", ARRAY_A),
            'backup_date' => current_time('c')
        );
        
        // Guardar backup en un archivo temporal
        $backup_file = WP_CONTENT_DIR . '/doguify_backup_' . date('Y-m-d_H-i-s') . '.json';
        if (is_writable(WP_CONTENT_DIR)) {
            file_put_contents($backup_file, json_encode($backup_data));
        }
        
        // Eliminar tablas
        $wpdb->query("DROP TABLE IF EXISTS $tabla_comparativas");
        $wpdb->query("DROP TABLE IF EXISTS $tabla_logs");
    }
    
    public static function remove_pages() {
        $page_id = get_option('doguify_example_page_id');
        if ($page_id) {
            // Verificar que es realmente nuestra página
            $page = get_post($page_id);
            if ($page && get_post_meta($page_id, '_doguify_example_page', true)) {
                wp_delete_post($page_id, true);
            }
            delete_option('doguify_example_page_id');
        }
    }
    
    public static function remove_options() {
        $options_to_remove = array(
            'doguify_config',
            'doguify_stats_cache',
            'doguify_last_cleanup',
            'doguify_plugin_version',
            'doguify_db_version',
            'doguify_installation_date',
            'doguify_installation_stats',
            'doguify_rewrite_version'
        );
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
    }
    
    public static function remove_capabilities() {
        $roles = array('administrator', 'editor');
        $capabilities = array(
            'manage_doguify_comparator',
            'view_doguify_stats',
            'export_doguify_data',
            'configure_doguify'
        );
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    public static function cleanup_transients() {
        global $wpdb;
        
        // Limpiar todos los transients relacionados con Doguify
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_doguify_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_timeout_doguify_%'");
    }
    
    public static function unschedule_events() {
        wp_clear_scheduled_hook('doguify_daily_cleanup');
        wp_clear_scheduled_hook('doguify_weekly_report');
        wp_clear_scheduled_hook('doguify_monthly_backup');
    }
    
    public static function upgrade() {
        $current_version = get_option('doguify_plugin_version', '0.0.0');
        
        if (version_compare($current_version, DOGUIFY_PLUGIN_VERSION, '<')) {
            // Ejecutar migraciones según la versión
            self::run_migrations($current_version);
            
            // Actualizar versión
            update_option('doguify_plugin_version', DOGUIFY_PLUGIN_VERSION);
            
            // Log de actualización
            if (function_exists('doguify_log')) {
                doguify_log('info', "Plugin actualizado de v{$current_version} a v" . DOGUIFY_PLUGIN_VERSION);
            }
        }
    }
    
    private static function run_migrations($from_version) {
        // Migraciones específicas por versión
        if (version_compare($from_version, '1.0.1', '<')) {
            self::migrate_to_1_0_1();
        }
        
        if (version_compare($from_version, '1.1.0', '<')) {
            self::migrate_to_1_1_0();
        }
    }
    
    private static function migrate_to_1_0_1() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        // Añadir columna de datos adicionales si no existe
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'datos_adicionales'",
            DB_NAME, $tabla
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $tabla ADD COLUMN datos_adicionales text DEFAULT NULL AFTER estado");
            
            if (function_exists('doguify_log')) {
                doguify_log('info', 'Migración 1.0.1: Columna datos_adicionales añadida');
            }
        }
    }
    
    private static function migrate_to_1_1_0() {
        // Crear tabla de logs si no existe
        global $wpdb;
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$tabla_logs'");
        
        if ($table_exists != $tabla_logs) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $tabla_logs (
                id int(11) NOT NULL AUTO_INCREMENT,
                session_id varchar(50) DEFAULT NULL,
                level varchar(20) NOT NULL,
                message text NOT NULL,
                context text DEFAULT NULL,
                fecha datetime NOT NULL,
                ip_address varchar(45) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY level (level),
                KEY fecha (fecha)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            if (function_exists('doguify_log')) {
                doguify_log('info', 'Migración 1.1.0: Tabla de logs creada');
            }
        }
    }
    
    public static function check_requirements() {
        $errors = array();
        
        // Verificar versión de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = 'Se requiere PHP 7.4 o superior. Versión actual: ' . PHP_VERSION;
        }
        
        // Verificar versión de WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = 'Se requiere WordPress 5.0 o superior. Versión actual: ' . get_bloginfo('version');
        }
        
        // Verificar extensiones de PHP
        if (!extension_loaded('curl')) {
            $errors[] = 'Se requiere la extensión cURL de PHP';
        }
        
        if (!extension_loaded('json')) {
            $errors[] = 'Se requiere la extensión JSON de PHP';
        }
        
        // Verificar permisos de escritura
        if (!is_writable(WP_CONTENT_DIR)) {
            $errors[] = 'No se tienen permisos de escritura en el directorio wp-content';
        }
        
        // Verificar límites de memoria
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && $memory_limit != -1) {
            $memory_in_bytes = wp_convert_hr_to_bytes($memory_limit);
            if ($memory_in_bytes < 134217728) { // 128MB
                $errors[] = 'Se recomienda al menos 128MB de memoria PHP. Actual: ' . $memory_limit;
            }
        }
        
        return $errors;
    }
    
    public static function display_admin_notice($message, $type = 'error') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible">';
            echo '<p><strong>Doguify Comparador:</strong> ' . esc_html($message) . '</p>';
            echo '</div>';
        });
    }
    
    /**
     * Verificar que las tablas existen y están correctas
     */
    public static function verify_installation() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'doguify_comparativas',
            $wpdb->prefix . 'doguify_logs'
        );
        
        $missing_tables = array();
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            // Intentar recrear las tablas
            self::create_tables();
            
            if (function_exists('doguify_log')) {
                doguify_log('warning', 'Tablas faltantes recreadas: ' . implode(', ', $missing_tables));
            }
        }
        
        return empty($missing_tables);
    }
    
    /**
     * Obtener información de diagnóstico
     */
    public static function get_diagnostic_info() {
        global $wpdb;
        
        $info = array(
            'plugin_version' => get_option('doguify_plugin_version', 'No instalado'),
            'db_version' => get_option('doguify_db_version', 'No disponible'),
            'installation_date' => get_option('doguify_installation_date', 'No disponible'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'tables_status' => array()
        );
        
        // Verificar estado de las tablas
        $tables = array(
            'doguify_comparativas',
            'doguify_logs'
        );
        
        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") == $full_table;
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
                $info['tables_status'][$table] = array(
                    'exists' => true,
                    'records' => (int) $count
                );
            } else {
                $info['tables_status'][$table] = array(
                    'exists' => false,
                    'records' => 0
                );
            }
        }
        
        return $info;
    }
}

// Funciones de compatibilidad para uso directo
function doguify_create_tables() {
    return DoguifyInstaller::create_tables();
}

function doguify_verify_tables() {
    global $wpdb;
    
    $tables = array(
        'doguify_comparativas' => $wpdb->prefix . 'doguify_comparativas',
        'doguify_logs' => $wpdb->prefix . 'doguify_logs'
    );
    
    $existing = array();
    $missing = array();
    
    foreach ($tables as $name => $full_name) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$full_name'") == $full_name) {
            $existing[] = $name;
        } else {
            $missing[] = $name;
        }
    }
    
    return array(
        'existing' => $existing,
        'missing' => $missing,
        'all_present' => empty($missing)
    );
}

// Hooks de activación y desactivación
register_activation_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'install'));
register_deactivation_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'unschedule_events'));

// Hook de desinstalación
register_uninstall_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'uninstall'));

// Verificar actualizaciones
add_action('plugins_loaded', array('DoguifyInstaller', 'upgrade'));

// Verificar requisitos al cargar admin
add_action('admin_init', function() {
    if (is_admin() && current_user_can('activate_plugins')) {
        $errors = DoguifyInstaller::check_requirements();
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                DoguifyInstaller::display_admin_notice($error, 'error');
            }
            
            // Solo desactivar si hay errores críticos de PHP o WordPress
            $critical_errors = array_filter($errors, function($error) {
                return strpos($error, 'PHP') !== false || strpos($error, 'WordPress') !== false;
            });
            
            if (!empty($critical_errors)) {
                deactivate_plugins(plugin_basename(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php'));
            }
        }
    }
});

// Verificar instalación en cada carga de admin
add_action('admin_init', function() {
    if (is_admin() && get_option('doguify_plugin_version')) {
        DoguifyInstaller::verify_installation();
    }
});
