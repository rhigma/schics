<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_ADMIN);

$message    = '';
$msgClass   = 'alert-success';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolName  = trim((string)($_POST['school_name'] ?? ''));
    $readPwd     = (string)($_POST['read_password']  ?? '');
    $editPwd     = (string)($_POST['edit_password']  ?? '');
    $adminPwd    = (string)($_POST['admin_password'] ?? '');
    $jahrgangMin = filter_var($_POST['jahrgang_min'] ?? '', FILTER_VALIDATE_INT);
    $jahrgangMax = filter_var($_POST['jahrgang_max'] ?? '', FILTER_VALIDATE_INT);
    $faecherRaw  = (string)($_POST['faecher'] ?? '');
    $faecherList = array_values(array_filter(
        array_map('trim', preg_split('/\R/', $faecherRaw)),
        fn($f) => $f !== ''
    ));

    if ($schoolName === '')                 $errors[] = 'Schulname darf nicht leer sein.';
    if ($editPwd === '')                    $errors[] = 'Bearbeiten-Passwort darf nicht leer sein.';
    if ($adminPwd === '')                   $errors[] = 'Admin-Passwort darf nicht leer sein.';
    if ($editPwd !== '' && $editPwd === $adminPwd)
        $errors[] = 'Bearbeiten- und Admin-Passwort müssen sich unterscheiden.';
    if ($readPwd !== '' && ($readPwd === $editPwd || $readPwd === $adminPwd))
        $errors[] = 'Lese-Passwort darf nicht mit den anderen übereinstimmen.';

    if ($jahrgangMin === false || $jahrgangMin < 1 || $jahrgangMin > 13)
        $errors[] = 'Jahrgang von muss zwischen 1 und 13 liegen.';
    if ($jahrgangMax === false || $jahrgangMax < 1 || $jahrgangMax > 13)
        $errors[] = 'Jahrgang bis muss zwischen 1 und 13 liegen.';
    if ($jahrgangMin !== false && $jahrgangMax !== false && $jahrgangMin > $jahrgangMax)
        $errors[] = '„Jahrgang von" darf nicht größer als „Jahrgang bis" sein.';

    if (!$faecherList) $errors[] = 'Es muss mindestens ein Fach geben.';

    if (!$errors) {
        schics_set_setting('school_name',    $schoolName);
        schics_set_setting('read_password',  $readPwd);
        schics_set_setting('edit_password',  $editPwd);
        schics_set_setting('admin_password', $adminPwd);
        schics_set_setting('jahrgang_min',   (string)$jahrgangMin);
        schics_set_setting('jahrgang_max',   (string)$jahrgangMax);
        schics_set_setting('faecher',        implode("\n", $faecherList));
        $message = 'Einstellungen gespeichert.';
    }
}

$schoolName = schics_school_name();
$readPwd    = (string)schics_setting('read_password',  '');
$editPwd    = (string)schics_setting('edit_password',  '');
$adminPwd   = (string)schics_setting('admin_password', '');
[$jahrgangMin, $jahrgangMax] = schics_jahrgang_range();
$faecherList = schics_faecher();
$faecherText = implode("\n", $faecherList);
$flashMsg    = schics_consume_flash();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Einstellungen – <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-narrow">
        <div class="page-header">
            <div>
                <h1>Einstellungen</h1>
                <p class="text-muted" style="margin:0;">Schulname und drei Passwörter ändern. Bestehende Sitzungen bleiben bis zum Abmelden gültig.</p>
            </div>
        </div>

        <?php if ($flashMsg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($flashMsg) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert <?= $msgClass ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="post">
            <section class="section">
                <h2 class="section-title">Schule</h2>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Name der Schule *</label>
                        <input type="text" name="school_name" class="form-control"
                               value="<?= htmlspecialchars($schoolName) ?>" required>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Curriculum-Vorgaben</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Jahrgang von *</label>
                        <input type="number" name="jahrgang_min" class="form-control"
                               value="<?= (int)$jahrgangMin ?>" min="1" max="13" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jahrgang bis *</label>
                        <input type="number" name="jahrgang_max" class="form-control"
                               value="<?= (int)$jahrgangMax ?>" min="1" max="13" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Fächer *</label>
                        <textarea name="faecher" class="form-control" rows="9"><?= htmlspecialchars($faecherText) ?></textarea>
                        <div class="form-text">
                            Ein Fach pro Zeile. Bestehende Curricula behalten ihren Fach-Namen,
                            auch wenn er hier umbenannt oder entfernt wird.
                        </div>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Passwörter</h2>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Lese-Passwort <span class="text-muted">(leer = offen)</span></label>
                        <input type="text" name="read_password" class="form-control"
                               value="<?= htmlspecialchars($readPwd) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bearbeiten-Passwort *</label>
                        <input type="text" name="edit_password" class="form-control"
                               value="<?= htmlspecialchars($editPwd) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admin-Passwort *</label>
                        <input type="text" name="admin_password" class="form-control"
                               value="<?= htmlspecialchars($adminPwd) ?>" required>
                    </div>
                </div>
            </section>

            <div class="actions">
                <button class="btn btn-primary">Speichern</button>
                <a href="index.php" class="btn btn-secondary">Zurück</a>
            </div>
        </form>

        <section class="section">
            <h2 class="section-title">Software-Update</h2>
            <p>
                Holt den aktuellen Stand der Anwendung von GitHub
                (<code>rhigma/schics</code>, Branch <code>main</code>) und
                überschreibt die Programmdateien. Die Datenbank und der
                <code>data/</code>-Ordner bleiben unverändert; vor jedem Update
                wird eine zeitgestempelte DB-Sicherung in <code>data/backups/</code>
                abgelegt (es werden die letzten 10 Sicherungen behalten).
            </p>
            <form method="post" action="update.php"
                  onsubmit="return confirm('Programmdateien jetzt von GitHub aktualisieren? Die Datenbank wird vorher gesichert.');">
                <input type="hidden" name="confirm" value="yes">
                <button class="btn btn-primary">Jetzt von GitHub aktualisieren</button>
            </form>
        </section>
    </main>
</body>
</html>
