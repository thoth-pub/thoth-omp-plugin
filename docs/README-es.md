[English](/README.md) | **Español** | [Português Brasileiro](/docs/README-pt_BR.md)

# Plugin Thoth OMP

Integración en progreso de OMP y [Thoth](https://thoth.pub/) para la comunicación y sincronización de datos de libros entre las dos plataformas.

## Compatibilidad

Este plugin es compatible con las siguientes aplicaciones PKP:

- OMP 3.3.0-x
- OMP 3.4.0-x

## Requisitos

### Requisitos del Prensa

1. **api_key_secret**

La instancia de OMP debe tener configurado el `api_key_secret`. Puedes contactar a tu administrador del sistema para configurarlo (consulta [esta publicación](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).

Esto es necesario para utilizar las credenciales de la API proporcionadas, que se almacenan cifradas en la base de datos de OMP.

## Instalación

1. Descarga la última versión del paquete de instalación (`thoth.tar.gz`) desde la [página de lanzamientos](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Accede al área de administración de tu sitio OMP a través del Dashboard. Navega a `Configuración` > `Sitio web` > `Plugins` > `Subir un nuevo plugin` y selecciona el archivo `thoth.tar.gz`.

3. Haz clic en 'Guardar' para instalar el plugin.

## Uso

### Orientaciones

- Solo se conservan las etiquetas HTML básicas (`<strong>`, `<mark>`, `<em>`, `<i>`, `<u>`, `<sup>`, `<sub>`, `<ul>`, `<ol>` y `<li>`); todas las demás serán eliminadas
- El ISBN debe estar correctamente formateado (por ejemplo, 978-3-16-148410-0)

### Configuración

Para configurar el plugin:

- **Correo electrónico** y **Contraseña**: Introduce las credenciales de una cuenta de Thoth para conectar con la API.
- **Entorno de Prueba**: Marca esta opción si estás utilizando una instancia local de la API de Thoth para fines de prueba.

![settings](/images/settings.png)

### Gestión de Monografías

- **Monografías No Publicadas**: Registra metadatos en Thoth durante el proceso de publicación seleccionando la opción para registrar metadatos en el modal de publicación y eligiendo un sello.

![publish](/images/publish.png)

- **Monografías Publicadas**: Registra metadatos para monografías publicadas utilizando el botón 'Registrar' junto al estado de publicación.

![button](/images/button.png)
![register](/images/register.png)

### Actualización de Metadatos

Para actualizar los metadatos en Thoth, despublica la monografía, edita los datos y los cambios se actualizarán automáticamente en Thoth.

### Acceso a Registros de Libros en Thoth

Después de que los metadatos estén publicados, aparecerá un enlace al libro en Thoth en la parte superior de la publicación.

![link](/images/link.png)

### Registro masivo

En la página de Thoth, puedes enviar en masa una selección de títulos de OMP a Thoth.

![page](/images/page.png)

## Mapeo OMP-Thoth

<details>
    <summary>Haga clic aquí para ver la relación de datos entre Thoth y OMP</summary>

| OMP               |                    |   | Thoth                  |                     |             |
| ----------------- | ------------------ | - | ---------------------- | ------------------- | ----------- |
| Submission        |                    |   | Work                   |                     |             |
|                   | WorkType           |   |                        | WorkType            |             |
| SubmissionUrl     |                    |   |                        | LandingPage         |             |
| Publication       |                    |   |                        |                     |             |
|                   | FullTitle          |   |                        | FullTitle           |             |
|                   | Title              |   |                        | Title               |             |
|                   | Subtitle           |   |                        | Subtitle            |             |
|                   | Abstract           |   |                        | Abstract            |             |
|                   | Version            |   |                        | Edition             |             |
|                   | DOI                |   |                        | DOI                 |             |
|                   | DatePublished      |   |                        | PublicationDate     |             |
|                   | License            |   |                        | License             |             |
|                   | CopyrightHolder    |   |                        | CopyrightHolder     |             |
|                   | CoverUrl           |   |                        | CoverImageUrl       |             |
| Author            |                    |   | Contribution           |                     |             |
|                   | UserGroupId        |   |                        | ContributionType    |             |
|                   | PrimaryContactId   |   |                        | MainContribution    |             |
|                   | Sequence           |   |                        | ContributionOrdinal |             |
|                   | GivenName          |   |                        | FirstName           |             |
|                   | LastName           |   |                        | FamilyName          |             |
|                   | FullName           |   |                        | FullName            |             |
|                   | Biography          |   |                        | Biography           |             |
|                   | Affiliation        |   | Affiliation            |                     |             |
| Chapter           |                    |   | Work(Type: Chapter)    |                     |             |
|                   | FullTitle          |   |                        | FullTitle           |             |
|                   | Title              |   |                        | Title               |             |
|                   | Subtitle           |   |                        | Subtitle            |             |
|                   | Abstract           |   |                        | Abstract            |             |
|                   | Pages              |   |                        | pageCount           |             |
|                   | DatePublished      |   |                        | PublicationDate     |             |
|                   | DOI                |   |                        | DOI                 |             |
| SubmissionLocale  |                    |   | Language               |                     |             |
| PublicationFormat |                    |   | Publication            |                     |             |
|                   | EntryKey           |   |                        | PublicationType     |             |
|                   | IdentificationCode |   |                        | ISBN                |             |
|                   |                    |   |                        | Location            |             |
|                   | RemoteUrl/FileUrl  |   |                        |                     | FullTextUrl |
| SubmissionUrl     |                    |   |                        |                     | LandingPage |
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

</details>

## Créditos

Este plugin fue idealizado y patrocinado por [Thoth](https://thoth.pub/).

Desarrollado por [Lepidus Tecnologia](https://github.com/lepidus).

## Licencia

Este plugin está licenciado bajo la Licencia Pública General GNU v3.0 - [Consulta el archivo de licencia.](/LICENSE)

Copyright (c) 2024 Lepidus Tecnologia

Copyright (c) 2024 Thoth