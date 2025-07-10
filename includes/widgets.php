<?php
/**
 * Widgets y elementos adicionales para Doguify Comparador
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget de Formulario de Comparativa
 */
class Doguify_Comparador_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'doguify_comparador_widget',
            '🐕 Doguify Comparador',
            array(
                'description' => 'Formulario de comparativa de seguros para mascotas'
            )
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Mostrar formulario
        echo do_shortcode('[doguify_formulario titulo="' . esc_attr($instance['form_title'] ?? 'Compara seguros') . '"]');
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Comparar Seguros';
        $form_title = !empty($instance['form_title']) ? $instance['form_title'] : 'Obtén tu comparativa';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Título del Widget:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('form_title')); ?>">Título del Formulario:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('form_title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('form_title')); ?>" 
                   type="text" value="<?php echo esc_attr($form_title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['form_title'] = (!empty($new_instance['form_title'])) ? sanitize_text_field($new_instance['form_title']) : '';
        
        return $instance;
    }
}

/**
 * Widget de Estadísticas
 */
class Doguify_Stats_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'doguify_stats_widget',
            '📊 Doguify Estadísticas',
            array(
                'description' => 'Muestra estadísticas del comparador de seguros'
            )
        );
    }
    
    public function widget($args, $instance) {
        // Solo mostrar a usuarios con permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $stats = DoguifyUtilities::get_quick_stats();
        ?>
        <div class="doguify-stats-widget">
            <div class="doguify-stat-item">
                <span class="doguify-stat-number"><?php echo number_format($stats['total']); ?></span>
                <span class="doguify-stat-label">Total Comparativas</span>
            </div>
            
            <div class="doguify-stat-item">
                <span class="doguify-stat-number"><?php echo number_format($stats['today']); ?></span>
                <span class="doguify-stat-label">Hoy</span>
            </div>
            
            <div class="doguify-stat-item">
                <span class="doguify-stat-number"><?php echo number_format($stats['pending']); ?></span>
                <span class="doguify-stat-label">Pendientes</span>
            </div>
            
            <div class="doguify-stat-item">
                <span class="doguify-stat-number"><?php echo number_format($stats['completed']); ?></span>
                <span class="doguify-stat-label">Completadas</span>
            </div>
        </div>
        
        <style>
        .doguify-stats-widget {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .doguify-stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }
        
        .doguify-stat-number {
            display: block;
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .doguify-stat-label {
            display: block;
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Estadísticas';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Título:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p><small>Este widget solo es visible para administradores.</small></p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        
        return $instance;
    }
}

/**
 * Bloque de Gutenberg para el formulario
 */
function doguify_register_gutenberg_block() {
    if (!function_exists('register_block_type')) {
        return;
    }
    
    wp_register_script(
        'doguify-block-editor',
        DOGUIFY_PLUGIN_URL . 'assets/block-editor.js',
        array('wp-blocks', 'wp-element', 'wp-editor'),
        DOGUIFY_PLUGIN_VERSION
    );
    
    register_block_type('doguify/comparador-formulario', array(
        'editor_script' => 'doguify-block-editor',
        'render_callback' => 'doguify_render_gutenberg_block',
        'attributes' => array(
            'titulo' => array(
                'type' => 'string',
                'default' => 'Compara seguros para tu mascota'
            )
        )
    ));
}

function doguify_render_gutenberg_block($attributes) {
    $titulo = $attributes['titulo'] ?? 'Compara seguros para tu mascota';
    return do_shortcode('[doguify_formulario titulo="' . esc_attr($titulo) . '"]');
}

/**
 * JavaScript para el bloque de Gutenberg
 */
function doguify_create_block_editor_js() {
    $block_js = "
    (function(blocks, element, editor) {
        var el = element.createElement;
        var InspectorControls = editor.InspectorControls;
        var TextControl = wp.components.TextControl;
        var PanelBody = wp.components.PanelBody;
        
        blocks.registerBlockType('doguify/comparador-formulario', {
            title: '🐕 Doguify Comparador',
            icon: 'pets',
            category: 'widgets',
            attributes: {
                titulo: {
                    type: 'string',
                    default: 'Compara seguros para tu mascota'
                }
            },
            
            edit: function(props) {
                var attributes = props.attributes;
                var setAttributes = props.setAttributes;
                
                function onTituloChange(newTitulo) {
                    setAttributes({titulo: newTitulo});
                }
                
                return [
                    el(InspectorControls, {},
                        el(PanelBody, {title: 'Configuración'},
                            el(TextControl, {
                                label: 'Título del formulario',
                                value: attributes.titulo,
                                onChange: onTituloChange
                            })
                        )
                    ),
                    el('div', {
                        className: 'doguify-block-preview',
                        style: {
                            padding: '20px',
                            border: '2px dashed #667eea',
                            borderRadius: '8px',
                            textAlign: 'center',
                            background: '#f8f9fa'
                        }
                    },
                        el('h3', {style: {color: '#667eea', margin: '0 0 10px 0'}}, '🐕 Formulario Doguify'),
                        el('p', {style: {margin: '0', color: '#666'}}, attributes.titulo),
                        el('small', {style: {color: '#999'}}, 'El formulario se mostrará aquí en el frontend')
                    )
                ];
            },
            
            save: function() {
                return null; // Renderizado por PHP
            }
        });
    })(
        window.wp.blocks,
        window.wp.element,
        window.wp.editor
    );
    ";
    
    file_put_contents(DOGUIFY_PLUGIN_PATH . 'assets/block-editor.js', $block_js);
}

/**
 * Dashboard Widget para WordPress Admin
 */
function doguify_add_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'doguify_dashboard_widget',
            '🐕 Doguify Comparador - Resumen',
            'doguify_dashboard_widget_content'
        );
    }
}

