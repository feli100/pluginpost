<?php
/**
 * Sistema de logs para Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyLogger {
    
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_SUCCESS = 'success';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    private static $instance = null;
    private $enabled = true;
    private $session_id = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->enabled = doguify_config('debug_mode', false) || WP_DEBUG;
    }
    
    public function setSessionId($session_id) {
        $this->session_id = $session_id;
    }
    
    public function getSessionId() {
        return $this->session_id;
    }
    
    /**
     * Log de debug
     */
    public function debug($message, $context = array()) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log de información
     */
    public function info($message, $context = array()) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log de éxito
     */
    public function success($message, $context = array()) {
        $this->log(self::LEVEL_SUCCESS, $message, $context);
    }
    
    /**
     * Log de advertencia
     */
    public function warning($message, $context = array()) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log de error
     */
    public function error($message, $context = array()) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log crítico
     */
    public function critical($message, $context = array()) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Método principal de logging
     */
    public function log($level, $message, $context = array()) {
        if (!$this->enabled && !in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            return false;
        }
        
        // Preparar datos del log
        $log_data = array(
            'session_id' => $this->session_id,
            'level' => $level,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context) : null,
            'fecha' => current_time('mysql')
        );
        
        // Guardar en base de datos
        $this->saveToDatabase($log_data);
        
        // Guardar en archivo de log de WordPress si es error crítico
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            $this->saveToWordPressLog($level, $message, $context);
        }
        
        return true;
    }
    
    /**
     * Guardar en base de datos
     */
    private function saveToDatabase($log_data) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_logs'") !== $tabla_logs) {
            return false;
        }
        
        return $wpdb->insert(
            $tabla_logs,
            $log_data,
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Guardar en log de WordPress
     */
    private function saveToWordPressLog($level, $message, $context) {
        $log_message = "[DOGUIFY {$level}] {$message}";
        
        if (!empty($context)) {
            $log_message .= ' | Context: ' . json_encode($context);
        }
        
        if ($this->session_id) {
            $log_message .= ' | Session: ' . $this->session_id;
        }
        
        error_log($log_message);
    }
    
    /**
     * Obtener logs por session ID
     */
    public function getSessionLogs($session_id, $limit = 50) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logs WHERE session_id = %s ORDER BY fecha DESC LIMIT %d",
            $session_id,
            $limit
        ));
    }
    
    /**
     * Obtener logs por nivel
     */
    public function getLogsByLevel($level, $limit = 100, $offset = 0) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logs WHERE level = %s ORDER BY fecha DESC LIMIT %d OFFSET %d",
            $level,
            $limit,
            $offset
        ));
    }
    
    /**
     * Obtener logs recientes
     */
    public function getRecentLogs($limit = 100, $hours = 24) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logs WHERE fecha >= %s ORDER BY fecha DESC LIMIT %d",
            date('Y-m-d H:i:s', strtotime("-{$hours} hours")),
            $limit
        ));
    }
    
    /**
     * Contar logs por nivel
     */
    public function countLogsByLevel($hours = 24) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT level, COUNT(*) as count FROM $tabla_logs 
             WHERE fecha >= %s 
             GROUP BY level 
             ORDER BY count DESC",
            date('Y-m-d H:i:s', strtotime("-{$hours} hours"))
        ));
        
        $counts = array();
        foreach ($results as $row) {
            $counts[$row->level] = intval($row->count);
        }
        
        return $counts;
    }
    
    /**
     * Limpiar logs antiguos
     */
    public function cleanupOldLogs($days = 30) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $tabla_logs WHERE fecha < %s",
            date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ));
        
        if ($deleted > 0) {
            $this->info("Logs cleanup: {$deleted} registros eliminados");
        }
        
        return $deleted;
    }
    
    /**
     * Exportar logs como CSV
     */
    public function exportLogs($filters = array()) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $where = "WHERE 1=1";
        $params = array();
        
        if (!empty($filters['session_id'])) {
            $where .= " AND session_id = %s";
            $params[] = $filters['session_id'];
        }
        
        if (!empty($filters['level'])) {
            $where .= " AND level = %s";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['fecha_desde'])) {
            $where .= " AND fecha >= %s";
            $params[] = $filters['fecha_desde'];
        }
        
        if (!empty($filters['fecha_hasta'])) {
            $where .= " AND fecha <= %s";
            $params[] = $filters['fecha_hasta'];
        }
        
        $sql = "SELECT * FROM $tabla_logs $where ORDER BY fecha DESC";
        
        if (!empty($params)) {
            $logs = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $logs = $wpdb->get_results($sql);
        }
        
        return $logs;
    }
    
    /**
     * Obtener estadísticas de logs
     */
    public function getLogStats($days = 7) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $stats = array();
        
        // Total de logs por día
        $daily_logs = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha) as fecha, COUNT(*) as total 
             FROM $tabla_logs 
             WHERE fecha >= %s 
             GROUP BY DATE(fecha) 
             ORDER BY fecha DESC",
            date('Y-m-d', strtotime("-{$days} days"))
        ));
        
        $stats['daily'] = $daily_logs;
        
        // Logs por nivel en el período
        $level_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT level, COUNT(*) as count 
             FROM $tabla_logs 
             WHERE fecha >= %s 
             GROUP BY level 
             ORDER BY count DESC",
            date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ));
        
        $stats['by_level'] = $level_stats;
        
        // Sessions más activas
        $active_sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT session_id, COUNT(*) as count 
             FROM $tabla_logs 
             WHERE fecha >= %s AND session_id IS NOT NULL 
             GROUP BY session_id 
             ORDER BY count DESC 
             LIMIT 10",
            date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ));
        
        $stats['active_sessions'] = $active_sessions;
        
        // Errores recientes
        $recent_errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_logs 
             WHERE level IN ('error', 'critical') 
             AND fecha >= %s 
             ORDER BY fecha DESC 
             LIMIT 20",
            date('Y-m-d H:i:s', strtotime("-{$days} days"))
        ));
        
        $stats['recent_errors'] = $recent_errors;
        
        return $stats;
    }
    
    /**
     * Logging específico para proceso de comparativa
     */
    public function logComparativaStep($step, $message, $data = array()) {
        $context = array_merge(array('step' => $step), $data);
        $this->info("Comparativa Step: {$step} - {$message}", $context);
    }
    
    /**
     * Logging para consultas Petplan
     */
    public function logPetplanQuery($url, $response_code, $response_body, $execution_time) {
        $context = array(
            'url' => $url,
            'response_code' => $response_code,
            'response_body' => substr($response_body, 0, 500), // Limitar tamaño
            'execution_time' => $execution_time
        );
        
        if ($response_code === 200) {
            $this->success("Consulta Petplan exitosa", $context);
        } else {
            $this->error("Error en consulta Petplan", $context);
        }
    }
    
    /**
     * Logging para eventos de usuario
     */
    public function logUserEvent($event, $user_data = array()) {
        $context = array(
            'event' => $event,
            'user_data' => $user_data,
            'user_ip' => DoguifyUtilities::get_real_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );
        
        $this->info("User Event: {$event}", $context);
    }
    
    /**
     * Obtener resumen de errores
     */
    public function getErrorSummary($hours = 24) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT message, COUNT(*) as occurrences, MAX(fecha) as last_occurrence
             FROM $tabla_logs 
             WHERE level IN ('error', 'critical') 
             AND fecha >= %s 
             GROUP BY message 
             ORDER BY occurrences DESC, last_occurrence DESC
             LIMIT 20",
            date('Y-m-d H:i:s', strtotime("-{$hours} hours"))
        ));
        
        return $errors;
    }
    
    /**
     * Verificar si hay errores críticos recientes
     */
    public function hasCriticalErrors($minutes = 60) {
        global $wpdb;
        
        $tabla_logs = $wpdb->prefix . 'doguify_logs';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_logs 
             WHERE level = 'critical' 
             AND fecha >= %s",
            date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))
        ));
        
        return intval($count) > 0;
    }
}

