<?php
/**
 * Session guard for the site guide.
 *
 * The built site lives in _private/, which the web server refuses to serve. Everything
 * reaches the browser through gate.php, so an unauthenticated request can never touch a
 * file - not a page, not a stylesheet, not the search index.
 */

const DOCS_SESSION = 'cas_docs_auth';

function docs_users(): array {
    $file = __DIR__ . '/users.php';
    return is_readable($file) ? require $file : [];
}

function docs_boot(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('cas_docs');
        session_start();
    }
}

function docs_is_signed_in(): bool {
    return !empty($_SESSION[DOCS_SESSION]);
}

function docs_sign_in(string $user, string $password): bool {
    $users = docs_users();
    $hash  = $users[strtolower(trim($user))] ?? null;

    /* Verify against a dummy hash when the username is unknown, so a wrong username and a
       wrong password take the same time to reject. */
    if ($hash === null) {
        password_verify($password, '$2y$12$usqRUCJlSJ8ChTMV2wBd1uEHZ7ynW1sk3iX0ByQrPCcqLZTFrHnBC');
        return false;
    }

    if (!password_verify($password, $hash)) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION[DOCS_SESSION] = strtolower(trim($user));
    return true;
}

function docs_sign_out(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}
