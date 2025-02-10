**English** | [Español](/docs/README-es.md) | [Português Brasileiro](/docs/README-pt_BR.md)

# Thoth OMP Plugin

Work in Progress Integration of OMP and [Thoth](https://thoth.pub/) for communication and synchronization of book data between the two platforms.

## Compatibility

This plugin is compatible with the following PKP applications:

- OMP 3.3.0-x

## Requirements

### Press Requirements

1. **api_key_secret**

The OMP instance must have the `api_key_secret` configuration set up, you may contact your system administrator to do that (see [this post](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).

This is required to use the API credentials provided, that are stored encrypted in the OMP database.

## Installation

1. Download the latest version of the installation package (`thoth.tar.gz`) from the [Release page](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Access the administration area of your OMP website through the Dashboard. Navigate to `Settings` > `Website` > `Plugins` > `Upload a new plugin`, and select the `thoth.tar.gz` file.

3. Click 'Save' to install the plugin.

## Usage

### Configuration

To configure the plugin:

- **E-mail** and **Password**: Enter the credentials for a Thoth account to connect with the API.
- **Test Environment**: Check this option if you are using a local instance of the Thoth API for testing purposes.

![settings](/images/settings.png)

### Managing Monographs

- **Unpublished Monographs**: Register metadata in Thoth during the publishing process by selecting the option to register metadata in the publish modal and choosing an imprint.

![publish](/images/publish.png)

- **Published Monographs**: Register metadata for published monographs by using the 'Register' button next to the publication status.

![button](/images/button.png)
![register](/images/register.png)

### Updating Metadata

To update metadata in Thoth, unpublish the monograph, edit the data, and the changes will be automatically updated in Thoth.

### Accessing Thoth Book Records

After metadata is published, a link to the book on Thoth will appear at the top of the publication.

![link](/images/link.png)

## OMP-Thoth Mapping

<details>
    <summary>Click here to see the data relationship between Thoth and OMP</summary>

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

## Credits

This plugin was idealized and sponsored by [Thoth](https://thoth.pub/).

Developed by [Lepidus Tecnologia](https://github.com/lepidus).

## License

This plugin is licensed under the GNU General Public License v3.0 - [See the License file.](/LICENSE)

Copyright (c) 2024-2025 Lepidus Tecnologia

Copyright (c) 2024-2025 Thoth
