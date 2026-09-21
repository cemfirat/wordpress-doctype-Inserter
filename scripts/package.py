#!/usr/bin/env python3
"""Build a reproducible WordPress installation ZIP from runtime files only."""
import pathlib
import re
import sys
import zipfile

root = pathlib.Path(__file__).resolve().parents[1]
header = (root / 'doctype-inserter.php').read_text()
version = re.search(r'^ \* Version: (\d+\.\d+\.\d+)$', header, re.M).group(1)
assert f"define( 'DOCTYPE_INSERTER_VERSION', '{version}' );" in header
assert f'Stable tag: {version}\n' in (root / 'readme.txt').read_text()
license_text = (root / 'LICENSE').read_text()
assert 'GNU GENERAL PUBLIC LICENSE' in license_text and len(license_text) > 10000
files = ['doctype-inserter.php', 'includes/class-doctype-inserter-output.php',
         'includes/class-doctype-inserter-updater.php', 'readme.txt', 'README.md',
         'CHANGELOG.md', 'LICENSE']
archive = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else root / 'dist/doctype-inserter.zip')
archive.parent.mkdir(parents=True, exist_ok=True)
with zipfile.ZipFile(archive, 'w', compression=zipfile.ZIP_DEFLATED) as out:
    for name in sorted(files):
        info = zipfile.ZipInfo('wordpress-doctype-Inserter/' + name, (2026, 1, 1, 0, 0, 0))
        info.compress_type = zipfile.ZIP_DEFLATED
        info.external_attr = 0o100644 << 16
        out.writestr(info, (root / name).read_bytes())
with zipfile.ZipFile(archive) as built:
    assert built.testzip() is None
    assert len(built.namelist()) == len(files)
print(f'Built Doctype Inserter {version}: {archive}')
