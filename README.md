**English** | [Español](/docs/README-es.md) | [Português Brasileiro](/docs/README-pt_BR.md)

# Thoth OMP Plugin

[![Current Version](https://img.shields.io/github/v/release/thoth-pub/thoth-omp-plugin?filter=v0.3.%2A&sort=semver&label=version&color=blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![OMP compatibility](https://img.shields.io/badge/OMP-3.3_%7C_3.4_%7C_3.5-blue)](https://pkp.sfu.ca/software/omp/)

Integrates [OMP (Open Monograph Press)](https://pkp.sfu.ca/software/omp/) with [Thoth](https://thoth.pub/), an open metadata management platform for books. This plugin enables the registration and synchronization of book- and chapter-level metadata directly from OMP into Thoth, where it can be disseminated in multiple industry-standard formats including ONIX, MARC, KBART, and Crossref XML.

## Compatibility

This plugin is compatible with the following PKP applications:

- OMP 3.3.0-x [![Latest plugin version](https://img.shields.io/github/v/release/thoth-pub/thoth-omp-plugin?filter=v0.1.%2A&sort=semver&label=plugin&color=blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
- OMP 3.4.0-x [![Latest plugin version](https://img.shields.io/github/v/release/thoth-pub/thoth-omp-plugin?filter=v0.2.%2A&sort=semver&label=plugin&color=blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
- OMP 3.5.0-x [![Latest plugin version](https://img.shields.io/github/v/release/thoth-pub/thoth-omp-plugin?filter=v0.3.%2A&sort=semver&label=plugin&color=blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)

## Installation

1. From the [Release page](https://github.com/thoth-pub/thoth-omp-plugin/releases), download the installation
   package (`thoth.tar.gz`) that is compatible with your OMP version.

2. Access the administration area of your OMP website through the Dashboard. Navigate to `Settings` > `Website` > `Plugins` > `Upload a new plugin`, and select the `thoth.tar.gz` file.

3. Click 'Save' to install the plugin.

## Usage

### Configuration

After enabling the plugin, go to the plugin settings and fill in:

- **Personal access token**: A valid Thoth personal access token used to authenticate API requests.
- **Custom Thoth API**: Check this option to use a custom Thoth API instead of the official one.
- **Thoth API URL**: The URL of the custom Thoth API (only required when the custom API option is enabled).

<img src="/docs/images/plugin_settings.png" alt="Plugin settings form with personal access token, custom API and URL fields" width="700">

### Registering Monographs

#### Unpublished Monographs

Register metadata in Thoth during the publishing process by selecting the option to register metadata in the publish modal and choosing an imprint.

<img src="/docs/images/register_field.png" alt="Publish modal with Thoth registration option" width="700">

#### Published Monographs

Register metadata for already-published monographs by using the 'Register' button next to the publication status.

<img src="/docs/images/register_button.png" alt="Register button in the publication workflow" width="700">
<img src="/docs/images/register_modal.png" alt="Registration modal with imprint selection" width="700">

### Updating Metadata

After registration, some changes to the book's catalog entry, titles, and abstracts are sent to Thoth automatically
when they are saved.

To reconcile the complete record, click **Update Metadata** next to the publication status. This action synchronizes
the book and its chapters, including contributors, publication formats and links, language, subjects, keywords,
references, and chapter order.

OMP is the source for the metadata managed by this synchronization. Information added or changed in OMP is reflected
in Thoth, and information removed from OMP is also removed from Thoth when possible. Locations managed by Thoth
itself are preserved.

If the plugin cannot safely identify the corresponding record in Thoth, it stops the synchronization instead of
making an uncertain association. A warning may also be shown when a publication format cannot be removed from an
active work in Thoth.

<img src="/docs/images/update_button.png" alt="Update Metadata button in the publication workflow" width="700">

### Accessing Thoth Book Records

After metadata is registered, a link to the book on Thoth will appear at the top of the publication workflow.

<img src="/docs/images/view_button.png" alt="View link to the Thoth book record" width="700">

### Hosting Files in Thoth

The plugin can send publication files, the front cover, and a featured video to Thoth. These features are available
after the book has been registered in Thoth and require the configured Thoth user to have the `cdnWrite` permission, which is available to publishers subscribing to Thoth Open Metadata's [Obelisk](https://thoth.pub/packages/obelisk), [Sphinx](https://thoth.pub/packages/sphinx), and [Pyramid](https://thoth.pub/packages/pyramid) service packages.

#### Publication Files

In the publication workflow, open the publication formats grid and use **Upload to Thoth** on the desired format.
The action appears below the format details and above the files associated with it.

<img src="/docs/images/publication_file_upload_action.png" alt="Upload to Thoth action in the publication formats grid" width="700">

In the upload form, select a file and indicate whether it belongs to the monograph or to a chapter. The selected
monograph or chapter must have a DOI. Files can be up to 50 MB.

<img src="/docs/images/publication_file_upload_form.png" alt="Upload to Thoth form with the file and related publication component" width="700">

After the upload, the **View** button in the **Thoth files** column opens a list of the monograph and chapter files
hosted by Thoth. Each filename is a link to the file identified by its DOI.

<img src="/docs/images/publication_file_view_action.png" alt="View button in the Thoth files column" width="700">

<img src="/docs/images/publication_file_view_form.png" alt="List of monograph and chapter files hosted by Thoth" width="700">

On the public book page, files hosted by Thoth are displayed alongside the files from OMP publication formats.

<img src="/docs/images/publication_file_landing_page.png" alt="Public book page displaying files hosted by Thoth alongside OMP publication format files" width="700">

#### Front Cover

In the catalog entry form, upload the book cover and select **Host this cover image file on Thoth**, below the cover
field. After the publication is saved and synchronized, the public book page uses the cover hosted by Thoth.
Clearing this option stops the plugin from using the previously hosted cover.

<img src="/docs/images/cover_upload.png" alt="Thoth cover file hosting option below the cover image field" width="700">

#### Featured Video

Open the **Marketing** tab in the publication workflow and select **Featured video**, below **Publication dates**.
Add a title and upload an MP4, WebM, or MOV file. The file is hosted by Thoth and displayed with playback controls on
the public book page, after the book information and synopsis. Each book can have one featured video.

<img src="/docs/images/feature_video_form.png" alt="Featured video form with title and uploaded video file" width="700">

<img src="/docs/images/feature_video_landing_page.png" alt="Featured video player on the public book page" width="700">

### Bulk Registration

On the Thoth management page, you can submit a selection of titles from OMP into Thoth in bulk.

<img src="/docs/images/bulk_register_page.png" alt="Thoth management page with bulk registration" width="700">

### Guidelines

- Only basic HTML tags are preserved in text fields: `<strong>`, `<mark>`, `<em>`, `<i>`, `<u>`, `<sup>`, `<sub>`, `<ul>`, `<ol>`, and `<li>`. All other tags will be stripped.
- ISBN must be properly formatted as ISBN-13 (e.g., `978-3-16-148410-0`).

## OMP-Thoth Mapping of Data Fields

<details>
<summary>Click here to see the data relationship between OMP and Thoth</summary>

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
| Subject           |                    |   | Subject(Type: detected scheme or Keyword) |         |             |
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

### Subjects and keywords

OMP offers two complementary ways to describe a publication:

- **Keywords** are free terms that help readers discover the publication. Every keyword is sent to Thoth as a
  keyword and is never treated as a custom classification.
- **Subjects** can be descriptive terms or codes from recognized classification systems, such as LCC, BISAC, BIC,
  and Thema. When the classification can be confirmed, Thoth records the subject using the corresponding system.
- When a subject cannot be confidently associated with a classification system, it is kept as a keyword instead
  of being discarded or assigned an uncertain classification.
- Subjects associated with another clearly identified vocabulary are recorded as a custom classification.

To make the Subjects field available, go to **Settings > Workflow > Submission > Metadata**, select **Enable
subject metadata**, and save the changes. In the same settings, choose whether the field should be available only
to the editorial team, requested from authors, or required during submission.

Editors can enter the code alone, such as `GTK` or `EDU000000`. They can also identify the classification system
with a prefix, such as `THEMA:GTK` or `BISAC:EDU000000`. Classification information included in metadata imported
into OMP 3.5 is also recognized automatically.

</details>

## Development

### Requirements

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (for building frontend assets)

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies and build frontend assets
npm ci
npm run build
```

### Running Tests

```bash
# From the OMP root directory
php lib/pkp/lib/vendor/bin/phpunit -c lib/pkp/tests/phpunit.xml --no-coverage plugins/generic/thoth/tests
```

## Credits

This plugin was idealized and sponsored by [Thoth Open Metadata](https://thoth.pub/).

Developed by [Lepidus Tecnologia](https://github.com/lepidus).

## License

This plugin is licensed under the GNU General Public License v3.0 - [See the License file.](/LICENSE)

Copyright (c) 2024-2026 Lepidus Tecnologia

Copyright (c) 2024-2026 Thoth Open Metadata
