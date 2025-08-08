<?php
/**
 * Template de la página de espera con progreso - Doguify
 * Versión completamente rediseñada
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

get_header(); // Mostrar header del tema
?>

<div class="doguify-waiting-page-wrapper">
    <div class="doguify-waiting-container">
        <div class="doguify-content-grid">
            <!-- Imagen izquierda (perro y gato) -->
            <div class="doguify-side-image doguify-left-image">
                <img src="https://doguify.com/wp-content/uploads/2025/07/perro-gato-1-e1751989375681.png" 
                     alt="Perro y gato" 
                     loading="lazy">
            </div>
            
            <!-- Contenido central -->
            <div class="doguify-main-content">
                <!-- Logo Doguify -->
                <div class="doguify-logo">
                    <img src="https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-scaled-e1750429316951.png" 
                         alt="Doguify Logo">
                </div>
                
                <!-- Mensaje principal -->
                <div class="doguify-message">
                    <div class="doguify-small-text">Por favor no cierres esta página</div>
                    <h1 class="doguify-main-title">Estamos obteniendo<br>tus resultados!</h1>
                </div>
                
                <!-- Barra de progreso -->
                <div class="doguify-progress-container">
                    <div class="doguify-progress-bar" id="doguifyProgressBar"></div>
                </div>
                
                <div class="doguify-progress-text" id="doguifyProgressText">0%</div>
                
                <div class="doguify-sub-text">
                    trabajamos con los mejores proveedores<br>
                    para que puedas comparar planes<br>
                    y precios en un solo lugar
                </div>
            </div>
            
            <!-- Imagen derecha (perros) -->
            <div class="doguify-side-image doguify-right-image">
                <img src="https://doguify.com/wp-content/uploads/2025/07/perros-web-1-e1751989411921.png" 
                     alt="Perros" 
                     loading="lazy">
            </div>
        </div>
    </div>
    
    <!-- Efecto de olas decorativas -->
    <div class="doguify-wave-container">
        <div class="doguify-wave"></div>
        <div class="doguify-wave"></div>
        <div class="doguify-wave"></div>
    </div>
</div>

<style>
/* ========================================
   DOGUIFY WAITING PAGE - COMPLETE REDESIGN
   ======================================== */

.doguify-waiting-page-wrapper {
    /* Fondo azul gradiente */
    background: linear-gradient(135deg, #4A90E2 0%, #357ABD 50%, #2E6DA4 100%) !important;
    min-height: 100vh;
    position: relative;
    color: white;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    
    /* Resetear márgenes del tema */
    margin: 0 !important;
    padding: 0 !important;
    
    /* Expandir a ancho completo */
    width: 100vw;
    margin-left: calc(-50vw + 50%) !important;
    
    /* Ocultar scroll horizontal */
    overflow-x: hidden;
}

/* Resetear contenedores del tema WordPress */
.doguify-waiting-page-wrapper .ast-container,
.doguify-waiting-page-wrapper .container,
.doguify-waiting-page-wrapper .site-main,
.doguify-waiting-page-wrapper .entry-content {
    max-width: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
}

.doguify-waiting-container {
    position: relative;
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px 120px 20px;
    z-index: 2;
}

.doguify-content-grid {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    align-items: center;
    gap: 40px;
    max-width: 1200px;
    width: 100%;
}

.doguify-side-image {
    display: flex;
    justify-content: center;
    align-items: center;
}

.doguify-side-image img {
    max-width: 100%;
    height: auto;
    max-height: 400px;
    object-fit: contain;
}

.doguify-main-content {
    text-align: center;
    padding: 20px;
}

.doguify-logo {
    margin-bottom: 30px;
}

.doguify-logo img {
    max-width: 250px;
    height: auto;
}

.doguify-message {
    margin-bottom: 15px;
}

.doguify-small-text {
    font-size: 16px;
    margin-bottom: 8px;
    opacity: 0.9;
}

.doguify-main-title {
    font-size: 2.2rem;
    font-weight: 600;
    line-height: 1.2;
    margin-bottom: 30px !important;
    margin-top: 0 !important;
    color: white !important;
}

.doguify-progress-container {
    width: 100%;
    max-width: 400px;
    margin: 0 auto 20px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    padding: 4px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.doguify-progress-bar {
    height: 12px;
    background: white;
    border-radius: 50px;
    transition: width 3s ease-in-out;
    position: relative;
    overflow: hidden;
    width: 0%;
}

.doguify-progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255,255,255,0.3) 50%, 
        transparent 100%);
    animation: doguify-shimmer 2s infinite;
}

@keyframes doguify-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.doguify-progress-text {
    font-size: 2rem;
    font-weight: bold;
    margin: 15px 0;
    color: white !important;
}

.doguify-sub-text {
    font-size: 14px;
    opacity: 0.8;
    max-width: 350px;
    margin: 0 auto;
    line-height: 1.4;
    color: white !important;
}

/* Efecto de olas en la parte inferior */
.doguify-wave-container {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 80px;
    z-index: 1;
}

.doguify-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: #ECF3FD;
    clip-path: polygon(0 30%, 20% 40%, 40% 35%, 60% 45%, 80% 30%, 100% 40%, 100% 100%, 0% 100%);
}

.doguify-wave:nth-child(2) {
    background: rgba(236, 243, 253, 0.8);
    clip-path: polygon(0 45%, 25% 55%, 45% 50%, 65% 60%, 85% 45%, 100% 55%, 100% 100%, 0% 100%);
    height: 45px;
}

