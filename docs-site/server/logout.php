<?php
require __DIR__ . '/auth.php';

docs_boot();
docs_sign_out();

header('Location: /login.php');
