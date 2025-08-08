<?php
/**
 * Plugin Name: Doguify Comparador de Seguros
 * Description: Plugin completo para comparar seguros de mascotas con integración a Petplan
 * Version: 1.0.0
 * Author: Doguify
 * Text Domain: doguify-comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('DOGUIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DOGUIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DOGUIFY_PLUGIN_VERSION', '1.0.0');

class DoguifyComparador {
    
    public function __construct() {
        // Cargar dependencias
        $this->load_dependencies();
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_doguify_procesar_comparativa', array($this, 'procesar_comparativa'));
        add_action('wp_ajax_nopriv_doguify_procesar_comparativa', array($this, 'procesar_comparativa'));
        add_action('wp_ajax_doguify_consultar_petplan', array($this, 'consultar_petplan'));
        add_action('wp_ajax_nopriv_doguify_consultar_petplan', array($this, 'consultar_petplan'));
        add_action('wp_ajax_doguify_check_status', array($this, 'check_comparison_status'));
        add_action('wp_ajax_nopriv_doguify_check_status', array($this, 'check_comparison_status'));
        add_action('template_redirect', array($this, 'handle_custom_pages'));
        add_action('send_headers', array($this, 'add_security_headers'));
        add_action('wp_head', array($this, 'add_waiting_page_head'));
        
        // Shortcodes
        add_shortcode('doguify_formulario', array($this, 'mostrar_formulario'));
        
        // Hooks de limpieza
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        
        // Cron jobs
        add_action('doguify_daily_cleanup', array($this, 'cleanup_old_sessions'));
    }
    
    private function load_dependencies() {
        // Cargar archivos del plugin
        require_once DOGUIFY_PLUGIN_PATH . 'includes/utilities.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/logger.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/installer.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/ajax-handlers.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/cron-jobs.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/webhooks.php';
        require_once DOGUIFY_PLUGIN_PATH . 'includes/widgets.php';
        
        // Cargar configuración si existe
        if (file_exists(DOGUIFY_PLUGIN_PATH . 'includes/config.php')) {
            require_once DOGUIFY_PLUGIN_PATH . 'includes/config.php';
        }
        
        // Cargar panel admin si estamos en admin
        if (is_admin()) {
            require_once DOGUIFY_PLUGIN_PATH . 'admin/admin-panel.php';
        }
    }
    
    public function init() {
        // Añadir reglas de rewrite para páginas personalizadas
        add_rewrite_rule('^doguify-espera/?$', 'index.php?doguify_page=espera', 'top');
        add_rewrite_rule('^doguify-resultado/?$', 'index.php?doguify_page=resultado', 'top');
        
        // Añadir query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'doguify_page';
            $vars[] = 'session_id';
            return $vars;
        });
        
        // Flush rewrite rules si es necesario
        if (get_option('doguify_rewrite_version') != DOGUIFY_PLUGIN_VERSION) {
            flush_rewrite_rules();
            update_option('doguify_rewrite_version', DOGUIFY_PLUGIN_VERSION);
        }
        
        // Programar limpieza diaria si no existe
        if (!wp_next_scheduled('doguify_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'doguify_daily_cleanup');
        }
    }
    
    public function enqueue_scripts() {
        // Script principal
        wp_enqueue_script(
            'doguify-comparador',
            DOGUIFY_PLUGIN_URL . 'assets/doguify-comparador.js',
            array('jquery'),
            DOGUIFY_PLUGIN_VERSION,
            true
        );
        
        // Estilos principales
        wp_enqueue_style(
            'doguify-comparador',
            DOGUIFY_PLUGIN_URL . 'assets/doguify-comparador.css',
            array(),
            DOGUIFY_PLUGIN_VERSION
        );
        
        // Localizar variables para JavaScript
        wp_localize_script('doguify-comparador', 'doguify_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('doguify_nonce'),
            'espera_url' => home_url('/doguify-espera/'),
            'resultado_url' => home_url('/doguify-resultado/'),
            'config' => array(
                'loading_texts' => doguify_get_config('loading_texts', array(
                    'trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar',
                    'analizando las mejores opciones<br>para tu mascota',
                    'comparando precios y coberturas<br>en tiempo real',
                    'finalizando tu comparativa<br>personalizada'
                )),
                'progress' => doguify_get_config('progress', array(
                    'initial_delay' => 500,
                    'text_change_interval' => 4000,
                    'phases' => array(
                        array('end' => 30, 'speed_min' => 1, 'speed_max' => 4),
                        array('end' => 70, 'speed_min' => 0.5, 'speed_max' => 2.5),
                        array('end' => 90, 'speed_min' => 0.2, 'speed_max' => 1.2),
                        array('end' => 100, 'speed_min' => 0.1, 'speed_max' => 0.6)
                    )
                )),
                'check_interval' => 5000,
                'max_retries' => 3
            )
        ));
    }
    
    public function mostrar_formulario($atts) {
        $atts = shortcode_atts(array(
            'titulo' => 'Obtén tu comparativa de seguros'
        ), $atts);
        
        ob_start();
        include DOGUIFY_PLUGIN_PATH . 'templates/formulario.php';
        return ob_get_clean();
    }
    
    public function procesar_comparativa() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'doguify_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Error de seguridad')));
        }
        
        // Rate limiting básico
        $ip = $this->obtener_ip_real();
        $attempts = get_transient('doguify_attempts_' . md5($ip));
        if ($attempts && $attempts >= 5) {
            wp_die(json_encode(array('success' => false, 'message' => 'Demasiados intentos. Espera unos minutos.')));
        }
        
        // Validar datos
        $datos = $this->validar_datos_formulario($_POST);
        
        if (!$datos['valido']) {
            // Incrementar intentos fallidos
            set_transient('doguify_attempts_' . md5($ip), ($attempts ?? 0) + 1, 300); // 5 minutos
            wp_die(json_encode(array('success' => false, 'message' => $datos['errores'])));
        }
        
        // Generar session ID único
        $session_id = uniqid('dog_', true);
        
        // Guardar en base de datos
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $resultado = $wpdb->insert(
            $tabla,
            array(
                'session_id' => $session_id,
                'tipo_mascota' => $datos['tipo_mascota'],
                'nombre' => $datos['nombre'],
                'email' => $datos['email'],
                'codigo_postal' => $datos['codigo_postal'],
                'edad_dia' => $datos['edad_dia'],
                'edad_mes' => $datos['edad_mes'],
                'edad_año' => $datos['edad_año'],
                'raza' => $datos['raza'],
                'ip_address' => $ip,
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'fecha_registro' => current_time('mysql'),
                'estado' => 'pendiente'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($resultado === false) {
            // Log del error
            if (function_exists('doguify_log')) {
                doguify_log('error', 'Error al guardar comparativa: ' . $wpdb->last_error);
            }
            wp_die(json_encode(array('success' => false, 'message' => 'Error al guardar datos')));
        }
        
        // Log de éxito
        if (function_exists('doguify_log')) {
            doguify_log('info', "Nueva comparativa creada: {$session_id}");
        }
        
        // Limpiar intentos fallidos
        delete_transient('doguify_attempts_' . md5($ip));
        
        // Trigger webhook si está configurado
        $this->trigger_webhook($session_id, $datos);
        
        wp_die(json_encode(array(
            'success' => true,
            'session_id' => $session_id,
            'redirect_url' => home_url('/doguify-espera/?session_id=' . $session_id)
        )));
    }
    
    public function consultar_petplan() {
        if (!wp_verify_nonce($_POST['nonce'], 'doguify_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Error de seguridad')));
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Obtener datos de la base de datos
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $datos = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE session_id = %s AND estado = 'pendiente'",
            $session_id
        ));
        
        if (!$datos) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sesión no encontrada o ya procesada')));
        }
        
        // Verificar si Petplan está habilitado
        $config = get_option('doguify_config', array());
        if (empty($config['petplan_enabled'])) {
            // Simular precio si Petplan está deshabilitado
            $precio = rand(300, 800) + (rand(0, 99) / 100);
        } else {
            // Consultar Petplan real
            $precio = $this->consultar_precio_petplan($datos);
        }
        
        // Actualizar base de datos
        $update_result = $wpdb->update(
            $tabla,
            array(
                'precio_petplan' => $precio,
                'estado' => 'completado',
                'fecha_consulta' => current_time('mysql')
            ),
            array('session_id' => $session_id),
            array('%f', '%s', '%s'),
            array('%s')
        );
        
        if ($update_result === false) {
            if (function_exists('doguify_log')) {
                doguify_log('error', "Error actualizando comparativa {$session_id}: " . $wpdb->last_error);
            }
            wp_die(json_encode(array('success' => false, 'message' => 'Error al actualizar datos')));
        }
        
        // Log de éxito
        if (function_exists('doguify_log')) {
            doguify_log('info', "Comparativa completada: {$session_id}, Precio: €{$precio}");
        }
        
        // Enviar email de notificación si está habilitado
        $this->send_notification_email($session_id, $datos, $precio);
        
        wp_die(json_encode(array(
            'success' => true,
            'precio' => $precio,
            'datos' => array(
                'nombre' => $datos->nombre,
                'tipo_mascota' => $datos->tipo_mascota,
                'raza' => $datos->raza,
                'email' => $datos->email
            ),
            'redirect_url' => home_url('/doguify-resultado/?session_id=' . $session_id)
        )));
    }
    
    public function check_comparison_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'doguify_nonce')) {
            wp_die('Error de seguridad');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($session_id)) {
            wp_send_json_error('Sesión inválida');
        }
        
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT estado, precio_petplan, nombre, tipo_mascota, raza FROM {$wpdb->prefix}doguify_comparativas WHERE session_id = %s",
            $session_id
        ));
        
        if ($result) {
            $response = array(
                'estado' => $result->estado,
                'completado' => $result->estado === 'completado',
                'precio' => $result->precio_petplan
            );
            
            if ($result->estado === 'completado') {
                $response['redirect_url'] = home_url('/doguify-resultado/?session_id=' . $session_id);
                $response['datos'] = array(
                    'nombre' => $result->nombre,
                    'tipo_mascota' => $result->tipo_mascota,
                    'raza' => $result->raza
                );
            }
            
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Comparativa no encontrada');
        }
    }
    
    public function handle_custom_pages() {
        $doguify_page = get_query_var('doguify_page');
        
        if ($doguify_page == 'espera') {
            // Verificar sesión válida
            $session_id = get_query_var('session_id');
            if (empty($session_id)) {
                wp_redirect(home_url());
                exit;
            }
            
            // Verificar que la sesión existe en la base de datos
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}doguify_comparativas WHERE session_id = %s",
                $session_id
            ));
            
            if (!$exists) {
                wp_redirect(home_url());
                exit;
            }
            
            // Agregar clase al body
            add_filter('body_class', function($classes) {
                $classes[] = 'doguify-waiting-body';
                return $classes;
            });
            
            // Incluir el template
            include DOGUIFY_PLUGIN_PATH . 'templates/pagina-espera.php';
            exit;
            
        } elseif ($doguify_page == 'resultado') {
            // Verificar sesión válida para resultados
            $session_id = get_query_var('session_id');
            if (empty($session_id)) {
                wp_redirect(home_url());
                exit;
            }
            
            // Verificar que la comparativa está completada
            global $wpdb;
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}doguify_comparativas WHERE session_id = %s AND estado = 'completado'",
                $session_id
            ));
            
            if (!$result) {
                // Redirigir a espera si no está completado
                wp_redirect(home_url('/doguify-espera/?session_id=' . $session_id));
                exit;
            }
            
            include DOGUIFY_PLUGIN_PATH . 'templates/pagina-resultado.php';
            exit;
        }
    }
    
    public function add_security_headers() {
        if (get_query_var('doguify_page') === 'espera') {
            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    public function add_waiting_page_head() {
        if (get_query_var('doguify_page') === 'espera') {
            echo '<meta name="robots" content="noindex, nofollow">';
            echo '<meta name="description" content="Procesando comparativa de seguros para mascotas - Doguify">';
            
            // Agregar CSS dinámico si la función existe
            if (function_exists('doguify_generate_dynamic_css')) {
                echo doguify_generate_dynamic_css();
            }
        }
    }
    
    private function consultar_precio_petplan($datos) {
        // Construir URL con formato americano MM/DD/YYYY
        $fecha_nacimiento = sprintf('%02d/%02d/%d', $datos->edad_mes, $datos->edad_dia, $datos->edad_año);
        
        $url_petplan = 'https://ws.petplan.es/pricing?' . http_build_query(array(
            'postalcode' => $datos->codigo_postal,
            'age' => $fecha_nacimiento,
            'column' => 2,
            'breed' => $datos->raza
        ));
        
        // Verificar caché primero
        $cache_key = 'doguify_petplan_' . md5($url_petplan);
        $cached_price = get_transient($cache_key);
        
        if ($cached_price !== false) {
            return floatval($cached_price);
        }
        
        // Obtener timeout de configuración
        $config = get_option('doguify_config', array());
        $timeout = intval($config['petplan_timeout'] ?? 30);
        
        // Realizar consulta
        $response = wp_remote_get($url_petplan, array(
            'timeout' => $timeout,
            'headers' => array(
                'User-Agent' => 'Doguify Comparador/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            if (function_exists('doguify_log')) {
                doguify_log('error', 'Error consultando Petplan: ' . $response->get_error_message());
            }
            // Retornar precio simulado en caso de error
            return rand(300, 800) + (rand(0, 99) / 100);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $precio = isset($data['Precio']) ? floatval($data['Precio']) : (rand(300, 800) + (rand(0, 99) / 100));
        
        // Cachear resultado
        $cache_duration = intval($config['cache_duration'] ?? 60) * 60; // minutos a segundos
        set_transient($cache_key, $precio, $cache_duration);
        
        return $precio;
    }
    
    private function trigger_webhook($session_id, $datos) {
        $config = get_option('doguify_config', array());
        $webhook_url = $config['webhook_url'] ?? '';
        
        if (empty($webhook_url)) {
            return;
        }
        
        $payload = array(
            'session_id' => $session_id,
            'timestamp' => current_time('c'),
            'data' => $datos
        );
        
        wp_remote_post($webhook_url, array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        ));
    }
    
    private function send_notification_email($session_id, $datos, $precio) {
        $config = get_option('doguify_config', array());
        
        if (empty($config['email_notifications'])) {
            return;
        }
        
        $admin_email = $config['admin_email'] ?? get_option('admin_email');
        
        $subject = '🐕 Nueva comparativa completada - Doguify';
        $message = "Se ha completado una nueva comparativa:\n\n";
        $message .= "Mascota: {$datos->nombre} ({$datos->tipo_mascota})\n";
        $message .= "Raza: {$datos->raza}\n";
        $message .= "Email: {$datos->email}\n";
        $message .= "Código postal: {$datos->codigo_postal}\n";
        $message .= "Precio Petplan: €{$precio}\n";
        $message .= "Sesión: {$session_id}\n";
        $message .= "Fecha: " . current_time('Y-m-d H:i:s') . "\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Email al usuario si está habilitado
        if (!empty($config['user_confirmation_email'])) {
            $user_subject = 'Comparativa de seguros para ' . $datos->nombre;
            $user_message = "Hola,\n\n";
            $user_message .= "Tu comparativa para {$datos->nombre} ha sido procesada.\n";
            $user_message .= "Precio encontrado: €{$precio}\n\n";
            $user_message .= "Puedes ver los resultados completos en:\n";
            $user_message .= home_url('/doguify-resultado/?session_id=' . $session_id) . "\n\n";
            $user_message .= "Gracias por usar Doguify.";
            
            wp_mail($datos->email, $user_subject, $user_message);
        }
    }
    
    public function cleanup_old_sessions() {
        global $wpdb;
        
        $config = get_option('doguify_config', array());
        $retention_days = intval($config['data_retention_days'] ?? 730);
        
        // Eliminar comparativas antiguas
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}doguify_comparativas 
             WHERE fecha_registro < %s",
            date('Y-m-d H:i:s', strtotime("-{$retention_days} days"))
        ));
        
        // Eliminar logs antiguos
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}doguify_logs 
             WHERE fecha < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        if (function_exists('doguify_log')) {
            doguify_log('info', "Limpieza automática completada. Eliminadas {$deleted} comparativas antiguas.");
        }
    }
    
    private function validar_datos_formulario($datos) {
        $errores = array();
        $resultado = array();
        
        // Validar campos requeridos
        $campos_requeridos = array('tipo_mascota', 'nombre', 'email', 'codigo_postal', 'edad_dia', 'edad_mes', 'edad_año', 'raza');
        
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                $errores[] = "El campo $campo es requerido";
            }
        }
        
        if (!empty($errores)) {
            return array('valido' => false, 'errores' => implode(', ', $errores));
        }
        
        // Sanitizar datos
        $resultado['tipo_mascota'] = sanitize_text_field($datos['tipo_mascota']);
        $resultado['nombre'] = sanitize_text_field($datos['nombre']);
        $resultado['email'] = sanitize_email($datos['email']);
        $resultado['codigo_postal'] = sanitize_text_field($datos['codigo_postal']);
        $resultado['edad_dia'] = intval($datos['edad_dia']);
        $resultado['edad_mes'] = intval($datos['edad_mes']);
        $resultado['edad_año'] = intval($datos['edad_año']);
        $resultado['raza'] = sanitize_text_field($datos['raza']);
        
        // Validaciones específicas
        if (!in_array($resultado['tipo_mascota'], array('perro', 'gato'))) {
            $errores[] = 'Tipo de mascota inválido';
        }
        
        if (!is_email($resultado['email'])) {
            $errores[] = 'Email inválido';
        }
        
        if (!preg_match('/^\d{5}$/', $resultado['codigo_postal'])) {
            $errores[] = 'Código postal debe tener exactamente 5 dígitos';
        }
        
        // Validar fecha de nacimiento
        if (function_exists('DoguifyUtilities::validate_birth_date')) {
            if (!DoguifyUtilities::validate_birth_date($resultado['edad_dia'], $resultado['edad_mes'], $resultado['edad_año'])) {
                $error_message = DoguifyUtilities::get_birth_date_validation_error($resultado['edad_dia'], $resultado['edad_mes'], $resultado['edad_año']);
                $errores[] = $error_message ?: 'Fecha de nacimiento inválida';
            }
        } else {
            // Validación básica si no existe la clase de utilidades
            $date = checkdate($resultado['edad_mes'], $resultado['edad_dia'], $resultado['edad_año']);
            if (!$date || $resultado['edad_año'] < 2018 || $resultado['edad_año'] > date('Y')) {
                $errores[] = 'Fecha de nacimiento inválida';
            }
        }
        
        if (!empty($errores)) {
            return array('valido' => false, 'errores' => implode(', ', $errores));
        }
        
        $resultado['valido'] = true;
        return $resultado;
    }
    
    private function obtener_ip_real() {
        $ip_keys = array(
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    public function activate_plugin() {
        // Crear tablas si no existen
        if (function_exists('doguify_create_tables')) {
            doguify_create_tables();
        }
        
        // Configurar cron jobs
        if (!wp_next_scheduled('doguify_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'doguify_daily_cleanup');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate_plugin() {
        // Limpiar cron jobs
        wp_clear_scheduled_hook('doguify_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Inicializar plugin
new DoguifyComparador();
