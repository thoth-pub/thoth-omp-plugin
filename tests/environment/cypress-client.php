<?php

/** Credentials remain server-side, outside the web root and Cypress environment. */

function thothCypressCredentials(): array
{
    if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true) || getenv('THOTH_DISPOSABLE') !== '1') {
        throw new RuntimeException('Disposable test environment required');
    }
    if (\PKP\config\Config::getVar('database', 'host') !== 'omp-db'
        || \PKP\config\Config::getVar('database', 'name') !== 'thoth_cypress') {
        throw new RuntimeException('Refusing a database outside the disposable test environment');
    }
    return json_decode(file_get_contents('/thoth-state/client.json'), true, flags: JSON_THROW_ON_ERROR);
}
