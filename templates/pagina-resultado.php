<?php
/**
 * Template de la página de resultados
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$session_id = get_query_var('session_id');

if (empty($session_id)) {
    wp_redirect(home_url());
    exit;
}

// Obtener datos de la base de datos
global $wpdb;
$tabla = $wpdb->prefix . 'doguify_comparativas';

$datos = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla WHERE session_id = %s",
    $session_id
));

if (!$datos) {
    wp_redirect(home_url());
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Tu comparativa de seguro - <?php bloginfo('name'); ?></title>
    
    <?php wp_head(); ?>
    
    <style>
        /* Reset y base */
        html, body {
            margin: 0;
            padding: 0;
            background: #f8f9fa;
            font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .doguify-results-page {
            min-height: 100vh;
            padding: 20px;
        }
        
        .doguify-results-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Header */
        .doguify-results-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .doguify-results-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: doguify-bg-move 20s linear infinite;
        }
        
        @keyframes doguify-bg-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 30px); }
        }
        
        .doguify-results-content {
            position: relative;
            z-index: 1;
        }
        
        .doguify-results-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .doguify-results-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .doguify-mascot-info-brief {
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 25px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        /* Cards principales */
        .doguify-main-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        /* Card de precio */
        .doguify-price-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            border: 3px solid transparent;
            background-clip: padding-box;
        }
        
        .doguify-price-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2ecc71, #27ae60);
        }
        
        .doguify-price-header {
            margin-bottom: 20px;
        }
        
        .doguify-price-label {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .doguify-price-amount {
            font-size: 3.5rem;
            font-weight: 700;
            color: #2ecc71;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .doguify-price-period {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .doguify-price-features {
            text-align: left;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .doguify-feature {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .doguify-feature::before {
            content: '✅';
            margin-right: 10px;
            font-size: 1rem;
        }
        
        /* Card de información de mascota */
        .doguify-mascot-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .doguify-mascot-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .doguify-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .doguify-card-title::before {
            content: '🐕';
            font-size: 2rem;
            margin-right: 15px;
        }
        
        .doguify-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .doguify-info-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .doguify-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .doguify-info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .doguify-info-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        /* Sección de acciones */
        .doguify-actions-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .doguify-actions-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .doguify-actions-subtitle {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .doguify-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Botones */
        .doguify-btn {
            display: inline-flex;
            align-items: center;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 180px;
            justify-content: center;
        }
        
        .doguify-btn-primary {
            background: linear-gradient(135deg, #F26419, #e05a17);
            color: white;
            box-shadow: 0 4px 15px rgba(242, 100, 25, 0.3);
        }
        
        .doguify-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(242, 100, 25, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .doguify-btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .doguify-btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .doguify-btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .doguify-btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            text-decoration: none;
        }
        
        /* Disclaimer */
        .doguify-disclaimer {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #856404;
            line-height: 1.5;
        }
        
        .doguify-disclaimer strong {
            color: #6b4423;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .doguify-results-page {
                padding: 10px;
            }
            
            .doguify-results-header {
                padding: 30px 20px;
            }
            
            .doguify-results-title {
                font-size: 2rem;
            }
            
            .doguify-main-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .doguify-price-card,
            .doguify-mascot-card,
            .doguify-actions-section {
                padding: 30px 20px;
            }
            
            .doguify-price-amount {
                font-size: 2.5rem;
            }
            
            .doguify-info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .doguify-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .doguify-btn {
                width: 100%;
                max-width: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .doguify-results-header {
                padding: 25px 15px;
            }
            
            .doguify-results-title {
                font-size: 1.8rem;
            }
            
            .doguify-price-card,
            .doguify-mascot-card,
            .doguify-actions-section {
                padding: 25px 15px;
            }
            
            .doguify-price-amount {
                font-size: 2rem;
            }
            
            .doguify-mascot-info-brief {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
        
        /* Animaciones */
        @keyframes doguify-fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .doguify-results-container > * {
            animation: doguify-fadeInUp 0.6s ease-out forwards;
        }
        
        .doguify-results-header {
            animation-delay: 0.1s;
        }
        
        .doguify-main-cards {
            animation-delay: 0.2s;
        }
        
        .doguify-actions-section {
            animation-delay: 0.3s;
        }
        
        /* Efectos especiales */
        .doguify-price-amount {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .doguify-sparkle {
            position: relative;
        }
        
        .doguify-sparkle::after {
            content: '✨';
            position: absolute;
            top: -10px;
            right: -20px;
            font-size: 1.5rem;
            animation: doguify-sparkle 2s infinite;
        }
        
        @keyframes doguify-sparkle {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1) rotate(0deg); 
            }
            50% { 
                opacity: 0.7; 
                transform: scale(1.2) rotate(180deg); 
            }
        }
    </style>
</head>
<body>
    <div class="doguify-results-page">
        <div class="doguify-results-container">
            <!-- Header -->
            <div class="doguify-results-header">
                <div class="doguify-results-content">
                    <h1 class="doguify-results-title">¡Tu comparativa está lista!</h1>
                    <p class="doguify-results-subtitle">
                        Hemos encontrado la mejor opción para <?php echo esc_html($datos->nombre); ?>
                    </p>
                    <div class="doguify-mascot-info-brief">
                        🐕 <?php echo ucfirst(esc_html($datos->tipo_mascota)); ?> • 
                        <?php echo ucfirst(esc_html($datos->raza)); ?> • 
                        CP: <?php echo esc_html($datos->codigo_postal); ?>
                    </div>
                </div>
            </div>
            
            <!-- Cards principales -->
            <div class="doguify-main-cards">
                <!-- Card de precio -->
                <div class="doguify-price-card">
                    <div class="doguify-price-header">
                        <div class="doguify-price-label">Precio estimado mensual</div>
                        <div class="doguify-price-amount doguify-sparkle">
                            <?php if ($datos->precio_petplan && $datos->precio_petplan > 0): ?>
                                <?php echo number_format($datos->precio_petplan / 12, 2); ?>€
                            <?php else: ?>
                                Consultar
                            <?php endif; ?>
                        </div>
                        <div class="doguify-price-period">por mes</div>
                    </div>
                    
                    <?php if ($datos->precio_petplan && $datos->precio_petplan > 0): ?>
                    <div class="doguify-price-features">
                        <div class="doguify-feature">Cobertura veterinaria completa</div>
                        <div class="doguify-feature">Urgencias 24/7</div>
                        <div class="doguify-feature">Tratamientos especializados</div>
                        <div class="doguify-feature">Sin periodo de carencia</div>
                        <div class="doguify-feature">Reembolso hasta 80%</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Card de información de mascota -->
                <div class="doguify-mascot-card">
                    <h3 class="doguify-card-title">Información de tu mascota</h3>
                    
                    <div class="doguify-info-grid">
                        <div class="doguify-info-item">
                            <div class="doguify-info-label">Nombre</div>
                            <div class="doguify-info-value"><?php echo esc_html($datos->nombre); ?></div>
                        </div>
                        
                        <div class="doguify-info-item">
                            <div class="doguify-info-label">Tipo</div>
                            <div class="doguify-info-value">
                                <?php echo $datos->tipo_mascota === 'perro' ? '🐕 Perro' : '🐱 Gato'; ?>
                            </div>
                        </div>
                        
                        <div class="doguify-info-item">
                            <div class="doguify-info-label">Raza</div>
                            <div class="doguify-info-value"><?php echo ucfirst(esc_html($datos->raza)); ?></div>
                        </div>
                        
                        <div class="doguify-info-item">
                            <div class="doguify-info-label">Edad</div>
                            <div class="doguify-info-value">
                                <?php
                                $fecha_nacimiento = new DateTime("{$datos->edad_año}-{$datos->edad_mes}-{$datos->edad_dia}");
                                $hoy = new DateTime();
                                $edad = $hoy->diff($fecha_nacimiento);
                                
                                if ($edad->y > 0) {
                                    echo $edad->y . ' año' . ($edad->y > 1 ? 's' : '');
                                    if ($edad->m > 0) {
                                        echo ' y ' . $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
                                    }
                                } elseif ($edad->m > 0) {
                                    echo $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
                                } else {
                                    echo $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de acciones -->
            <div class="doguify-actions-section">
                <h3 class="doguify-actions-title">¿Qué quieres hacer ahora?</h3>
                <p class="doguify-actions-subtitle">
                    Puedes contratar directamente este seguro o recibir más información personalizada por email
                </p>
                
                <div class="doguify-actions">
                    <a href="https://doguify.web.app/calcula-seguro-mascota/" target="_blank" class="doguify-btn doguify-btn-primary">
                        🚀 Contratar ahora
                    </a>
                    
                    <a href="mailto:info@doguify.com?subject=Consulta sobre seguro para <?php echo esc_attr($datos->nombre); ?>&body=Hola, me interesa obtener más información sobre el seguro para mi <?php echo esc_attr($datos->tipo_mascota); ?> <?php echo esc_attr($datos->nombre); ?> (<?php echo esc_attr($datos->raza); ?>)." class="doguify-btn doguify-btn-outline">
                        📧 Más información
                    </a>
                    
                    <a href="<?php echo home_url(); ?>" class="doguify-btn doguify-btn-secondary">
                        🏠 Volver al inicio
                    </a>
                </div>
            </div>
            
            <!-- Disclaimer -->
            <div class="doguify-disclaimer">
                <strong>Información importante:</strong> 
                Los precios mostrados son estimaciones basadas en la información proporcionada y pueden variar según las condiciones específicas de la póliza. 
                Para obtener una cotización exacta, te recomendamos contactar directamente con la aseguradora. 
                Esta herramienta es únicamente informativa y no constituye una oferta vinculante.
            </div>
        </div>
    </div>

    <script>
        // Verificar si hay datos en sessionStorage
        const resultsData = sessionStorage.getItem('doguify_results');
        if (resultsData) {
            try {
                const data = JSON.parse(resultsData);
                console.log('Datos de la comparativa:', data);
                
                // Limpiar sessionStorage
                sessionStorage.removeItem('doguify_results');
            } catch (e) {
                console.error('Error al procesar datos de resultados:', e);
            }
        }
        
        // Prevenir navegación hacia atrás a la página de espera
        if (document.referrer.includes('doguify-espera')) {
            history.pushState(null, null, location.href);
            window.onpopstate = function () {
                history.go(1);
            };
        }
        
        // Animaciones adicionales
        document.addEventListener('DOMContentLoaded', function() {
            // Animar contador de precio
            const priceElement = document.querySelector('.doguify-price-amount');
            if (priceElement && priceElement.textContent !== 'Consultar') {
                const finalPrice = parseFloat(priceElement.textContent.replace('€', ''));
                let currentPrice = 0;
                const increment = finalPrice / 50;
                
                const timer = setInterval(() => {
                    currentPrice += increment;
                    if (currentPrice >= finalPrice) {
                        currentPrice = finalPrice;
                        clearInterval(timer);
                    }
                    priceElement.textContent = currentPrice.toFixed(2) + '€';
                }, 30);
            }
            
            // Efecto de hover en las cards
            const cards = document.querySelectorAll('.doguify-price-card, .doguify-mascot-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 15px 50px rgba(0, 0, 0, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 10px 40px rgba(0, 0, 0, 0.1)';
                });
            });
        });
        
        // Tracking de eventos (opcional)
        function trackEvent(eventName, properties = {}) {
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, properties);
            }
            console.log('Event tracked:', eventName, properties);
        }
        
        // Track cuando se carga la página de resultados
        trackEvent('comparison_completed', {
            'pet_type': '<?php echo esc_js($datos->tipo_mascota); ?>',
            'breed': '<?php echo esc_js($datos->raza); ?>',
            'postal_code': '<?php echo esc_js($datos->codigo_postal); ?>',
            'price': <?php echo $datos->precio_petplan ? floatval($datos->precio_petplan) : 0; ?>
        });
        
        // Track clicks en botones
        document.querySelectorAll('.doguify-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.textContent.trim();
                trackEvent('result_action_clicked', {
                    'action': action,
                    'session_id': '<?php echo esc_js($session_id); ?>'
                });
            });
        });
    </script>

    <?php wp_footer(); ?>
</body>
</html>