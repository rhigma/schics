<?php
require_once __DIR__ . '/auth.php';

if (!schics_setup_done()) {
    header('Location: setup.php');
    exit;
}

$next   = $_GET['next'] ?? $_POST['next'] ?? 'index.php';
$need   = (int)($_GET['need'] ?? $_POST['need'] ?? SCHICS_LEVEL_READ);
$error  = '';

if (!preg_match('~^[A-Za-z0-9_./?=&%-]+$~', $next) || str_starts_with($next, '//')) {
    $next = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidate = (string)($_POST['password'] ?? '');
    $level = schics_match_password($candidate);
    if ($level === SCHICS_LEVEL_NONE) {
        $error = 'Passwort nicht erkannt.';
    } elseif ($level < $need) {
        $error = 'Dieses Passwort reicht für den angeforderten Bereich nicht aus.';
    } else {
        schics_login($level);
        header('Location: ' . $next);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmelden – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <main class="container-narrow">
        <div class="auth-card">
            <h1>Anmelden</h1>
            <p class="lede">
                Geforderte Ebene: <strong><?= htmlspecialchars(schics_level_label($need)) ?></strong>.
                Wer ein höheres Passwort kennt, kann es ebenfalls eingeben.
            </p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">
                <input type="hidden" name="need" value="<?= (int)$need ?>">
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" name="password" class="form-control" autofocus required>
                </div>
                <button class="btn btn-primary">Anmelden</button>
                <a href="index.php" class="topbar-link" style="margin-left:.75rem;">Abbrechen</a>
            </form>
        </div>
    </main>
</body>
</html>
