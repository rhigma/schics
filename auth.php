<?php
require_once __DIR__ . '/db.php';

const SCHICS_LEVEL_NONE  = 0;
const SCHICS_LEVEL_READ  = 1;
const SCHICS_LEVEL_EDIT  = 2;
const SCHICS_LEVEL_ADMIN = 3;

function schics_session_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('schics');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function schics_current_level(): int {
    schics_session_start();
    return (int)($_SESSION['schics_level'] ?? SCHICS_LEVEL_NONE);
}

function schics_read_open(): bool {
    return (string)schics_setting('read_password', '') === '';
}

// Passwort gegen alle drei Ebenen prüfen, höchste Treffer-Ebene zurückgeben.
function schics_match_password(string $candidate): int {
    $admin = (string)schics_setting('admin_password', '');
    $edit  = (string)schics_setting('edit_password',  '');
    $read  = (string)schics_setting('read_password',  '');

    if ($admin !== '' && hash_equals($admin, $candidate)) return SCHICS_LEVEL_ADMIN;
    if ($edit  !== '' && hash_equals($edit,  $candidate)) return SCHICS_LEVEL_EDIT;
    if ($read  !== '' && hash_equals($read,  $candidate)) return SCHICS_LEVEL_READ;
    return SCHICS_LEVEL_NONE;
}

function schics_login(int $level): void {
    schics_session_start();
    session_regenerate_id(true);
    $_SESSION['schics_level'] = $level;
}

function schics_logout(): void {
    schics_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/', $params['domain'] ?? '',
            $params['secure'] ?? false, $params['httponly'] ?? false);
    }
    session_destroy();
}

// Bei Aufruf einer geschützten Seite:
//   - Setup nicht abgeschlossen → setup.php
//   - Ebene zu niedrig → login.php (oder 401 bei AJAX)
function schics_require_level(int $required): void {
    if (!schics_setup_done()) {
        header('Location: setup.php');
        exit;
    }

    $current = schics_current_level();
    if ($required === SCHICS_LEVEL_READ && schics_read_open()) {
        return;
    }
    if ($current >= $required) {
        return;
    }

    $isAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
        || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);
    if ($isAjax) {
        http_response_code(401);
        echo 'Nicht angemeldet.';
        exit;
    }

    $target = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?next=' . urlencode($target) . '&need=' . $required);
    exit;
}

// Einmalige Statusmeldung über mehrere Requests hinweg (z. B. nach POST+Redirect).
function schics_flash(string $message): void {
    schics_session_start();
    $_SESSION['schics_flash'] = $message;
}

function schics_consume_flash(): ?string {
    schics_session_start();
    if (!isset($_SESSION['schics_flash'])) return null;
    $msg = $_SESSION['schics_flash'];
    unset($_SESSION['schics_flash']);
    return $msg;
}

function schics_level_label(int $level): string {
    return match ($level) {
        SCHICS_LEVEL_ADMIN => 'Admin',
        SCHICS_LEVEL_EDIT  => 'Bearbeiten',
        SCHICS_LEVEL_READ  => 'Lesen',
        default            => 'Nicht angemeldet',
    };
}
