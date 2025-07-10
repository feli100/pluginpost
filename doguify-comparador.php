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
        add_action('template_redirect', array($this, 'handle_custom_pages'));
        
        // Shortcodes
        add_shortcode('doguify_formulario', array($this, 'mostrar_formulario'));
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
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script(
            'doguify-comparador',
            DOGUIFY_PLUGIN_URL . 'assets/doguify-comparador.js',
            array('jquery'),
            DOGUIFY_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'doguify-comparador',
            DOGUIFY_PLUGIN_URL . 'assets/doguify-comparador.css',
            array(),
            DOGUIFY_PLUGIN_VERSION
        );
        
        // Cargar CSS específico para la página de espera
        if (get_query_var('doguify_page') == 'espera') {
            wp_enqueue_style(
                'doguify-waiting-new',
                DOGUIFY_PLUGIN_URL . 'assets/doguify-waiting-new.css',
                array(),
                DOGUIFY_PLUGIN_VERSION
            );
        }
        
        wp_localize_script('doguify-comparador', 'doguify_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('doguify_nonce'),
            'espera_url' => home_url('/doguify-espera/'),
            'resultado_url' => home_url('/doguify-resultado/')
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
        
        // Validar datos
        $datos = $this->validar_datos_formulario($_POST);
        
        if (!$datos['valido']) {
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
                'ip_address' => $this->obtener_ip_real(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
                'fecha_registro' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($resultado === false) {
            wp_die(json_encode(array('success' => false, 'message' => 'Error al guardar datos')));
        }
        
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
            "SELECT * FROM $tabla WHERE session_id = %s",
            $session_id
        ));
        
        if (!$datos) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sesión no encontrada')));
        }
        
        // Construir URL de Petplan con formato americano MM/DD/YYYY
        $fecha_nacimiento = sprintf('%02d/%02d/%d', $datos->edad_mes, $datos->edad_dia, $datos->edad_año);
        
        $url_petplan = 'https://ws.petplan.es/pricing?' . http_build_query(array(
            'postalcode' => $datos->codigo_postal,
            'age' => $fecha_nacimiento,
            'column' => 2,
            'breed' => $datos->raza
        ));
        
        // Realizar consulta a Petplan
        $response = wp_remote_get($url_petplan, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Doguify Comparador/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Error al consultar Petplan')));
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $precio = isset($data['Precio']) ? floatval($data['Precio']) : 0;
        
        // Actualizar base de datos
        $wpdb->update(
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
        
        wp_die(json_encode(array(
            'success' => true,
            'precio' => $precio,
            'datos' => array(
                'nombre' => $datos->nombre,
                'tipo_mascota' => $datos->tipo_mascota,
                'raza' => $datos->raza
            )
        )));
    }
    
    public function handle_custom_pages() {
        $doguify_page = get_query_var('doguify_page');
        
        if ($doguify_page == 'espera') {
            // Agregar clase al body para el nuevo diseño
            add_filter('body_class', function($classes) {
                $classes[] = 'doguify-waiting-body';
                return $classes;
            });
            
            // Asegurar que se cargue el CSS específico
            add_action('wp_head', function() {
                echo '<link rel="stylesheet" href="' . DOGUIFY_PLUGIN_URL . 'assets/doguify-waiting-new.css?v=' . DOGUIFY_PLUGIN_VERSION . '">';
            });
            
            // Incluir el nuevo template
            include DOGUIFY_PLUGIN_PATH . 'templates/pagina-espera.php';
            exit;
        } elseif ($doguify_page == 'resultado') {
            include DOGUIFY_PLUGIN_PATH . 'templates/pagina-resultado.php';
            exit;
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
        
        // Sanitizar y validar datos
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
        
        // Validar fecha de nacimiento mejorada
        if (!DoguifyUtilities::validate_birth_date($resultado['edad_dia'], $resultado['edad_mes'], $resultado['edad_año'])) {
            $error_message = DoguifyUtilities::get_birth_date_validation_error($resultado['edad_dia'], $resultado['edad_mes'], $resultado['edad_año']);
            $errores[] = $error_message ?: 'Fecha de nacimiento inválida';
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
}

// Inicializar plugin
new DoguifyComparador();