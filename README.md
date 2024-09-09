# Thoth

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

1. Go to [Release page](https://github.com/lepidus/thoth-omp-plugin/releases) to download the latest version of the installation package `thoth.tar.gz` from the page.

2. Enter the administration area of ​​your OMP website through the Dashboard.
Navigate to `Settings` > `Website` > `Plugins` > Upload a new plugin and select the file `thoth.tar.gz`.

3. Click 'Save' to install the plugin on your website.

## Usage

### Configuration

Open the plugin settings form to fill the fields: 

- **E-mail** and **Password**: Credentials of a Thoth account to connect with the API.
- **Test Enviroment**: Check this option only if you have a local instance of Thoth API which you want to use for plugin tests.

### Unpublished monographs

You can register the metadata in Thoth during the publishing process.
To do this, check the option to register the metadata in the publish modal and select an imprint.

### Published monographs

For published monographs, you can register the metadata via the register button next to the publication status.

### Updating metadata

To update the metadata in Thoth, unpublish the monograph and edit the data. The update is done automatically in Thoth.

### Accessing the book registered on Thoth

Once the metadata has been published, a link to the book on Thoth is displayed at the top of the publication.

## Credits

This plugin was idealized and sponsored by [Thoth](https://thoth.pub/).

Developed by [Lepidus Tecnologia](https://github.com/lepidus).

## License

This plugin is licensed under the GNU General Public License v3.0 - [See the License file.](/LICENSE)

Copyright (c) 2024 Lepidus Tecnologia

Copyright (c) 2024 Thoth
