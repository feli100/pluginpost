/**
 * Doguify Comparador - JavaScript completo
 * Versión con página de espera rediseñada
 */

(function($) {
    'use strict';
    
    /**
     * Clase principal para manejar el comparador de seguros
     */
    class DoguifyComparador {
        constructor() {
            this.form = null;
            this.submitBtn = null;
            this.sessionId = null;
            this.progressInterval = null;
            this.statusCheckInterval = null;
            this.currentProgress = 0;
            this.retryCount = 0;
            this.maxRetries = 3;
            this.isProcessingComplete = false;
            
            // Configuración por defecto
            this.config = {
                checkInterval: 5000,
                maxRetries: 3,
                progressPhases: [
                    { end: 30, speedMin: 1, speedMax: 4 },
                    { end: 70, speedMin: 0.5, speedMax: 2.5 },
                    { end: 90, speedMin: 0.2, speedMax: 1.2 },
                    { end: 100, speedMin: 0.1, speedMax: 0.6 }
                ],
                loadingTexts: [
                    'trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar',
                    'analizando las mejores opciones<br>para tu mascota',
                    'comparando precios y coberturas<br>en tiempo real',
                    'finalizando tu comparativa<br>personalizada'
                ]
            };
            
            this.init();
        }
        
        /**
         * Inicializar el comparador
         */
        init() {
            $(document).ready(() => {
                this.loadConfiguration();
                this.setupFormHandler();
                this.setupProgressPage();
                this.setupImageLoading();
                this.setupErrorHandling();
                this.setupPerformanceOptimizations();
                
                console.log('DoguifyComparador: Inicializado correctamente');
            });
        }
        
        /**
         * Cargar configuración desde el servidor
         */
        loadConfiguration() {
            if (typeof doguify_ajax !== 'undefined' && doguify_ajax.config) {
                this.config = Object.assign(this.config, doguify_ajax.config);
            }
            
            if (typeof window.DoguifyConfig !== 'undefined') {
                this.config = Object.assign(this.config, window.DoguifyConfig);
            }
        }
        
        /**
         * Configurar el manejador del formulario
         */
        setupFormHandler() {
            const form = $('#doguify-formulario-comparativa');
            if (form.length) {
                this.form = form;
                this.submitBtn = form.find('.doguify-submit-btn');
                
                form.on('submit', (e) => this.handleFormSubmit(e));
                this.setupRealTimeValidation();
            }
        }
        
        /**
         * Configurar validación en tiempo real
         */
        setupRealTimeValidation() {
            // Código postal - solo números
            $('input[name="codigo_postal"]').on('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // Configurar límites de año dinámicamente
            const currentYear = new Date().getFullYear();
            $('input[name="edad_año"]').attr('max', currentYear);
            $('input[name="edad_año"]').attr('min', 2018);
            
            // Validación de email en tiempo real
            $('input[name="email"]').on('blur', function() {
                const email = $(this).val();
                if (email && !DoguifyComparador.isValidEmail(email)) {
                    $(this).addClass('error');
                    if (!$(this).next('.error-message').length) {
                        $(this).after('<div class="error-message">Email no válido</div>');
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.error-message').remove();
                }
            });
            
            // Validación de fecha en tiempo real
            $('input[name="edad_dia"], input[name="edad_mes"], input[name="edad_año"]').on('change', () => {
                this.validateBirthDateFields();
            });
        }
        
        /**
         * Validar campos de fecha de nacimiento
         */
        validateBirthDateFields() {
            const day = parseInt($('input[name="edad_dia"]').val());
            const month = parseInt($('input[name="edad_mes"]').val());
            const year = parseInt($('input[name="edad_año"]').val());
            
            if (day && month && year) {
                const validation = this.validateBirthDate(day, month, year);
                const yearField = $('input[name="edad_año"]');
                
                if (!validation.valid) {
                    yearField.addClass('error');
                    yearField.next('.error-message').remove();
                    yearField.after(`<div class="error-message">${validation.error}</div>`);
                } else {
                    yearField.removeClass('error');
                    yearField.next('.error-message').remove();
                }
            }
        }
        
        /**
         * Manejar envío del formulario
         */
        handleFormSubmit(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            const formData = this.collectFormData();
            this.submitForm(formData);
        }
        
        /**
         * Validar formulario completo
         */
        validateForm() {
            const errors = [];
            
            // Limpiar errores anteriores
            $('.doguify-errors').remove();
            $('.error').removeClass('error');
            $('.error-message').remove();
            
            // Validar campos requeridos
            const requiredFields = [
                'tipo_mascota', 'nombre', 'email', 'codigo_postal', 
                'edad_dia', 'edad_mes', 'edad_año', 'raza', 'politicas'
            ];
            
            requiredFields.forEach(field => {
                const input = this.form.find(`[name="${field}"]`);
                const value = input.val();
                
                if (field === 'politicas') {
                    if (!input.is(':checked')) {
                        errors.push('Debe aceptar la política de privacidad');
                        input.closest('.doguify-checkbox-group').addClass('error');
                    }
                } else if (field === 'tipo_mascota') {
                    const selected = this.form.find('input[name="tipo_mascota"]:checked').val();
                    if (!selected) {
                        errors.push('Debe seleccionar el tipo de mascota');
                        this.form.find('.doguify-pet-types').addClass('error');
                    }
                } else if (!value || value.trim() === '') {
                    errors.push(`El campo ${this.getFieldLabel(field)} es requerido`);
                    input.addClass('error');
                }
            });
            
            // Validaciones específicas
            const email = this.form.find('input[name="email"]').val();
            if (email && !DoguifyComparador.isValidEmail(email)) {
                errors.push('El email no es válido');
                this.form.find('input[name="email"]').addClass('error');
            }
            
            const cp = this.form.find('input[name="codigo_postal"]').val();
            if (cp && !/^\d{5}$/.test(cp)) {
                errors.push('El código postal debe tener exactamente 5 números');
                this.form.find('input[name="codigo_postal"]').addClass('error');
            }
            
            // Validar fecha
            const dia = parseInt(this.form.find('input[name="edad_dia"]').val());
            const mes = parseInt(this.form.find('input[name="edad_mes"]').val());
            const año = parseInt(this.form.find('input[name="edad_año"]').val());
            
            const fechaValidacion = this.validateBirthDate(dia, mes, año);
            if (!fechaValidacion.valid) {
                errors.push(fechaValidacion.error);
                this.form.find('input[name="edad_año"]').addClass('error');
            }
            
            if (errors.length > 0) {
                this.showErrors(errors);
                return false;
            }
            
            return true;
        }
        
        /**
         * Validar fecha de nacimiento
         */
        validateBirthDate(day, month, year) {
            if (!day || !month || !year || isNaN(day) || isNaN(month) || isNaN(year)) {
                return { valid: false, error: 'Fecha de nacimiento incompleta' };
            }
            
            if (day < 1 || day > 31 || month < 1 || month > 12) {
                return { valid: false, error: 'Fecha de nacimiento con valores incorrectos' };
            }
            
            const currentYear = new Date().getFullYear();
            if (year < 2018 || year > currentYear) {
                return { valid: false, error: `El año debe estar entre 2018 y ${currentYear}` };
            }
            
            const date = new Date(year, month - 1, day);
            if (date.getDate() !== day || date.getMonth() !== month - 1 || date.getFullYear() !== year) {
                return { valid: false, error: 'La fecha introducida no es válida (ej: 30 de febrero no existe)' };
            }
            
            const minDate = new Date(2018, 0, 1);
            const maxDate = new Date();
            
            if (date < minDate) {
                return { valid: false, error: 'La fecha debe ser posterior al 1 de enero de 2018' };
            }
            
            if (date > maxDate) {
                return { valid: false, error: 'La fecha no puede ser posterior a hoy' };
            }
            
            return { valid: true };
        }
        
        /**
         * Recopilar datos del formulario
         */
        collectFormData() {
            const formData = new FormData(this.form[0]);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                if (key === 'politicas') {
                    data[key] = true;
                } else if (key.startsWith('edad_')) {
                    data[key] = parseInt(value) || null;
                } else {
                    data[key] = value.trim();
                }
            }
            
            // Asegurar tipo_mascota
            const tipoMascota = this.form.find('input[name="tipo_mascota"]:checked').val();
            if (tipoMascota) {
                data.tipo_mascota = tipoMascota;
            }
            
            return data;
        }
        
        /**
         * Enviar formulario via AJAX
         */
        submitForm(formData) {
            this.setLoadingState(true);
            
            $.ajax({
                url: doguify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'doguify_procesar_comparativa',
                    nonce: doguify_ajax.nonce,
                    ...formData
                },
                timeout: 30000,
                success: (response) => {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            this.sessionId = result.session_id;
                            this.redirectToWaitingPage();
                        } else {
                            this.showErrors([result.message]);
                            this.setLoadingState(false);
                        }
                    } catch (e) {
                        this.showErrors(['Error al procesar la respuesta del servidor']);
                        this.setLoadingState(false);
                        console.error('Error parsing response:', e, response);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMessage = 'Error al enviar el formulario';
                    
                    if (status === 'timeout') {
                        errorMessage = 'El servidor tardó demasiado en responder. Inténtalo de nuevo.';
                    } else if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            // Mantener mensaje por defecto
                        }
                    }
                    
                    this.showErrors([errorMessage]);
                    this.setLoadingState(false);
                    console.error('AJAX Error:', status, error, xhr.responseText);
                }
            });
        }
        
        /**
         * Redirigir a la página de espera
         */
        redirectToWaitingPage() {
            const url = `${doguify_ajax.espera_url}?session_id=${this.sessionId}`;
            window.location.href = url;
        }
        
        /**
         * Configurar página de progreso
         */
        setupProgressPage() {
            if ($('.doguify-waiting-page-wrapper').length || $('.doguify-waiting-page-new').length) {
                this.initProgressPage();
            }
        }
        
        /**
         * Inicializar página de progreso
         */
        initProgressPage() {
            const urlParams = new URLSearchParams(window.location.search);
            this.sessionId = urlParams.get('session_id');
            
            if (!this.sessionId) {
                this.showProgressError('Sesión no válida');
                return;
            }
            
            // Agregar clase al body
            document.body.classList.add('doguify-waiting-body');
            
            // Iniciar progreso
            setTimeout(() => {
                this.startProgress();
                this.startStatusChecking();
                this.startDynamicTexts();
            }, this.config.progress?.initial_delay || 500);
        }
        
        /**
         * Iniciar animación de progreso
         */
        startProgress() {
            this.currentProgress = 0;
            this.progressInterval = setInterval(() => {
                this.updateProgressAnimation();
            }, 200 + Math.random() * 300);
        }
        
        /**
         * Actualizar animación de progreso
         */
        updateProgressAnimation() {
            if (this.isProcessingComplete) {
                return;
            }
            
            const phase = this.getCurrentPhase();
            if (!phase) return;
            
            const increment = Math.random() * (phase.speedMax - phase.speedMin) + phase.speedMin;
            this.currentProgress = Math.min(this.currentProgress + increment, phase.end);
            
            this.updateProgressDisplay(this.currentProgress);
            
            if (this.currentProgress >= 100) {
                clearInterval(this.progressInterval);
            }
        }
        
        /**
         * Obtener fase actual del progreso
         */
        getCurrentPhase() {
            const phases = this.config.progressPhases || this.config.progress?.phases;
            if (!phases) return null;
            
            for (let phase of phases) {
                if (this.currentProgress < phase.end) {
                    return phase;
                }
            }
            
            return phases[phases.length - 1];
        }
        
        /**
         * Actualizar display del progreso
         */
        updateProgressDisplay(progress) {
            const roundedProgress = Math.round(progress);
            
            // Nuevo diseño
            const progressBar = document.getElementById('doguifyProgressBar');
            const progressText = document.getElementById('doguifyProgressText');
            
            if (progressBar) {
                progressBar.style.width = roundedProgress + '%';
            }
            
            if (progressText) {
                progressText.textContent = roundedProgress + '%';
            }
            
            // Diseño anterior (compatibilidad)
            const progressBarOld = $('.doguify-progress-fill-new');
            const progressTextOld = $('.doguify-progress-percentage');
            
            if (progressBarOld.length) {
                progressBarOld.css('width', roundedProgress + '%');
            }
            
            if (progressTextOld.length) {
                progressTextOld.text(roundedProgress + '%');
            }
            
            // Función global para compatibilidad
            if (typeof window.updateDoguifyProgress === 'function') {
                window.updateDoguifyProgress(roundedProgress);
            }
        }
        
        /**
         * Iniciar verificación de estado con el servidor
         */
        startStatusChecking() {
            this.statusCheckInterval = setInterval(() => {
                this.checkComparisonStatus();
            }, this.config.checkInterval || 5000);
        }
        
        /**
         * Verificar estado de la comparativa
         */
        checkComparisonStatus() {
            if (this.isProcessingComplete) {
                return;
            }
            
            $.ajax({
                url: doguify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'doguify_check_status',
                    session_id: this.sessionId,
                    nonce: doguify_ajax.nonce
                },
                timeout: 10000,
                success: (response) => {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            const data = result.data;
                            
                            if (data.completado) {
                                this.completeProgress(data);
                            }
                            
                            // Resetear contador de reintentos
                            this.retryCount = 0;
                        } else {
                            this.handleStatusError('Error verificando estado: ' + result.data);
                        }
                    } catch (e) {
                        this.handleStatusError('Error procesando respuesta del servidor');
                        console.error('Error parsing status response:', e, response);
                    }
                },
                error: (xhr, status, error) => {
                    this.handleStatusError(`Error de conexión: ${status}`);
                    console.error('Status check error:', status, error);
                }
            });
        }
        
        /**
         * Manejar errores de verificación de estado
         */
        handleStatusError(error) {
            this.retryCount++;
            
            console.warn(`Error verificando estado (${this.retryCount}/${this.maxRetries}):`, error);
            
            if (this.retryCount >= this.maxRetries) {
                console.log('Máximo de reintentos alcanzado, continuando con simulación');
                this.retryCount = 0;
            }
        }
        
        /**
         * Completar progreso y redirigir
         */
        completeProgress(data) {
            this.isProcessingComplete = true;
            
            // Limpiar intervalos
            clearInterval(this.progressInterval);
            clearInterval(this.statusCheckInterval);
            
            // Forzar progreso a 100%
            this.currentProgress = 100;
            this.updateProgressDisplay(100);
            
            // Actualizar texto
            const subText = document.querySelector('.doguify-sub-text');
            if (subText) {
                subText.innerHTML = '¡Comparativa completada!<br>Redirigiendo a resultados...';
            }
            
            // Redirigir después de un momento
            setTimeout(() => {
                window.location.href = data.redirect_url || `${doguify_ajax.resultado_url}?session_id=${this.sessionId}`;
            }, 1500);
        }
        
        /**
         * Iniciar textos dinámicos
         */
        startDynamicTexts() {
            let textIndex = 0;
            const interval = this.config.progress?.text_change_interval || 4000;
            
            setInterval(() => {
                if (this.isProcessingComplete || this.currentProgress < 20 || this.currentProgress >= 95) {
                    return;
                }
                
                const subTextElement = document.querySelector('.doguify-sub-text');
                if (subTextElement) {
                    textIndex = (textIndex + 1) % this.config.loadingTexts.length;
                    subTextElement.innerHTML = this.config.loadingTexts[textIndex];
                }
            }, interval);
        }
        
        /**
         * Configurar carga de imágenes
         */
        setupImageLoading() {
            $('.doguify-side-image img').each(function() {
                const $img = $(this);
                const $container = $img.parent();
                
                $container.addClass('loading');
                
                $img.on('load', function() {
                    $container.removeClass('loading');
                    $img.addClass('loaded').fadeIn(300);
                }).on('error', function() {
                    $container.removeClass('loading');
                    console.warn('Error cargando imagen:', $img.attr('src'));
                    $container.fadeOut(300);
                });
                
                if (!this.complete) {
                    $img.hide();
                }
            });
        }
        
        /**
         * Configurar manejo de errores globales
         */
        setupErrorHandling() {
            window.addEventListener('error', (event) => {
                console.error('Error JavaScript:', {
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno
                });
            });
            
            window.addEventListener('unhandledrejection', (event) => {
                console.error('Promesa rechazada:', event.reason);
            });
        }
        
        /**
         * Configurar optimizaciones de rendimiento
         */
        setupPerformanceOptimizations() {
            // Pausar animaciones cuando la pestaña no está activa
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.pauseAnimations();
                } else {
                    this.resumeAnimations();
                }
            });
            
            // Optimizar para dispositivos con batería baja
            if ('getBattery' in navigator) {
                navigator.getBattery().then((battery) => {
                    if (battery.level < 0.2) {
                        this.enableLowPowerMode();
                    }
                });
            }
        }
        
        /**
         * Pausar animaciones
         */
        pauseAnimations() {
            const style = document.createElement('style');
            style.id = 'doguify-pause-animations';
            style.textContent = `
                .doguify-progress-bar::after {
                    animation-play-state: paused !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        /**
         * Reanudar animaciones
         */
        resumeAnimations() {
            const pauseStyle = document.getElementById('doguify-pause-animations');
            if (pauseStyle) {
                pauseStyle.remove();
            }
        }
        
        /**
         * Activar modo de bajo consumo
         */
        enableLowPowerMode() {
            console.log('Modo de bajo consumo activado');
            
            // Reducir frecuencia de verificaciones
            if (this.statusCheckInterval) {
                clearInterval(this.statusCheckInterval);
                this.statusCheckInterval = setInterval(() => {
                    this.checkComparisonStatus();
                }, 10000); // 10 segundos en lugar de 5
            }
            
            // Pausar animaciones no esenciales
            const style = document.createElement('style');
            style.id = 'doguify-low-power';
            style.textContent = `
                .doguify-progress-bar::after {
                    animation: none !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        /**
         * Mostrar error en página de progreso
         */
        showProgressError(message) {
            const isNewDesign = $('.doguify-waiting-page-wrapper').length > 0;
            
            if (isNewDesign) {
                $('.doguify-content-grid, .doguify-wave-container').fadeOut(300, function() {
                    $('.doguify-waiting-container').html(`
                        <div class="doguify-error-container">
                            <div class="doguify-logo">
                                <img src="${this.config.images?.logo || 'https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-scaled-e1750429316951.png'}" alt="Doguify Logo">
                            </div>
                            <div class="doguify-error-content">
                                <h3>❌ Error</h3>
                                <p>${message}</p>
                                <button onclick="history.back()" class="doguify-btn-back">
                                    Volver al formulario
                                </button>
                            </div>
                        </div>
                    `);
                });
            } else {
                $('.doguify-waiting-content').html(`
                    <div class="doguify-error-new">
                        <div class="doguify-logo-container">
                            <img src="https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-1-_3_-1.webp" alt="Doguify" class="doguify-logo">
                        </div>
                        <h3>❌ Error</h3>
                        <p>${message}</p>
                        <button onclick="history.back()" class="doguify-btn doguify-btn-back">
                            Volver al formulario
                        </button>
                    </div>
                `);
            }
        }
        
        /**
         * Establecer estado de carga del botón
         */
        setLoadingState(loading) {
            if (!this.submitBtn || !this.submitBtn.length) return;
            
            if (loading) {
                this.submitBtn.prop('disabled', true);
                this.submitBtn.html('<span class="doguify-spinner"></span> Procesando...');
                this.submitBtn.addClass('loading');
            } else {
                this.submitBtn.prop('disabled', false);
                this.submitBtn.html('🔍 Obtener comparativa');
                this.submitBtn.removeClass('loading');
            }
        }
        
        /**
         * Mostrar errores del formulario
         */
        showErrors(errors) {
            $('.doguify-errors').remove();
            
            if (errors.length > 0) {
                const errorHtml = `
                    <div class="doguify-errors">
                        <div class="doguify-errors-header">
                            <strong>Por favor, corrige los siguientes errores:</strong>
                        </div>
                        <ul>
                            ${errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `;
                
                this.form.prepend(errorHtml);
                
                // Scroll al primer error
                $('html, body').animate({
                    scrollTop: this.form.offset().top - 20
                }, 300);
                
                // Auto-remover después de 8 segundos
                setTimeout(() => {
                    $('.doguify-errors').fadeOut();
                }, 8000);
            }
        }
        
        /**
         * Obtener etiqueta del campo
         */
        getFieldLabel(field) {
            const labels = {
                'nombre': 'nombre de la mascota',
                'email': 'email',
                'codigo_postal': 'código postal',
                'edad_dia': 'día de nacimiento',
                'edad_mes': 'mes de nacimiento',
                'edad_año': 'año de nacimiento',
                'raza': 'raza de la mascota'
            };
            
            return labels[field] || field;
        }
        
        /**
         * Validar email estático
         */
        static isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    }
    
    /**
     * Objeto de utilidades
     */
    window.DoguifyUtils = {
        /**
         * Formatear tiempo transcurrido
         */
        formatElapsedTime: function(startTime) {
            const elapsed = Date.now() - startTime;
            const seconds = Math.floor(elapsed / 1000);
            const minutes = Math.floor(seconds / 60);
            
            if (minutes > 0) {
                return `${minutes}m ${seconds % 60}s`;
            }
            return `${seconds}s`;
        },
        
        /**
         * Detectar dispositivo móvil
         */
        isMobile: function() {
            return /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },
        
        /**
         * Detectar conexión lenta
         */
        isSlowConnection: function() {
            return navigator.connection && 
                   (navigator.connection.effectiveType === 'slow-2g' || 
                    navigator.connection.effectiveType === '2g');
        },
        
        /**
         * Throttle function
         */
        throttle: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) func(...args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func(...args);
            };
        }
    };
    
    /**
     * Funciones de compatibilidad global
     */
    window.updateDoguifyProgress = function(progress, statusText) {
        const progressBar = document.getElementById('doguifyProgressBar');
        const progressText = document.getElementById('doguifyProgressText');
        
        if (progressBar && progressText) {
            const roundedProgress = Math.min(Math.round(progress), 100);
            progressBar.style.width = roundedProgress + '%';
            progressText.textContent = roundedProgress + '%';
        }
        
        // Compatibilidad con diseño anterior
        $('.doguify-progress-fill-new').css('width', roundedProgress + '%');
        $('.doguify-progress-percentage').text(roundedProgress + '%');
        
        if (statusText) {
            $('.doguify-status-text').text(statusText);
        }
    };
    
    /**
     * Analytics y tracking
     */
    window.DoguifyAnalytics = {
        /**
         * Track evento
         */
        trackEvent: function(eventName, eventData = {}) {
            // Google Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'Doguify',
                    event_label: eventData.label || '',
                    value: eventData.value || 0,
                    ...eventData
                });
            }
            
            // Facebook Pixel
            if (typeof fbq !== 'undefined') {
                fbq('track', eventName, eventData);
            }
            
            // Log local para debugging
            if (window.doguify_debug) {
                console.log('Analytics Event:', eventName, eventData);
            }
        },
        
        /**
         * Track milestone del progreso
         */
        trackProgressMilestone: function(progress) {
            const milestones = [25, 50, 75, 90, 100];
            if (milestones.includes(progress)) {
                this.trackEvent('progress_milestone', {
                    label: `${progress}%`,
                    value: progress
                });
            }
        },
        
        /**
         * Track tiempo en página
         */
        trackTimeOnPage: function() {
            const startTime = window.doguify_start_time || Date.now();
            const timeSpent = Math.round((Date.now() - startTime) / 1000);
            
            this.trackEvent('time_on_page', {
                label: 'waiting_page',
                value: timeSpent
            });
        },
        
        /**
         * Track form submission
         */
        trackFormSubmission: function(formData) {
            this.trackEvent('form_submission', {
                label: 'pet_insurance_form',
                pet_type: formData.tipo_mascota,
                breed: formData.raza
            });
        }
    };
    
    /**
     * Gestión de errores avanzada
     */
    window.DoguifyErrorHandler = {
        errors: [],
        
        /**
         * Log error
         */
        logError: function(error, context = {}) {
            const errorData = {
                message: error.message || error,
                stack: error.stack || '',
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent,
                context: context
            };
            
            this.errors.push(errorData);
            
            // Enviar al servidor si hay muchos errores
            if (this.errors.length >= 5) {
                this.sendErrorsToServer();
            }
            
            console.error('Doguify Error:', errorData);
        },
        
        /**
         * Enviar errores al servidor
         */
        sendErrorsToServer: function() {
            if (typeof doguify_ajax === 'undefined' || this.errors.length === 0) {
                return;
            }
            
            $.ajax({
                url: doguify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'doguify_log_client_errors',
                    nonce: doguify_ajax.nonce,
                    errors: JSON.stringify(this.errors)
                },
                success: () => {
                    this.errors = [];
                },
                error: (xhr, status, error) => {
                    console.warn('Error sending client errors to server:', error);
                }
            });
        }
    };
    
    /**
     * Gestión de estado local
     */
    window.DoguifyState = {
        data: {},
        
        /**
         * Guardar estado
         */
        save: function(key, value) {
            this.data[key] = value;
            
            // Intentar guardar en sessionStorage si está disponible
            try {
                const stateData = JSON.stringify(this.data);
                sessionStorage.setItem('doguify_state', stateData);
            } catch (e) {
                // Ignorar errores de storage
            }
        },
        
        /**
         * Obtener estado
         */
        get: function(key, defaultValue = null) {
            // Intentar cargar desde sessionStorage primero
            if (Object.keys(this.data).length === 0) {
                try {
                    const stored = sessionStorage.getItem('doguify_state');
                    if (stored) {
                        this.data = JSON.parse(stored);
                    }
                } catch (e) {
                    // Ignorar errores de storage
                }
            }
            
            return this.data[key] !== undefined ? this.data[key] : defaultValue;
        },
        
        /**
         * Limpiar estado
         */
        clear: function() {
            this.data = {};
            try {
                sessionStorage.removeItem('doguify_state');
            } catch (e) {
                // Ignorar errores de storage
            }
        }
    };
    
    /**
     * CSS dinámico para estilos de error y loading
     */
    function addDynamicStyles() {
        if (document.getElementById('doguify-dynamic-styles')) {
            return;
        }
        
        const style = document.createElement('style');
        style.id = 'doguify-dynamic-styles';
        style.textContent = `
            /* Estilos de error */
            .doguify-errors {
                background: #fee;
                border: 1px solid #fcc;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                color: #c33;
                animation: doguify-shake 0.5s ease-in-out;
            }
            
            .doguify-errors-header {
                margin-bottom: 10px;
                font-weight: 600;
            }
            
            .doguify-errors ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .doguify-errors li {
                margin-bottom: 5px;
            }
            
            @keyframes doguify-shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            /* Estilos de campo con error */
            .doguify-form input.error,
            .doguify-form select.error {
                border-color: #f56565 !important;
                box-shadow: 0 0 0 3px rgba(245, 101, 101, 0.1) !important;
            }
            
            .doguify-checkbox-group.error {
                background: rgba(245, 101, 101, 0.05);
                border-radius: 4px;
                padding: 5px;
            }
            
            .doguify-pet-types.error {
                background: rgba(245, 101, 101, 0.05);
                border-radius: 8px;
                padding: 10px;
            }
            
            .error-message {
                color: #f56565;
                font-size: 0.875rem;
                margin-top: 5px;
                display: block;
            }
            
            /* Estilos de loading */
            .doguify-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                animation: doguify-spin 1s linear infinite;
                margin-right: 8px;
            }
            
            @keyframes doguify-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .doguify-submit-btn.loading {
                opacity: 0.7;
                cursor: not-allowed;
            }
            
            /* Estilos de página de error */
            .doguify-error-container {
                text-align: center;
                color: white;
                padding: 40px 20px;
                max-width: 500px;
                margin: 0 auto;
            }
            
            .doguify-error-content h3 {
                font-size: 2rem;
                margin: 20px 0;
                color: #ff6b6b;
            }
            
            .doguify-error-content p {
                font-size: 1.1rem;
                margin: 20px 0;
                opacity: 0.9;
                line-height: 1.5;
            }
            
            .doguify-btn-back {
                background: white;
                color: #4690E8;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-top: 20px;
                text-decoration: none;
                display: inline-block;
            }
            
            .doguify-btn-back:hover {
                background: #f0f0f0;
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
                color: #4690E8;
                text-decoration: none;
            }
            
            /* Estilos para el preloader de imágenes */
            .doguify-side-image.loading::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 40px;
                height: 40px;
                border: 3px solid rgba(255,255,255,0.3);
                border-top: 3px solid white;
                border-radius: 50%;
                animation: doguify-spin 1s linear infinite;
                z-index: 10;
            }
            
            .doguify-side-image img {
                transition: opacity 0.3s ease;
            }
            
            .doguify-side-image img.loaded {
                opacity: 1;
            }
            
            /* Animaciones de entrada */
            @keyframes doguify-fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .doguify-fade-in {
                animation: doguify-fadeInUp 0.6s ease-out forwards;
            }
            
            /* Responsive helpers */
            @media (max-width: 768px) {
                .doguify-errors {
                    padding: 12px;
                    font-size: 0.9rem;
                }
                
                .doguify-error-content h3 {
                    font-size: 1.5rem;
                }
                
                .doguify-error-content p {
                    font-size: 1rem;
                }
                
                .doguify-btn-back {
                    padding: 10px 20px;
                    font-size: 14px;
                }
            }
            
            /* High contrast mode */
            @media (prefers-contrast: high) {
                .doguify-errors {
                    border-width: 2px;
                }
                
                .doguify-form input.error,
                .doguify-form select.error {
                    border-width: 2px !important;
                }
            }
            
            /* Reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .doguify-spinner,
                .doguify-side-image.loading::before {
                    animation: none;
                }
                
                .doguify-errors {
                    animation: none;
                }
                
                .doguify-fade-in {
                    animation: none;
                    opacity: 1;
                    transform: none;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Inicialización global
     */
    $(document).ready(function() {
        // Agregar estilos dinámicos
        addDynamicStyles();
        
        // Inicializar comparador
        window.doguifyComparador = new DoguifyComparador();
        
        // Configurar tiempo de inicio para métricas
        if (!window.doguify_start_time) {
            window.doguify_start_time = Date.now();
        }
        
        // Configurar tracking de salida si está en página de espera
        if ($('.doguify-waiting-page-wrapper').length) {
            window.addEventListener('beforeunload', function() {
                DoguifyAnalytics.trackTimeOnPage();
            });
            
            // Prevenir navegación hacia atrás accidental
            history.pushState(null, null, location.href);
            window.onpopstate = function () {
                const confirmed = confirm('Tu comparativa se está procesando. ¿Estás seguro de que quieres salir?');
                if (!confirmed) {
                    history.go(1);
                }
            };
        }
        
        // Debug mode
        if (typeof doguify_ajax !== 'undefined' && doguify_ajax.debug) {
            window.doguify_debug = true;
            console.log('Doguify Debug Mode: Activado');
        }
        
        // Notificar que el plugin está listo
        $(document).trigger('doguify:ready');
        
        console.log('Doguify Comparador: Totalmente inicializado');
    });
    
    /**
     * API pública para extensiones
     */
    window.Doguify = {
        version: '1.0.0',
        
        // Acceso a instancias principales
        get comparador() {
            return window.doguifyComparador;
        },
        
        get utils() {
            return window.DoguifyUtils;
        },
        
        get analytics() {
            return window.DoguifyAnalytics;
        },
        
        get state() {
            return window.DoguifyState;
        },
        
        get errorHandler() {
            return window.DoguifyErrorHandler;
        },
        
        // Métodos de utilidad
        isReady: function() {
            return !!window.doguifyComparador;
        },
        
        onReady: function(callback) {
            if (this.isReady()) {
                callback();
            } else {
                $(document).on('doguify:ready', callback);
            }
        },
        
        // Extensiones
        extend: function(name, extension) {
            this[name] = extension;
        }
    };
    
})(jQuery);

/**
 * Polyfills para compatibilidad
 */
(function() {
    // Polyfill para Object.assign
    if (typeof Object.assign !== 'function') {
        Object.assign = function(target) {
            if (target == null) {
                throw new TypeError('Cannot convert undefined or null to object');
            }
            
            var to = Object(target);
            
            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];
                
                if (nextSource != null) {
                    for (var nextKey in nextSource) {
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        };
    }
    
    // Polyfill para Array.includes
    if (!Array.prototype.includes) {
        Array.prototype.includes = function(searchElement, fromIndex) {
            return this.indexOf(searchElement, fromIndex) !== -1;
        };
    }
})();

/**
 * Eventos personalizados para hooks
 */
jQuery(document).ready(function($) {
    // Hook después de envío de formulario
    $(document).on('doguify:form:submitted', function(event, data) {
        DoguifyAnalytics.trackFormSubmission(data);
    });
    
    // Hook de progreso
    $(document).on('doguify:progress:updated', function(event, progress) {
        DoguifyAnalytics.trackProgressMilestone(progress);
    });
    
    // Hook de error
    $(document).on('doguify:error', function(event, error, context) {
        DoguifyErrorHandler.logError(error, context);
    });
    
    // Hook de completado
    $(document).on('doguify:completed', function(event, data) {
        DoguifyAnalytics.trackEvent('comparison_completed', {
            session_id: data.session_id,
            price: data.precio
        });
    });
});/**
 * Doguify Comparador - JavaScript completo
 * Versión con página de espera rediseñada
 */

(
