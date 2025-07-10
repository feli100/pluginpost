// Doguify Admin JavaScript
(function($) {
    'use strict';
    
    class DoguifyAdmin {
        constructor() {
            this.init();
        }
        
        init() {
            $(document).ready(() => {
                this.initModal();
                this.initFilters();
                this.initStats();
                this.initNotifications();
                this.initTooltips();
            });
        }
        
        initModal() {
            // Abrir modal de detalles
            $(document).on('click', '.doguify-ver-detalles', (e) => {
                e.preventDefault();
                const id = $(e.target).data('id');
                this.loadDetails(id);
            });
            
            // Cerrar modal
            $(document).on('click', '.doguify-modal-close, .doguify-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });
            
            // Cerrar con ESC
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            });
        }
        
        loadDetails(id) {
            const $modal = $('#doguify-modal');
            const $body = $modal.find('.doguify-modal-body');
            
            $body.html('<div class="doguify-loading">🔄 Cargando...</div>');
            $modal.show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'doguify_get_details',
                    id: id,
                    nonce: doguify_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $body.html(response.data);
                    } else {
                        $body.html('<div class="doguify-error">❌ Error al cargar los detalles</div>');
                    }
                },
                error: () => {
                    $body.html('<div class="doguify-error">❌ Error de conexión</div>');
                }
            });
        }
        
        closeModal() {
            $('#doguify-modal').hide();
        }
        
        initFilters() {
            // Auto-submit filtros con delay
            let filterTimeout;
            
            $('.doguify-filters select, .doguify-filters input').on('change input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    $(this).closest('form').submit();
                }, 500);
            });
            
            // Limpiar filtros
            $('.doguify-clear-filters').on('click', function(e) {
                e.preventDefault();
                window.location.href = $(this).attr('href');
            });
        }
        
        initStats() {
            // Animar números de estadísticas
            this.animateCounters();
            
            // Actualizar estadísticas cada 30 segundos
            setInterval(() => {
                this.refreshStats();
            }, 30000);
        }
        
        animateCounters() {
            $('.doguify-stat-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text().replace(/[^\d]/g, ''));
                
                if (target > 0) {
                    let current = 0;
                    const increment = target / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        $this.text(Math.floor(current).toLocaleString());
                    }, 30);
                }
            });
        }
        
        refreshStats() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'doguify_refresh_stats',
                    nonce: doguify_admin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatsDisplay(response.data);
                    }
                }
            });
        }
        
        updateStatsDisplay(stats) {
            Object.keys(stats).forEach(key => {
                const $stat = $(`.doguify-stat-number[data-stat="${key}"]`);
                if ($stat.length) {
                    const newValue = parseInt(stats[key]);
                    const oldValue = parseInt($stat.text().replace(/[^\d]/g, ''));
                    
                    if (newValue !== oldValue) {
                        $stat.addClass('doguify-stat-updated');
                        $stat.text(newValue.toLocaleString());
                        
                        setTimeout(() => {
                            $stat.removeClass('doguify-stat-updated');
                        }, 2000);
                    }
                }
            });
        }
        
        initNotifications() {
            // Auto-hide notices después de 5 segundos
            setTimeout(() => {
                $('.notice.is-dismissible').slideUp();
            }, 5000);
            
            // Confirmaciones para acciones destructivas
            $('.doguify-eliminar').on('click', function(e) {
                const nombre = $(this).closest('tr').find('.doguify-mascota-nombre').text().trim();
                if (!confirm(`¿Estás seguro de eliminar el registro de "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                    e.preventDefault();
                }
            });
            
            // Confirmación para exportación masiva
            $('.doguify-export-all').on('click', function(e) {
                if (!confirm('¿Exportar todos los registros? Esto puede tardar unos momentos.')) {
                    e.preventDefault();
                }
            });
        }
        
        initTooltips() {
            // Tooltips simples con CSS
            $('[data-tooltip]').hover(
                function() {
                    $(this).addClass('doguify-tooltip-active');
                },
                function() {
                    $(this).removeClass('doguify-tooltip-active');
                }
            );
        }
        
        // Utilidad para formatear fechas
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Utilidad para formatear precios
        formatPrice(price) {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR'
            }).format(price);
        }
        
        // Función para mostrar notificaciones
        showNotification(message, type = 'success') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible doguify-notification">
                    <p>${message}</p>
                </div>
            `);
            
            $('.doguify-admin').prepend($notification);
            
            setTimeout(() => {
                $notification.slideUp(() => {
                    $notification.remove();
                });
            }, 5000);
        }
        
        // Función para validar formularios
        validateForm($form) {
            let isValid = true;
            const errors = [];
            
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('doguify-field-error');
                    errors.push(`El campo "${$field.attr('name')}" es obligatorio`);
                } else {
                    $field.removeClass('doguify-field-error');
                }
                
                // Validar email
                if ($field.attr('type') === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        $field.addClass('doguify-field-error');
                        errors.push('El email no tiene un formato válido');
                    }
                }
            });
            
            if (!isValid) {
                this.showNotification(errors.join('<br>'), 'error');
            }
            
            return isValid;
        }
    }
    
    // Clase para manejar gráficos
    class DoguifyCharts {
        constructor() {
            this.charts = {};
        }
        
        createPieChart(container, data, title) {
            // Implementación simple de gráfico de tarta con CSS
            const total = data.reduce((sum, item) => sum + item.value, 0);
            let angle = 0;
            
            const $container = $(container);
            $container.html(`
                <div class="doguify-pie-chart">
                    <div class="doguify-pie-chart-title">${title}</div>
                    <div class="doguify-pie-chart-canvas"></div>
                    <div class="doguify-pie-chart-legend"></div>
                </div>
            `);
            
            const $canvas = $container.find('.doguify-pie-chart-canvas');
            const $legend = $container.find('.doguify-pie-chart-legend');
            
            data.forEach((item, index) => {
                const percentage = (item.value / total) * 100;
                const color = this.getColor(index);
                
                // Agregar segmento al gráfico (simplificado)
                $canvas.append(`
                    <div class="doguify-pie-segment" 
                         style="--percentage: ${percentage}%; --color: ${color};">
                    </div>
                `);
                
                // Agregar leyenda
                $legend.append(`
                    <div class="doguify-legend-item">
                        <span class="doguify-legend-color" style="background: ${color};"></span>
                        <span class="doguify-legend-label">${item.label}: ${item.value}</span>
                    </div>
                `);
            });
        }
        
        createBarChart(container, data, title) {
            const maxValue = Math.max(...data.map(item => item.value));
            
            const $container = $(container);
            $container.html(`
                <div class="doguify-bar-chart">
                    <div class="doguify-bar-chart-title">${title}</div>
                    <div class="doguify-bar-chart-canvas"></div>
                </div>
            `);
            
            const $canvas = $container.find('.doguify-bar-chart-canvas');
            
            data.forEach((item, index) => {
                const height = (item.value / maxValue) * 100;
                const color = this.getColor(index);
                
                $canvas.append(`
                    <div class="doguify-bar-item">
                        <div class="doguify-bar" 
                             style="height: ${height}%; background: ${color};"
                             title="${item.label}: ${item.value}">
                        </div>
                        <div class="doguify-bar-label">${item.label}</div>
                    </div>
                `);
            });
        }
        
        getColor(index) {
            const colors = [
                '#667eea', '#764ba2', '#2ecc71', '#f39c12', 
                '#e74c3c', '#3498db', '#9b59b6', '#1abc9c'
            ];
            return colors[index % colors.length];
        }
    }
    
    // Inicializar cuando esté listo
    const doguifyAdmin = new DoguifyAdmin();
    const doguifyCharts = new DoguifyCharts();
    
    // Hacer disponible globalmente
    window.DoguifyAdmin = doguifyAdmin;
    window.DoguifyCharts = doguifyCharts;
    
})(jQuery);

