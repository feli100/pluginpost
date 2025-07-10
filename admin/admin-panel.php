<?php
/**
 * Panel de administración del plugin Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyAdminPanel {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Doguify Comparador',
            'Doguify Comparador',
            'manage_options',
            'doguify-comparador',
            array($this, 'admin_page'),
            'dashicons-pets',
            30
        );
        
        add_submenu_page(
            'doguify-comparador',
            'Comparativas',
            'Comparativas',
            'manage_options',
            'doguify-comparador',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'doguify-comparador',
            'Configuración',
            'Configuración',
            'manage_options',
            'doguify-config',
            array($this, 'config_page')
        );
        
        add_submenu_page(
            'doguify-comparador',
            'Estadísticas',
            'Estadísticas',
            'manage_options',
            'doguify-stats',
            array($this, 'stats_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'doguify') !== false) {
            wp_enqueue_style('doguify-admin', DOGUIFY_PLUGIN_URL . 'admin/admin.css', array(), DOGUIFY_PLUGIN_VERSION);
            wp_enqueue_script('doguify-admin', DOGUIFY_PLUGIN_URL . 'admin/admin.js', array('jquery'), DOGUIFY_PLUGIN_VERSION, true);
        }
    }
    
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Manejar exportación CSV
        if (isset($_GET['action']) && $_GET['action'] === 'export_csv' && isset($_GET['page']) && $_GET['page'] === 'doguify-comparador') {
            if (wp_verify_nonce($_GET['nonce'], 'doguify_export')) {
                $this->export_csv();
            }
        }
        
        // Manejar eliminación de registro
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['nonce'], 'doguify_delete_' . $_GET['id'])) {
                $this->delete_record($_GET['id']);
            }
        }
    }
    
    public function admin_page() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        // Paginación
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Filtros
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($_GET['filter_estado'])) {
            $where .= " AND estado = %s";
            $params[] = sanitize_text_field($_GET['filter_estado']);
        }
        
        if (!empty($_GET['filter_fecha_desde'])) {
            $where .= " AND DATE(fecha_registro) >= %s";
            $params[] = sanitize_text_field($_GET['filter_fecha_desde']);
        }
        
        if (!empty($_GET['filter_fecha_hasta'])) {
            $where .= " AND DATE(fecha_registro) <= %s";
            $params[] = sanitize_text_field($_GET['filter_fecha_hasta']);
        }
        
        // Obtener registros
        $sql = "SELECT * FROM $tabla $where ORDER BY fecha_registro DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        $registros = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Obtener total para paginación
        $total_sql = "SELECT COUNT(*) FROM $tabla $where";
        array_pop($params); // Quitar OFFSET
        array_pop($params); // Quitar LIMIT
        $total = $wpdb->get_var($wpdb->prepare($total_sql, $params));
        
        // Estadísticas básicas
        $stats = $this->get_basic_stats();
        
        include DOGUIFY_PLUGIN_PATH . 'admin/views/admin-main.php';
    }
    
    public function config_page() {
        if (isset($_POST['save_config'])) {
            if (wp_verify_nonce($_POST['doguify_config_nonce'], 'doguify_save_config')) {
                $this->save_config();
            }
        }
        
        $config = get_option('doguify_config', array(
            'petplan_enabled' => true,
            'email_notifications' => true,
            'admin_email' => get_option('admin_email'),
            'results_page_title' => '¡Tu comparativa está lista!',
            'results_page_subtitle' => 'Hemos encontrado la mejor opción para tu mascota'
        ));
        
        include DOGUIFY_PLUGIN_PATH . 'admin/views/config.php';
    }
    
    public function stats_page() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        // Estadísticas avanzadas
        $stats = array(
            'total_comparativas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla"),
            'comparativas_hoy' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_registro) = CURDATE()"),
            'comparativas_mes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())"),
            'precio_promedio' => $wpdb->get_var("SELECT AVG(precio_petplan) FROM $tabla WHERE precio_petplan IS NOT NULL AND precio_petplan > 0"),
            'por_tipo_mascota' => $wpdb->get_results("SELECT tipo_mascota, COUNT(*) as total FROM $tabla GROUP BY tipo_mascota"),
            'por_raza' => $wpdb->get_results("SELECT raza, COUNT(*) as total FROM $tabla GROUP BY raza ORDER BY total DESC LIMIT 10"),
            'por_mes' => $wpdb->get_results("SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as total FROM $tabla WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY mes ORDER BY mes"),
            'conversiones' => $wpdb->get_results("SELECT estado, COUNT(*) as total FROM $tabla GROUP BY estado")
        );
        
        include DOGUIFY_PLUGIN_PATH . 'admin/views/stats.php';
    }
    
    private function get_basic_stats() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla"),
            'pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'"),
            'completadas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'completado'"),
            'hoy' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_registro) = CURDATE()"),
            'esta_semana' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")
        );
    }
    
    private function export_csv() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $registros = $wpdb->get_results("SELECT * FROM $tabla ORDER BY fecha_registro DESC");
        
        $filename = 'doguify_comparativas_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        fputcsv($output, array(
            'ID', 'Session ID', 'Tipo Mascota', 'Nombre', 'Email', 'Código Postal',
            'Día Nacimiento', 'Mes Nacimiento', 'Año Nacimiento', 'Raza',
            'Precio Petplan', 'Estado', 'IP', 'Fecha Registro', 'Fecha Consulta'
        ), ';');
        
        // Datos
        foreach ($registros as $registro) {
            fputcsv($output, array(
                $registro->id,
                $registro->session_id,
                $registro->tipo_mascota,
                $registro->nombre,
                $registro->email,
                $registro->codigo_postal,
                $registro->edad_dia,
                $registro->edad_mes,
                $registro->edad_año,
                $registro->raza,
                $registro->precio_petplan,
                $registro->estado,
                $registro->ip_address,
                $registro->fecha_registro,
                $registro->fecha_consulta
            ), ';');
        }
        
        fclose($output);
        exit;
    }
    
    private function delete_record($id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $resultado = $wpdb->delete($tabla, array('id' => intval($id)), array('%d'));
        
        if ($resultado !== false) {
            wp_redirect(admin_url('admin.php?page=doguify-comparador&message=deleted'));
        } else {
            wp_redirect(admin_url('admin.php?page=doguify-comparador&message=error'));
        }
        exit;
    }
    
    private function save_config() {
        $config = array(
            'petplan_enabled' => isset($_POST['petplan_enabled']),
            'email_notifications' => isset($_POST['email_notifications']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'results_page_title' => sanitize_text_field($_POST['results_page_title']),
            'results_page_subtitle' => sanitize_text_field($_POST['results_page_subtitle'])
        );
        
        update_option('doguify_config', $config);
        
        wp_redirect(admin_url('admin.php?page=doguify-config&message=saved'));
        exit;
    }
}

// Inicializar panel admin
if (is_admin()) {
    new DoguifyAdminPanel();
}