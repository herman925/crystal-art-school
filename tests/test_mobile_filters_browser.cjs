/* Loopback-only controls use the real filter scripts; no WordPress/cart writes. */
const assert = require("node:assert/strict"),
  fs = require("node:fs"),
  path = require("node:path"),
  http = require("node:http");
const { chromium } = require("playwright");
const root = path.resolve(
  __dirname,
  "../wp-content/themes/hello-elementor-child",
);
const chip = (value, label) =>
  `<button class="fopt${value === "all" ? " active" : ""}" type="button" data-val="${value}" aria-pressed="${value === "all"}">${label}</button>`;
function markup(kind) {
  const course = kind === "courses",
    prefix = course ? "lesson" : "artwork",
    sort = course
      ? ["featured", "price-asc", "price-desc"]
      : ["newest", "oldest", "price-asc", "price-desc"];
  const rail = `<div class="filter-col"><aside class="filter-panel"><div id="${course ? "lesson-sort" : "artwork-sort"}">${sort.map((x) => chip(x, x)).join("")}</div></aside><aside class="filter-panel"><button id="${course ? "fp-clear" : "artwork-filter-clear"}" type="button">Clear all</button><div class="fgroup" data-group="medium">${(course
    ? [
        ["all", "Any"],
        ["watercolour", "Watercolour"],
        ["pencil", "Pencil"],
      ]
    : [
        ["watercolour", "Watercolour"],
        ["pencil", "Pencil"],
      ]
  )
    .map((x) => chip(...x))
    .join(
      "",
    )}</div><div class="fgroup"><span id="${prefix}-price-val" data-symbol="$">Any price</span><div id="${prefix}-price-ui"><input id="${prefix}-price-slider" type="range" min="0" max="100" value="100" aria-label="Maximum price"></div></div></aside></div>`;
  const items = [
    ["first", 20, "watercolour"],
    ["second", 60, "pencil"],
    ["third", 80, "watercolour"],
  ]
    .map(
      ([id, price, medium], i) =>
        `<article id="${id}" class="${course ? "lesson-cell" : "art-card"}" data-media="${medium}" data-medium="${medium}" data-price="${price}" data-date="${i}" style="min-height:180px">${id}</article>`,
    )
    .join("");
  return `<div class="${course ? "catalog-layout" : "store-layout"}">${rail}<section><div><span id="${course ? "lesson-count" : "store-count"}" aria-live="polite">3 ${course ? "courses" : "pieces"}</span><div id="active-tags"></div></div><div id="${course ? "lesson-grid" : "store-grid"}">${items}</div><div id="${course ? "no-results" : "store-empty"}" style="display:none">No results</div></section></div>`;
}
const server = http.createServer((req, res) => {
  const url = new URL(req.url, "http://localhost");
  if (url.pathname.startsWith("/theme/")) {
    const p = path.resolve(root, url.pathname.slice(7));
    if (!p.startsWith(root + path.sep) || !fs.existsSync(p)) {
      res.writeHead(404);
      res.end();
      return;
    }
    res.setHeader(
      "content-type",
      p.endsWith(".css") ? "text/css" : "application/javascript",
    );
    res.end(fs.readFileSync(p));
    return;
  }
  const kind = url.pathname.includes("gallery") ? "gallery" : "courses";
  res.setHeader("content-type", "text/html");
  res.end(
    `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mobile filter fixture</title>${["industrial-1", "industrial-2", "industrial-3", "cas-nav", "cas-mobile-filters"].map((x) => `<link rel="stylesheet" href="/theme/css/${x}.css">`).join("")}</head><body class="cas-root" data-theme="${url.searchParams.get("theme") || "D"}"><main class="container" style="min-height:2000px"><h1>Catalogue</h1>${markup(kind)}</main><script>window.originalRail=document.querySelector('.filter-col')</script><script src="/theme/js/cas-site-extras.js"></script><script src="/theme/js/cas-${kind}.js"></script><script src="/theme/js/cas-mobile-filters.js"></script></body></html>`,
  );
});
(async () => {
  await new Promise((r) => server.listen(0, "127.0.0.1", r));
  const base = `http://127.0.0.1:${server.address().port}`,
    b = await chromium.launch({
      headless: true,
      ...(process.env.CAS_CHROME
        ? { executablePath: process.env.CAS_CHROME }
        : {}),
    }),
    errors = [],
    cases = [];
  try {
    for (const kind of ["courses", "gallery"])
      for (const [width, height] of [
        [320, 568],
        [390, 844],
        [768, 1024],
        [844, 390],
        [568, 320],
      ])
        for (const theme of ["A", "D"]) {
          const c = await b.newContext({
              viewport: { width, height },
              hasTouch: true,
              isMobile: true,
              reducedMotion: "reduce",
            }),
            p = await c.newPage();
          p.setDefaultTimeout(6000);
          p.on("pageerror", (e) => errors.push(e.message));
          await p.goto(base + "/" + kind + "?theme=" + theme);
          const handle = p.locator(".cas-filter-handle"),
            drawer = p.locator(".cas-filter-drawer"),
            rail = p.locator(".filter-col"),
            count = p.locator(
              kind === "courses" ? "#lesson-count" : "#store-count",
            ),
            slider = p.locator(
              kind === "courses"
                ? "#lesson-price-slider"
                : "#artwork-price-slider",
            );
          assert(await handle.isVisible());
          assert.equal(await drawer.isVisible(), false);
          assert(
            await p.evaluate(
              () => originalRail === document.querySelector(".filter-col"),
            ),
          );
          const originalY = await handle.evaluate(
            (e) => e.getBoundingClientRect().y,
          );
          await p.evaluate(() => scrollTo(0, 700));
          assert.equal(
            await handle.evaluate((e) => e.getBoundingClientRect().y),
            originalY,
          );
          await handle.tap();
          assert(await drawer.isVisible());
          const box = await drawer.boundingBox();
          assert(box.y >= 0 && box.y + box.height <= height + 1);
          assert.equal(await handle.getAttribute("aria-expanded"), "true");
          await p.locator(".cas-filter-close").focus();
          await p.keyboard.press("Shift+Tab");
          assert(
            await p
              .locator(".cas-filter-foot button")
              .evaluate((e) => e === document.activeElement),
          );
          await p.keyboard.press("Tab");
          assert(
            await p
              .locator(".cas-filter-close")
              .evaluate((e) => e === document.activeElement),
          );
          const medium = rail.locator(
            '.fgroup[data-group="medium"] [data-val="watercolour"]',
          );
          const oldLabel = await medium.textContent();
          await medium.evaluate((el) => {
            el.textContent = "Watercolour".repeat(12);
          });
          const wrapped = await medium.boundingBox();
          assert(wrapped.x >= 0 && wrapped.x + wrapped.width <= width);
          assert(
            await medium.evaluate((el) => el.scrollWidth <= el.clientWidth + 1),
          );
          await medium.evaluate((el, text) => {
            el.textContent = text;
          }, oldLabel);
          await medium.tap();
          assert.equal(await medium.getAttribute("aria-pressed"), "true");
          assert((await count.textContent()).startsWith("2"));
          await slider.focus();
          await p.keyboard.press("Home");
          assert((await count.textContent()).startsWith("0"));
          await p.keyboard.press("End");
          assert((await count.textContent()).startsWith("2"));
          await drawer.locator('[data-val="price-asc"]').tap();
          const ids = await p
            .locator(
              kind === "courses"
                ? "#lesson-grid >article:not(.hidden)"
                : "#store-grid >article:not(.hidden)",
            )
            .evaluateAll((es) => es.map((e) => e.id));
          assert.deepEqual(ids, ["first", "third"]);
          assert.equal(
            await p.locator(".cas-filter-foot button").textContent(),
            "Show 2 " + (kind === "courses" ? "courses" : "pieces"),
          );
          await p.locator(".cas-filter-foot button").tap();
          assert.equal(await drawer.isVisible(), false);
          assert.equal(
            await p.locator("body").evaluate((e) => e.style.overflow),
            "",
          );
          await handle.tap();
          assert.equal(await medium.getAttribute("aria-pressed"), "true");
          await p.keyboard.press("Escape");
          assert(await handle.evaluate((e) => e === document.activeElement));
          await handle.tap();
          await p.setViewportSize({ width: 1440, height: 1000 });
          await p.waitForFunction(
            () => document.querySelector(".cas-filter-handle").hidden,
          );
          assert.equal(await handle.isVisible(), false);
          assert.equal(await drawer.isVisible(), false);
          assert(await rail.isVisible());
          assert(
            await p.evaluate(
              () => originalRail === document.querySelector(".filter-col"),
            ),
          );
          assert.equal(await medium.getAttribute("aria-pressed"), "true");
          await p.setViewportSize({ width, height });
          await handle.waitFor({ state: "visible" });
          assert(await handle.isVisible());
          await handle.tap();
          await drawer
            .locator(kind === "courses" ? "#fp-clear" : "#artwork-filter-clear")
            .tap();
          assert((await count.textContent()).startsWith("3"));
          await p
            .locator(".cas-filter-backdrop")
            .tap({ position: { x: 5, y: 5 } });
          assert.equal(await drawer.isVisible(), false);
          assert.equal(await handle.getAttribute("aria-expanded"), "false");
          for (const name of ["nav-sheet", "mc-drawer", "art-modal"]) {
            await p.evaluate((name) => {
              const overlay = document.createElement("div");
              overlay.id = "fixture-overlay";
              overlay.className = name + " open";
              document.body.appendChild(overlay);
            }, name);
            assert.equal(await handle.isVisible(), false);
            await p.locator("#fixture-overlay").evaluate((el) => el.remove());
            assert(await handle.isVisible());
          }
          cases.push({ kind, width, height, theme });
          await c.close();
          console.log("PASS mobile filters", kind, width, height, theme);
        }
    for (const kind of ["courses", "gallery"]) {
      const c = await b.newContext({
          javaScriptEnabled: false,
          viewport: { width: 390, height: 844 },
        }),
        p = await c.newPage();
      await p.goto(base + "/" + kind);
      assert(await p.locator(".filter-col").isVisible());
      assert.equal(await p.locator(".cas-filter-handle").count(), 0);
      await c.close();
    }
    assert.deepEqual(errors, []);
    const result = {
      status: "PASS",
      cases,
      noJavaScriptFallback: true,
      errors,
    };
    if (process.env.CAS_QA_OUTPUT) {
      fs.mkdirSync(process.env.CAS_QA_OUTPUT, { recursive: true });
      fs.writeFileSync(
        path.join(process.env.CAS_QA_OUTPUT, "mobile-filter-results.json"),
        JSON.stringify(result, null, 2),
      );
    }
    console.log(
      "PASS",
      cases.length,
      "mobile filter cases and two no-JavaScript cases",
    );
  } finally {
    await b.close();
    await new Promise((r) => server.close(r));
  }
})().catch((e) => {
  console.error(e.stack);
  server.close();
  process.exitCode = 1;
});
