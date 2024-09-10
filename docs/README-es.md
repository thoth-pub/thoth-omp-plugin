[Inglés](README.md) | **Español** | [Portugués brasileño](README-pt_BR.md)

# Plugin Thoth OMP

Integración en progreso de OMP y [Thoth](https://thoth.pub/) para la comunicación y sincronización de datos de libros entre las dos plataformas.

## Compatibilidad

Este plugin es compatible con las siguientes aplicaciones PKP:

- OMP 3.3.0-x

## Requisitos

### Requisitos del Prensa

1. **api_key_secret**

La instancia de OMP debe tener configurado el `api_key_secret`. Puedes contactar a tu administrador del sistema para configurarlo (consulta [esta publicación](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).

Esto es necesario para utilizar las credenciales de la API proporcionadas, que se almacenan cifradas en la base de datos de OMP.

## Instalación

1. Descarga la última versión del paquete de instalación (`thoth.tar.gz`) desde la [página de lanzamientos](https://github.com/lepidus/thoth-omp-plugin/releases).

2. Accede al área de administración de tu sitio OMP a través del Dashboard. Navega a `Configuración` > `Sitio web` > `Plugins` > `Subir un nuevo plugin` y selecciona el archivo `thoth.tar.gz`.

3. Haz clic en 'Guardar' para instalar el plugin.

## Uso

### Configuración

Para configurar el plugin:

- **Correo electrónico** y **Contraseña**: Introduce las credenciales de una cuenta de Thoth para conectar con la API.
- **Entorno de Prueba**: Marca esta opción si estás utilizando una instancia local de la API de Thoth para fines de prueba.

### Gestión de Monografías

- **Monografías No Publicadas**: Registra metadatos en Thoth durante el proceso de publicación seleccionando la opción para registrar metadatos en el modal de publicación y eligiendo un sello.

- **Monografías Publicadas**: Registra metadatos para monografías publicadas utilizando el botón 'Registrar' junto al estado de publicación.

### Actualización de Metadatos

Para actualizar los metadatos en Thoth, despublica la monografía, edita los datos y los cambios se actualizarán automáticamente en Thoth.

### Acceso a Registros de Libros en Thoth

Después de que los metadatos estén publicados, aparecerá un enlace al libro en Thoth en la parte superior de la publicación.

## Créditos

Este plugin fue idealizado y patrocinado por [Thoth](https://thoth.pub/).

Desarrollado por [Lepidus Tecnologia](https://github.com/lepidus).

## Licencia

Este plugin está licenciado bajo la Licencia Pública General GNU v3.0 - [Consulta el archivo de licencia.](/LICENSE)

Copyright (c) 2024 Lepidus Tecnologia

Copyright (c) 2024 Thoth