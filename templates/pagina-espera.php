<?php
/**
 * Template de la página de espera con progreso
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

<div class="doguify-waiting-page-new">
    <div class="doguify-waiting-content">
        <!-- Logo Doguify -->
        <div class="doguify-logo-container">
            <img src="https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-1-_3_-1.webp" 
                 alt="Doguify" class="doguify-logo">
        </div>
        
        <!-- Mensaje superior -->
        <p class="doguify-top-message">Por favor no cierres esta página</p>
        
        <!-- Título principal -->
        <h1 class="doguify-main-title">
            Estamos obteniendo<br>
            tus resultados!
        </h1>
        
        <!-- Imagen izquierda (perro y gato) -->
        <div class="doguify-left-pets">
            <img src="https://doguify.com/wp-content/uploads/2025/07/perro-gato-1-e1751989375681.png" 
                 alt="Perro y gato" class="doguify-pets-image">
        </div>
        
        <!-- Imagen derecha (perros) -->
        <div class="doguify-right-pets">
            <img src="https://doguify.com/wp-content/uploads/2025/07/perros-web-1-e1751989411921.png" 
                 alt="Perros" class="doguify-pets-image">
        </div>
        
        <!-- Contenido central -->
        <div class="doguify-center-content">
            <!-- Barra de progreso -->
            <div class="doguify-progress-bar-container">
                <div class="doguify-progress-bar-new">
                    <div class="doguify-progress-fill-new" style="width: 0%"></div>
                </div>
                <div class="doguify-progress-percentage">0%</div>
            </div>
            
            <!-- Texto explicativo -->
            <p class="doguify-explanation-text">
                trabajamos con los mejores proveedores<br>
                para que puedas comparar planes<br>
                y precios en un solo lugar.
            </p>
            
            <!-- Estado del progreso (oculto por CSS) -->
            <div class="doguify-status-container">
                <p class="doguify-status-text">Iniciando consulta...</p>
            </div>
        </div>
    </div>
    
    <!-- Nuevas olas decorativas con el diseño mejorado -->
    <div class="doguify-waves">
        <svg viewBox="0 0 1440 150" preserveAspectRatio="none" class="doguify-wave">
            <path fill="#e6f0fb" d="M0,80 C180,130 360,30 540,80 C720,130 900,30 1080,80 C1260,130 1440,30 1440,30 L1440,150 L0,150 Z" />
        </svg>
    </div>
    
    <!-- Contenido después de las olas -->
    <div class="doguify-wave-content"></div>
</div>

<style>
.doguify-waiting-page-new {
    background: #4690E8;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    width: 100%;
    font-family: 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.doguify-waiting-content {
    position: relative;
    width: 100%;
    max-width: 1200px;
    padding: 80px 20px 20px 20px;
    text-align: center;
    z-index: 2;
}

/* Logo */
.doguify-logo-container {
    margin-bottom: 20px;
}

.doguify-logo {
    height: 60px;
    width: auto;
}

/* Mensaje superior */
.doguify-top-message {
    font-size: 16px;
    margin-bottom: 20px;
    opacity: 0.9;
    font-weight: 400;
}

/* Título principal */
.doguify-main-title {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 40px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    color: white !important;
}

/* Contenido central */
.doguify-center-content {
    position: relative;
    z-index: 3;
    max-width: 600px;
    margin: 0 auto;
}

/* Barra de progreso */
.doguify-progress-bar-container {
    margin-bottom: 30px;
}

.doguify-progress-bar-new {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 25px;
    height: 20px;
    margin-bottom: 15px;
    overflow: hidden;
    position: relative;
    backdrop-filter: blur(10px);
}

.doguify-progress-fill-new {
    background: white;
    height: 100%;
    border-radius: 25px;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
}

.doguify-progress-fill-new::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: doguify-shimmer 2s infinite;
}

