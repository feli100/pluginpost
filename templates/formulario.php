<?php
/**
 * Template del formulario de comparativa
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="doguify-formulario-container">
    <div class="doguify-formulario-wrapper">
        <?php if (!empty($atts['titulo'])): ?>
        <h2 class="doguify-formulario-title"><?php echo esc_html($atts['titulo']); ?></h2>
        <?php endif; ?>
        
        <form id="doguify-formulario-comparativa" class="doguify-formulario-comparativa">
            <!-- Primera fila - Campos básicos -->
            <div class="doguify-formulario-row doguify-formulario-row-main">
                <!-- Radio buttons -->
                <div class="doguify-radio-group">
                    <label class="doguify-radio-option">
                        <input type="radio" name="tipo_mascota" value="perro" checked>
                        <span class="doguify-radio-text">🐕 Perro</span>
                    </label>
                    <label class="doguify-radio-option">
                        <input type="radio" name="tipo_mascota" value="gato">
                        <span class="doguify-radio-text">🐱 Gato</span>
                    </label>
                </div>
                
                <!-- Nombre -->
                <div class="doguify-group">
                    <label class="doguify-label">Nombre de tu mascota</label>
                    <input type="text" name="nombre" class="doguify-input" placeholder="Ej: Max, Luna..." required maxlength="100">
                </div>
                
                <!-- Email -->
                <div class="doguify-group">
                    <label class="doguify-label">Tu email</label>
                    <input type="email" name="email" class="doguify-input" placeholder="tu@email.com" required>
                </div>
            </div>
            
            <!-- Segunda fila - Nuevos campos -->
            <div class="doguify-formulario-row doguify-formulario-row-secondary">
                <!-- Código Postal -->
                <div class="doguify-group">
                    <label class="doguify-label">Código Postal</label>
                    <input type="text" name="codigo_postal" class="doguify-input" placeholder="28001" required maxlength="5" pattern="\d{5}">
                </div>
                
                <!-- Edad (3 campos) -->
                <div class="doguify-group doguify-group-edad">
                    <label class="doguify-label">Fecha de Nacimiento</label>
                    <div class="doguify-edad-inputs">
                        <input type="number" name="edad_dia" class="doguify-input doguify-input-small" placeholder="Día" required min="1" max="31">
                        <input type="number" name="edad_mes" class="doguify-input doguify-input-small" placeholder="Mes" required min="1" max="12">
                        <input type="number" name="edad_año" class="doguify-input doguify-input-small" placeholder="Año" required min="2018">
                    </div>
                    <small class="doguify-fecha-info">Desde 1 enero 2018 hasta hoy</small>
                </div>
                
                <!-- Raza -->
                <div class="doguify-group">
                    <label class="doguify-label">Raza</label>
                    <select name="raza" class="doguify-select" required>
                        <option value="">Seleccionar raza</option>
                        <option value="beagle">Beagle</option>
                        <option value="labrador">Labrador</option>
                        <option value="golden_retriever">Golden Retriever</option>
                        <option value="pastor_aleman">Pastor Alemán</option>
                        <option value="bulldog_frances">Bulldog Francés</option>
                        <option value="chihuahua">Chihuahua</option>
                        <option value="yorkshire">Yorkshire Terrier</option>
                        <option value="boxer">Boxer</option>
                        <option value="cocker_spaniel">Cocker Spaniel</option>
                        <option value="mestizo">Mestizo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <!-- Botón -->
                <div class="doguify-group doguify-button-group">
                    <button type="submit" class="doguify-submit-btn">
                        🔍 Obtener comparativa
                    </button>
                </div>
            </div>
            
            <!-- Checkbox de políticas (fila separada) -->
            <div class="doguify-checkbox-row">
                <div class="doguify-checkbox-group">
                    <input type="checkbox" id="doguify-politicas" name="politicas" class="doguify-checkbox" required>
                    <label for="doguify-politicas" class="doguify-checkbox-text">
                        He leído y acepto la <a href="/politica-de-privacidad/" target="_blank">política de privacidad</a> 
                        y los <a href="/terminos-y-condiciones/" target="_blank">términos y condiciones</a>
                    </label>
                </div>
            </div>
        </form>
        
        <!-- Información adicional -->
        <div class="doguify-info-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; font-size: 14px;">
                <div style="text-align: center;">
                    <div style="font-size: 2em; margin-bottom: 10px;">🛡️</div>
                    <strong>100% Seguro</strong><br>
                    Tus datos están protegidos
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; margin-bottom: 10px;">⚡</div>
                    <strong>Resultados Instantáneos</strong><br>
                    Comparativa en segundos
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; margin-bottom: 10px;">💰</div>
                    <strong>Mejores Precios</strong><br>
                    Comparamos por ti
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configurar validación de fecha mejorada
document.addEventListener('DOMContentLoaded', function() {
    const diaInput = document.querySelector('input[name="edad_dia"]');
    const mesInput = document.querySelector('input[name="edad_mes"]');
    const añoInput = document.querySelector('input[name="edad_año"]');
    
    // Configurar año máximo dinámicamente
    if (añoInput) {
        añoInput.setAttribute('max', new Date().getFullYear());
    }
    
    // Función para validar fecha en tiempo real
    function validarFecha() {
        const dia = parseInt(diaInput.value) || 0;
        const mes = parseInt(mesInput.value) || 0;
        const año = parseInt(añoInput.value) || 0;
        
        // Limpiar clases de error previas
        [diaInput, mesInput, añoInput].forEach(input => {
            input.classList.remove('error');
        });
        
        if (dia && mes && año) {
            // Verificar si la fecha es válida
            const fecha = new Date(año, mes - 1, dia);
            const fechaValida = fecha.getDate() === dia && 
                               fecha.getMonth() === mes - 1 && 
                               fecha.getFullYear() === año;
            
            if (!fechaValida) {
                // Fecha inválida (ej: 30 de febrero)
                [diaInput, mesInput, añoInput].forEach(input => {
                    input.classList.add('error');
                });
                return false;
            }
            
            // Verificar rango de fechas (1 enero 2018 hasta hoy)
            const fechaMinima = new Date(2018, 0, 1); // 1 enero 2018
            const fechaMaxima = new Date(); // Hoy
            
            if (fecha < fechaMinima || fecha > fechaMaxima) {
                [diaInput, mesInput, añoInput].forEach(input => {
                    input.classList.add('error');
                });
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    // Agregar event listeners para validación en tiempo real
    [diaInput, mesInput, añoInput].forEach(input => {
        if (input) {
            input.addEventListener('blur', validarFecha);
            input.addEventListener('input', function() {
                // Validar después de un pequeño delay
                setTimeout(validarFecha, 300);
            });
        }
    });
    
    // Mejorar UX del código postal
    const cpInput = document.querySelector('input[name="codigo_postal"]');
    if (cpInput) {
        cpInput.addEventListener('input', function() {
            // Solo permitir números
            this.value = this.value.replace(/\D/g, '');
            // Máximo 5 dígitos
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
        
        // Agregar placeholder dinámico
        cpInput.setAttribute('title', 'Introduce tu código postal (5 dígitos)');
    }
    
    // Auto-avanzar entre campos de fecha
    if (diaInput && mesInput && añoInput) {
        diaInput.addEventListener('input', function() {
            if (this.value.length === 2 && parseInt(this.value) <= 31) {
                mesInput.focus();
            }
        });
        
        mesInput.addEventListener('input', function() {
            if (this.value.length === 2 && parseInt(this.value) <= 12) {
                añoInput.focus();
            }
        });
        
        // Validación en tiempo real para día
        diaInput.addEventListener('blur', function() {
            const val = parseInt(this.value);
            if (val < 1) this.value = 1;
            if (val > 31) this.value = 31;
        });
        
        // Validación en tiempo real para mes
        mesInput.addEventListener('blur', function() {
            const val = parseInt(this.value);
            if (val < 1) this.value = 1;
            if (val > 12) this.value = 12;
        });
        
        // Validación para año
        añoInput.addEventListener('blur', function() {
            const val = parseInt(this.value);
            const añoActual = new Date().getFullYear();
            if (val < 2018) this.value = 2018;
            if (val > añoActual) this.value = añoActual;
        });
    }
});
</script>

<style>
/* Estilos específicos para este template */
.doguify-info-section {
    opacity: 0.9;
}

.doguify-formulario-wrapper .doguify-info-section strong {
    color: #ffffff;
}

.doguify-fecha-info {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    margin-top: 5px;
    display: block;
    text-align: center;
}

/* Estilos para campos con error */
.doguify-input.error {
    border: 2px solid #e74c3c !important;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2) !important;
}

/* Animación de entrada */
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

.doguify-formulario-container {
    animation: doguify-fadeInUp 0.6s ease-out;
}

/* Mejoras visuales adicionales */
.doguify-formulario-row:hover .doguify-input:not(:focus),
.doguify-formulario-row:hover .doguify-select:not(:focus) {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.doguify-submit-btn {
    position: relative;
    overflow: hidden;
}

.doguify-submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.doguify-submit-btn:hover::before {
    left: 100%;
}

/* Responsive específico del template */
@media (max-width: 768px) {
    .doguify-info-section {
        margin-top: 20px;
        padding-top: 15px;
    }
    
    .doguify-info-section > div {
        grid-template-columns: 1fr;
        gap: 15px;
        font-size: 13px;
    }
}
</style>