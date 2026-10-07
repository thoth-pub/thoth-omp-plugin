"""Configure only the disposable OMP container, preserving its other INI settings."""
import os
from pathlib import Path
import re

if os.environ.get("THOTH_DISPOSABLE") != "1" or Path.cwd() != Path("/var/www/omp"):
    raise SystemExit("Disposable OMP container required")
settings = {
    "general": {"installed": "On", "base_url": '"http://127.0.0.1:8001"'},
    "database": {"driver": "mysqli", "host": "omp-db", "username": "omp",
                 "password": "disposable-omp-only", "name": "thoth_cypress"},
    "files": {"files_dir": '"/var/www/omp/files"'},
    "security": {"force_ssl": "Off", "force_login_ssl": "Off"},
}
path = Path("config.inc.php")
source = path.read_text()
for section, values in settings.items():
    pattern = rf"(?ms)^\[{section}\]\s*\n(.*?)(?=^\[|\Z)"
    match = re.search(pattern, source)
    if not match:
        raise SystemExit(f"Missing config section {section}")
    body = match[1]
    for key, value in values.items():
        body = re.sub(rf"(?m)^\s*;?\s*{key}\s*=.*$", "", body)
        body += f"\n{key} = {value}\n"
    source = source[:match.start(1)] + body + source[match.end(1):]
path.write_text(source)
