"""Serve this site from a SUBDIRECTORY, the way GitHub Pages serves it.

WHY THIS EXISTS. A relative url() inside a CSS custom property is resolved when
the variable is SUBSTITUTED, against the document — not against the stylesheet
that declared it. --banner-grain holds url("../img/wood-creamy.jpg"), which is
right for any rule inside css/*.css (css/../img/ = img/) and wrong for a rule
written in a page's own <style>, where ../ is relative to the page.

A plain dev server hides that completely, because it serves the site from the
domain root, where ../ clamps harmlessly to /img/ and the file is found. The
bug only appears when the site is served one directory deep. It shipped twice
before anyone noticed: the home page's "Course overview" plaque and the Free
Videos section bands were both requesting
herman925.github.io/img/wood-creamy.jpg (404) instead of
herman925.github.io/crystal-art-school/img/wood-creamy.jpg (200), and rendered
as flat colour with no grain.

    python tools/serve-subdir.py
    -> http://127.0.0.1:8941/crystal-art-school/index.html

Check for same-origin 404s, not just for a page that looks right. Ignore
requests to fonts.gstatic.com; those are a third-party CDN and not this site.
"""
import argparse
import http.server
import os

# The site is this file's grandparent — tools/ sits inside the site root — so
# the server finds it wherever the repo is checked out. An absolute path baked
# in here would tie the tool to one machine, and the first version of this file
# did exactly that.
SITE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
MOUNT = "/" + os.path.basename(SITE)


def build_handler(site, mount):
    class Handler(http.server.SimpleHTTPRequestHandler):
        def translate_path(self, path):
            p = path.split("?")[0].split("#")[0]
            if p == mount:
                p = mount + "/"
            if p.startswith(mount + "/"):
                rest = p[len(mount) + 1:]
                return os.path.join(site, rest.replace("/", os.sep))
            # Anything outside the mount is deliberately NOT served. That is the
            # point: a request that escapes the subdirectory must 404 here
            # exactly as it would on the host. It has to be a path that merely
            # does not exist — a path containing a null byte raises out of
            # SimpleHTTPRequestHandler instead of answering 404, which turns the
            # finding into a stack trace and drops the connection.
            return os.path.join(site, "__outside_mount__")

        def end_headers(self):
            self.send_header("Cache-Control", "no-store")
            super().end_headers()

        def log_message(self, fmt, *args):
            code = args[1] if len(args) > 1 else ""
            if str(code).startswith(("4", "5")):
                print("  %s %s" % (code, self.path))

    return Handler


def main():
    ap = argparse.ArgumentParser(description=__doc__.split("\n")[0])
    ap.add_argument("--port", type=int, default=8941)
    ap.add_argument("--mount", default=MOUNT,
                    help="path prefix to serve the site under (default: the site folder's own name)")
    a = ap.parse_args()
    mount = "/" + a.mount.strip("/")
    print("serving %s\n     at http://127.0.0.1:%d%s/" % (SITE, a.port, mount))
    print("only 4xx/5xx are logged, so anything printed below is a finding")
    http.server.HTTPServer(("127.0.0.1", a.port), build_handler(SITE, mount)).serve_forever()


if __name__ == "__main__":
    main()
