// Archivo: assets/doguify-image-loader.js
// Script para optimizar la carga de imágenes en la página de espera

document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar en la página de espera con nuevo diseño
    if (!document.querySelector('.doguify-waiting-page-new')) {
        return;
    }
    
    // Precargar imágenes críticas
    const criticalImages = [
        'https://doguify.com/wp-content/uploads/2025/06/Logos_Doguify_blanco-1-_3_-1.webp',
        'https://doguify.com/wp-content/uploads/2025/07/perro-gato-1-e1751989375681.png',
        'https://doguify.com/wp-content/uploads/2025/07/perros-web-1-e1751989411921.png'
    ];
    
    // Función para precargar una imagen
    function preloadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
            img.src = src;
        });
    }
    
    // Precargar todas las imágenes críticas
    Promise.all(criticalImages.map(preloadImage))
        .then(images => {
            console.log('Todas las imágenes críticas cargadas');
            
            // Marcar las imágenes como cargadas para aplicar estilos
            document.querySelectorAll('.doguify-pets-image').forEach(img => {
                img.classList.add('loaded');
            });
            
            // Mejorar la animación inicial
            setTimeout(() => {
                document.querySelector('.doguify-waiting-content').classList.add('fully-loaded');
            }, 300);
        })
        .catch(error => {
            console.warn('Error cargando algunas imágenes:', error);
            
            // Aún así mostrar las imágenes que se hayan cargado
            document.querySelectorAll('.doguify-pets-image').forEach(img => {
                img.classList.add('loaded');
            });
        });
    
    // Lazy loading para imágenes no críticas en dispositivos móviles
    if (window.innerWidth <= 768) {
        // En móviles, las imágenes de mascotas están ocultas, no necesitan cargarse
        document.querySelectorAll('.doguify-left-pets, .doguify-right-pets').forEach(container => {
            container.style.display = 'none';
        });
    }
    
    // Optimizar animaciones según el rendimiento del dispositivo
    function optimizeAnimations() {
        // Detectar si el dispositivo tiene un rendimiento limitado
        const isLowPerformance = navigator.hardwareConcurrency <= 2 || 
                                navigator.deviceMemory <= 2 ||
                                /Android.*Chrome\/[0-5]/.test(navigator.userAgent);
        
        if (isLowPerformance) {
            // Reducir animaciones en dispositivos de bajo rendimiento
            document.documentElement.style.setProperty('--animation-duration', '0.3s');
            
            // Desactivar animaciones de flotación
            document.querySelectorAll('.doguify-left-pets, .doguify-right-pets').forEach(el => {
                el.style.animation = 'none';
            });
        }
    }
    
    optimizeAnimations();
    
    // Manejo de errores de imagen
    document.querySelectorAll('.doguify-pets-image, .doguify-logo').forEach(img => {
        img.addEventListener('error', function() {
            console.warn('Error cargando imagen:', this.src);
            
            // Ocultar contenedor si la imagen falla
            if (this.classList.contains('doguify-pets-image')) {
                this.parentElement.style.display = 'none';
            }
            
            // Para el logo, mostrar texto alternativo
            if (this.classList.contains('doguify-logo')) {
                this.style.display = 'none';
                const logoContainer = this.parentElement;
                logoContainer.innerHTML = '<h2 style="color: white; font-size: 2rem; margin: 0;">Doguify</h2>';
            }
        });
    });
    
    // Optimización de rendering
    function enableGPUAcceleration() {
        const elementsToAccelerate = [
            '.doguify-progress-fill-new',
            '.doguify-left-pets',
            '.doguify-right-pets',
            '.doguify-main-title'
        ];
        
        elementsToAccelerate.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.transform = 'translateZ(0)';
                el.style.willChange = 'transform';
            });
        });
    }
    
    enableGPUAcceleration();
    
    // Cleanup al salir de la página
    window.addEventListener('beforeunload', function() {
        // Limpiar will-change para liberar recursos
        document.querySelectorAll('[style*="will-change"]').forEach(el => {
            el.style.willChange = 'auto';
        });
    });
    
    // Debugging en modo desarrollo
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('staging')) {
        console.log('Doguify Waiting Page - Nuevo diseño cargado');
        
        // Mostrar información de rendimiento
        window.addEventListener('load', function() {
            if (performance.getEntriesByType) {
                const paintMetrics = performance.getEntriesByType('paint');
                paintMetrics.forEach(metric => {
                    console.log(`${metric.name}: ${metric.startTime}ms`);
                });
            }
        });
    }
});

// CSS adicional para las optimizaciones
const optimizationStyles = `
<style>
:root {
    --animation-duration: 0.6s;
}

.doguify-waiting-content.fully-loaded {
    opacity: 1;
}

.doguify-waiting-content.fully-loaded .doguify-main-title {
    transform: translateY(0);
    opacity: 1;
}

/* Mejoras de performance */
.doguify-pets-image {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* Estados de carga mejorados */
.doguify-logo.loading {
    opacity: 0.7;
    transform: scale(0.95);
}

.doguify-pets-image:not(.loaded) {
    opacity: 0;
    transform: scale(0.95);
}

.doguify-pets-image.loaded {
    opacity: 1;
    transform: scale(1);
    transition: all 0.5s ease-out;
}

/* Reducir motion para usuarios que lo prefieren */
@media (prefers-reduced-motion: reduce) {
    .doguify-pets-image {
        transition: none;
    }
    
    .doguify-waiting-content > * {
        animation-duration: 0.1s;
    }
}
</style>
`;

// Inyectar estilos
document.head.insertAdjacentHTML('beforeend', optimizationStyles);