// CSS adicional para gráficos (se inyecta dinámicamente)
jQuery(document).ready(function($) {
    const chartStyles = `
        <style>
        .doguify-pie-chart, .doguify-bar-chart {
            text-align: center;
        }
        
        .doguify-pie-chart-title, .doguify-bar-chart-title {
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--doguify-dark);
        }
        
        .doguify-pie-chart-canvas {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            background: conic-gradient(from 0deg, #667eea 0deg, #764ba2 120deg, #2ecc71 240deg, #f39c12 360deg);
        }
        
        .doguify-pie-chart-legend {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .doguify-legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .doguify-legend-color {
            width: 16px;
            height: 16px;
            border-radius: 2px;
        }
        
        .doguify-bar-chart-canvas {
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 10px;
            height: 200px;
            margin-bottom: 10px;
        }
        
        .doguify-bar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }
        
        .doguify-bar {
            width: 30px;
            margin-bottom: 5px;
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .doguify-bar:hover {
            transform: scale(1.1);
        }
        
        .doguify-bar-label {
            font-size: 12px;
            color: #666;
            transform: rotate(-45deg);
            white-space: nowrap;
        }
        
        .doguify-loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }
        
        .doguify-error {
            text-align: center;
            padding: 40px;
            color: var(--doguify-danger);
            font-weight: 600;
        }
        
        .doguify-stat-updated {
            background: var(--doguify-success) !important;
            color: white !important;
            border-radius: 4px;
            animation: doguify-pulse 2s ease-in-out;
        }
        
        @keyframes doguify-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .doguify-field-error {
            border-color: var(--doguify-danger) !important;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
        }
        
        .doguify-notification {
            animation: doguify-slideDown 0.3s ease-out;
        }
        
        @keyframes doguify-slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        </style>
    `;
    
    $('head').append(chartStyles);
});