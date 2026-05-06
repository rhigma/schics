<?php
require_once __DIR__ . '/auth.php';

if (schics_setup_done()) {
    header('Location: einstellungen.php');
    exit;
}

$errors = [];
$schoolName = '';
$readPwd    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolName = trim((string)($_POST['school_name'] ?? ''));
    $readPwd    = (string)($_POST['read_password']  ?? '');
    $editPwd    = (string)($_POST['edit_password']  ?? '');
    $adminPwd   = (string)($_POST['admin_password'] ?? '');

    if ($schoolName === '')                 $errors[] = 'Schulname ist erforderlich.';
    if ($editPwd === '')                    $errors[] = 'Bearbeiten-Passwort darf nicht leer sein.';
    if ($adminPwd === '')                   $errors[] = 'Admin-Passwort darf nicht leer sein.';
    if ($editPwd !== '' && $editPwd === $adminPwd)
        $errors[] = 'Bearbeiten- und Admin-Passwort müssen sich unterscheiden.';
    if ($readPwd !== '' && ($readPwd === $editPwd || $readPwd === $adminPwd))
        $errors[] = 'Lese-Passwort darf nicht mit den anderen übereinstimmen.';

    if (!$errors) {
        schics_set_setting('school_name',     $schoolName);
        schics_set_setting('read_password',   $readPwd);
        schics_set_setting('edit_password',   $editPwd);
        schics_set_setting('admin_password',  $adminPwd);
        schics_login(SCHICS_LEVEL_ADMIN);
        header('Location: index.php?setup=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erste Einrichtung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <main class="container-narrow">
        <div class="auth-card">
            <h1>Willkommen</h1>
            <p class="lede">Diese Seite erscheint nur einmal. Lege Schulnamen und drei Passwörter fest — alles ist später unter <em>Einstellungen</em> änderbar.</p>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <form method="post" class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Name der Schule *</label>
                    <input type="text" name="school_name" class="form-control"
                           value="<?= htmlspecialchars($schoolName) ?>" required>
                    <div class="form-text">Erscheint in der Navigation.</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Lese-Passwort <span class="text-muted">(optional)</span></label>
                    <input type="text" name="read_password" class="form-control"
                           value="<?= htmlspecialchars($readPwd) ?>">
                    <div class="form-text">
                        Leer lassen, wenn das Lesen offen sein soll. Kann später ergänzt
                        oder entfernt werden.
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Bearbeiten-Passwort *</label>
                    <input type="text" name="edit_password" class="form-control" required>
                    <div class="form-text">Für Lehrkräfte: Anlegen und Bearbeiten von Curricula.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Admin-Passwort *</label>
                    <input type="text" name="admin_password" class="form-control" required>
                    <div class="form-text">Zusätzlich zum Ändern dieser Einstellungen.</div>
                </div>

                <div class="col-md-12">
                    <button class="btn btn-primary">Einrichten</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
