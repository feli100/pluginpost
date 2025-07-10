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
        self::create_tables();
        self::create_pages();
        self::set_default_options();
        self::create_capabilities();
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Marcar como instalado
        update_option('doguify_plugin_version', DOGUIFY_PLUGIN_VERSION);
        update_option('doguify_installation_date', current_time('mysql'));
    }
    
    public static function uninstall() {
        self::remove_tables();
        self::remove_pages();
        self::remove_options();
        self::unschedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
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
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY level (level),
            KEY fecha (fecha)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_comparativas);
        dbDelta($sql_logs);
        
        // Crear índices adicionales si es necesario
        self::create_indexes();
    }
    
    public static function create_indexes() {
        global $wpdb;
        
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        // Índices compuestos para consultas frecuentes
        $wpdb->query("CREATE INDEX idx_estado_fecha ON $tabla (estado, fecha_registro)");
        $wpdb->query("CREATE INDEX idx_tipo_codigo ON $tabla (tipo_mascota, codigo_postal)");
        $wpdb->query("CREATE INDEX idx_email_fecha ON $tabla (email, fecha_registro)");
    }
    
    public static function create_pages() {
        // No necesitamos crear páginas ya que usamos rewrite rules personalizadas
        // Pero podemos crear una página de ejemplo si es necesario
        
        $page_content = '[doguify_formulario titulo="Compara seguros para tu mascota"]';
        
        $page_data = array(
            'post_title'    => 'Comparador de Seguros para Mascotas',
            'post_content'  => $page_content,
            'post_status'   => 'draft', // Crear como borrador
            'post_type'     => 'page',
            'post_name'     => 'comparador-seguros-mascotas'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            update_option('doguify_example_page_id', $page_id);
        }
    }
    
    public static function set_default_options() {
        $default_config = array(
            'petplan_enabled' => true,
            'petplan_timeout' => 30,
            'email_notifications' => true,
            'admin_email' => get_option('admin_email'),
            'user_confirmation_email' => false,
            'results_page_title' => '¡Tu comparativa está lista!',
            'results_page_subtitle' => 'Hemos encontrado la mejor opción para tu mascota',
            'waiting_page_message' => 'Estamos trabajando con los mejores proveedores para encontrar las mejores opciones para tu mascota',
            'available_breeds' => "beagle\nlabrador\ngolden_retriever\npastor_aleman\nbulldog_frances\nchihuahua\nyorkshire\nboxer\ncocker_spaniel\nmestizo\notro",
            'allow_custom_breed' => true,
            'cache_duration' => 60,
            'debug_mode' => false,
            'gdpr_compliance' => true,
            'data_retention_days' => 730,
            'google_analytics_id' => '',
            'facebook_pixel_id' => '',
            'webhook_url' => ''
        );
        
        add_option('doguify_config', $default_config);
        add_option('doguify_stats_cache', array());
        add_option('doguify_last_cleanup', current_time('mysql'));
    }
    
    public static function create_capabilities() {
        // Añadir capacidades específicas del plugin
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_doguify_comparator');
            $admin_role->add_cap('view_doguify_stats');
            $admin_role->add_cap('export_doguify_data');
        }
        
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('view_doguify_stats');
        }
    }
    
    public static function schedule_events() {
        // Programar limpieza automática
        if (!wp_next_scheduled('doguify_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'doguify_daily_cleanup');
        }
        
        // Programar envío de reportes semanales
        if (!wp_next_scheduled('doguify_weekly_report')) {
            wp_schedule_event(time(), 'weekly', 'doguify_weekly_report');
        }
        
        // Programar backup de datos mensual
        if (!wp_next_scheduled('doguify_monthly_backup')) {
            wp_schedule_event(time(), 'monthly', 'doguify_monthly_backup');
        }
    }
    
    public static function remove_tables() {
        global $wpdb;
        
        $tabla_comparativas = $wpdb->prefix . 'doguify_comparativas';
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $wpdb->query("DROP TABLE IF EXISTS $tabla_comparativas");
        $wpdb->query("DROP TABLE IF EXISTS $tabla_logs");
    }
    
    public static function remove_pages() {
        $page_id = get_option('doguify_example_page_id');
        if ($page_id) {
            wp_delete_post($page_id, true);
            delete_option('doguify_example_page_id');
        }
    }
    
    public static function remove_options() {
        delete_option('doguify_config');
        delete_option('doguify_stats_cache');
        delete_option('doguify_last_cleanup');
        delete_option('doguify_plugin_version');
        delete_option('doguify_installation_date');
        delete_option('doguify_rewrite_version');
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
        }
    }
    
    private static function run_migrations($from_version) {
        // Ejemplo de migraciones
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
                PRIMARY KEY (id),
                KEY session_id (session_id),
                KEY level (level),
                KEY fecha (fecha)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
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
        
        return $errors;
    }
    
    public static function display_admin_notice($message, $type = 'error') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . $type . ' is-dismissible">';
            echo '<p><strong>Doguify Comparador:</strong> ' . $message . '</p>';
            echo '</div>';
        });
    }
}

// Hooks de activación y desactivación
register_activation_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'install'));
register_deactivation_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'unschedule_events'));

// Hook de desinstalación
register_uninstall_hook(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php', array('DoguifyInstaller', 'uninstall'));

// Verificar actualizaciones
add_action('plugins_loaded', array('DoguifyInstaller', 'upgrade'));

// Verificar requisitos al activar
add_action('admin_init', function() {
    if (is_admin() && current_user_can('activate_plugins')) {
        $errors = DoguifyInstaller::check_requirements();
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                DoguifyInstaller::display_admin_notice($error, 'error');
            }
            
            // Desactivar el plugin si hay errores críticos
            deactivate_plugins(plugin_basename(DOGUIFY_PLUGIN_PATH . 'doguify-comparador.php'));
        }
    }
});