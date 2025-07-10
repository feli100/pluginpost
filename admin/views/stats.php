<?php
/**
 * Vista de estadísticas del plugin
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap doguify-admin">
    <h1 class="wp-heading-inline">📊 Estadísticas - Doguify Comparador</h1>
    <a href="<?php echo admin_url('admin.php?page=doguify-comparador'); ?>" class="page-title-action">← Volver a Comparativas</a>
    <a href="<?php echo admin_url('admin.php?page=doguify-config'); ?>" class="page-title-action">⚙️ Configuración</a>
    <hr class="wp-header-end">
    
    <!-- Estadísticas generales -->
    <div class="doguify-stats-cards">
        <div class="doguify-stat-card">
            <div class="doguify-stat-number" data-stat="total"><?php echo number_format($stats['total_comparativas']); ?></div>
            <div class="doguify-stat-label">Total Comparativas</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number" data-stat="hoy"><?php echo number_format($stats['comparativas_hoy']); ?></div>
            <div class="doguify-stat-label">Hoy</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number" data-stat="mes"><?php echo number_format($stats['comparativas_mes']); ?></div>
            <div class="doguify-stat-label">Este Mes</div>
        </div>
        
        <div class="doguify-stat-card">
            <div class="doguify-stat-number">
                <?php echo $stats['precio_promedio'] ? number_format($stats['precio_promedio'], 2) . '€' : 'N/A'; ?>
            </div>
            <div class="doguify-stat-label">Precio Promedio</div>
        </div>
    </div>
    
    <!-- Gráficos y estadísticas detalladas -->
    <div class="doguify-stats-grid">
        <!-- Distribución por tipo de mascota -->
        <div class="doguify-chart-container">
            <h3 class="doguify-chart-title">🐕🐱 Distribución por Tipo de Mascota</h3>
            <div id="chart-tipo-mascota"></div>
            
            <div class="doguify-stats-list">
                <ul class="doguify-list">
                    <?php foreach ($stats['por_tipo_mascota'] as $item): ?>
                        <li class="doguify-list-item">
                            <span class="doguify-list-label">
                                <?php echo $item->tipo_mascota === 'perro' ? '🐕 Perros' : '🐱 Gatos'; ?>
                            </span>
                            <span class="doguify-list-value"><?php echo number_format($item->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Top razas -->
        <div class="doguify-chart-container">
            <h3 class="doguify-chart-title">🏆 Top 10 Razas Más Consultadas</h3>
            <div id="chart-razas"></div>
            
            <div class="doguify-stats-list">
                <ul class="doguify-list">
                    <?php foreach (array_slice($stats['por_raza'], 0, 10) as $index => $item): ?>
                        <li class="doguify-list-item">
                            <span class="doguify-list-label">
                                #<?php echo $index + 1; ?> <?php echo ucfirst(str_replace('_', ' ', $item->raza)); ?>
                            </span>
                            <span class="doguify-list-value"><?php echo number_format($item->total); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Evolución mensual -->
        <div class="doguify-chart-container" style="grid-column: 1 / -1;">
            <h3 class="doguify-chart-title">📈 Evolución de Comparativas (Últimos 12 Meses)</h3>
            <div id="chart-evolucion"></div>
            
            <div class="doguify-stats-summary">
                <div class="doguify-summary-grid">
                    <?php 
                    $total_anual = array_sum(array_column($stats['por_mes'], 'total'));
                    $promedio_mensual = $total_anual / max(count($stats['por_mes']), 1);
                    $mejor_mes = !empty($stats['por_mes']) ? max($stats['por_mes']) : null;
                    ?>
                    <div class="doguify-summary-item">
                        <div class="doguify-summary-number"><?php echo number_format($total_anual); ?></div>
                        <div class="doguify-summary-label">Total Anual</div>
                    </div>
                    <div class="doguify-summary-item">
                        <div class="doguify-summary-number"><?php echo number_format($promedio_mensual, 1); ?></div>
                        <div class="doguify-summary-label">Promedio Mensual</div>
                    </div>
                    <?php if ($mejor_mes): ?>
                    <div class="doguify-summary-item">
                        <div class="doguify-summary-number"><?php echo number_format($mejor_mes->total); ?></div>
                        <div class="doguify-summary-label">
                            Mejor Mes (<?php echo date('M Y', strtotime($mejor_mes->mes . '-01')); ?>)
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Estados de conversión -->
        <div class="doguify-chart-container">
            <h3 class="doguify-chart-title">🎯 Estados de Conversión</h3>
            <div id="chart-conversiones"></div>
            
            <div class="doguify-stats-list">
                <ul class="doguify-list">
                    <?php 
                    $total_conversiones = array_sum(array_column($stats['conversiones'], 'total'));
                    foreach ($stats['conversiones'] as $item): 
                        $porcentaje = $total_conversiones > 0 ? ($item->total / $total_conversiones) * 100 : 0;
                    ?>
                        <li class="doguify-list-item">
                            <span class="doguify-list-label">
                                <?php
                                switch ($item->estado) {
                                    case 'pendiente':
                                        echo '⏳ Pendientes';
                                        break;
                                    case 'completado':
                                        echo '✅ Completadas';
                                        break;
                                    default:
                                        echo ucfirst($item->estado);
                                }
                                ?>
                            </span>
                            <span class="doguify-list-value">
                                <?php echo number_format($item->total); ?> 
                                (<?php echo number_format($porcentaje, 1); ?>%)
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- Análisis de precios -->
        <div class="doguify-chart-container">
            <h3 class="doguify-chart-title">💰 Análisis de Precios</h3>
            
            <?php
            global $wpdb;
            $tabla = $wpdb->prefix . 'doguify_comparativas';
            
            $precio_stats = $wpdb->get_row("
                SELECT 
                    MIN(precio_petplan) as precio_min,
                    MAX(precio_petplan) as precio_max,
                    AVG(precio_petplan) as precio_promedio,
                    COUNT(*) as total_con_precio
                FROM $tabla 
                WHERE precio_petplan IS NOT NULL AND precio_petplan > 0
            ");
            
            $rangos_precio = $wpdb->get_results("
                SELECT 
                    CASE 
                        WHEN precio_petplan < 300 THEN 'Menos de 300€'
                        WHEN precio_petplan < 500 THEN '300€ - 500€'
                        WHEN precio_petplan < 700 THEN '500€ - 700€'
                        WHEN precio_petplan < 1000 THEN '700€ - 1000€'
                        ELSE 'Más de 1000€'
                    END as rango,
                    COUNT(*) as total
                FROM $tabla 
                WHERE precio_petplan IS NOT NULL AND precio_petplan > 0
                GROUP BY 1
                ORDER BY MIN(precio_petplan)
            ");
            ?>
            
            <div class="doguify-price-analysis">
                <?php if ($precio_stats && $precio_stats->total_con_precio > 0): ?>
                    <div class="doguify-price-stats">
                        <div class="doguify-price-stat">
                            <div class="doguify-price-value"><?php echo number_format($precio_stats->precio_min, 2); ?>€</div>
                            <div class="doguify-price-label">Precio Mínimo</div>
                        </div>
                        <div class="doguify-price-stat">
                            <div class="doguify-price-value"><?php echo number_format($precio_stats->precio_max, 2); ?>€</div>
                            <div class="doguify-price-label">Precio Máximo</div>
                        </div>
                        <div class="doguify-price-stat">
                            <div class="doguify-price-value"><?php echo number_format($precio_stats->precio_promedio, 2); ?>€</div>
                            <div class="doguify-price-label">Precio Promedio</div>
                        </div>
                    </div>
                    
                    <div class="doguify-price-ranges">
                        <h4>Distribución por Rangos de Precio</h4>
                        <ul class="doguify-list">
                            <?php foreach ($rangos_precio as $rango): ?>
                                <li class="doguify-list-item">
                                    <span class="doguify-list-label"><?php echo $rango->rango; ?></span>
                                    <span class="doguify-list-value"><?php echo number_format($rango->total); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="doguify-no-price-data">
                        <p>📊 No hay suficientes datos de precios para mostrar estadísticas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Resumen ejecutivo -->
    <div class="doguify-executive-summary">
        <div class="doguify-config-section">
            <div class="doguify-config-header">
                <h3>📋 Resumen Ejecutivo</h3>
            </div>
            <div class="doguify-config-body">
                <div class="doguify-insights">
                    <?php
                    // Generar insights automáticos
                    $insights = array();
                    
                    if ($stats['total_comparativas'] > 0) {
                        $tasa_conversion = 0;
                        foreach ($stats['conversiones'] as $conv) {
                            if ($conv->estado === 'completado') {
                                $tasa_conversion = ($conv->total / $stats['total_comparativas']) * 100;
                                break;
                            }
                        }
                        
                        $insights[] = "Tasa de conversión actual: " . number_format($tasa_conversion, 1) . "%";
                        
                        if ($stats['comparativas_hoy'] > 0) {
                            $insights[] = "Se han realizado " . $stats['comparativas_hoy'] . " comparativas hoy";
                        }
                        
                        if (!empty($stats['por_tipo_mascota'])) {
                            $tipo_popular = $stats['por_tipo_mascota'][0];
                            $porcentaje_tipo = ($tipo_popular->total / $stats['total_comparativas']) * 100;
                            $insights[] = ucfirst($tipo_popular->tipo_mascota) . "s representan el " . number_format($porcentaje_tipo, 1) . "% de las consultas";
                        }
                        
                        if (!empty($stats['por_raza'])) {
                            $raza_popular = $stats['por_raza'][0];
                            $insights[] = "La raza más consultada es: " . ucfirst(str_replace('_', ' ', $raza_popular->raza)) . " (" . $raza_popular->total . " consultas)";
                        }
                        
                        if ($precio_stats && $precio_stats->precio_promedio > 0) {
                            $precio_mensual = $precio_stats->precio_promedio / 12;
                            $insights[] = "Precio promedio mensual estimado: " . number_format($precio_mensual, 2) . "€";
                        }
                    } else {
                        $insights[] = "Aún no hay datos suficientes para generar insights";
                    }
                    ?>
                    
                    <ul class="doguify-insights-list">
                        <?php foreach ($insights as $insight): ?>
                            <li class="doguify-insight-item">
                                <span class="doguify-insight-icon">💡</span>
                                <?php echo $insight; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="doguify-recommendations">
                        <h4>🎯 Recomendaciones</h4>
                        <ul class="doguify-recommendations-list">
                            <?php if ($tasa_conversion < 70): ?>
                                <li>⚠️ La tasa de conversión es baja. Considera optimizar el proceso de comparativa.</li>
                            <?php endif; ?>
                            
                            <?php if ($stats['comparativas_hoy'] < 5): ?>
                                <li>📈 Considera implementar estrategias de marketing para aumentar el tráfico.</li>
                            <?php endif; ?>
                            
                            <?php if (count($stats['por_raza']) < 5): ?>
                                <li>🐕 Añade más razas al formulario para captar un público más amplio.</li>
                            <?php endif; ?>
                            
                            <li>✅ Exporta regularmente los datos para análisis externos.</li>
                            <li>📊 Revisa estas estadísticas semanalmente para identificar tendencias.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.doguify-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.doguify-summary-item {
    text-align: center;
    padding: 15px;
    background: var(--doguify-light);
    border-radius: 8px;
}

.doguify-summary-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--doguify-primary);
}

.doguify-summary-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.doguify-price-analysis {
    padding: 20px;
}

.doguify-price-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.doguify-price-stat {
    text-align: center;
    padding: 15px;
    background: var(--doguify-light);
    border-radius: 8px;
}

.doguify-price-value {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--doguify-success);
}

.doguify-price-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.doguify-price-ranges h4 {
    margin-bottom: 15px;
    color: var(--doguify-dark);
}

.doguify-no-price-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.doguify-executive-summary {
    margin-top: 30px;
}

.doguify-insights-list {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
}

.doguify-insight-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.doguify-insight-item:last-child {
    border-bottom: none;
}

.doguify-insight-icon {
    font-size: 16px;
    flex-shrink: 0;
}

.doguify-recommendations h4 {
    color: var(--doguify-primary);
    margin-bottom: 15px;
}

.doguify-recommendations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.doguify-recommendations-list li {
    padding: 8px 0;
    font-size: 14px;
    color: #666;
}

@media (max-width: 768px) {
    .doguify-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .doguify-price-stats {
        grid-template-columns: 1fr;
    }
    
    .doguify-summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Datos para los gráficos
    const tipoMascotaData = <?php echo json_encode(array_map(function($item) {
        return array('label' => ucfirst($item->tipo_mascota), 'value' => intval($item->total));
    }, $stats['por_tipo_mascota'])); ?>;
    
    const razasData = <?php echo json_encode(array_map(function($item) {
        return array('label' => ucfirst(str_replace('_', ' ', $item->raza)), 'value' => intval($item->total));
    }, array_slice($stats['por_raza'], 0, 5))); ?>;
    
    const evolucionData = <?php echo json_encode(array_map(function($item) {
        return array('label' => date('M Y', strtotime($item->mes . '-01')), 'value' => intval($item->total));
    }, $stats['por_mes'])); ?>;
    
    const conversionesData = <?php echo json_encode(array_map(function($item) {
        return array('label' => ucfirst($item->estado), 'value' => intval($item->total));
    }, $stats['conversiones'])); ?>;
    
    // Crear gráficos
    if (typeof window.DoguifyCharts !== 'undefined') {
        window.DoguifyCharts.createPieChart('#chart-tipo-mascota', tipoMascotaData, 'Tipo de Mascota');
        window.DoguifyCharts.createBarChart('#chart-razas', razasData, 'Top 5 Razas');
        window.DoguifyCharts.createBarChart('#chart-evolucion', evolucionData, 'Evolución Mensual');
        window.DoguifyCharts.createPieChart('#chart-conversiones', conversionesData, 'Estados');
    }
    
    // Actualizar estadísticas automáticamente cada 60 segundos
    setInterval(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'doguify_refresh_stats',
                nonce: '<?php echo wp_create_nonce('doguify_refresh_stats'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar números
                    $('[data-stat="total"]').text(parseInt(response.data.total).toLocaleString());
                    $('[data-stat="hoy"]').text(parseInt(response.data.hoy).toLocaleString());
                    $('[data-stat="mes"]').text(parseInt(response.data.mes).toLocaleString());
                    
                    // Highlight de cambios
                    $('[data-stat]').addClass('doguify-stat-updated');
                    setTimeout(function() {
                        $('[data-stat]').removeClass('doguify-stat-updated');
                    }, 2000);
                }
            }
        });
    }, 60000);
});
</script>