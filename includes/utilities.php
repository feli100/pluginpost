<?php
/**
 * Utilidades y funciones auxiliares para Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyUtilities {
    
    /**
     * Obtener configuración del plugin
     */
    public static function get_config($key = null, $default = null) {
        $config = get_option('doguify_config', array());
        
        if ($key === null) {
            return $config;
        }
        
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Actualizar configuración del plugin
     */
    public static function update_config($key, $value) {
        $config = get_option('doguify_config', array());
        $config[$key] = $value;
        return update_option('doguify_config', $config);
    }
    
    /**
     * Validar código postal español
     */
    public static function validate_postal_code($postal_code) {
        return preg_match('/^[0-5][0-9]{4}$/', $postal_code);
    }
    
    /**
     * Validar fecha de nacimiento
     */
/**
     * Validar fecha de nacimiento mejorada
     */
    public static function validate_birth_date($day, $month, $year) {
        // Verificar que los valores son números válidos
        if (!is_numeric($day) || !is_numeric($month) || !is_numeric($year)) {
            return false;
        }
        
        $day = intval($day);
        $month = intval($month);
        $year = intval($year);
        
        // Verificar rangos básicos
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }
        
        // Verificar rango de años (desde 2018 hasta año actual)
        $current_year = date('Y');
        if ($year < 2018 || $year > $current_year) {
            return false;
        }
        
        // Verificar que la fecha es válida (evita fechas como 30 de febrero)
        if (!checkdate($month, $day, $year)) {
            return false;
        }
        
        // Verificar que está dentro del rango permitido (1 enero 2018 hasta hoy)
        $birth_date = new DateTime("$year-$month-$day");
        $min_date = new DateTime('2018-01-01'); // 1 enero 2018
        $max_date = new DateTime(); // Hoy
        
        // La fecha debe ser mayor o igual al 1 enero 2018
        if ($birth_date < $min_date) {
            return false;
        }
        
        // La fecha no puede ser futura (mayor que hoy)
        if ($birth_date > $max_date) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtener mensaje específico de error para fecha de nacimiento
     */
    public static function get_birth_date_validation_error($day, $month, $year) {
        // Verificar que los valores son números válidos
        if (!is_numeric($day) || !is_numeric($month) || !is_numeric($year)) {
            return 'La fecha de nacimiento debe contener números válidos';
        }
        
        $day = intval($day);
        $month = intval($month);
        $year = intval($year);
        
        // Verificar rangos básicos
        if ($day < 1 || $day > 31) {
            return 'El día debe estar entre 1 y 31';
        }
        
        if ($month < 1 || $month > 12) {
            return 'El mes debe estar entre 1 y 12';
        }
        
        // Verificar rango de años
        $current_year = date('Y');
        if ($year < 2018) {
            return 'El año debe ser 2018 o posterior';
        }
        
        if ($year > $current_year) {
            return "El año no puede ser mayor a $current_year";
        }
        
        // Verificar que la fecha es válida
        if (!checkdate($month, $day, $year)) {
            return 'La fecha introducida no es válida (ej: 30 de febrero no existe)';
        }
        
        // Verificar rango de fechas
        $birth_date = new DateTime("$year-$month-$day");
        $min_date = new DateTime('2018-01-01');
        $max_date = new DateTime();
        
        if ($birth_date < $min_date) {
            return 'La fecha debe ser posterior al 1 de enero de 2018';
        }
        
        if ($birth_date > $max_date) {
            return 'La fecha no puede ser posterior a hoy';
        }
        
        return ''; // Sin errores
    }
    
    /**
     * Calcular edad exacta en años, meses y días
     */
    public static function calculate_age($day, $month, $year) {
        $birth_date = new DateTime("$year-$month-$day");
        $today = new DateTime();
        $age = $today->diff($birth_date);
        
        return array(
            'years' => $age->y,
            'months' => $age->m,
            'days' => $age->d,
            'total_days' => $birth_date->diff($today)->days
        );
    }
    
    /**
     * Formatear edad para mostrar
     */
    public static function format_age($day, $month, $year) {
        $age = self::calculate_age($day, $month, $year);
        
        $parts = array();
        
        if ($age['years'] > 0) {
            $parts[] = $age['years'] . ' año' . ($age['years'] > 1 ? 's' : '');
        }
        
        if ($age['months'] > 0) {
            $parts[] = $age['months'] . ' mes' . ($age['months'] > 1 ? 'es' : '');
        }
        
        if (empty($parts) && $age['days'] > 0) {
            $parts[] = $age['days'] . ' día' . ($age['days'] > 1 ? 's' : '');
        }
        
        return implode(', ', $parts);
    }
	
	    public static function format_date_for_petplan($day, $month, $year) {
        // Validar que la fecha sea válida antes de formatear
        if (!self::validate_birth_date($day, $month, $year)) {
            return false;
        }
        
        // Formato americano: MM/DD/YYYY
        return sprintf('%02d/%02d/%d', intval($month), intval($day), intval($year));
    }
    
    /**
     * Generar clave de cache para Petplan mejorada
     */
    public static function generate_petplan_cache_key($postal_code, $day, $month, $year, $breed) {
        $formatted_date = self::format_date_for_petplan($day, $month, $year);
        return md5($postal_code . '_' . $formatted_date . '_' . $breed);
    }
    
    /**
     * Construir URL de Petplan
     */
    public static function build_petplan_url($postal_code, $day, $month, $year, $breed) {
        $formatted_date = self::format_date_for_petplan($day, $month, $year);
        
        if (!$formatted_date) {
            return false;
        }
        
        $params = array(
            'postalcode' => $postal_code,
            'age' => $formatted_date,
            'column' => 2,
            'breed' => $breed
        );
        
        return 'https://ws.petplan.es/pricing?' . http_build_query($params);
    }
    
    /**
     * Obtener IP real del usuario
     */
    public static function get_real_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy/Load Balancer
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Si hay múltiples IPs separadas por coma, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                $ip = trim($ip);
                
                // Validar que es una IP válida y no privada
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback a REMOTE_ADDR aunque sea privada
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Generar session ID único
     */
    public static function generate_session_id() {
        return uniqid('dog_', true) . '_' . wp_generate_password(8, false);
    }
    
    /**
     * Limpiar datos de entrada
     */
    public static function sanitize_form_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                case 'codigo_postal':
                    $sanitized[$key] = preg_replace('/[^0-9]/', '', $value);
                    break;
                case 'edad_dia':
                case 'edad_mes':
                case 'edad_año':
                    $sanitized[$key] = intval($value);
                    break;
                case 'precio_petplan':
                    $sanitized[$key] = floatval($value);
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Formatear precio
     */
    public static function format_price($price, $include_monthly = false) {
        if (!$price || $price <= 0) {
            return 'Consultar';
        }
        
        $formatted = number_format($price, 2) . '€';
        
        if ($include_monthly) {
            $monthly = $price / 12;
            $formatted .= ' (' . number_format($monthly, 2) . '€/mes)';
        }
        
        return $formatted;
    }
    
    /**
     * Validar raza
     */
    public static function validate_breed($breed) {
        $available_breeds = explode("\n", self::get_config('available_breeds', ''));
        $available_breeds = array_map('trim', $available_breeds);
        
        return in_array($breed, $available_breeds);
    }
    
    /**
     * Obtener lista de razas disponibles
     */
    public static function get_available_breeds() {
        $breeds_string = self::get_config('available_breeds', 'beagle\nlabrador\ngolden_retriever\npastor_aleman\nbulldog_frances\nchihuahua\nyorkshire\nboxer\ncocker_spaniel\nmestizo\notro');
        $breeds = explode("\n", $breeds_string);
        
        $formatted_breeds = array();
        foreach ($breeds as $breed) {
            $breed = trim($breed);
            if (!empty($breed)) {
                $formatted_breeds[$breed] = ucfirst(str_replace('_', ' ', $breed));
            }
        }
        
        return $formatted_breeds;
    }
    
    /**
     * Verificar si el modo debug está activo
     */
    public static function is_debug_mode() {
        return self::get_config('debug_mode', false);
    }
    
    /**
     * Log de debug
     */
    public static function debug_log($message, $data = null) {
        if (!self::is_debug_mode()) {
            return;
        }
        
        $log_message = '[DOGUIFY DEBUG] ' . $message;
        
        if ($data !== null) {
            $log_message .= ' | Data: ' . json_encode($data);
        }
        
        error_log($log_message);
    }
    
    /**
     * Verificar límites de rate limiting
     */
    public static function check_rate_limit($ip, $action = 'form_submission', $limit = 5, $period = 3600) {
        $transient_key = "doguify_rate_limit_{$action}_{$ip}";
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            set_transient($transient_key, 1, $period);
            return true;
        }
        
        if ($attempts >= $limit) {
            return false;
        }
        
        set_transient($transient_key, $attempts + 1, $period);
        return true;
    }
    
    /**
     * Enviar email de notificación
     */
    public static function send_notification_email($to, $subject, $message, $headers = array()) {
        if (!self::get_config('email_notifications', true)) {
            return false;
        }
        
        // Headers por defecto
        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $headers = array_merge($default_headers, $headers);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Obtener template de email
     */
    public static function get_email_template($template_name, $variables = array()) {
        $templates = array(
            'admin_notification' => '
                <h2>Nueva Comparativa Registrada</h2>
                <p>Se ha registrado una nueva comparativa en el sitio web.</p>
                <ul>
                    <li><strong>Mascota:</strong> {{mascota_nombre}} ({{mascota_tipo}})</li>
                    <li><strong>Raza:</strong> {{mascota_raza}}</li>
                    <li><strong>Propietario:</strong> {{propietario_email}}</li>
                    <li><strong>Código Postal:</strong> {{codigo_postal}}</li>
                    <li><strong>Fecha:</strong> {{fecha_registro}}</li>
                </ul>
            ',
            'user_confirmation' => '
                <h2>Confirmación de Comparativa</h2>
                <p>Hola,</p>
                <p>Hemos recibido tu solicitud de comparativa para <strong>{{mascota_nombre}}</strong>.</p>
                <p>Te enviaremos los resultados en breve.</p>
                <p>Saludos,<br>El equipo de {{site_name}}</p>
            '
        );
        
        if (!isset($templates[$template_name])) {
            return '';
        }
        
        $template = $templates[$template_name];
        
        // Reemplazar variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Limpiar datos antiguos según configuración GDPR
     */
    public static function cleanup_old_data() {
        if (!self::get_config('gdpr_compliance', true)) {
            return;
        }
        
        $retention_days = self::get_config('data_retention_days', 730);
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla WHERE fecha_registro < %s",
            date('Y-m-d H:i:s', strtotime("-{$retention_days} days"))
        ));
        
        if ($deleted > 0) {
            self::debug_log("Limpieza automática: $deleted registros eliminados");
        }
        
        return $deleted;
    }
    
    /**
     * Exportar datos de usuario para GDPR
     */
    public static function export_user_data($email) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $registros = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE email = %s ORDER BY fecha_registro DESC",
            $email
        ));
        
        return $registros;
    }
    
    /**
     * Eliminar datos de usuario para GDPR
     */
    public static function delete_user_data($email) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        return $wpdb->delete($tabla, array('email' => $email), array('%s'));
    }
    
    /**
     * Verificar si Petplan está habilitado
     */
    public static function is_petplan_enabled() {
        return self::get_config('petplan_enabled', true);
    }
    
    /**
     * Obtener timeout para Petplan
     */
    public static function get_petplan_timeout() {
        return self::get_config('petplan_timeout', 30);
    }
    
    /**
     * Cache para consultas Petplan
     */
    public static function get_petplan_cache($key) {
        $cache_duration = self::get_config('cache_duration', 60) * MINUTE_IN_SECONDS;
        return get_transient("doguify_petplan_cache_{$key}");
    }
    
    public static function set_petplan_cache($key, $data) {
        $cache_duration = self::get_config('cache_duration', 60) * MINUTE_IN_SECONDS;
        return set_transient("doguify_petplan_cache_{$key}", $data, $cache_duration);
    }
    
    /**
     * Verificar salud del sistema
     */
    public static function system_health_check() {
        $checks = array();
        
        // Verificar base de datos
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;
        $checks['database'] = $table_exists;
        
        // Verificar configuración
        $config = get_option('doguify_config');
        $checks['config'] = !empty($config);
        
        // Verificar conexión a Petplan
        if (self::is_petplan_enabled()) {
            $response = wp_remote_get('https://ws.petplan.es/pricing?postalcode=28001&age=01/01/2020&column=2&breed=beagle', array(
                'timeout' => 10
            ));
            $checks['petplan'] = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        } else {
            $checks['petplan'] = null; // Deshabilitado
        }
        
        // Verificar permisos de archivos
        $checks['file_permissions'] = is_writable(WP_CONTENT_DIR);
        
        // Verificar eventos programados
        $checks['cron'] = wp_next_scheduled('doguify_daily_cleanup') !== false;
        
        return $checks;
    }
    
    /**
     * Obtener estadísticas rápidas
     */
    public static function get_quick_stats() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla"),
            'today' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_registro) = CURDATE()"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'completado'")
        );
    }
}

