"""Wait for PostgreSQL and prepare the PAT directory before starting Zitadel."""
import os
from pathlib import Path
import socket
from common import wait_for

if os.environ.get('THOTH_DISPOSABLE') != '1':
    raise RuntimeError('THOTH_DISPOSABLE=1 is required')
state = Path(os.environ.get('THOTH_STATE_DIR', '/state'))
state.mkdir(mode=0o700, parents=True, exist_ok=True)
os.umask(0o077)

def ready():
    with socket.create_connection(('zitadel-db', 5432), timeout=3):
        return True

wait_for('Zitadel PostgreSQL', ready)
os.execv('/app/zitadel', ['zitadel', 'start-from-init', '--masterkey',
                        os.environ['ZITADEL_MASTERKEY'], '--tlsMode', 'disabled'])
