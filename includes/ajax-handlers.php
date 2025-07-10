<?php
/**
 * Manejadores AJAX para el plugin Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyAjaxHandlers {
    
    public function __construct() {
        // AJAX para admin
        add_action('wp_ajax_doguify_get_details', array($this, 'get_comparison_details'));
        add_action('wp_ajax_doguify_refresh_stats', array($this, 'refresh_stats'));
        add_action('wp_ajax_doguify_delete_comparison', array($this, 'delete_comparison'));
        add_action('wp_ajax_doguify_bulk_action', array($this, 'bulk_action'));
        add_action('wp_ajax_doguify_test_petplan', array($this, 'test_petplan_connection'));
        add_action('wp_ajax_doguify_export_filtered', array($this, 'export_filtered_data'));
        
        // AJAX para frontend (ya definidos en el archivo principal)
        // add_action('wp_ajax_doguify_procesar_comparativa', array($this, 'procesar_comparativa'));
        // add_action('wp_ajax_nopriv_doguify_procesar_comparativa', array($this, 'procesar_comparativa'));
        
        // Localizar scripts
        add_action('admin_enqueue_scripts', array($this, 'localize_admin_scripts'));
    }
    
    public function localize_admin_scripts($hook) {
        if (strpos($hook, 'doguify') !== false) {
            wp_localize_script('doguify-admin', 'doguify_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('doguify_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => '¿Estás seguro de eliminar este registro?',
                    'confirm_bulk_delete' => '¿Estás seguro de eliminar los registros seleccionados?',
                    'loading' => 'Cargando...',
                    'error' => 'Error al procesar la solicitud',
                    'success' => 'Operación completada con éxito'
                )
            ));
        }
    }
    
    public function get_comparison_details() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        $id = intval($_POST['id']);
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $registro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $id
        ));
        
        if (!$registro) {
            wp_die(json_encode(array('success' => false, 'message' => 'Registro no encontrado')));
        }
        
        // Calcular edad exacta
        $fecha_nacimiento = new DateTime("{$registro->edad_año}-{$registro->edad_mes}-{$registro->edad_dia}");
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nacimiento);
        
        // Obtener logs relacionados
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logs WHERE session_id = %s ORDER BY fecha DESC LIMIT 10",
            $registro->session_id
        ));
        
        ob_start();
        ?>
        <div class="doguify-details">
            <div class="doguify-details-grid">
                <div class="doguify-detail-section">
                    <h4>🐕 Información de la Mascota</h4>
                    <table class="doguify-detail-table">
                        <tr>
                            <td><strong>Nombre:</strong></td>
                            <td><?php echo esc_html($registro->nombre); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td><?php echo $registro->tipo_mascota === 'perro' ? '🐕 Perro' : '🐱 Gato'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Raza:</strong></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', esc_html($registro->raza))); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de nacimiento:</strong></td>
                            <td><?php echo sprintf('%02d/%02d/%d', $registro->edad_dia, $registro->edad_mes, $registro->edad_año); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Edad exacta:</strong></td>
                            <td>
                                <?php
                                if ($edad->y > 0) {
                                    echo $edad->y . ' año' . ($edad->y > 1 ? 's' : '');
                                    if ($edad->m > 0) echo ', ' . $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
                                    if ($edad->d > 0) echo ', ' . $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
                                } elseif ($edad->m > 0) {
                                    echo $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
                                    if ($edad->d > 0) echo ', ' . $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
                                } else {
                                    echo $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="doguify-detail-section">
                    <h4>👤 Información del Propietario</h4>
                    <table class="doguify-detail-table">
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><a href="mailto:<?php echo esc_attr($registro->email); ?>"><?php echo esc_html($registro->email); ?></a></td>
                        </tr>
                        <tr>
                            <td><strong>Código postal:</strong></td>
                            <td><?php echo esc_html($registro->codigo_postal); ?></td>
                        </tr>
                        <tr>
                            <td><strong>IP:</strong></td>
                            <td><code><?php echo esc_html($registro->ip_address); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Navegador:</strong></td>
                            <td><?php echo esc_html(substr($registro->user_agent, 0, 100)); ?><?php echo strlen($registro->user_agent) > 100 ? '...' : ''; ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="doguify-detail-section">
                    <h4>💰 Información de Precio</h4>
                    <table class="doguify-detail-table">
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td>
                                <span class="doguify-estado-badge doguify-estado-<?php echo esc_attr($registro->estado); ?>">
                                    <?php echo $registro->estado === 'completado' ? '✅ Completado' : '⏳ Pendiente'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Precio Petplan:</strong></td>
                            <td>
                                <?php if ($registro->precio_petplan && $registro->precio_petplan > 0): ?>
                                    <strong style="color: #2ecc71;"><?php echo number_format($registro->precio_petplan, 2); ?>€</strong>
                                    <small>(<?php echo number_format($registro->precio_petplan / 12, 2); ?>€/mes)</small>
                                <?php else: ?>
                                    <span style="color: #999;">Sin precio</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de consulta:</strong></td>
                            <td>
                                <?php if ($registro->fecha_consulta): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($registro->fecha_consulta)); ?>
                                <?php else: ?>
                                    <span style="color: #999;">No consultado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="doguify-detail-section">
                    <h4>📅 Información de Registro</h4>
                    <table class="doguify-detail-table">
                        <tr>
                            <td><strong>ID de sesión:</strong></td>
                            <td><code><?php echo esc_html($registro->session_id); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Fecha de registro:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($registro->fecha_registro)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Última actualización:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($registro->fecha_actualizacion)); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if (!empty($logs)): ?>
            <div class="doguify-detail-section" style="grid-column: 1 / -1; margin-top: 20px;">
                <h4>📋 Logs de Actividad</h4>
                <div class="doguify-logs">
                    <?php foreach ($logs as $log): ?>
                        <div class="doguify-log-entry doguify-log-<?php echo esc_attr($log->level); ?>">
                            <span class="doguify-log-time"><?php echo date('H:i:s', strtotime($log->fecha)); ?></span>
                            <span class="doguify-log-level"><?php echo strtoupper($log->level); ?></span>
                            <span class="doguify-log-message"><?php echo esc_html($log->message); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="doguify-detail-actions" style="margin-top: 20px; text-align: center;">
                <a href="<?php echo home_url('/doguify-resultado/?session_id=' . $registro->session_id); ?>" 
                   target="_blank" class="button button-primary">
                    👁️ Ver página de resultados
                </a>
                
                <?php if ($registro->estado === 'pendiente'): ?>
                <button type="button" class="button" onclick="retryPetplanQuery('<?php echo $registro->session_id; ?>')">
                    🔄 Reintentar consulta Petplan
                </button>
                <?php endif; ?>
                
                <button type="button" class="button button-secondary" onclick="sendTestEmail('<?php echo $registro->email; ?>')">
                    📧 Enviar email de prueba
                </button>
            </div>
        </div>
        
        <style>
        .doguify-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .doguify-detail-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        
        .doguify-detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .doguify-detail-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        
        .doguify-detail-table td:first-child {
            width: 40%;
            color: #666;
        }
        
        .doguify-logs {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background: #f9f9f9;
        }
        
        .doguify-log-entry {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
            font-size: 12px;
            font-family: monospace;
        }
        
        .doguify-log-time {
            color: #666;
            min-width: 60px;
        }
        
        .doguify-log-level {
            min-width: 60px;
            font-weight: bold;
        }
        
        .doguify-log-info .doguify-log-level { color: #3498db; }
        .doguify-log-success .doguify-log-level { color: #2ecc71; }
        .doguify-log-warning .doguify-log-level { color: #f39c12; }
        .doguify-log-error .doguify-log-level { color: #e74c3c; }
        
        @media (max-width: 768px) {
            .doguify-details-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        function retryPetplanQuery(sessionId) {
            if (confirm('¿Reintentar la consulta a Petplan?')) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'doguify_retry_petplan',
                        session_id: sessionId,
                        nonce: doguify_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Consulta reintentada. Recarga la página para ver los resultados.');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }
        
        function sendTestEmail(email) {
            if (confirm('¿Enviar email de prueba a ' + email + '?')) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'doguify_send_test_email',
                        email: email,
                        nonce: doguify_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Email de prueba enviado.');
                        } else {
                            alert('Error al enviar email: ' + response.message);
                        }
                    }
                });
            }
        }
        </script>
        <?php
        
        $content = ob_get_clean();
        
        wp_die(json_encode(array('success' => true, 'data' => $content)));
    }
    
    public function refresh_stats() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla"),
            'hoy' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE DATE(fecha_registro) = CURDATE()"),
            'mes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())"),
            'pendientes' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'pendiente'"),
            'completadas' => $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'completado'")
        );
        
        wp_die(json_encode(array('success' => true, 'data' => $stats)));
    }
    
    public function delete_comparison() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        $id = intval($_POST['id']);
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $resultado = $wpdb->delete($tabla, array('id' => $id), array('%d'));
        
        if ($resultado !== false) {
            wp_die(json_encode(array('success' => true, 'message' => 'Registro eliminado')));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Error al eliminar')));
        }
    }
    
    public function bulk_action() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = array_map('intval', $_POST['ids']);
        
        if (empty($ids)) {
            wp_die(json_encode(array('success' => false, 'message' => 'No se seleccionaron registros')));
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        
        switch ($action) {
            case 'delete':
                $resultado = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $tabla WHERE id IN ($ids_placeholder)",
                    $ids
                ));
                break;
                
            case 'mark_completed':
                $resultado = $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla SET estado = 'completado' WHERE id IN ($ids_placeholder)",
                    $ids
                ));
                break;
                
            case 'mark_pending':
                $resultado = $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla SET estado = 'pendiente' WHERE id IN ($ids_placeholder)",
                    $ids
                ));
                break;
                
            default:
                wp_die(json_encode(array('success' => false, 'message' => 'Acción no válida')));
        }
        
        if ($resultado !== false) {
            wp_die(json_encode(array('success' => true, 'message' => "Acción aplicada a $resultado registros")));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Error al procesar la acción')));
        }
    }
    
    public function test_petplan_connection() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        // Datos de prueba con formato americano MM/DD/YYYY
        $test_data = array(
            'postalcode' => '28001',
            'age' => '01/01/2020', // MM/DD/YYYY - formato americano
            'column' => 2,
            'breed' => 'beagle'
        );
        
        $url = 'https://ws.petplan.es/pricing?' . http_build_query($test_data);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Doguify Comparador/1.0'
            )
        ));
        
        if (is_wp_error($response)) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Error de conexión: ' . $response->get_error_message()
            )));
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'status_code' => $status_code,
                'response' => $body,
                'url' => $url,
                'date_format' => 'MM/DD/YYYY (formato americano)'
            )
        )));
    }
    
    public function export_filtered_data() {
        check_ajax_referer('doguify_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Sin permisos')));
        }
        
        // Obtener filtros del POST
        $filters = array(
            'estado' => sanitize_text_field($_POST['filter_estado'] ?? ''),
            'fecha_desde' => sanitize_text_field($_POST['filter_fecha_desde'] ?? ''),
            'fecha_hasta' => sanitize_text_field($_POST['filter_fecha_hasta'] ?? ''),
            'tipo_mascota' => sanitize_text_field($_POST['filter_tipo_mascota'] ?? ''),
            'codigo_postal' => sanitize_text_field($_POST['filter_codigo_postal'] ?? '')
        );
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $where = "WHERE 1=1";
        $params = array();
        
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case 'fecha_desde':
                        $where .= " AND DATE(fecha_registro) >= %s";
                        $params[] = $value;
                        break;
                    case 'fecha_hasta':
                        $where .= " AND DATE(fecha_registro) <= %s";
                        $params[] = $value;
                        break;
                    default:
                        $where .= " AND $key = %s";
                        $params[] = $value;
                        break;
                }
            }
        }
        
        $sql = "SELECT * FROM $tabla $where ORDER BY fecha_registro DESC";
        $registros = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Generar CSV temporal
        $temp_file = tempnam(sys_get_temp_dir(), 'doguify_export_');
        $handle = fopen($temp_file, 'w');
        
        // BOM para UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        fputcsv($handle, array(
            'ID', 'Session ID', 'Tipo', 'Nombre', 'Email', 'CP', 
            'Día', 'Mes', 'Año', 'Raza', 'Precio', 'Estado', 
            'IP', 'Fecha Registro', 'Fecha Consulta'
        ), ';');
        
        // Datos
        foreach ($registros as $registro) {
            fputcsv($handle, array(
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
        
        fclose($handle);
        
        // Codificar archivo en base64 para envío
        $file_content = base64_encode(file_get_contents($temp_file));
        unlink($temp_file);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'content' => $file_content,
                'filename' => 'doguify_filtrado_' . date('Y-m-d_H-i-s') . '.csv',
                'count' => count($registros)
            )
        )));
    }
}

// Inicializar manejadores AJAX
new DoguifyAjaxHandlers();