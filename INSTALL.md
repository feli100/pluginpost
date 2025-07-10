# 🚀 Guía de Instalación Rápida - Doguify Comparador

## 📋 Requisitos Previos

Antes de instalar el plugin, asegúrate de cumplir con estos requisitos:

- ✅ **WordPress 5.0+**
- ✅ **PHP 7.4+**
- ✅ **MySQL 5.6+**
- ✅ **Extensión cURL de PHP**
- ✅ **Extensión JSON de PHP**
- ✅ **Permisos de escritura en wp-content**

## 📦 Instalación

### Método 1: Instalación Manual

1. **Descarga los archivos del plugin**
2. **Sube la carpeta** `doguify-comparador` a `/wp-content/plugins/`
3. **Activa el plugin** desde WordPress Admin → Plugins
4. **¡Listo!** El plugin se configurará automáticamente

### Método 2: Instalación via ZIP

1. **Comprime todos los archivos** en un ZIP llamado `doguify-comparador.zip`
2. **Ve a** WordPress Admin → Plugins → Añadir nuevo
3. **Haz clic en** "Subir plugin"
4. **Selecciona el ZIP** y haz clic en "Instalar ahora"
5. **Activa el plugin**

## ⚙️ Configuración Inicial

### 1. Acceso al Panel de Administración

Una vez activado, verás un nuevo menú en WordPress Admin:

```
🐕 Doguify Comparador
├── Comparativas
├── Configuración  
└── Estadísticas
```

### 2. Configuración Básica

Ve a **Doguify Comparador → Configuración** y configura:

#### 🔗 Integración con Petplan
- ✅ **Habilitar consultas a Petplan**: Activado
- ⏱️ **Tiempo límite**: 30 segundos

#### 📧 Notificaciones
- ✅ **Enviar notificaciones**: Activado
- 📧 **Email admin**: tu@email.com

#### 🎨 Personalización
- 📝 **Título página resultados**: ¡Tu comparativa está lista!
- 📝 **Subtítulo**: Hemos encontrado la mejor opción para tu mascota

### 3. Configurar Razas (Opcional)

En la sección **Gestión de Razas**, puedes añadir más razas:

```
beagle
labrador
golden_retriever
pastor_aleman
bulldog_frances
chihuahua
yorkshire
boxer
cocker_spaniel
mestizo
otro
```

## 📄 Uso del Plugin

### Shortcode Principal

Añade el formulario a cualquier página o post:

```php
[doguify_formulario]
```

**Con título personalizado:**
```php
[doguify_formulario titulo="Compara seguros para tu mascota"]
```

### En Código PHP

```php
echo do_shortcode('[doguify_formulario]');
```

### Widget

1. Ve a **Apariencia → Widgets**
2. Arrastra **🐕 Doguify Comparador** a tu sidebar
3. Configura el título y guarda

### Bloque de Gutenberg

1. **Añade un bloque** en el editor
2. **Busca** "Doguify"
3. **Selecciona** "🐕 Doguify Comparador"
4. **Configura** el título en la barra lateral

## 🔧 Verificación de Funcionamiento

### 1. Verificar Base de Datos

Ve a **Doguify Comparador → Comparativas**. Deberías ver:
- ✅ Estadísticas en la parte superior
- ✅ Tabla preparada para registros
- ✅ Sin errores

### 2. Probar el Formulario

1. **Añade el shortcode** a una página de prueba
2. **Completa el formulario** con datos de prueba:
   - Tipo: Perro
   - Nombre: Test
   - Email: test@email.com
   - CP: 28001
   - Fecha: 01/01/2020
   - Raza: Beagle
3. **Envía el formulario**
4. **Verifica** que te redirige a la página de espera

### 3. Verificar Páginas Automáticas

Estas URLs deberían funcionar automáticamente:
- ✅ `tudominio.com/doguify-espera/`
- ✅ `tudominio.com/doguify-resultado/`

## 🚨 Solución de Problemas

### Problema: Error 404 en páginas de espera

**Solución:**
```php
// Añadir a functions.php temporalmente
flush_rewrite_rules();
```

Luego elimina esta línea.

### Problema: No se guardan los datos

**Verificar:**
1. ✅ Tabla creada: `wp_doguify_comparativas`
2. ✅ AJAX funciona: Abre consola del navegador
3. ✅ Permisos: Directorio wp-content escribible

### Problema: No funciona Petplan

**Verificar:**
```php
// Test de conectividad
$response = wp_remote_get('https://ws.petplan.es/pricing?postalcode=28001&age=01/01/2020&column=2&breed=beagle');
var_dump($response);
```

### Problema: Estilos no se cargan

**Solución:**
1. **Limpiar cache** del navegador
2. **Verificar permisos** de archivos CSS
3. **Comprobar** que no hay conflictos con el tema

## 📊 Monitoreo

### Dashboard Widget

En el Dashboard de WordPress verás un widget con:
- 📈 **Estadísticas rápidas**
- 🔧 **Estado del sistema**
- 🔗 **Enlaces rápidos**

### Logs de Debug

Para activar logs detallados:

1. Ve a **Configuración → Configuración Avanzada**
2. Activa **Modo debug**
3. Los logs aparecerán en `wp-content/debug.log`

### Health Check

El plugin verifica automáticamente:
- ✅ **Base de datos**: Tablas creadas
- ✅ **Petplan**: Conectividad API
- ✅ **Archivos**: Permisos correctos
- ✅ **Cron**: Tareas programadas

## 🔄 Actualizaciones

### Automáticas

El plugin incluye migraciones automáticas. Al actualizar:
1. **Desactiva** el plugin
2. **Sube** los nuevos archivos
3. **Activa** el plugin
4. Las migraciones se ejecutan automáticamente

### Backup Antes de Actualizar

```sql
-- Backup de seguridad
CREATE TABLE wp_doguify_comparativas_backup AS 
SELECT * FROM wp_doguify_comparativas;
```

## 📞 Soporte

### Información del Sistema

Ve a **Configuración → Información del Sistema** para ver:
- 🔧 **Versión del plugin**
- 🐘 **Versión de PHP**
- 📊 **Estado de la base de datos**
- 🔗 **URLs importantes**

### Logs y Debug

Para reportar errores, incluye:
1. **Versión de WordPress y PHP**
2. **Logs del plugin** (si están activados)
3. **Pasos para reproducir** el problema
4. **Capturas de pantalla** si es necesario

## ✅ Checklist Post-Instalación

- [ ] Plugin activado correctamente
- [ ] Configuración básica completada
- [ ] Shortcode funcionando en una página
- [ ] Páginas de espera/resultado accesibles
- [ ] Primer registro de prueba creado
- [ ] Dashboard widget visible
- [ ] Notificaciones por email configuradas
- [ ] Backup de base de datos realizado

## 🎉 ¡Felicidades!

Tu plugin Doguify Comparador está listo para usar. Los usuarios pueden ahora:

- 🐕 **Comparar seguros** para sus mascotas
- ⚡ **Obtener precios** automáticamente de Petplan
- 📧 **Recibir resultados** en tiempo real
- 📱 **Usar** desde cualquier dispositivo

**¿Necesitas ayuda?** Consulta el archivo `README.md` para documentación completa.

---

**🐕 ¡Hecho con amor para las mascotas! 🐱**