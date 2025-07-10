// Doguify Comparador JavaScript
(function($) {
    'use strict';
    
    class DoguifyComparador {
        constructor() {
            this.form = null;
            this.submitBtn = null;
            this.sessionId = null;
            this.progressInterval = null;
            
            this.init();
        }
        
        init() {
            $(document).ready(() => {
                this.setupFormHandler();
                this.setupProgressPage();
            });
        }
        
        setupFormHandler() {
            const form = $('#doguify-formulario-comparativa');
            if (form.length) {
                this.form = form;
                this.submitBtn = form.find('.doguify-submit-btn');
                
                form.on('submit', (e) => this.handleFormSubmit(e));
                
                // Validación en tiempo real
                this.setupRealTimeValidation();
            }
        }
        
        setupRealTimeValidation() {
            // Código postal - solo números
            $('input[name="codigo_postal"]').on('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // Configurar año máximo dinámicamente
            $('input[name="edad_año"]').attr('max', new Date().getFullYear());
            $('input[name="edad_año"]').attr('min', 2018);
        }
        
        handleFormSubmit(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            const formData = this.collectFormData();
            this.submitForm(formData);
        }
        
        validateForm() {
            const errors = [];
            
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
                    }
                } else if (!value || value.trim() === '') {
                    errors.push(`El campo ${this.getFieldLabel(field)} es requerido`);
                }
            });
            
            // Validaciones específicas
            const email = this.form.find('input[name="email"]').val();
            if (email && !this.isValidEmail(email)) {
                errors.push('El email no es válido');
            }
            
            const cp = this.form.find('input[name="codigo_postal"]').val();
            if (cp && !/^\d{5}$/.test(cp)) {
                errors.push('El código postal debe tener exactamente 5 números');
            }
            
            // Validar fecha mejorada
            const dia = parseInt(this.form.find('input[name="edad_dia"]').val());
            const mes = parseInt(this.form.find('input[name="edad_mes"]').val());
            const año = parseInt(this.form.find('input[name="edad_año"]').val());
            
            const fechaValidacion = this.validateBirthDate(dia, mes, año);
            if (!fechaValidacion.valid) {
                errors.push(fechaValidacion.error);
            }
            
            if (errors.length > 0) {
                this.showErrors(errors);
                return false;
            }
            
            return true;
        }
        
        validateBirthDate(day, month, year) {
            // Verificar que los valores son números válidos
            if (!day || !month || !year || isNaN(day) || isNaN(month) || isNaN(year)) {
                return { valid: false, error: 'Fecha de nacimiento incompleta' };
            }
            
            // Verificar rangos básicos
            if (day < 1 || day > 31 || month < 1 || month > 12) {
                return { valid: false, error: 'Fecha de nacimiento con valores incorrectos' };
            }
            
            // Verificar rango de años (desde 2018 hasta año actual)
            const currentYear = new Date().getFullYear();
            if (year < 2018 || year > currentYear) {
                return { valid: false, error: `El año debe estar entre 2018 y ${currentYear}` };
            }
            
            // Verificar que la fecha es válida (evita 30 de febrero, etc.)
            const date = new Date(year, month - 1, day);
            if (date.getDate() !== day || date.getMonth() !== month - 1 || date.getFullYear() !== year) {
                return { valid: false, error: 'La fecha introducida no es válida (ej: 30 de febrero no existe)' };
            }
            
            // Verificar que está dentro del rango permitido (1 enero 2018 hasta hoy)
            const minDate = new Date(2018, 0, 1); // 1 enero 2018
            const maxDate = new Date(); // Hoy
            
            if (date < minDate) {
                return { valid: false, error: 'La fecha debe ser posterior al 1 de enero de 2018' };
            }
            
            if (date > maxDate) {
                return { valid: false, error: 'La fecha no puede ser posterior a hoy' };
            }
            
            return { valid: true };
        }
        
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
                        this.showErrors(['Error al procesar la respuesta']);
                        this.setLoadingState(false);
                    }
                },
                error: () => {
                    this.showErrors(['Error al enviar el formulario']);
                    this.setLoadingState(false);
                }
            });
        }
        
        redirectToWaitingPage() {
            const url = `${doguify_ajax.espera_url}?session_id=${this.sessionId}`;
            window.location.href = url;
        }
        
        setupProgressPage() {
            // Detectar si estamos en la página de espera (ambos diseños)
            if ($('.doguify-progress-page').length || $('.doguify-waiting-page-new').length) {
                this.initProgressPage();
            }
        }
        
        initProgressPage() {
            const urlParams = new URLSearchParams(window.location.search);
            this.sessionId = urlParams.get('session_id');
            
            if (!this.sessionId) {
                this.showProgressError('Sesión no válida');
                return;
            }
            
            this.startProgress();
        }
        
        startProgress() {
            let progress = 0;
            
            // Detectar qué tipo de página de progreso tenemos
            const isNewDesign = $('.doguify-waiting-page-new').length > 0;
            
            let progressBar, progressText, statusText;
            
            if (isNewDesign) {
                // Nuevo diseño
                progressBar = $('.doguify-progress-fill-new');
                progressText = $('.doguify-progress-percentage');
                statusText = $('.doguify-status-text');
            } else {
                // Diseño original
                progressBar = $('.doguify-progress-fill');
                progressText = $('.doguify-progress-text');
                statusText = $('.doguify-status-text');
            }
            
            // Simular progreso inicial (30%)
            const initialProgress = setInterval(() => {
                progress += Math.random() * 10;
                if (progress > 30) {
                    progress = 30;
                    clearInterval(initialProgress);
                    this.consultarPetplan(progress, progressBar, progressText, statusText);
                }
                this.updateProgress(progress, progressBar, progressText);
            }, 300);
        }
        
        consultarPetplan(currentProgress, progressBar, progressText, statusText) {
            if (statusText && statusText.length) {
                statusText.text('Consultando precios con Petplan...');
            }
            
            $.ajax({
                url: doguify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'doguify_consultar_petplan',
                    nonce: doguify_ajax.nonce,
                    session_id: this.sessionId
                },
                success: (response) => {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            this.completeProgress(currentProgress, progressBar, progressText, statusText, result);
                        } else {
                            this.showProgressError(result.message);
                        }
                    } catch (e) {
                        this.showProgressError('Error al procesar la consulta');
                    }
                },
                error: () => {
                    this.showProgressError('Error al consultar Petplan');
                }
            });
        }
        
        completeProgress(currentProgress, progressBar, progressText, statusText, result) {
            if (statusText && statusText.length) {
                statusText.text('Finalizando comparativa...');
            }
            
            // Completar progreso hasta 100%
            const finalProgress = setInterval(() => {
                currentProgress += Math.random() * 15;
                if (currentProgress >= 100) {
                    currentProgress = 100;
                    clearInterval(finalProgress);
                    
                    setTimeout(() => {
                        this.redirectToResults(result);
                    }, 500);
                }
                this.updateProgress(currentProgress, progressBar, progressText);
            }, 100);
        }
        
        updateProgress(progress, progressBar, progressText) {
            const roundedProgress = Math.min(Math.round(progress), 100);
            
            if (progressBar && progressBar.length) {
                progressBar.css('width', roundedProgress + '%');
            }
            
            if (progressText && progressText.length) {
                progressText.text(roundedProgress + '%');
            }
            
            // También llamar a la función global si existe (para compatibilidad)
            if (typeof window.updateDoguifyProgress === 'function') {
                window.updateDoguifyProgress(roundedProgress);
            }
        }
        
        redirectToResults(result) {
            // Guardar datos en sessionStorage para la página de resultados
            sessionStorage.setItem('doguify_results', JSON.stringify(result));
            
            const url = `${doguify_ajax.resultado_url}?session_id=${this.sessionId}`;
            window.location.href = url;
        }
        
        showProgressError(message) {
            // Detectar qué diseño estamos usando
            const isNewDesign = $('.doguify-waiting-page-new').length > 0;
            
            if (isNewDesign) {
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
            } else {
                $('.doguify-progress-container').html(`
                    <div class="doguify-error">
                        <h3>❌ Error</h3>
                        <p>${message}</p>
                        <button onclick="history.back()" class="doguify-btn doguify-btn-secondary">
                            Volver al formulario
                        </button>
                    </div>
                `);
            }
        }
        
        setLoadingState(loading) {
            if (loading) {
                this.submitBtn.prop('disabled', true);
                this.submitBtn.text('Procesando...');
            } else {
                this.submitBtn.prop('disabled', false);
                this.submitBtn.text('🔍 Obtener comparativa');
            }
        }
        
        showErrors(errors) {
            // Remover errores anteriores
            $('.doguify-errors').remove();
            
            if (errors.length > 0) {
                const errorHtml = `
                    <div class="doguify-errors">
                        <ul>
                            ${errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `;
                
                this.form.prepend(errorHtml);
                
                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    $('.doguify-errors').fadeOut();
                }, 5000);
            }
        }
        
        getFieldLabel(field) {
            const labels = {
                'nombre': 'nombre',
                'email': 'email',
                'codigo_postal': 'código postal',
                'edad_dia': 'día de nacimiento',
                'edad_mes': 'mes de nacimiento',
                'edad_año': 'año de nacimiento',
                'raza': 'raza'
            };
            
            return labels[field] || field;
        }
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        // Función mejorada de validación de fecha
        isValidDate(day, month, year) {
            const validation = this.validateBirthDate(day, month, year);
            return validation.valid;
        }
    }
    
    // Inicializar cuando el documento esté listo
    new DoguifyComparador();
    
})(jQuery);

// CSS adicional para el error en el nuevo diseño
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.doguify-waiting-page-new')) {
        const style = document.createElement('style');
        style.textContent = `
            .doguify-error-new {
                text-align: center;
                color: white;
                padding: 40px 20px;
            }
            
            .doguify-error-new h3 {
                font-size: 2rem;
                margin: 20px 0;
                color: #ff6b6b;
            }
            
            .doguify-error-new p {
                font-size: 1.1rem;
                margin: 20px 0;
                opacity: 0.9;
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
            }
            
            .doguify-btn-back:hover {
                background: #f0f0f0;
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
            }
        `;
        document.head.appendChild(style);
    }
});