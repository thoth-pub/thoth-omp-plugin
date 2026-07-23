[English](/README.md) | **Español** | [Português Brasileiro](/docs/README-pt_BR.md)

# Plugin Thoth OMP

[![Versión Actual](https://img.shields.io/badge/versión-v0.3.0.0-blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
[![Licencia: GPL v3](https://img.shields.io/badge/Licencia-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Compatibilidad OMP](https://img.shields.io/badge/OMP-3.3_%7C_3.4_%7C_3.5-blue)](https://pkp.sfu.ca/software/omp/)

Integra [OMP (Open Monograph Press)](https://pkp.sfu.ca/software/omp/) con [Thoth](https://thoth.pub/), una plataforma abierta de gestión de metadatos para libros. Este plugin permite el registro y la sincronización de metadatos a nivel de libro y capítulo directamente desde OMP hacia Thoth, donde pueden ser diseminados en múltiples formatos estándar de la industria, incluyendo ONIX, MARC, KBART y Crossref XML.

## Compatibilidad

Este plugin es compatible con las siguientes aplicaciones PKP:

- OMP 3.3.0-x
- OMP 3.4.0-x
- OMP 3.5.0-x

## Instalación

1. Descargue la última versión del paquete de instalación (`thoth.tar.gz`) desde la [página de lanzamientos](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Acceda al área de administración de su sitio OMP a través del Dashboard. Navegue a `Configuración` > `Sitio web` > `Plugins` > `Subir un nuevo plugin` y seleccione el archivo `thoth.tar.gz`.

3. Haga clic en 'Guardar' para instalar el plugin.

## Uso

### Configuración

Después de habilitar el plugin, vaya a la configuración del plugin y complete:

- **Token de acceso personal**: Un token de acceso personal válido de Thoth para autenticar las solicitudes a la API.
- **API Thoth personalizada**: Marque esta opción para usar una API Thoth personalizada en lugar de la oficial.
- **URL de la API Thoth**: La URL de la API Thoth personalizada (solo requerida cuando la opción de API personalizada está habilitada).

<img src="/docs/images/plugin_settings.png" alt="Formulario de configuración del plugin con token de acceso personal, API personalizada y URL" width="700">

### Registro de Monografías

#### Monografías No Publicadas

Registre metadatos en Thoth durante el proceso de publicación seleccionando la opción para registrar metadatos en el modal de publicación y eligiendo una imprenta.

<img src="/docs/images/register_field.png" alt="Modal de publicación con opción de registro en Thoth" width="700">

#### Monografías Publicadas

Registre metadatos para monografías ya publicadas utilizando el botón 'Registrar' junto al estado de publicación.

<img src="/docs/images/register_button.png" alt="Botón de registro en el flujo de trabajo de publicación" width="700">
<img src="/docs/images/register_modal.png" alt="Modal de registro con selección de imprenta" width="700">

### Actualización de Metadatos

Una vez que una monografía está registrada, las actualizaciones de metadatos son **automáticas**. Despublique la monografía, edite los datos y los cambios se sincronizarán con Thoth al republicar.

También es posible actualizar manualmente los metadatos en Thoth haciendo clic en el botón 'Actualizar metadatos' junto al estado de publicación.

<img src="/docs/images/update_button.png" alt="Botón de actualización de metadatos en el flujo de trabajo de publicación" width="700">

### Acceso a Registros de Libros en Thoth

Después de que los metadatos estén registrados, aparecerá un enlace al libro en Thoth en la parte superior del flujo de trabajo de publicación.

<img src="/docs/images/view_button.png" alt="Enlace al registro del libro en Thoth" width="700">

### Envío y Alojamiento de Archivos en Thoth

El plugin permite enviar a Thoth archivos de publicación, la portada y un vídeo destacado. Estas funciones están
disponibles después de registrar el libro en Thoth y requieren que el usuario configurado en Thoth tenga el permiso
`cdnWrite`.

#### Archivos de Publicación

En el flujo de publicación, abra la cuadrícula de formatos de publicación y use **Enviar a Thoth** en el formato
deseado. La acción aparece debajo de los detalles del formato y encima de los archivos asociados a este.

<img src="/docs/images/publication_file_upload_action.png" alt="Acción Enviar a Thoth en la cuadrícula de formatos de publicación" width="700">

En el formulario de envío, seleccione un archivo e indique si pertenece a la monografía o a un capítulo. La monografía
o el capítulo seleccionado debe tener DOI. Los archivos pueden tener hasta 50 MB.

<img src="/docs/images/publication_file_upload_form.png" alt="Formulario de envío a Thoth con el archivo y el componente de publicación relacionado" width="700">

Después del envío, el botón **Ver**, en la columna **Archivos en Thoth**, abre la lista de archivos de la monografía y
de los capítulos alojados por Thoth. Cada nombre de archivo es un enlace al archivo identificado por su DOI.

<img src="/docs/images/publication_file_view_action.png" alt="Botón Ver en la columna Archivos en Thoth" width="700">

<img src="/docs/images/publication_file_view_form.png" alt="Lista de archivos de la monografía y los capítulos alojados por Thoth" width="700">

En la página pública del libro, los archivos alojados por Thoth se muestran junto con los archivos de los formatos de
publicación de OMP.

<img src="/docs/images/publication_file_landing_page.png" alt="Página pública del libro mostrando archivos alojados por Thoth junto con los archivos de los formatos de publicación de OMP" width="700">

#### Portada

En el formulario de entrada de catálogo, cargue la portada del libro y seleccione **Alojar el archivo de esta imagen
de cubierta en Thoth**, debajo del campo de portada. Después de guardar y sincronizar la publicación, la página
pública del libro utilizará la portada alojada en Thoth. Al desmarcar esta opción, el plugin deja de utilizar la portada
alojada anteriormente.

<img src="/docs/images/cover_upload.png" alt="Opción de alojamiento del archivo de portada en Thoth debajo del campo de imagen de portada" width="700">

#### Vídeo Destacado

Abra la pestaña **Marketing** en el flujo de publicación y seleccione **Vídeo destacado**, debajo de **Fechas de
publicación**. Añada un título y cargue un archivo MP4, WebM o MOV. Thoth aloja el archivo y lo muestra con controles
de reproducción en la página pública del libro, después de la información y la sinopsis. Cada libro puede tener un
vídeo destacado.

<img src="/docs/images/feature_video_form.png" alt="Formulario del vídeo destacado con título y archivo de vídeo cargado" width="700">

<img src="/docs/images/feature_video_landing_page.png" alt="Reproductor del vídeo destacado en la página pública del libro" width="700">

### Registro Masivo

En la página de gestión de Thoth, puede enviar en masa una selección de títulos desde OMP hacia Thoth.

<img src="/docs/images/bulk_register_page.png" alt="Página de gestión de Thoth con registro masivo" width="700">

### Orientaciones

- Solo se conservan las etiquetas HTML básicas en campos de texto: `<strong>`, `<mark>`, `<em>`, `<i>`, `<u>`, `<sup>`, `<sub>`, `<ul>`, `<ol>` y `<li>`. Todas las demás etiquetas serán eliminadas.
- El ISBN debe estar correctamente formateado como ISBN-13 (por ejemplo, `978-3-16-148410-0`).

## Mapeo OMP-Thoth

<details>
<summary>Haga clic aquí para ver la relación de datos entre OMP y Thoth</summary>

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
| Subject           |                    |   | Subject(Type: esquema detectado o Keyword) |          |             |
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

### Temas y palabras clave

| Metadato de OMP | Tipo de tema en Thoth | Regla |
| --- | --- | --- |
| `keywords` | `Keyword` | Siempre. Las palabras clave nunca se reclasifican ni se envían como `Custom`. |
| `subjects` con esquema confirmado | `LCC`, `BISAC`, `BIC` o `Thema` | Utiliza el esquema detectado. |
| `subjects` con fuente no ONIX e identificador explícitos | `Custom` | El texto simple nunca se vuelve `Custom`. |
| Otros valores de `subjects` | `Keyword` | Cuando el esquema es inválido, ambiguo o no se puede validar. |

Los esquemas admitidos siguen la [Lista 27 de ONIX](https://ns.editeur.org/onix/en/27):

- `03` o `LCC`: requiere un esquema explícito y un código con el formato esperado de LCC;
- `10` o `BISAC`: requiere un esquema explícito y un código con el formato esperado de BISAC;
- `12` o `BIC`: valida el código con la lista BIC 2.1 incluida en OMP;
- `93` o `THEMA`: valida el código con el [registro oficial de Thema](https://ns.editeur.org/thema/en).

Los valores estructurados de temas en OMP 3.5 usan `name`, `source` e `identifier`. Los valores simples también
pueden incluir un prefijo de esquema, como `THEMA:MFGV` o `93:MFGV`. Sin un esquema explícito, el plugin solo
clasifica el valor como BIC o Thema cuando exactamente una validación tiene éxito. Los temas se sincronizan antes
de las palabras clave, los pares duplicados de tipo y código se envían una sola vez y sus ordinales en Thoth siguen
ese orden.

</details>

## Desarrollo

### Requisitos

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (para compilar los recursos del frontend)

### Configuración del Entorno

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias de Node.js y compilar recursos del frontend
npm install
npm run build
```

### Ejecución de Pruebas

```bash
# Desde el directorio raíz de OMP
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit.xml -v plugins/generic/thoth/tests
```

## Créditos

Este plugin fue idealizado y patrocinado por [Thoth](https://thoth.pub/).

Desarrollado por [Lepidus Tecnologia](https://github.com/lepidus).

## Licencia

Este plugin está licenciado bajo la Licencia Pública General GNU v3.0 - [Consulte el archivo de licencia.](/LICENSE)

Copyright (c) 2024-2026 Lepidus Tecnologia

Copyright (c) 2024-2026 Thoth Open Metadata
