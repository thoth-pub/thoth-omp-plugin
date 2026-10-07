"""Wait for the service bootstrap, then test it from the actual OMP CI image."""
import json
import os
from pathlib import Path
import socket
import time
from common import wait_for
from smoke import verify

started = time.monotonic()
state = Path(os.environ['THOTH_STATE_DIR'])
for host, port in [('thoth-db', 5432), ('zitadel-db', 5432), ('zitadel', 8080), ('thoth', 8000)]:
    def connect():
        with socket.create_connection((host, port), timeout=3):
            return True
    wait_for(host, connect, timeout=180)
    print(f'Connected: {host}:{port}', flush=True)
wait_for('completed bootstrap', lambda: (state / 'ready').exists(), timeout=180)
verify('http://thoth:8000', json.loads((Path(os.environ['THOTH_CLIENT_DIR']) / 'client.json').read_text()))
print(f'Probe completed in {time.monotonic() - started:.1f}s after job script started')
for name in ['memory.max', 'cpu.max']:
    path = Path('/sys/fs/cgroup') / name
    if path.exists():
        print(f'Job cgroup {name}: {path.read_text().strip()}')
