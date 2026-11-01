# Mobile catalogue filter checks

`test_mobile_filters_browser.cjs` runs a loopback fixture with the actual Courses/Gallery
filter scripts, shared accessibility helper and mobile drawer. The controls and records
are synthetic; it never boots WordPress, authenticates to production or submits a cart.

```bash
NODE_PATH=/path/to/existing/node_modules CAS_CHROME=/path/to/chrome node tests/test_mobile_filters_browser.cjs
```

Twenty A/D touch cases cover phone, tablet and short landscape, fixed-handle scrolling,
original control identity, price/filter/sort/clear and result counts, selections across
resize/reopening, keyboard focus/trapping, Escape/backdrop and competing overlays.
Long labels and two no-JavaScript pages are checked too. `CAS_QA_OUTPUT=/tmp/filters`
retains structured results outside the repository. Native data, asset enqueueing and
served layout still need separate release checks.
