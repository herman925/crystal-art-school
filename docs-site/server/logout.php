<?php
require __DIR__ . '/auth.php';

docs_boot();
docs_sign_out();

header('Cache-Control: no-store');
header('Clear-Site-Data: "cache"');
header('Location: /login.php');
