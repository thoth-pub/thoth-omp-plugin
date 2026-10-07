#!/usr/bin/env bash
set -euo pipefail
test "${THOTH_DISPOSABLE:-}" = 1
test "$PWD" = /var/www/omp
test -f /thoth-state/client.json

if [[ "${1:-}" = --image-dataset ]]; then
    # The OMP CI image ships this dump and its matching files/public directories.
    dataset_dump=/tmp/dump.sql
elif [[ $# = 0 ]]; then
    dataset_dump=/dataset/database.sql
    test -d /dataset/files
    test -d /dataset/public
    cp -a /dataset/files/. files/
    cp -a /dataset/public/. public/
else
    echo 'Expected no argument or --image-dataset' >&2
    exit 1
fi
test -f "$dataset_dump"

python3 plugins/generic/thoth/tests/environment/cypress-configure.py
# This hostname and database belong only to the Compose test project.
for attempt in {1..60}; do
    if MYSQL_PWD=disposable-omp-only mysql --skip-ssl -h omp-db -u omp thoth_cypress \
      -e 'SELECT 1' >/dev/null 2>&1; then
        break
    fi
    if [[ "$attempt" = 60 ]]; then
        echo 'Disposable OMP database did not become ready' >&2
        exit 1
    fi
    sleep 1
done
MYSQL_PWD=disposable-omp-only mysql --skip-ssl -h omp-db -u omp thoth_cypress < "$dataset_dump"
php -v | head -1
php lib/pkp/tools/installPluginVersion.php plugins/generic/thoth/version.xml
php plugins/generic/thoth/cypress/support/ThothTestData.php configure
