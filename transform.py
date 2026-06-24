#!/usr/bin/env python3
"""Strip Claude Design scaffolding from .dc.html files.
Each input page becomes:
  - <name>.html   (clean HTML, no x-dc/helmet, hoisted <head>)
  - js/<name>.js  (Component class wrapped in IIFE)
The Component class only uses componentDidMount/componentWillUnmount,
no props/state, so we just run the body of componentDidMount on
DOMContentLoaded.
"""
import os, re, sys
from pathlib import Path

SRC = Path(r"G:\My Drive\Workplace\Personal\Crystal Education Project\Sample Website\industrial-art-school-redesign\project")
DST = Path(r"C:\Users\hkkchan\claude-jobs\crystal-art-school")

# Map: input filename (with .dc.html) -> output html name + js name
PAGES = {
    "index.html":     ("index.html",     "index.js"),
    "About.dc.html":  ("about.html",     "about.js"),
    "Blog.dc.html":   ("blog.html",      "blog.js"),
    "blog-post.dc.html": ("blog-post.html", "blog-post.js"),
    "Checkout.dc.html": ("checkout.html", "checkout.js"),
    "Dashboard.dc.html": ("dashboard.html", "dashboard.js"),
    "Gallery.dc.html": ("gallery.html",  "gallery.js"),
    "Lessons.dc.html": ("lessons.html",  "lessons.js"),
    "lesson.dc.html":  ("lesson.html",   "lesson.js"),
    "Login.dc.html":   ("login.html",    "login.js"),
    "Signup.dc.html":  ("signup.html",   "signup.js"),
}

HELMET_RE = re.compile(r"<helmet[^>]*>([\s\S]*?)</helmet>", re.IGNORECASE)
XD_OPEN_RE = re.compile(r"<x-dc[^>]*>", re.IGNORECASE)
XD_CLOSE_RE = re.compile(r"</x-dc\s*>", re.IGNORECASE)
SUPPORT_RE = re.compile(r'<script\s+src="\./support\.js"[^>]*></script>', re.IGNORECASE)
DC_SCRIPT_RE = re.compile(
    r'<script\s+type="text/x-dc"\s+data-dc-script[^>]*>([\s\S]*?)</script>',
    re.IGNORECASE,
)
COMMENT_ANCHOR_RE = re.compile(r'\s*data-comment-anchor="[^"]*"', re.IGNORECASE)