@keyframes doguify-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.doguify-progress-percentage {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

/* Texto explicativo */
.doguify-explanation-text {
    font-size: 18px;
    line-height: 1.4;
    margin-bottom: 30px;
    opacity: 0.9;
    font-weight: 400;
}

/* Estado oculto */
.doguify-status-container {
    display: none;
}

.doguify-status-text {
    font-size: 16px;
    font-weight: 500;
    opacity: 0.8;
}

/* Imágenes de mascotas */
.doguify-left-pets {
    position: absolute;
    bottom: 0;
    left: 0;
    z-index: 1;
    max-width: 300px;
}

.doguify-right-pets {
    position: absolute;
    bottom: 0;
    right: 0;
    z-index: 1;
    max-width: 350px;
}

.doguify-pets-image {
    width: 100%;
    height: auto;
    display: block;
}

/* Nuevas olas */
.doguify-waves {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    z-index: 1;
}

.doguify-wave {
    position: relative;
    display: block;
    width: 100%;
    height: 150px;
}

.doguify-wave path {
    fill: #e6f0fb;
}

.doguify-wave-content {
    background-color: #e6f0fb;
    height: 200px;
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 0;
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

.doguify-waiting-content > * {
    animation: doguify-fadeInUp 0.6s ease-out forwards;
}

.doguify-logo-container {
    animation-delay: 0.1s;
}

.doguify-top-message {
    animation-delay: 0.2s;
}

.doguify-main-title {
    animation-delay: 0.3s;
}

.doguify-center-content {
    animation-delay: 0.4s;
}

/* Responsive */
@media (max-width: 1024px) {
    .doguify-left-pets {
        max-width: 250px;
    }
    
    .doguify-right-pets {
        max-width: 280px;
    }
    
    .doguify-main-title {
        font-size: 3rem;
    }
}

@media (max-width: 768px) {
    .doguify-waiting-content {
        padding: 40px 30px 20px 30px;
    }
    
    .doguify-main-title {
        font-size: 2.8rem;
        margin-bottom: 30px;
    }
    
    .doguify-explanation-text {
        font-size: 16px;
    }
    
    .doguify-top-message {
        font-size: 14px;
    }
    
    /* Ocultar imágenes de mascotas en móviles */
    .doguify-left-pets,
    .doguify-right-pets {
        display: none;
    }
    
    .doguify-logo {
        height: 50px;
    }
}

@media (max-width: 480px) {
    .doguify-waiting-content {
        padding: 20px 15px 15px 15px;
    }
    
    .doguify-main-title {
        font-size: 2rem;
    }
    
    .doguify-explanation-text {
        font-size: 14px;
        line-height: 1.5;
    }
    
    .doguify-progress-bar-new {
        height: 16px;
    }
    
    .doguify-progress-percentage {
        font-size: 1.2rem;
    }
    
    .doguify-wave {
        height: 100px;
    }
    
    .doguify-wave-content {
        height: 150px;
    }
}

/* Ajustes para evitar conflictos con el tema */
.doguify-waiting-page-new * {
    box-sizing: border-box;
}

.doguify-waiting-page-new p {
    margin: 0;
}

.doguify-waiting-page-new h1 {
    margin: 0;
}

/* Asegurar que las imágenes no se desborden */
.doguify-left-pets,
.doguify-right-pets {
    pointer-events: none;
}

/* Efectos adicionales para las imágenes */
.doguify-left-pets {
    animation: doguify-float-left 6s ease-in-out infinite;
}

.doguify-right-pets {
    animation: doguify-float-right 6s ease-in-out infinite reverse;
}

@keyframes doguify-float-left {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes doguify-float-right {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
}

/* Estilos específicos para el tema Astra */
body.doguify-waiting-body .ast-container {
    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
}
</style>

<script>
// Pasar el session_id al JavaScript
window.doguify_session_id = '<?php echo esc_js($session_id); ?>';

// Función para actualizar el progreso
window.updateDoguifyProgress = function(progress, statusText) {
    const progressBar = document.querySelector('.doguify-progress-fill-new');
    const progressText = document.querySelector('.doguify-progress-percentage');
    const statusElement = document.querySelector('.doguify-status-text');
    
    if (progressBar && progressText) {
        const roundedProgress = Math.min(Math.round(progress), 100);
        progressBar.style.width = roundedProgress + '%';
        progressText.textContent = roundedProgress + '%';
        
        if (statusText && statusElement) {
            statusElement.textContent = statusText;
        }
    }
};

// Prevenir navegación hacia atrás
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

// Mostrar mensaje si el usuario intenta cerrar la pestaña
window.addEventListener('beforeunload', function(e) {
    const message = 'Tu comparativa se está procesando. ¿Estás seguro de que quieres salir?';
    e.returnValue = message;
    return message;
});

// Simular progreso inicial más suave
document.addEventListener('DOMContentLoaded', function() {
    let progress = 0;
    const progressBar = document.querySelector('.doguify-progress-fill-new');
    const progressText = document.querySelector('.doguify-progress-percentage');
    
    // Animación inicial del logo
    const logo = document.querySelector('.doguify-logo');
    if (logo) {
        logo.style.opacity = '0';
        logo.style.transform = 'scale(0.8)';
        setTimeout(() => {
            logo.style.transition = 'all 0.6s ease-out';
            logo.style.opacity = '1';
            logo.style.transform = 'scale(1)';
        }, 200);
    }
});
</script>

<?php get_footer(); // Mostrar footer del tema ?>