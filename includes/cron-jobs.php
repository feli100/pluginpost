<?php
/**
 * Tareas programadas y cron jobs para Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DogufyCronJobs {
    
    public function __construct() {
        // Registrar intervalos personalizados
        add_filter('cron_schedules', array($this, 'add_custom_intervals'));
        
        // Hooks para tareas programadas
        add_action('doguify_daily_cleanup', array($this, 'daily_cleanup'));
        add_action('doguify_weekly_report', array($this, 'weekly_report'));
        add_action('doguify_monthly_backup', array($this, 'monthly_backup'));
        add_action('doguify_hourly_health_check', array($this, 'hourly_health_check'));
        add_action('doguify_process_pending_queries', array($this, 'process_pending_queries'));
        
        // Programar tareas si no existen
        add_action('wp', array($this, 'schedule_events'));
    }
    
    /**
     * Añadir intervalos personalizados
     */
    public function add_custom_intervals($schedules) {
        $schedules['every_15_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display' => 'Cada 15 minutos'
        );
        
        $schedules['every_30_minutes'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => 'Cada 30 minutos'
        );
        
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => 'Una vez a la semana'
        );
        
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => 'Una vez al mes'
        );
        
        return $schedules;
    }
    
    /**
     * Programar eventos si no existen
     */
    public function schedule_events() {
        // Limpieza diaria
        if (!wp_next_scheduled('doguify_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'doguify_daily_cleanup');
        }
        
        // Reporte semanal
        if (!wp_next_scheduled('doguify_weekly_report')) {
            $next_monday = strtotime('next monday 09:00');
            wp_schedule_event($next_monday, 'weekly', 'doguify_weekly_report');
        }
        
        // Backup mensual
        if (!wp_next_scheduled('doguify_monthly_backup')) {
            $first_day_next_month = strtotime('first day of next month 02:00');
            wp_schedule_event($first_day_next_month, 'monthly', 'doguify_monthly_backup');
        }
        
        // Health check cada hora
        if (!wp_next_scheduled('doguify_hourly_health_check')) {
            wp_schedule_event(time(), 'hourly', 'doguify_hourly_health_check');
        }
        
        // Procesar consultas pendientes cada 15 minutos
        if (!wp_next_scheduled('doguify_process_pending_queries')) {
            wp_schedule_event(time(), 'every_15_minutes', 'doguify_process_pending_queries');
        }
    }
    
    /**
     * Limpieza diaria
     */
    public function daily_cleanup() {
        $logger = DoguifyLogger::getInstance();
        $logger->info('Iniciando limpieza diaria');
        
        try {
            // Limpiar logs antiguos
            $deleted_logs = $logger->cleanupOldLogs(30);
            $logger->info("Logs limpiados: {$deleted_logs}");
            
            // Limpiar datos antiguos según GDPR
            $deleted_data = DoguifyUtilities::cleanup_old_data();
            $logger->info("Datos antiguos limpiados: {$deleted_data}");
            
            // Limpiar transients expirados
            $this->cleanup_expired_transients();
            
            // Optimizar base de datos
            $this->optimize_database();
            
            // Limpiar cache de Petplan si es muy grande
            $this->cleanup_petplan_cache();
            
            $logger->success('Limpieza diaria completada');
            
        } catch (Exception $e) {
            $logger->error('Error en limpieza diaria: ' . $e->getMessage());
        }
    }
    
    /**
     * Reporte semanal
     */
    public function weekly_report() {
        $logger = DoguifyLogger::getInstance();
        $logger->info('Generando reporte semanal');
        
        try {
            if (!doguify_config('email_notifications', true)) {
                return;
            }
            
            $admin_email = doguify_config('admin_email', get_option('admin_email'));
            
            if (!$admin_email) {
                return;
            }
            
            // Obtener estadísticas de la semana
            $stats = $this->get_weekly_stats();
            
            // Generar reporte HTML
            $report_html = $this->generate_weekly_report_html($stats);
            
            // Enviar email
            $subject = 'Reporte Semanal - Doguify Comparador';
            $sent = DoguifyUtilities::send_notification_email($admin_email, $subject, $report_html);
            
            if ($sent) {
                $logger->success('Reporte semanal enviado');
            } else {
                $logger->error('Error al enviar reporte semanal');
            }
            
        } catch (Exception $e) {
            $logger->error('Error generando reporte semanal: ' . $e->getMessage());
        }
    }
    
    /**
     * Backup mensual
     */
    public function monthly_backup() {
        $logger = DoguifyLogger::getInstance();
        $logger->info('Iniciando backup mensual');
        
        try {
            global $wpdb;
            $tabla = $wpdb->prefix . 'doguify_comparativas';
            
            // Crear backup
            $backup_table = $tabla . '_backup_' . date('Y_m');
            
            $wpdb->query("CREATE TABLE $backup_table AS SELECT * FROM $tabla");
            
            // Verificar backup
            $backup_count = $wpdb->get_var("SELECT COUNT(*) FROM $backup_table");
            $original_count = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
            
            if ($backup_count == $original_count) {
                $logger->success("Backup mensual creado: $backup_table ($backup_count registros)");
                
                // Eliminar backups antiguos (mantener solo 6 meses)
                $this->cleanup_old_backups();
                
            } else {
                $logger->error("Error en backup mensual: conteos no coinciden");
            }
            
        } catch (Exception $e) {
            $logger->error('Error en backup mensual: ' . $e->getMessage());
        }
    }
    
    /**
     * Health check cada hora
     */
    public function hourly_health_check() {
        $logger = DoguifyLogger::getInstance();
        
        try {
            $health = DoguifyUtilities::system_health_check();
            
            $issues = array();
            
            foreach ($health as $component => $status) {
                if ($status === false) {
                    $issues[] = $component;
                }
            }
            
            if (!empty($issues)) {
                $logger->warning('Health check detectó problemas: ' . implode(', ', $issues), $health);
                
                // Si hay problemas críticos, enviar notificación inmediata
                if (in_array('database', $issues) || in_array('petplan', $issues)) {
                    $this->send_critical_alert($issues, $health);
                }
            } else {
                $logger->debug('Health check OK', $health);
            }
            
        } catch (Exception $e) {
            $logger->error('Error en health check: ' . $e->getMessage());
        }
    }
    
    /**
     * Procesar consultas pendientes
     */
    public function process_pending_queries() {
        $logger = DoguifyLogger::getInstance();
        
        try {
            global $wpdb;
            $tabla = $wpdb->prefix . 'doguify_comparativas';
            
            // Obtener consultas pendientes de más de 5 minutos
            $pending = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla 
                 WHERE estado = 'pendiente' 
                 AND fecha_registro < %s 
                 ORDER BY fecha_registro ASC 
                 LIMIT 10",
                date('Y-m-d H:i:s', strtotime('-5 minutes'))
            ));
            
            if (empty($pending)) {
                return;
            }
            
            $logger->info('Procesando ' . count($pending) . ' consultas pendientes');
            
            foreach ($pending as $registro) {
                $this->retry_petplan_query($registro);
            }
            
        } catch (Exception $e) {
            $logger->error('Error procesando consultas pendientes: ' . $e->getMessage());
        }
    }
    
    /**
     * Reintentar consulta Petplan
     */
        private function retry_petplan_query($registro) {
        $logger = DoguifyLogger::getInstance();
        $logger->setSessionId($registro->session_id);
        
        try {
            // Construir URL de Petplan con formato americano MM/DD/YYYY
            $fecha_nacimiento = sprintf('%02d/%02d/%d', $registro->edad_mes, $registro->edad_dia, $registro->edad_año);
            
            $url_petplan = 'https://ws.petplan.es/pricing?' . http_build_query(array(
                'postalcode' => $registro->codigo_postal,
                'age' => $fecha_nacimiento,
                'column' => 2,
                'breed' => $registro->raza
            ));
            
            $start_time = microtime(true);
            
            $response = wp_remote_get($url_petplan, array(
                'timeout' => DoguifyUtilities::get_petplan_timeout(),
                'headers' => array(
                    'User-Agent' => 'Doguify Comparador/1.0'
                )
            ));
            
            $execution_time = microtime(true) - $start_time;
            
            if (is_wp_error($response)) {
                $logger->error('Error en consulta Petplan: ' . $response->get_error_message());
                return false;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            $logger->logPetplanQuery($url_petplan, $status_code, $body, $execution_time);
            
            if ($status_code === 200) {
                $data = json_decode($body, true);
                $precio = isset($data['Precio']) ? floatval($data['Precio']) : 0;
                
                // Actualizar base de datos
                global $wpdb;
                $tabla = $wpdb->prefix . 'doguify_comparativas';
                
                $wpdb->update(
                    $tabla,
                    array(
                        'precio_petplan' => $precio,
                        'estado' => 'completado',
                        'fecha_consulta' => current_time('mysql')
                    ),
                    array('id' => $registro->id),
                    array('%f', '%s', '%s'),
                    array('%d')
                );
                
                $logger->success("Consulta Petplan exitosa: {$precio}€");
                return true;
            }
            
        } catch (Exception $e) {
            $logger->error('Error reintentando consulta Petplan: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Obtener estadísticas semanales
     */
    private function get_weekly_stats() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'doguify_comparativas';
        
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        return array(
            'total_semana' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE fecha_registro >= %s", $week_ago
            )),
            'completadas_semana' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE fecha_registro >= %s AND estado = 'completado'", $week_ago
            )),
            'precio_promedio' => $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(precio_petplan) FROM $tabla WHERE fecha_registro >= %s AND precio_petplan > 0", $week_ago
            )),
            'por_tipo' => $wpdb->get_results($wpdb->prepare(
                "SELECT tipo_mascota, COUNT(*) as total FROM $tabla WHERE fecha_registro >= %s GROUP BY tipo_mascota", $week_ago
            )),
            'top_razas' => $wpdb->get_results($wpdb->prepare(
                "SELECT raza, COUNT(*) as total FROM $tabla WHERE fecha_registro >= %s GROUP BY raza ORDER BY total DESC LIMIT 5", $week_ago
            ))
        );
    }
    
    /**
     * Generar HTML del reporte semanal
     */
    private function generate_weekly_report_html($stats) {
        $tasa_conversion = $stats['total_semana'] > 0 ? 
            round(($stats['completadas_semana'] / $stats['total_semana']) * 100, 1) : 0;
        
        $html = '
        <h2>📊 Reporte Semanal - Doguify Comparador</h2>
        <p><strong>Período:</strong> ' . date('d/m/Y', strtotime('-7 days')) . ' - ' . date('d/m/Y') . '</p>
        
        <h3>📈 Resumen General</h3>
        <ul>
            <li><strong>Total de comparativas:</strong> ' . number_format($stats['total_semana']) . '</li>
            <li><strong>Comparativas completadas:</strong> ' . number_format($stats['completadas_semana']) . '</li>
            <li><strong>Tasa de conversión:</strong> ' . $tasa_conversion . '%</li>
            <li><strong>Precio promedio:</strong> ' . ($stats['precio_promedio'] ? number_format($stats['precio_promedio'], 2) . '€' : 'N/A') . '</li>
        </ul>
        
        <h3>🐕 Por Tipo de Mascota</h3>
        <ul>';
        
        foreach ($stats['por_tipo'] as $tipo) {
            $emoji = $tipo->tipo_mascota === 'perro' ? '🐕' : '🐱';
            $html .= '<li>' . $emoji . ' ' . ucfirst($tipo->tipo_mascota) . 's: ' . number_format($tipo->total) . '</li>';
        }
        
        $html .= '
        </ul>
        
        <h3>🏆 Top 5 Razas</h3>
        <ol>';
        
        foreach ($stats['top_razas'] as $raza) {
            $html .= '<li>' . ucfirst(str_replace('_', ' ', $raza->raza)) . ': ' . number_format($raza->total) . '</li>';
        }
        
        $html .= '
        </ol>
        
        <hr>
        <p><small>Este reporte se genera automáticamente cada lunes.</small></p>
        ';
        
        return $html;
    }
    
    /**
     * Limpiar transients expirados
     */
    private function cleanup_expired_transients() {
        global $wpdb;
        
        // Limpiar transients de WordPress
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        if ($deleted > 0) {
            // Limpiar los transients asociados
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_%' 
                 AND option_name NOT LIKE '_transient_timeout_%' 
                 AND option_name NOT IN (
                     SELECT REPLACE(option_name, '_timeout', '') 
                     FROM {$wpdb->options} 
                     WHERE option_name LIKE '_transient_timeout_%'
                 )"
            );
        }
        
        return $deleted;
    }
    
    /**
     * Optimizar base de datos
     */
    private function optimize_database() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'doguify_comparativas',
            $wpdb->prefix . 'doguify_logs'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }
    
    /**
     * Limpiar cache de Petplan
     */
    private function cleanup_petplan_cache() {
        global $wpdb;
        
        // Contar transients de Petplan
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_doguify_petplan_cache_%'"
        );
        
        // Si hay más de 1000 entradas de cache, limpiar las más antiguas
        if ($count > 1000) {
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_doguify_petplan_cache_%' 
                 ORDER BY option_id ASC 
                 LIMIT 500"
            );
        }
    }
    
    /**
     * Limpiar backups antiguos
     */
    private function cleanup_old_backups() {
        global $wpdb;
        
        $tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}doguify_comparativas_backup_%'"
        );
        
        $cutoff_date = date('Y_m', strtotime('-6 months'));
        
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            
            // Extraer fecha del nombre de la tabla
            if (preg_match('/_backup_(\d{4}_\d{2})$/', $table_name, $matches)) {
                $table_date = $matches[1];
                
                if ($table_date < $cutoff_date) {
                    $wpdb->query("DROP TABLE IF EXISTS $table_name");
                }
            }
        }
    }
    
    /**
     * Enviar alerta crítica
     */
    private function send_critical_alert($issues, $health_data) {
        $admin_email = doguify_config('admin_email', get_option('admin_email'));
        
        if (!$admin_email) {
            return;
        }
        
        $message = '<h2>🚨 Alerta Crítica - Doguify Comparador</h2>';
        $message .= '<p>Se han detectado problemas críticos en el sistema:</p>';
        $message .= '<ul>';
        
        foreach ($issues as $issue) {
            $message .= '<li><strong>' . ucfirst($issue) . '</strong>: No funciona correctamente</li>';
        }
        
        $message .= '</ul>';
        $message .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>';
        $message .= '<p>Por favor, revisa el sistema lo antes posible.</p>';
        
        DoguifyUtilities::send_notification_email(
            $admin_email,
            '🚨 Alerta Crítica - Doguify Comparador',
            $message
        );
    }
    
    /**
     * Desactivar todas las tareas programadas
     */
    public static function unschedule_all_events() {
        wp_clear_scheduled_hook('doguify_daily_cleanup');
        wp_clear_scheduled_hook('doguify_weekly_report');
        wp_clear_scheduled_hook('doguify_monthly_backup');
        wp_clear_scheduled_hook('doguify_hourly_health_check');
        wp_clear_scheduled_hook('doguify_process_pending_queries');
    }
}

// Inicializar cron jobs
new DogufyCronJobs();

// Función para obtener estado de cron jobs
function doguify_get_cron_status() {
    $events = array(
        'doguify_daily_cleanup' => wp_next_scheduled('doguify_daily_cleanup'),
        'doguify_weekly_report' => wp_next_scheduled('doguify_weekly_report'),
        'doguify_monthly_backup' => wp_next_scheduled('doguify_monthly_backup'),
        'doguify_hourly_health_check' => wp_next_scheduled('doguify_hourly_health_check'),
        'doguify_process_pending_queries' => wp_next_scheduled('doguify_process_pending_queries')
    );
    
    return $events;
}