.doguify-wave:nth-child(3) {
    background: rgba(236, 243, 253, 0.6);
    clip-path: polygon(0 60%, 30% 70%, 50% 65%, 70% 75%, 90% 60%, 100% 70%, 100% 100%, 0% 100%);
    height: 30px;
}

/* Resetear colores del texto para evitar conflictos con el tema */
.doguify-waiting-page-wrapper h1,
.doguify-waiting-page-wrapper p,
.doguify-waiting-page-wrapper div {
    color: white !important;
}

.doguify-waiting-page-wrapper * {
    box-sizing: border-box;
}

/* Ocultar elementos del tema que interfieren */
.doguify-waiting-page-wrapper .ast-breadcrumbs,
.doguify-waiting-page-wrapper .entry-header,
.doguify-waiting-page-wrapper .ast-single-post,
.doguify-waiting-page-wrapper .post-navigation {
    display: none !important;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .doguify-content-grid {
        grid-template-columns: 1fr 3fr 1fr;
        gap: 20px;
    }
    
    .doguify-main-title {
        font-size: 2rem !important;
    }
    
    .doguify-side-image img {
        max-height: 350px;
    }
}

@media (max-width: 768px) {
    .doguify-content-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .doguify-side-image {
        display: none;
    }
    
    .doguify-main-content {
        padding: 10px;
    }
    
    .doguify-main-title {
        font-size: 1.8rem !important;
    }
    
    .doguify-progress-text {
        font-size: 1.5rem;
    }
    
    .doguify-waiting-container {
        padding: 20px 20px 100px 20px;
        min-height: 70vh;
    }
}

@media (max-width: 480px) {
    .doguify-main-title {
        font-size: 1.5rem !important;
    }
    
    .doguify-waiting-container {
        padding: 10px 10px 80px 10px;
    }
    
    .doguify-logo img {
        max-width: 200px;
    }
}

/* Clase específica para el body */
body.doguify-waiting-body {
    background: linear-gradient(135deg, #4A90E2 0%, #357ABD 50%, #2E6DA4 100%) !important;
}
</style>

<script>
(function() {
    'use strict';
    
    // Variables globales
    let doguifyProgress = 0;
    const doguifySessionId = '<?php echo esc_js($session_id); ?>';
    
    // Textos dinámicos durante la carga
    const loadingTexts = [
        "trabajamos con los mejores proveedores<br>para que puedas comparar planes<br>y precios en un solo lugar",
        "analizando las mejores opciones<br>para tu mascota",
        "comparando precios y coberturas<br>en tiempo real",
        "finalizando tu comparativa<br>personalizada"
    ];
    
    let textIndex = 0;
    
    // Función principal para actualizar el progreso
    function updateDoguifyProgress() {
        if (doguifyProgress <= 100) {
            const roundedProgress = Math.round(doguifyProgress);
            const progressBar = document.getElementById('doguifyProgressBar');
            const progressText = document.getElementById('doguifyProgressText');
            
            if (progressBar && progressText) {
                progressBar.style.width = roundedProgress + '%';
                progressText.textContent = roundedProgress + '%';
            }
            
            // Velocidad de progreso variable (más realista)
            if (doguifyProgress < 30) {
                doguifyProgress += Math.random() * 3 + 1;
            } else if (doguifyProgress < 70) {
                doguifyProgress += Math.random() * 2 + 0.5;
            } else if (doguifyProgress < 90) {
                doguifyProgress += Math.random() * 1 + 0.2;
            } else if (doguifyProgress < 100) {
                doguifyProgress += Math.random() * 0.5 + 0.1;
            }
            
            setTimeout(updateDoguifyProgress, Math.random() * 300 + 200);
        } else {
            // Completado al 100%
            const progressBar = document.getElementById('doguifyProgressBar');
            const progressText = document.getElementById('doguifyProgressText');
            
            if (progressBar && progressText) {
                progressBar.style.width = '100%';
                progressText.textContent = '100%';
            }
            
            // Redirigir a la página de resultados
            setTimeout(() => {
                if (doguifySessionId) {
                    window.location.href = '<?php echo home_url('/doguify-resultado/'); ?>?session=' + doguifySessionId;
                }
            }, 1000);
        }
    }
    
    // Cambiar textos dinámicamente
    function changeLoadingText() {
        if (doguifyProgress > 20 && doguifyProgress < 100) {
            const subTextElement = document.querySelector('.doguify-sub-text');
            if (subTextElement) {
                textIndex = (textIndex + 1) % loadingTexts.length;
                subTextElement.innerHTML = loadingTexts[textIndex];
            }
        }
    }
    
    // Función expuesta globalmente para uso del plugin
    window.updateDoguifyProgress = function(progress, statusText) {
        const progressBar = document.getElementById('doguifyProgressBar');
        const progressText = document.getElementById('doguifyProgressText');
        
        if (progressBar && progressText) {
            const roundedProgress = Math.min(Math.round(progress), 100);
            progressBar.style.width = roundedProgress + '%';
            progressText.textContent = roundedProgress + '%';
            doguifyProgress = progress;
        }
    };
    
    // Inicializar cuando se carga el DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar clase específica al body
        document.body.classList.add('doguify-waiting-body');
        
        // Iniciar la simulación de progreso
        setTimeout(updateDoguifyProgress, 500);
        
        // Cambiar textos cada 4 segundos
        setInterval(changeLoadingText, 4000);
        
        console.log('Doguify Waiting Page: Iniciada para sesión ' + doguifySessionId);
    });
    
})();
</script>

<?php get_footer(); // Mostrar footer del tema ?>
