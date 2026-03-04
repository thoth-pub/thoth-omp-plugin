**English** | [Español](/docs/README-es.md) | [Português Brasileiro](/docs/README-pt_BR.md)

# Thoth OMP Plugin

[![Current Version](https://img.shields.io/badge/version-v0.3.0.0-blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![OMP compatibility](https://img.shields.io/badge/OMP-3.3_%7C_3.4_%7C_3.5-blue)](https://pkp.sfu.ca/software/omp/)

Integrates [OMP (Open Monograph Press)](https://pkp.sfu.ca/software/omp/) with [Thoth](https://thoth.pub/), an open metadata management platform for books. This plugin enables the registration and synchronization of book- and chapter-level metadata directly from OMP into Thoth, where it can be disseminated in multiple industry-standard formats including ONIX, MARC, KBART, and Crossref XML.

## Compatibility

This plugin is compatible with the following PKP applications:

- OMP 3.3.0-x
- OMP 3.4.0-x
- OMP 3.5.0-x

## Installation

1. Download the latest version of the installation package (`thoth.tar.gz`) from the [Release page](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Access the administration area of your OMP website through the Dashboard. Navigate to `Settings` > `Website` > `Plugins` > `Upload a new plugin`, and select the `thoth.tar.gz` file.

3. Click 'Save' to install the plugin.

## Usage

### Configuration

After enabling the plugin, go to the plugin settings and fill in:

- **Email** and **Password**: Credentials for a Thoth account to connect with the API.
- **Custom Thoth API**: Check this option to use a custom Thoth API instead of the official one.
- **Thoth API URL**: The URL of the custom Thoth API (only required when the custom API option is enabled).

<img src="/docs/images/plugin_settings.png" alt="Plugin settings form with email, password, custom API and URL fields" width="700">

### Registering Monographs

#### Unpublished Monographs

Register metadata in Thoth during the publishing process by selecting the option to register metadata in the publish modal and choosing an imprint.

<img src="/docs/images/register_field.png" alt="Publish modal with Thoth registration option" width="700">

#### Published Monographs

Register metadata for already-published monographs by using the 'Register' button next to the publication status.

<img src="/docs/images/register_button.png" alt="Register button in the publication workflow" width="700">
<img src="/docs/images/register_modal.png" alt="Registration modal with imprint selection" width="700">

### Updating Metadata

Once a monograph is registered, metadata updates are **automatic**. Unpublish the monograph, edit the data, and the changes will be synchronized with Thoth upon republication.

It is also possible to manually update the metadata in Thoth by clicking the 'Update Metadata' button next to the publication status.

<img src="/docs/images/update_button.png" alt="Update Metadata button in the publication workflow" width="700">

### Accessing Thoth Book Records

After metadata is registered, a link to the book on Thoth will appear at the top of the publication workflow.

<img src="/docs/images/view_button.png" alt="View link to the Thoth book record" width="700">

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
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

</details>

## Development

### Requirements

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (for building frontend assets)

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies and build frontend assets
npm install
npm run build
```

### Running Tests

```bash
# From the OMP root directory
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit.xml -v plugins/generic/thoth/tests
```

## Credits

This plugin was idealized and sponsored by [Thoth Open Metadata](https://thoth.pub/).

Developed by [Lepidus Tecnologia](https://github.com/lepidus).

## License

This plugin is licensed under the GNU General Public License v3.0 - [See the License file.](/LICENSE)

Copyright (c) 2024-2026 Lepidus Tecnologia

Copyright (c) 2024-2026 Thoth Open Metadata