def transform_one(src_name: str, html_out: str, js_out: str) -> tuple[str, str]:
    raw = (SRC / src_name).read_text(encoding="utf-8")

    # 1) Extract helmet (head hoists)
    m = HELMET_RE.search(raw)
    if not m:
        raise SystemExit(f"{src_name}: no <helmet> found")
    helmet_inner = m.group(1).strip()
    # Strip data-comment-anchor from helmet contents too
    helmet_inner = COMMENT_ANCHOR_RE.sub("", helmet_inner)
    raw = raw[: m.start()] + raw[m.end():]

    # 2) Strip <script src="./support.js">
    raw = SUPPORT_RE.sub("", raw)

    # 3) Extract embedded Component script
    m2 = DC_SCRIPT_RE.search(raw)
    if not m2:
        raise SystemExit(f"{src_name}: no data-dc-script found")
    component_src = m2.group(1)
    raw = raw[: m2.start()] + raw[m2.end():]

    # 4) Strip <x-dc> and </x-dc>
    raw = XD_OPEN_RE.sub("", raw)
    raw = XD_CLOSE_RE.sub("", raw)

    # 5) Remove data-comment-anchor attrs from body markup
    raw = COMMENT_ANCHOR_RE.sub("", raw)

    # 6) Inject helmet content into <head> (replace nothing — insert before </head>)
    if "</head>" in raw:
        raw = raw.replace("</head>", helmet_inner + "\n</head>", 1)
    else:
        raise SystemExit(f"{src_name}: no </head>")

    # 7) Inject our own per-page script before </body>
    page_name = Path(src_name).stem.replace(".dc", "")
    script_tag = f'<script src="js/{js_out}" defer></script>\n'
    if "</body>" in raw:
        raw = raw.replace("</body>", script_tag + "</body>", 1)
    else:
        raise SystemExit(f"{src_name}: no </body>")

    # 8) Build extracted JS: wrap Component class in IIFE.
    # The class body uses `extends DCLogic` (which we never reference anywhere
    # else in the page); strip that base class so the IIFE has no external deps.
    js_body = component_src.strip()
    # remove "extends DCLogic"
    js_body = re.sub(r"extends\s+DCLogic\b", "", js_body)
    # Provide a tiny shim that runs componentDidMount + componentWillUnmount
    # via standard DOM events. No React, no runtime needed.
    shim = """/* Auto-generated from Claude Design handoff bundle.
   The original Component class wrapped its init in componentDidMount and
   only ran setInterval timers in componentWillUnmount. We replicate that
   pair without a runtime, and hoist the timer handles onto `window` so the
   unload hook can find them. */
(function () {
  function start() {
"""
    trailer = """
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
  // cleanup if the page is hidden
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden' && window.__casComponentWillUnmount) {
      window.__casComponentWillUnmount();
    }
  });
})();
"""
    # Strip outer class wrapper, keep body inside
    # original: class Component { ... }
    # we just drop the "class Component {" and the trailing "}"
    cls_match = re.match(r"class\s+Component\s*\{", js_body)
    if not cls_match:
        raise SystemExit(f"{src_name}: malformed Component class")
    inner = js_body[cls_match.end():].rstrip()
    if not inner.endswith("}"):
        raise SystemExit(f"{src_name}: Component class missing closing brace")
    inner = inner[:-1].rstrip()

    # The Component class methods use `this.foo()` for sibling helpers and
    # `this.props.defaultTheme` for the theme prop (always "A" in our pages).
    # In a plain object literal `this` doesn't have those. Rewrite:
    #   - this.<methodName>()  -> <methodName>()
    #   - this.props.<anything> -> defaults from data-theme attr or 'A'
    # Then invoke componentDidMount / componentWillUnmount via the literal.

    # collect method names declared in the body (both method() { ... } and
    # static-style shorthand we see: `coursePool() { ... }`)
    method_names = set(re.findall(r"^\s*([A-Za-z_$][\w$]*)\s*\(", inner, re.MULTILINE))
    # Build a regex that rewrites this.<methodName> -> <methodName>
    if method_names:
        names_alt = "|".join(re.escape(n) for n in sorted(method_names, key=len, reverse=True))
        inner = re.sub(r"\bthis\.(" + names_alt + r")\b", r"\1", inner)

    # this.props.defaultTheme -> 'A' (no per-page override prop set anywhere)
    inner = re.sub(r"\bthis\.props\.[A-Za-z_$][\w$]*\b", "'A'", inner)
    # any other leftover this.props.X.X (e.g. cas-theme key lookups): replace
    # the entire this.props.* with the literal 'A' fallback (matches the
    # original intent — empty props passed in)
    inner = re.sub(r"\bthis\.props\b", "{}", inner)

    # The class defines methods. We want to invoke componentDidMount on load
    # and store componentWillUnmount on window. Easiest: wrap methods in a
    # plain object literal then call its mount() and unmount().
    # NOTE: in a class body, methods are separated by newlines; in an object
    # literal they must be comma-separated. Insert commas between top-level
    # method declarations (a top-level method begins at a line whose first
    # non-whitespace is a name followed by "(").
    # Walk through inner line by line. When we see a method declaration
    # (top-level), insert a comma after the previous method's closing brace.
    lines = inner.split("\n")
    out_lines: list[str] = []
    method_re = re.compile(r"^(\s*)([A-Za-z_$][\w$]*)\s*\(")
    last_was_method_close = False
    for i, line in enumerate(lines):
        # Detect a method declaration at the top level of the object (column 2)
        m = method_re.match(line)
        is_method_decl = bool(m) and len(m.group(1)) == 2 and not line.lstrip().startswith("//")
        if is_method_decl and out_lines and last_was_method_close:
            # previous top-level line closed a method -> add comma
            # find last non-empty line and append "," if missing
            for j in range(len(out_lines) - 1, -1, -1):
                if out_lines[j].strip() == "":
                    continue
                if not out_lines[j].rstrip().endswith(","):
                    out_lines[j] = out_lines[j].rstrip() + ","
                break
        out_lines.append(line)
        # Track method close: a top-level "}" alone on a line
        if line.strip() == "}":
            last_was_method_close = True
        elif line.strip() != "":
            last_was_method_close = False
    inner = "\n".join(out_lines)

    js_out_text = shim + "    var comp = {\n" + inner + "\n    };\n    if (comp.componentDidMount) comp.componentDidMount();\n    window.__casComponentWillUnmount = comp.componentWillUnmount || function () {};\n" + trailer

    return raw, js_out_text


def main() -> None:
    (DST / "css").mkdir(exist_ok=True)
    (DST / "js").mkdir(exist_ok=True)
    (DST / "img").mkdir(exist_ok=True)

    for src_name, (html_out, js_out) in PAGES.items():
        if not (SRC / src_name).exists():
            print(f"SKIP missing: {src_name}")
            continue
        html, js = transform_one(src_name, html_out, js_out)
        (DST / html_out).write_text(html, encoding="utf-8")
        (DST / "js" / js_out).write_text(js, encoding="utf-8")
        print(f"OK  {src_name} -> {html_out} + js/{js_out}")

    # Copy static assets
    import shutil
    shutil.copy2(SRC / "css" / "industrial.css", DST / "css" / "industrial.css")
    shutil.copy2(SRC / "js" / "site-extras.js", DST / "js" / "site-extras.js")
    for f in (SRC / "img").iterdir():
        if f.is_file():
            shutil.copy2(f, DST / "img" / f.name)
    print("Copied css/industrial.css, js/site-extras.js, img/*")


if __name__ == "__main__":
    main()