function doguify_dashboard_widget_content() {
    $stats = DoguifyUtilities::get_quick_stats();
    $health = DoguifyUtilities::system_health_check();
    
    ?>
    <div class="doguify-dashboard-widget">
        <div class="doguify-dashboard-stats">
            <div class="doguify-dashboard-stat">
                <span class="doguify-dashboard-number"><?php echo number_format($stats['total']); ?></span>
                <span class="doguify-dashboard-label">Total</span>
            </div>
            <div class="doguify-dashboard-stat">
                <span class="doguify-dashboard-number"><?php echo number_format($stats['today']); ?></span>
                <span class="doguify-dashboard-label">Hoy</span>
            </div>
            <div class="doguify-dashboard-stat">
                <span class="doguify-dashboard-number"><?php echo number_format($stats['pending']); ?></span>
                <span class="doguify-dashboard-label">Pendientes</span>
            </div>
        </div>
        
        <div class="doguify-dashboard-health">
            <h4>Estado del Sistema</h4>
            <?php foreach ($health as $component => $status): ?>
                <div class="doguify-health-item">
                    <span class="doguify-health-icon">
                        <?php if ($status === true): ?>
                            <span style="color: #46b450;">✅</span>
                        <?php elseif ($status === false): ?>
                            <span style="color: #dc3232;">❌</span>
                        <?php else: ?>
                            <span style="color: #ffb900;">⚠️</span>
                        <?php endif; ?>
                    </span>
                    <span class="doguify-health-label"><?php echo ucfirst(str_replace('_', ' ', $component)); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="doguify-dashboard-actions">
            <a href="<?php echo admin_url('admin.php?page=doguify-comparador'); ?>" class="button button-primary">
                Ver Todas las Comparativas
            </a>
            <a href="<?php echo admin_url('admin.php?page=doguify-stats'); ?>" class="button">
                Estadísticas Detalladas
            </a>
        </div>
    </div>
    
    <style>
    .doguify-dashboard-widget {
        font-size: 13px;
    }
    
    .doguify-dashboard-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        gap: 10px;
    }
    
    .doguify-dashboard-stat {
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        flex: 1;
        border-left: 3px solid #667eea;
    }
    
    .doguify-dashboard-number {
        display: block;
        font-size: 18px;
        font-weight: bold;
        color: #667eea;
    }
    
    .doguify-dashboard-label {
        display: block;
        color: #666;
        font-size: 11px;
        margin-top: 2px;
    }
    
    .doguify-dashboard-health h4 {
        margin: 10px 0 8px 0;
        font-size: 13px;
    }
    
    .doguify-health-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
        font-size: 12px;
    }
    
    .doguify-health-icon {
        margin-right: 8px;
        width: 16px;
    }
    
    .doguify-dashboard-actions {
        margin-top: 15px;
        display: flex;
        gap: 8px;
    }
    
    .doguify-dashboard-actions .button {
        font-size: 12px;
        height: 28px;
        line-height: 26px;
        padding: 0 10px;
    }
    </style>
    <?php
}

