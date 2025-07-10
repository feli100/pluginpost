<?php
/**
 * Vista de configuración del plugin
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap doguify-admin">
    <h1 class="wp-heading-inline">⚙️ Configuración - Doguify Comparador</h1>
    <a href="<?php echo admin_url('admin.php?page=doguify-comparador'); ?>" class="page-title-action">← Volver a Comparativas</a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ Configuración guardada correctamente.</p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="doguify-config-form">
        <?php wp_nonce_field('doguify_save_config', 'doguify_config_nonce'); ?>
        
        <!-- Configuración de Petplan -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>🔗 Integración con Petplan</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="petplan_enabled" name="petplan_enabled" value="1" 
                               <?php checked($config['petplan_enabled'], true); ?>>
                        <label for="petplan_enabled">Habilitar consultas a Petplan</label>
                    </div>
                    <p class="description">
                        Permite consultar precios automáticamente desde la API de Petplan durante el proceso de comparativa.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="petplan_timeout">Tiempo límite de consulta (segundos)</label>
                    <input type="number" id="petplan_timeout" name="petplan_timeout" 
                           value="<?php echo esc_attr($config['petplan_timeout'] ?? 30); ?>" 
                           min="10" max="120" step="5">
                    <p class="description">
                        Tiempo máximo de espera para las consultas a Petplan. Por defecto: 30 segundos.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Configuración de notificaciones -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>📧 Notificaciones por Email</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" value="1" 
                               <?php checked($config['email_notifications'], true); ?>>
                        <label for="email_notifications">Enviar notificaciones por email</label>
                    </div>
                    <p class="description">
                        Envía un email al administrador cada vez que se completa una nueva comparativa.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="admin_email">Email del administrador</label>
                    <input type="email" id="admin_email" name="admin_email" 
                           value="<?php echo esc_attr($config['admin_email']); ?>" required>
                    <p class="description">
                        Dirección de email donde se enviarán las notificaciones.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="user_confirmation_email" name="user_confirmation_email" value="1" 
                               <?php checked($config['user_confirmation_email'] ?? false, true); ?>>
                        <label for="user_confirmation_email">Enviar email de confirmación al usuario</label>
                    </div>
                    <p class="description">
                        Envía un email de confirmación al usuario con el resumen de su comparativa.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Personalización de páginas -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>🎨 Personalización de Páginas</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <label for="results_page_title">Título de la página de resultados</label>
                    <input type="text" id="results_page_title" name="results_page_title" 
                           value="<?php echo esc_attr($config['results_page_title']); ?>" 
                           maxlength="100">
                    <p class="description">
                        Título principal que se muestra en la página de resultados.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="results_page_subtitle">Subtítulo de la página de resultados</label>
                    <input type="text" id="results_page_subtitle" name="results_page_subtitle" 
                           value="<?php echo esc_attr($config['results_page_subtitle']); ?>" 
                           maxlength="200">
                    <p class="description">
                        Subtítulo que aparece debajo del título principal.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="waiting_page_message">Mensaje de la página de espera</label>
                    <textarea id="waiting_page_message" name="waiting_page_message" rows="3"><?php 
                        echo esc_textarea($config['waiting_page_message'] ?? 'Estamos trabajando con los mejores proveedores para encontrar las mejores opciones para tu mascota'); 
                    ?></textarea>
                    <p class="description">
                        Mensaje que se muestra durante el proceso de carga.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Configuración de razas -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>🐕 Gestión de Razas</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <label for="available_breeds">Razas disponibles (una por línea)</label>
                    <textarea id="available_breeds" name="available_breeds" rows="8"><?php 
                        $breeds = $config['available_breeds'] ?? "beagle\nlabrador\ngolden_retriever\npastor_aleman\nbulldog_frances\nchihuahua\nyorkshire\nboxer\ncocker_spaniel\nmestizo\notro";
                        echo esc_textarea($breeds); 
                    ?></textarea>
                    <p class="description">
                        Lista de razas que aparecerán en el formulario. Una raza por línea.
                        <br><strong>Formato:</strong> valor_interno (ej: pastor_aleman)
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="allow_custom_breed" name="allow_custom_breed" value="1" 
                               <?php checked($config['allow_custom_breed'] ?? true, true); ?>>
                        <label for="allow_custom_breed">Permitir campo "Otra raza"</label>
                    </div>
                    <p class="description">
                        Añade un campo de texto libre cuando el usuario selecciona "Otro".
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Configuración avanzada -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>🔧 Configuración Avanzada</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <label for="cache_duration">Duración de caché (minutos)</label>
                    <input type="number" id="cache_duration" name="cache_duration" 
                           value="<?php echo esc_attr($config['cache_duration'] ?? 60); ?>" 
                           min="1" max="1440" step="1">
                    <p class="description">
                        Tiempo que se mantienen en caché las consultas a Petplan. Por defecto: 60 minutos.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="debug_mode" name="debug_mode" value="1" 
                               <?php checked($config['debug_mode'] ?? false, true); ?>>
                        <label for="debug_mode">Modo debug</label>
                    </div>
                    <p class="description">
                        Activa logs detallados para depuración. Solo para desarrollo.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <div class="doguify-checkbox-group">
                        <input type="checkbox" id="gdpr_compliance" name="gdpr_compliance" value="1" 
                               <?php checked($config['gdpr_compliance'] ?? true, true); ?>>
                        <label for="gdpr_compliance">Cumplimiento GDPR</label>
                    </div>
                    <p class="description">
                        Añade funcionalidades para cumplir con el GDPR (eliminación automática de datos, etc.).
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="data_retention_days">Días de retención de datos</label>
                    <input type="number" id="data_retention_days" name="data_retention_days" 
                           value="<?php echo esc_attr($config['data_retention_days'] ?? 730); ?>" 
                           min="30" max="3650" step="1">
                    <p class="description">
                        Tiempo en días antes de eliminar automáticamente los datos. Por defecto: 730 días (2 años).
                    </p>
                </div>
            </div>
        </div>
        
        <!-- API Keys y integraciones -->
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>🔑 Integraciones y API Keys</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-form-group">
                    <label for="google_analytics_id">Google Analytics ID</label>
                    <input type="text" id="google_analytics_id" name="google_analytics_id" 
                           value="<?php echo esc_attr($config['google_analytics_id'] ?? ''); ?>" 
                           placeholder="G-XXXXXXXXXX">
                    <p class="description">
                        ID de Google Analytics para seguimiento de conversiones.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="facebook_pixel_id">Facebook Pixel ID</label>
                    <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" 
                           value="<?php echo esc_attr($config['facebook_pixel_id'] ?? ''); ?>" 
                           placeholder="123456789012345">
                    <p class="description">
                        ID del píxel de Facebook para seguimiento de conversiones.
                    </p>
                </div>
                
                <div class="doguify-form-group">
                    <label for="webhook_url">Webhook URL</label>
                    <input type="url" id="webhook_url" name="webhook_url" 
                           value="<?php echo esc_attr($config['webhook_url'] ?? ''); ?>" 
                           placeholder="https://ejemplo.com/webhook">
                    <p class="description">
                        URL donde enviar los datos de nuevas comparativas (opcional).
                    </p>
                </div>
            </div>
        </div>
        
        <div class="doguify-config-actions">
            <input type="hidden" name="save_config" value="1">
            <input type="submit" class="button-primary" value="💾 Guardar Configuración">
            <a href="<?php echo admin_url('admin.php?page=doguify-comparador'); ?>" class="button">Cancelar</a>
        </div>
    </form>
    
    <!-- Información del sistema -->
    <div class="doguify-config-section" style="margin-top: 30px;">
        <div class="doguify-config-header">
            <h3>ℹ️ Información del Sistema</h3>
        </div>
        <div class="doguify-config-body">
            <div class="doguify-system-info">
                <div class="doguify-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <strong>Versión del Plugin:</strong><br>
                        <?php echo DOGUIFY_PLUGIN_VERSION; ?>
                    </div>
                    <div>
                        <strong>Versión de WordPress:</strong><br>
                        <?php echo get_bloginfo('version'); ?>
                    </div>
                    <div>
                        <strong>Versión de PHP:</strong><br>
                        <?php echo PHP_VERSION; ?>
                    </div>
                    <div>
                        <strong>Base de Datos:</strong><br>
                        <?php 
                        global $wpdb;
                        echo $wpdb->prefix . 'doguify_comparativas';
                        ?>
                    </div>
                    <div>
                        <strong>URL del Plugin:</strong><br>
                        <code><?php echo DOGUIFY_PLUGIN_URL; ?></code>
                    </div>
                    <div>
                        <strong>Shortcode:</strong><br>
                        <code>[doguify_formulario]</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.doguify-config-form .doguify-config-section {
    margin-bottom: 25px;
}

.doguify-config-actions {
    padding: 20px 0;
    border-top: 1px solid var(--doguify-border);
    margin-top: 30px;
}

.doguify-config-actions .button-primary {
    margin-right: 10px;
}

.doguify-system-info {
    background: var(--doguify-light);
    padding: 20px;
    border-radius: 8px;
    font-size: 14px;
}

.doguify-system-info strong {
    color: var(--doguify-primary);
}

.doguify-system-info code {
    background: var(--doguify-border);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Validación del formulario
    $('.doguify-config-form').on('submit', function(e) {
        let isValid = true;
        
        // Validar email del administrador
        const adminEmail = $('#admin_email').val();
        if (!adminEmail || !isValidEmail(adminEmail)) {
            alert('Por favor, introduce un email válido para el administrador.');
            $('#admin_email').focus();
            isValid = false;
        }
        
        // Validar números positivos
        $('input[type="number"]').each(function() {
            const value = parseInt($(this).val());
            const min = parseInt($(this).attr('min'));
            if (value < min) {
                alert(`El valor de "${$(this).prev('label').text()}" debe ser mayor o igual a ${min}.`);
                $(this).focus();
                isValid = false;
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    // Previsualización de cambios
    $('#results_page_title, #results_page_subtitle').on('input', function() {
        const title = $('#results_page_title').val() || 'Título por defecto';
        const subtitle = $('#results_page_subtitle').val() || 'Subtítulo por defecto';
        
        if (!$('.doguify-preview').length) {
            $(this).closest('.doguify-config-body').append(`
                <div class="doguify-preview" style="margin-top: 15px; padding: 15px; background: #f0f0f0; border-radius: 8px;">
                    <strong>Vista previa:</strong><br>
                    <h2 style="margin: 10px 0 5px 0; color: var(--doguify-primary);" class="preview-title">${title}</h2>
                    <p style="margin: 0; color: #666;" class="preview-subtitle">${subtitle}</p>
                </div>
            `);
        } else {
            $('.preview-title').text(title);
            $('.preview-subtitle').text(subtitle);
        }
    });
});
</script>