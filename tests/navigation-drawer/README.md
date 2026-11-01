# Navigation and basket drawer checks

The browser test serves the actual header, drawer scripts and theme CSS on a temporary
loopback server. PHP fixtures provide synthetic sessions and cart reads; removal is a
simulated Tutor response. It never starts WordPress, logs into a real account or changes
a production cart. Native Tutor integration must be verified separately before release.

Run with PHP, an existing Playwright installation and its browser (or `CAS_CHROME`):

```bash
python3 -m unittest discover -s tests -p 'test_cart_count.py'
node tests/test_cart_count_behavior.js
NODE_PATH=/path/to/existing/node_modules node tests/test_navigation_drawer_browser.cjs
```

Optional `CAS_QA_OUTPUT=/tmp/navigation-drawer-evidence` saves screenshots and results
outside the repository. The browser suite covers both themes at 320, 390, 768 and
1440px, signed-out/member/guest/registration states, native-shaped read/removal requests,
focus return/trapping, Escape/outside dismissal, read/removal failures, stale responses,
reduced/normal motion and progressive-enhancement fallback to the real basket link.

Short-screen cases additionally use actual coarse-pointer/touch emulation at 320×480,
844×390 and 568×320 in both themes. They verify reachable 44px header targets, scrolling
to the final menu link, viewport-bounded basket actions, a synthetic WordPress toolbar,
touch dismissal and a fresh drawer scroll position when reopened (38 cases in total).