// Funciones auxiliares globales
if (!function_exists('doguify_config')) {
    function doguify_config($key = null, $default = null) {
        return DoguifyUtilities::get_config($key, $default);
    }
}

if (!function_exists('doguify_debug')) {
    function doguify_debug($message, $data = null) {
        DoguifyUtilities::debug_log($message, $data);
    }
}

if (!function_exists('doguify_format_price')) {
    function doguify_format_price($price, $include_monthly = false) {
        return DoguifyUtilities::format_price($price, $include_monthly);
    }
}

// Hooks para tareas programadas
add_action('doguify_daily_cleanup', array('DoguifyUtilities', 'cleanup_old_data'));

// Hook para exportación de datos GDPR
add_filter('wp_privacy_personal_data_exporters', function($exporters) {
    $exporters['doguify-comparador'] = array(
        'exporter_friendly_name' => 'Doguify Comparador',
        'callback' => function($email_address) {
            $data = DoguifyUtilities::export_user_data($email_address);
            
            $export_items = array();
            foreach ($data as $item) {
                $export_items[] = array(
                    'group_id' => 'doguify-comparativas',
                    'group_label' => 'Comparativas de Seguros',
                    'item_id' => 'comparativa-' . $item->id,
                    'data' => array(
                        array('name' => 'Mascota', 'value' => $item->nombre),
                        array('name' => 'Tipo', 'value' => $item->tipo_mascota),
                        array('name' => 'Raza', 'value' => $item->raza),
                        array('name' => 'Fecha', 'value' => $item->fecha_registro),
                    )
                );
            }
            
            return array(
                'data' => $export_items,
                'done' => true
            );
        }
    );
    
    return $exporters;
});

// Hook para eliminación de datos GDPR
add_filter('wp_privacy_personal_data_erasers', function($erasers) {
    $erasers['doguify-comparador'] = array(
        'eraser_friendly_name' => 'Doguify Comparador',
        'callback' => function($email_address) {
            $deleted = DoguifyUtilities::delete_user_data($email_address);
            
            return array(
                'items_removed' => $deleted,
                'items_retained' => false,
                'messages' => array(),
                'done' => true
            );
        }
    );
    
    return $erasers;
});