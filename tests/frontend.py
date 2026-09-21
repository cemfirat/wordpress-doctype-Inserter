#!/usr/bin/env python3
"""Check real front-end requests after the WordPress integration upgrade."""
import json
import os
import pathlib
import re
import subprocess
import time
import urllib.request

root = pathlib.Path(os.environ['RUNNER_TEMP']) / 'wordpress'
base = 'http://127.0.0.1:8080/'
snippet = '<!--\nHello -- developers -- >\n-->'
log_path = pathlib.Path(os.environ['RUNNER_TEMP']) / 'doctype-inserter-http.log'
with log_path.open('w') as log:
    server = subprocess.Popen(['php', '-S', '127.0.0.1:8080', '-t', str(root)], stdout=log, stderr=log)
    try:
        def fetch(query='', method='GET'):
            with urllib.request.urlopen(urllib.request.Request(base + query, method=method), timeout=15) as response:
                return response.headers, response.read().decode('utf-8')
        for attempt in range(30):
            try:
                headers, html = fetch()
                break
            except (OSError, TimeoutError):
                if server.poll() is not None or attempt == 29:
                    raise
                time.sleep(0.2)
        assert 'text/html' in headers['Content-Type']
        assert re.search(r'<!doctype\s+html[^>]*>\n' + re.escape(snippet), html, re.I), html[:1000]
        assert html.count(snippet) == 1
        print('PASS: Real theme output contains the normalized simple comment once after the doctype.')
        subprocess.run(['wp', 'option', 'update', 'doctype_inserter_enabled', '0', f'--path={root}'], check=True, stdout=subprocess.DEVNULL)
        _, disabled = fetch()
        assert snippet not in disabled
        print('PASS: Disabling output removes the simple comment without changing the page response.')
        subprocess.run(['wp', 'option', 'update', 'doctype_inserter_enabled', '1', f'--path={root}'], check=True, stdout=subprocess.DEVNULL)
        _, feed = fetch('?feed=rss2')
        assert snippet not in feed and '<rss' in feed
        print('PASS: RSS remains valid and contains no injected snippet.')
        headers, rest = fetch('?rest_route=/')
        assert 'application/json' in headers['Content-Type']
        assert isinstance(json.loads(rest), dict) and snippet not in rest
        print('PASS: REST output remains JSON and contains no injected snippet.')
        _, head = fetch(method='HEAD')
        assert head == ''
        print('PASS: HEAD returns no body.')
    finally:
        server.terminate()
        try:
            server.wait(timeout=5)
        except subprocess.TimeoutExpired:
            server.kill()
            server.wait()
