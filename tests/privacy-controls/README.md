Run the pure preference regression without a WordPress or database connection:

```sh
php tests/privacy-controls/preferences.php
```

Native release verification additionally uses a disposable, network-isolated WordPress/Tutor
site with synthetic students, restricted editor and artwork. Verify own-account nonce/identity
checks, invalid payload refusals, saved OFF and hidden-credit exclusions, global default
precedence and restricted settings access. In a browser, verify external video requests are
absent before permission, start only after enabling, stop/re-block on withdrawal, survive
reload, and respond to expiry, malformed storage, storage failures and cross-tab changes.
Check nested video/privacy dialogs, focus/Escape/scroll restoration, and the mobile filter
handle above the cookie notice. Production smoke checks must not alter a real student's choice.

The browser regression uses the actual privacy script and styles with a loopback fixture,
including a hidden accordion control and the modal backdrop before its Close button:

```sh
NODE_PATH=/path/to/existing/node_modules CAS_CHROME=/path/to/chrome node tests/privacy-controls/overlay.cjs
```

It checks focus entry/trapping/return, dismissal without saving, persistent-control focus
after the banner disappears, and actual iframe requests before and after saving/withdrawal.
It does not contact the public site or a video provider.
