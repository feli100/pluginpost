<?php
/**
 * Webhooks e integraciones externas para Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class DoguifyWebhooks {
    
    public function __construct() {
        // Hooks para envío de webhooks
        add_action('doguify_after_save_comparison', array($this, 'send_comparison_webhook'), 10, 2);
        add_action('doguify_after_petplan_query', array($this, 'send_completion_webhook'), 10, 3);
        
        // Integración con Google Analytics
        add_action('wp_head', array($this, 'add_google_analytics'));
        
        // Integración con Facebook Pixel
        add_action('wp_head', array($this, 'add_facebook_pixel'));
        
        // Endpoints para webhooks entrantes
        add_action('rest_api_init', array($this, 'register_webhook_endpoints'));
        
        // Tracking de eventos
        add_action('wp_footer', array($this, 'add_event_tracking'));
    }
    
    /**
     * Enviar webhook cuando se guarda una comparativa
     */
    public function send_comparison_webhook($data, $comparison_id) {
        $webhook_url = doguify_config('webhook_url');
        
        if (empty($webhook_url)) {
            return;
        }
        
        $payload = array(
            'event' => 'comparison_started',
            'timestamp' => current_time('c'),
            'data' => array(
                'id' => $comparison_id,
                'session_id' => $data['session_id'] ?? null,
                'pet_type' => $data['tipo_mascota'],
                'pet_name' => $data['nombre'],
                'breed' => $data['raza'],
                'postal_code' => $data['codigo_postal'],
                'age' => array(
                    'day' => $data['edad_dia'],
                    'month' => $data['edad_mes'],
                    'year' => $data['edad_año']
                ),
                'email' => $data['email'],
                'ip_address' => $data['ip_address'] ?? null
            )
        );
        
        $this->send_webhook($webhook_url, $payload, 'comparison_started');
    }
    
    /**
     * Enviar webhook cuando se completa la consulta Petplan
     */
    public function send_completion_webhook($session_id, $price, $data) {
        $webhook_url = doguify_config('webhook_url');
        
        if (empty($webhook_url)) {
            return;
        }
        
        $payload = array(
            'event' => 'comparison_completed',
            'timestamp' => current_time('c'),
            'data' => array(
                'session_id' => $session_id,
                'price' => $price,
                'price_monthly' => $price ? round($price / 12, 2) : null,
                'pet_data' => $data
            )
        );
        
        $this->send_webhook($webhook_url, $payload, 'comparison_completed');
    }
    
    /**
     * Enviar webhook genérico
     */
    private function send_webhook($url, $payload, $event_type) {
        $logger = DoguifyLogger::getInstance();
        
        try {
            // Añadir headers de autenticación si están configurados
            $headers = array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Doguify-Comparador-Webhook/1.0'
            );
            
            // Añadir signature para verificación
            $signature = $this->generate_webhook_signature($payload);
            if ($signature) {
                $headers['X-Doguify-Signature'] = $signature;
            }
            
            $response = wp_remote_post($url, array(
                'timeout' => 15,
                'headers' => $headers,
                'body' => json_encode($payload)
            ));
            
            if (is_wp_error($response)) {
                $logger->error("Error enviando webhook {$event_type}: " . $response->get_error_message());
                return false;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($status_code >= 200 && $status_code < 300) {
                $logger->success("Webhook {$event_type} enviado exitosamente", array(
                    'url' => $url,
                    'status_code' => $status_code
                ));
                return true;
            } else {
                $logger->error("Webhook {$event_type} falló", array(
                    'url' => $url,
                    'status_code' => $status_code,
                    'response' => substr($response_body, 0, 500)
                ));
                return false;
            }
            
        } catch (Exception $e) {
            $logger->error("Excepción enviando webhook {$event_type}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar signature para webhook
     */
    private function generate_webhook_signature($payload) {
        $secret = doguify_config('webhook_secret');
        
        if (empty($secret)) {
            return null;
        }
        
        return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    }
    
    /**
     * Añadir Google Analytics
     */
    public function add_google_analytics() {
        $ga_id = doguify_config('google_analytics_id');
        
        if (empty($ga_id) || is_admin()) {
            return;
        }
        
        ?>
        <!-- Google Analytics - Doguify Comparador -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_js($ga_id); ?>');
            
            // Configurar eventos personalizados
            window.doguifyGA = {
                trackEvent: function(action, category, label, value) {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', action, {
                            event_category: category || 'Doguify',
                            event_label: label,
                            value: value
                        });
                    }
                },
                
                trackComparison: function(petType, breed, postalCode) {
                    this.trackEvent('comparison_started', 'Comparisons', petType + '_' + breed, postalCode);
                },
                
                trackResult: function(price, petType) {
                    this.trackEvent('comparison_completed', 'Conversions', petType, price);
                }
            };
        </script>
        <?php
    }
    
    /**
     * Añadir Facebook Pixel
     */
    public function add_facebook_pixel() {
        $pixel_id = doguify_config('facebook_pixel_id');
        
        if (empty($pixel_id) || is_admin()) {
            return;
        }
        
        ?>
        <!-- Facebook Pixel - Doguify Comparador -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            
            fbq('init', '<?php echo esc_js($pixel_id); ?>');
            fbq('track', 'PageView');
            
            // Configurar eventos personalizados
            window.doguifyFB = {
                trackLead: function(petType, value) {
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'Lead', {
                            content_category: 'Pet Insurance',
                            content_name: petType,
                            value: value,
                            currency: 'EUR'
                        });
                    }
                },
                
                trackPurchase: function(value, petType) {
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'Purchase', {
                            value: value,
                            currency: 'EUR',
                            content_type: 'product',
                            content_category: 'Pet Insurance',
                            content_name: petType
                        });
                    }
                }
            };
        </script>
        <noscript>
            <img height="1" width="1" style="display:none" 
                 src="https://www.facebook.com/tr?id=<?php echo esc_attr($pixel_id); ?>&ev=PageView&noscript=1"/>
        </noscript>
        <?php
    }
    
    /**
     * Registrar endpoints para webhooks entrantes
     */
    public function register_webhook_endpoints() {
        register_rest_route('doguify/v1', '/webhook/(?P<type>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_incoming_webhook'),
            'permission_callback' => array($this, 'verify_webhook_permission'),
            'args' => array(
                'type' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array('petplan', 'payment', 'notification'));
                    }
                )
            )
        ));
        
        // Endpoint para verificación de webhook
        register_rest_route('doguify/v1', '/webhook/verify', array(
            'methods' => 'GET',
            'callback' => array($this, 'verify_webhook_endpoint'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Manejar webhooks entrantes
     */
    public function handle_incoming_webhook($request) {
        $logger = DoguifyLogger::getInstance();
        $type = $request->get_param('type');
        $body = $request->get_body();
        $headers = $request->get_headers();
        
        try {
            // Verificar signature si está configurada
            if (!$this->verify_webhook_signature($body, $headers)) {
                $logger->warning('Webhook signature inválida', array('type' => $type));
                return new WP_Error('invalid_signature', 'Invalid webhook signature', array('status' => 401));
            }
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('invalid_json', 'Invalid JSON data', array('status' => 400));
            }
            
            $logger->info("Webhook entrante recibido: {$type}", array('data' => $data));
            
            // Procesar según el tipo
            switch ($type) {
                case 'petplan':
                    return $this->handle_petplan_webhook($data);
                case 'payment':
                    return $this->handle_payment_webhook($data);
                case 'notification':
                    return $this->handle_notification_webhook($data);
                default:
                    return new WP_Error('unknown_type', 'Unknown webhook type', array('status' => 400));
            }
            
        } catch (Exception $e) {
            $logger->error("Error procesando webhook {$type}: " . $e->getMessage());
            return new WP_Error('processing_error', 'Error processing webhook', array('status' => 500));
        }
    }
    
    /**
     * Verificar permisos de webhook
     */
    public function verify_webhook_permission($request) {
        // Para webhooks, verificamos la signature en lugar de permisos WP
        return true;
    }
    
    /**
     * Verificar signature de webhook entrante
     */
    private function verify_webhook_signature($body, $headers) {
        $secret = doguify_config('webhook_secret');
        
        if (empty($secret)) {
            return true; // Si no hay secret configurado, permitir
        }
        
        $signature = $headers['x_doguify_signature'][0] ?? $headers['x-doguify-signature'][0] ?? '';
        
        if (empty($signature)) {
            return false;
        }
        
        $expected_signature = 'sha256=' . hash_hmac('sha256', $body, $secret);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Manejar webhook de Petplan
     */
    private function handle_petplan_webhook($data) {
        // Procesar actualizaciones de precios de Petplan
        if (isset($data['session_id']) && isset($data['price'])) {
            global $wpdb;
            $tabla = $wpdb->prefix . 'doguify_comparativas';
            
            $updated = $wpdb->update(
                $tabla,
                array(
                    'precio_petplan' => floatval($data['price']),
                    'estado' => 'completado',
                    'fecha_consulta' => current_time('mysql')
                ),
                array('session_id' => $data['session_id']),
                array('%f', '%s', '%s'),
                array('%s')
            );
            
            if ($updated) {
                do_action('doguify_after_petplan_webhook', $data['session_id'], $data['price'], $data);
                return rest_ensure_response(array('status' => 'success', 'updated' => true));
            }
        }
        
        return rest_ensure_response(array('status' => 'success', 'updated' => false));
    }
    
    /**
     * Manejar webhook de pago
     */
    private function handle_payment_webhook($data) {
        // Procesar notificaciones de pago
        $logger = DoguifyLogger::getInstance();
        $logger->info('Payment webhook recibido', $data);
        
        // Aquí se procesarían los pagos si fuera necesario
        do_action('doguify_payment_webhook', $data);
        
        return rest_ensure_response(array('status' => 'success'));
    }
    
    /**
     * Manejar webhook de notificación
     */
    private function handle_notification_webhook($data) {
        // Procesar notificaciones generales
        $logger = DoguifyLogger::getInstance();
        $logger->info('Notification webhook recibido', $data);
        
        // Enviar notificación al admin si es importante
        if (isset($data['priority']) && $data['priority'] === 'high') {
            $admin_email = doguify_config('admin_email', get_option('admin_email'));
            
            if ($admin_email) {
                $subject = 'Notificación Importante - Doguify';
                $message = $data['message'] ?? 'Notificación recibida via webhook';
                
                DoguifyUtilities::send_notification_email($admin_email, $subject, $message);
            }
        }
        
        do_action('doguify_notification_webhook', $data);
        
        return rest_ensure_response(array('status' => 'success'));
    }
    
    /**
     * Endpoint de verificación
     */
    public function verify_webhook_endpoint($request) {
        $challenge = $request->get_param('hub_challenge');
        
        if ($challenge) {
            return rest_ensure_response($challenge);
        }
        
        return rest_ensure_response(array(
            'status' => 'ok',
            'timestamp' => current_time('c'),
            'version' => DOGUIFY_PLUGIN_VERSION
        ));
    }
    
    /**
     * Añadir tracking de eventos al footer
     */
    public function add_event_tracking() {
        // Solo en páginas con el formulario o páginas de resultado
        if (!$this->should_add_tracking()) {
            return;
        }
        
        ?>
        <script>
        // Event tracking para Doguify Comparador
        (function() {
            'use strict';
            
            // Tracking de formulario
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('doguify-formulario-comparativa');
                
                if (form) {
                    // Track form start
                    let formStarted = false;
                    
                    form.addEventListener('input', function() {
                        if (!formStarted) {
                            formStarted = true;
                            
                            // Google Analytics
                            if (window.doguifyGA) {
                                window.doguifyGA.trackEvent('form_started', 'Engagement', 'Comparison Form');
                            }
                            
                            // Facebook Pixel
                            if (window.doguifyFB && typeof fbq !== 'undefined') {
                                fbq('track', 'InitiateCheckout');
                            }
                        }
                    });
                    
                    // Track form submission
                    form.addEventListener('submit', function() {
                        const petType = form.querySelector('input[name="tipo_mascota"]:checked')?.value;
                        const breed = form.querySelector('select[name="raza"]')?.value;
                        const postalCode = form.querySelector('input[name="codigo_postal"]')?.value;
                        
                        // Google Analytics
                        if (window.doguifyGA) {
                            window.doguifyGA.trackComparison(petType, breed, postalCode);
                        }
                        
                        // Facebook Pixel
                        if (window.doguifyFB) {
                            window.doguifyFB.trackLead(petType, 0);
                        }
                    });
                }
                
                // Track resultado page
                if (window.location.pathname.includes('doguify-resultado')) {
                    const priceElement = document.querySelector('.doguify-price-amount');
                    
                    if (priceElement) {
                        const priceText = priceElement.textContent;
                        const price = parseFloat(priceText.replace(/[^\d.,]/g, '').replace(',', '.'));
                        
                        if (price > 0) {
                            // Google Analytics
                            if (window.doguifyGA) {
                                window.doguifyGA.trackResult(price, 'unknown');
                            }
                            
                            // Facebook Pixel
                            if (window.doguifyFB) {
                                window.doguifyFB.trackLead('pet_insurance', price);
                            }
                        }
                    }
                }
                
                // Track clicks en botones importantes
                document.addEventListener('click', function(e) {
                    const target = e.target;
                    
                    if (target.matches('.doguify-submit-btn')) {
                        // Ya se trackea en submit
                    } else if (target.matches('a[href*="calcula-seguro-mascota"]')) {
                        // Track click en botón de contratar
                        if (window.doguifyGA) {
                            window.doguifyGA.trackEvent('cta_click', 'Conversions', 'Contratar Ahora');
                        }
                        
                        if (window.doguifyFB && typeof fbq !== 'undefined') {
                            fbq('track', 'AddToCart');
                        }
                    }
                });
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Verificar si se debe añadir tracking
     */
    private function should_add_tracking() {
        global $post;
        
        // En páginas de Doguify
        if (strpos($_SERVER['REQUEST_URI'], 'doguify-') !== false) {
            return true;
        }
        
        // En páginas/posts con el shortcode
        if ($post && has_shortcode($post->post_content, 'doguify_formulario')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Integración con Zapier
     */
    public function send_zapier_webhook($trigger, $data) {
        $zapier_url = doguify_config('zapier_webhook_url');
        
        if (empty($zapier_url)) {
            return;
        }
        
        $payload = array(
            'trigger' => $trigger,
            'timestamp' => current_time('c'),
            'site_url' => home_url(),
            'data' => $data
        );
        
        wp_remote_post($zapier_url, array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload)
        ));
    }
    
    /**
     * Integración con Slack
     */
    public function send_slack_notification($message, $channel = null) {
        $slack_webhook = doguify_config('slack_webhook_url');
        
        if (empty($slack_webhook)) {
            return;
        }
        
        $payload = array(
            'text' => $message,
            'username' => 'Doguify Comparador',
            'icon_emoji' => ':dog:'
        );
        
        if ($channel) {
            $payload['channel'] = $channel;
        }
        
        wp_remote_post($slack_webhook, array(
            'timeout' => 10,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload)
        ));
    }
}

// Inicializar webhooks
new DoguifyWebhooks();

// Funciones auxiliares para integraciones
function doguify_send_webhook($event, $data) {
    do_action('doguify_send_webhook', $event, $data);
}

function doguify_track_event($action, $category = 'Doguify', $label = '', $value = null) {
    // Este JavaScript se ejecutará en el frontend
    if (!is_admin()) {
        add_action('wp_footer', function() use ($action, $category, $label, $value) {
            ?>
            <script>
                if (window.doguifyGA) {
                    window.doguifyGA.trackEvent('<?php echo esc_js($action); ?>', '<?php echo esc_js($category); ?>', '<?php echo esc_js($label); ?>', <?php echo json_encode($value); ?>);
                }
            </script>
            <?php
        });
    }
}