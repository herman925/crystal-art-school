"""Serve the site from a SUBDIRECTORY, which is how GitHub Pages serves it.

The whole class of bug this catches is invisible at the domain root: `../img/`
clamps to /img/ there and resolves fine. Only a subdirectory mount reproduces
what a visitor actually gets.
"""
import http.server, threading, functools, os, sys
ROOT = r"C:/Users/Herman/AppData/Local/Temp/claude/G--My-Drive-Workplace-Personal-Crystal-Education-Project-Sample-Website-crystal-art-school/274336d9-f9ac-4b4a-a82e-6143abd20f41/scratchpad/_mount"
SITE = r"G:/My Drive/Workplace/Personal/Crystal Education Project/Sample Website/crystal-art-school"

os.makedirs(ROOT, exist_ok=True)
link = os.path.join(ROOT, 'crystal-art-school')
if not os.path.exists(link):
    try:
        os.symlink(SITE, link, target_is_directory=True)
    except OSError:
        print('symlink refused; falling back to a directory handler')

class H(http.server.SimpleHTTPRequestHandler):
    def translate_path(self, path):
        p = path.split('?')[0].split('#')[0]
        if p.startswith('/crystal-art-school'):
            rest = p[len('/crystal-art-school'):].lstrip('/')
            return os.path.join(SITE, rest.replace('/', os.sep))
        return os.path.join(ROOT, p.lstrip('/').replace('/', os.sep))
    def end_headers(self):
        self.send_header('Cache-Control', 'no-store')
        super().end_headers()
    def log_message(self, *a): pass

http.server.HTTPServer(('127.0.0.1', 8941), H).serve_forever()