// Funciones auxiliares globales para logging
if (!function_exists('doguify_log')) {
    function doguify_log($level, $message, $context = array()) {
        return DoguifyLogger::getInstance()->log($level, $message, $context);
    }
}

if (!function_exists('doguify_log_debug')) {
    function doguify_log_debug($message, $context = array()) {
        return DoguifyLogger::getInstance()->debug($message, $context);
    }
}

if (!function_exists('doguify_log_info')) {
    function doguify_log_info($message, $context = array()) {
        return DoguifyLogger::getInstance()->info($message, $context);
    }
}

if (!function_exists('doguify_log_success')) {
    function doguify_log_success($message, $context = array()) {
        return DoguifyLogger::getInstance()->success($message, $context);
    }
}

if (!function_exists('doguify_log_warning')) {
    function doguify_log_warning($message, $context = array()) {
        return DoguifyLogger::getInstance()->warning($message, $context);
    }
}

if (!function_exists('doguify_log_error')) {
    function doguify_log_error($message, $context = array()) {
        return DoguifyLogger::getInstance()->error($message, $context);
    }
}

if (!function_exists('doguify_log_critical')) {
    function doguify_log_critical($message, $context = array()) {
        return DoguifyLogger::getInstance()->critical($message, $context);
    }
}

// Hook para limpieza automática de logs
add_action('doguify_daily_cleanup', function() {
    DoguifyLogger::getInstance()->cleanupOldLogs(30);
});

// Hook para detectar errores críticos
add_action('init', function() {
    $logger = DoguifyLogger::getInstance();
    
    if ($logger->hasCriticalErrors(60)) {
        // Enviar notificación de errores críticos al admin
        $admin_email = doguify_config('admin_email', get_option('admin_email'));
        
        if ($admin_email && doguify_config('email_notifications', true)) {
            $error_summary = $logger->getErrorSummary(1);
            
            if (!empty($error_summary)) {
                $message = "<h2>Errores críticos detectados en Doguify Comparador</h2>";
                $message .= "<p>Se han detectado errores críticos en la última hora:</p>";
                $message .= "<ul>";
                
                foreach ($error_summary as $error) {
                    $message .= "<li><strong>{$error->message}</strong> (x{$error->occurrences})</li>";
                }
                
                $message .= "</ul>";
                $message .= "<p>Revisa el panel de administración para más detalles.</p>";
                
                DoguifyUtilities::send_notification_email(
                    $admin_email,
                    'Errores críticos en Doguify Comparador',
                    $message
                );
            }
        }
    }
});