/**
 * Shortcode para mostrar estadísticas públicas
 */
function doguify_stats_shortcode($atts) {
    $atts = shortcode_atts(array(
        'tipo' => 'basicas',
        'periodo' => '30'
    ), $atts);
    
    if ($atts['tipo'] === 'basicas') {
        $stats = DoguifyUtilities::get_quick_stats();
        
        ob_start();
        ?>
        <div class="doguify-public-stats">
            <div class="doguify-public-stat">
                <span class="doguify-public-number"><?php echo number_format($stats['total']); ?></span>
                <span class="doguify-public-label">Comparativas Realizadas</span>
            </div>
            <div class="doguify-public-stat">
                <span class="doguify-public-number"><?php echo number_format($stats['completed']); ?></span>
                <span class="doguify-public-label">Usuarios Satisfechos</span>
            </div>
        </div>
        
        <style>
        .doguify-public-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
        }
        
        .doguify-public-stat {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            min-width: 150px;
        }
        
        .doguify-public-number {
            display: block;
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .doguify-public-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        @media (max-width: 600px) {
            .doguify-public-stats {
                flex-direction: column;
                align-items: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    return '';
}

/**
 * Shortcode para testimonios
 */
function doguify_testimonials_shortcode($atts) {
    $atts = shortcode_atts(array(
        'numero' => '3'
    ), $atts);
    
    // Testimonios hardcodeados (podrían venir de una tabla personalizada)
    $testimonios = array(
        array(
            'nombre' => 'María García',
            'mascota' => 'Max (Labrador)',
            'texto' => 'Encontré el mejor seguro para Max en minutos. El proceso fue súper fácil y transparente.',
            'rating' => 5
        ),
        array(
            'nombre' => 'Carlos Ruiz',
            'mascota' => 'Luna (Gato)',
            'texto' => 'Excelente servicio. Comparé varios seguros y elegí el que mejor se adaptaba a Luna.',
            'rating' => 5
        ),
        array(
            'nombre' => 'Ana López',
            'mascota' => 'Toby (Beagle)',
            'texto' => 'Recomendado 100%. Muy fácil de usar y precios muy competitivos.',
            'rating' => 4
        )
    );
    
    $numero = min(intval($atts['numero']), count($testimonios));
    $testimonios_mostrar = array_slice($testimonios, 0, $numero);
    
    ob_start();
    ?>
    <div class="doguify-testimonials">
        <?php foreach ($testimonios_mostrar as $testimonio): ?>
            <div class="doguify-testimonial">
                <div class="doguify-testimonial-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?php echo $i <= $testimonio['rating'] ? 'star-filled' : 'star-empty'; ?>">⭐</span>
                    <?php endfor; ?>
                </div>
                <p class="doguify-testimonial-text">"<?php echo esc_html($testimonio['texto']); ?>"</p>
                <div class="doguify-testimonial-author">
                    <strong><?php echo esc_html($testimonio['nombre']); ?></strong>
                    <span>con <?php echo esc_html($testimonio['mascota']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <style>
    .doguify-testimonials {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin: 30px 0;
    }
    
    .doguify-testimonial {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #667eea;
    }
    
    .doguify-testimonial-rating {
        margin-bottom: 15px;
    }
    
    .star-filled {
        color: #ffd700;
    }
    
    .star-empty {
        color: #ddd;
    }
    
    .doguify-testimonial-text {
        font-style: italic;
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .doguify-testimonial-author strong {
        color: #667eea;
    }
    
    .doguify-testimonial-author span {
        color: #666;
        font-size: 0.9em;
    }
    </style>
    <?php
    return ob_get_clean();
}

// Registrar widgets
function doguify_register_widgets() {
    register_widget('Doguify_Comparador_Widget');
    register_widget('Doguify_Stats_Widget');
}

// Hooks
add_action('widgets_init', 'doguify_register_widgets');
add_action('wp_dashboard_setup', 'doguify_add_dashboard_widget');
add_action('init', 'doguify_register_gutenberg_block');
add_action('init', 'doguify_create_block_editor_js');

// Shortcodes
add_shortcode('doguify_stats', 'doguify_stats_shortcode');
add_shortcode('doguify_testimonios', 'doguify_testimonials_shortcode');