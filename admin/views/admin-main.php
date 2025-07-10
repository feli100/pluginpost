<?php
/**
 * Vista principal del panel de administración
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap doguify-admin">
    <h1 class="wp-heading-inline">🐕 Doguify Comparador</h1>
    <a href="<?php echo admin_url('admin.php?page=doguify-config'); ?>" class="page-title-action">⚙️ Configuración</a>
    <a href="<?php echo admin_url('admin.php?page=doguify-stats'); ?>" class="page-title-action">📊 Estadísticas</a>
    <hr class="wp-header-end">
    
    <?php if (isset($_GET['message'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                switch ($_GET['message']) {
                    case 'deleted':
                        echo '✅ Registro eliminado correctamente.';
                        break;
                    case 'error':
                        echo '❌ Error al procesar la acción.';
                        break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Estadísticas básicas -->
    <div class="doguify-stats-cards">
        <div class="doguify-stat-card">
            <div class="doguify-stat-number"><?php echo number_format($stats['total']); ?></div>
            <div class="doguify-stat-label">Total Comparativas</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number"><?php echo number_format($stats['completadas']); ?></div>
            <div class="doguify-stat-label">Completadas</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number"><?php echo number_format($stats['pendientes']); ?></div>
            <div class="doguify-stat-label">Pendientes</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number"><?php echo number_format($stats['hoy']); ?></div>
            <div class="doguify-stat-label">Hoy</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number"><?php echo number_format($stats['esta_semana']); ?></div>
            <div class="doguify-stat-label">Esta Semana</div>
        </div>
    </div>
    
    <!-- Filtros y acciones -->
    <div class="doguify-toolbar">
        <div class="doguify-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="doguify-comparador">
                
                <select name="filter_estado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php selected($_GET['filter_estado'] ?? '', 'pendiente'); ?>>Pendientes</option>
                    <option value="completado" <?php selected($_GET['filter_estado'] ?? '', 'completado'); ?>>Completadas</option>
                </select>
                
                <input type="date" name="filter_fecha_desde" value="<?php echo esc_attr($_GET['filter_fecha_desde'] ?? ''); ?>" placeholder="Fecha desde">
                <input type="date" name="filter_fecha_hasta" value="<?php echo esc_attr($_GET['filter_fecha_hasta'] ?? ''); ?>" placeholder="Fecha hasta">
                
                <input type="submit" value="Filtrar" class="button">
                
                <?php if (!empty($_GET['filter_estado']) || !empty($_GET['filter_fecha_desde']) || !empty($_GET['filter_fecha_hasta'])): ?>
                    <a href="<?php echo admin_url('admin.php?page=doguify-comparador'); ?>" class="button">Limpiar filtros</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="doguify-actions">
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=doguify-comparador&action=export_csv'), 'doguify_export', 'nonce'); ?>" class="button button-secondary">
                📥 Exportar CSV
            </a>
        </div>
    </div>
    
    <!-- Tabla de registros -->
    <div class="doguify-table-container">
        <?php if (empty($registros)): ?>
            <div class="doguify-no-data">
                <div class="doguify-no-data-icon">📭</div>
                <h3>No hay registros</h3>
                <p>No se encontraron comparativas con los filtros aplicados.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped doguify-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-id">ID</th>
                        <th scope="col" class="column-mascota">Mascota</th>
                        <th scope="col" class="column-propietario">Propietario</th>
                        <th scope="col" class="column-ubicacion">Ubicación</th>
                        <th scope="col" class="column-precio">Precio</th>
                        <th scope="col" class="column-estado">Estado</th>
                        <th scope="col" class="column-fecha">Fecha</th>
                        <th scope="col" class="column-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                        <tr class="doguify-row doguify-estado-<?php echo esc_attr($registro->estado); ?>">
                            <td class="column-id">
                                <strong>#<?php echo $registro->id; ?></strong>
                            </td>
                            
                            <td class="column-mascota">
                                <div class="doguify-mascota-info">
                                    <div class="doguify-mascota-nombre">
                                        <?php echo $registro->tipo_mascota === 'perro' ? '🐕' : '🐱'; ?>
                                        <strong><?php echo esc_html($registro->nombre); ?></strong>
                                    </div>
                                    <div class="doguify-mascota-detalles">
                                        <?php echo ucfirst(esc_html($registro->raza)); ?> • 
                                        <?php
                                        $fecha_nacimiento = new DateTime("{$registro->edad_año}-{$registro->edad_mes}-{$registro->edad_dia}");
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fecha_nacimiento);
                                        echo $edad->y . ' años';
                                        ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="column-propietario">
                                <a href="mailto:<?php echo esc_attr($registro->email); ?>">
                                    <?php echo esc_html($registro->email); ?>
                                </a>
                            </td>
                            
                            <td class="column-ubicacion">
                                <code><?php echo esc_html($registro->codigo_postal); ?></code>
                            </td>
                            
                            <td class="column-precio">
                                <?php if ($registro->precio_petplan && $registro->precio_petplan > 0): ?>
                                    <strong class="doguify-precio">
                                        <?php echo number_format($registro->precio_petplan, 2); ?>€
                                    </strong>
                                    <div class="doguify-precio-mensual">
                                        <?php echo number_format($registro->precio_petplan / 12, 2); ?>€/mes
                                    </div>
                                <?php else: ?>
                                    <span class="doguify-sin-precio">Sin precio</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="column-estado">
                                <span class="doguify-estado-badge doguify-estado-<?php echo esc_attr($registro->estado); ?>">
                                    <?php
                                    switch ($registro->estado) {
                                        case 'pendiente':
                                            echo '⏳ Pendiente';
                                            break;
                                        case 'completado':
                                            echo '✅ Completado';
                                            break;
                                        default:
                                            echo ucfirst($registro->estado);
                                    }
                                    ?>
                                </span>
                            </td>
                            
                            <td class="column-fecha">
                                <div class="doguify-fecha">
                                    <?php echo date('d/m/Y', strtotime($registro->fecha_registro)); ?>
                                </div>
                                <div class="doguify-hora">
                                    <?php echo date('H:i', strtotime($registro->fecha_registro)); ?>
                                </div>
                            </td>
                            
                            <td class="column-acciones">
                                <div class="doguify-acciones">
                                    <button type="button" class="button button-small doguify-ver-detalles" data-id="<?php echo $registro->id; ?>">
                                        👁️ Ver
                                    </button>
                                    
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=doguify-comparador&action=delete&id=' . $registro->id), 'doguify_delete_' . $registro->id, 'nonce'); ?>" 
                                       class="button button-small button-link-delete doguify-eliminar"
                                       onclick="return confirm('¿Estás seguro de eliminar este registro?')">
                                        🗑️ Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1):
            ?>
                <div class="doguify-pagination">
                    <?php
                    $current_url = remove_query_arg('paged');
                    
                    if ($page > 1):
                        echo '<a href="' . add_query_arg('paged', $page - 1, $current_url) . '" class="button">← Anterior</a>';
                    endif;
                    
                    echo '<span class="doguify-pagination-info">';
                    echo 'Página ' . $page . ' de ' . $total_pages . ' (' . number_format($total) . ' registros)';
                    echo '</span>';
                    
                    if ($page < $total_pages):
                        echo '<a href="' . add_query_arg('paged', $page + 1, $current_url) . '" class="button">Siguiente →</a>';
                    endif;
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para ver detalles -->
<div id="doguify-modal" class="doguify-modal" style="display: none;">
    <div class="doguify-modal-content">
        <div class="doguify-modal-header">
            <h3>Detalles de la Comparativa</h3>
            <button type="button" class="doguify-modal-close">&times;</button>
        </div>
        <div class="doguify-modal-body">
            <!-- Contenido se carga via AJAX -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal para ver detalles
    $('.doguify-ver-detalles').on('click', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'doguify_get_details',
                id: id,
                nonce: '<?php echo wp_create_nonce('doguify_get_details'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#doguify-modal .doguify-modal-body').html(response.data);
                    $('#doguify-modal').show();
                }
            }
        });
    });
    
    // Cerrar modal
    $('.doguify-modal-close, .doguify-modal').on('click', function(e) {
        if (e.target === this) {
            $('#doguify-modal').hide();
        }
    });
    
    // Confirmar eliminación
    $('.doguify-eliminar').on('click', function(e) {
        if (!confirm('¿Estás seguro de que quieres eliminar este registro? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
    
    // Auto-refresh cada 30 segundos si hay registros pendientes
    <?php if ($stats['pendientes'] > 0): ?>
    setInterval(function() {
        location.reload();
    }, 30000);
    <?php endif; ?>
});
</script>