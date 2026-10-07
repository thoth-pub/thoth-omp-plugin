#!/usr/bin/env bash
set -euo pipefail
test "${THOTH_DISPOSABLE:-}" = 1
test "$PWD" = /var/www/omp
test -f /thoth-state/client.json

environment_dir=plugins/generic/thoth/tests/environment
spec_folder='plugins/generic/thoth/cypress/tests/functional'
mode=${1:-}
case "$mode" in
    prepare)
        shift
        bash "$environment_dir/cypress-prepare.sh" "$@"
        php -S 127.0.0.1:8001 "$environment_dir/cypress-router.php" > /tmp/thoth-omp-server.log 2>&1 &
        server_pid=$!
        trap 'kill "$server_pid" 2>/dev/null || true' EXIT
        python3 - <<'PY'
import time
import urllib.request
for attempt in range(50):
    try:
        urllib.request.urlopen('http://127.0.0.1:8001/index.php/publicknowledge/login', timeout=2)
        break
    except OSError:
        time.sleep(0.2)
else:
    raise SystemExit('OMP server did not become ready; inspect /tmp/thoth-omp-server.log')
PY
        php "$environment_dir/cypress-check.php"
        echo 'Disposable OMP ready; use open or run.'
        wait "$server_pid"
        ;;
    open|run)
        php "$environment_dir/cypress-check.php"
        if [[ "$mode" = open ]]; then
            test $# = 1
            # Cypress 5 cannot forward Electron switches through the npm CLI.
            cypress_cache=$(npx --no-install cypress cache path)
            cypress_version=$(node -p "require('./node_modules/cypress/package.json').version")
            cypress_binary="$cypress_cache/$cypress_version/Cypress/Cypress"
            test -x "$cypress_binary"
            exec "$cypress_binary" --no-sandbox --in-process-gpu -- --project "$PWD" --cwd "$PWD" --browser electron \
              --config "{\"baseUrl\":\"http://127.0.0.1:8001\",\"integrationFolder\":\"$spec_folder\",\"watchForFileChanges\":true,\"numTestsKeptInMemory\":50}"
        fi
        test $# -le 2
        run_args=()
        if [[ $# = 2 ]]; then
            test "${2##*/}" = "$2"
            [[ "$2" = *.spec.js ]]
            test -f "plugins/generic/thoth/cypress/tests/functional/$2"
            run_args=(--spec "plugins/generic/thoth/cypress/tests/functional/$2")
        fi
        if [[ -n "${CI_PROJECT_DIR:-}" ]]; then
            mkdir -p "$CI_PROJECT_DIR/results"
            run_args+=(--reporter junit --reporter-options "mochaFile=$CI_PROJECT_DIR/results/spec-$(date +%s%N)-[hash].xml")
        fi
        exec npx --no-install cypress run --headless --browser electron "${run_args[@]}" \
          --config "{\"baseUrl\":\"http://127.0.0.1:8001\",\"integrationFolder\":\"$spec_folder\"}"
        ;;
    *) echo 'Expected prepare [--image-dataset], open or run [spec.spec.js]' >&2; exit 1 ;;
esac
