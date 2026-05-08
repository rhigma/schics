<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_EDIT);

$pdo  = schics_db();
$info = '';
$infoClass = 'alert-info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['stand', 'fach', 'jahrgang', 'thema'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $info = 'Bitte alle Pflichtfelder ausfüllen.';
            $infoClass = 'alert-danger';
            break;
        }
    }

    if (!$info) {
        try {
            $pdo->beginTransaction();
            $next = (int)$pdo->query('SELECT COALESCE(MAX(schic_id), 0) FROM curricula')->fetchColumn() + 1;

            // Sortierrang: niedrigstmögliche höchste Position innerhalb des
            // gewählten Fachs/Jahrgangs, sodass neue SchiCs immer hinten landen.
            $rangStmt = $pdo->prepare('SELECT COALESCE(MAX(reihenfolge), 0) + 1
                                       FROM curricula
                                       WHERE fach = :fach AND jahrgang = :jahrgang');
            $rangStmt->execute([':fach' => $_POST['fach'], ':jahrgang' => (int)$_POST['jahrgang']]);
            $reihenfolge = (int)$rangStmt->fetchColumn();

            $sql = 'INSERT INTO curricula
                (schic_id, version, status, stand, fach, jahrgang, thema, umfang, reihenfolge,
                 "fächerverbindung", "heterogenität", schulprofil, lebensweltbezug, kompetenzen,
                 kooperationen, leistungsbewertung, medienbildung, methoden, sprachbildung,
                 "übergreifende_themen", "änderungskommentar", bearbeitet_von)
                VALUES
                (:schic_id, :version, :status, :stand, :fach, :jahrgang, :thema, :umfang, :reihenfolge,
                 :fächerverbindung, :heterogenität, :schulprofil, :lebensweltbezug, :kompetenzen,
                 :kooperationen, :leistungsbewertung, :medienbildung, :methoden, :sprachbildung,
                 :übergreifende_themen, :änderungskommentar, :bearbeitet_von)';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':schic_id'             => $next,
                ':version'              => '1',
                ':status'               => 'Entwurf',
                ':stand'                => $_POST['stand'],
                ':fach'                 => $_POST['fach'],
                ':jahrgang'             => (int)$_POST['jahrgang'],
                ':thema'                => $_POST['thema'],
                ':umfang'               => $_POST['umfang']                ?? '',
                ':reihenfolge'          => $reihenfolge,
                ':fächerverbindung'     => $_POST['fächerverbindung']      ?? '',
                ':heterogenität'        => $_POST['heterogenität']         ?? '',
                ':schulprofil'          => $_POST['schulprofil']           ?? '',
                ':lebensweltbezug'      => $_POST['lebensweltbezug']       ?? '',
                ':kompetenzen'          => $_POST['kompetenzen']           ?? '',
                ':kooperationen'        => $_POST['kooperationen']         ?? '',
                ':leistungsbewertung'   => $_POST['leistungsbewertung']    ?? '',
                ':medienbildung'        => $_POST['medienbildung']         ?? '',
                ':methoden'             => $_POST['methoden']              ?? '',
                ':sprachbildung'        => $_POST['sprachbildung']         ?? '',
                ':übergreifende_themen' => $_POST['übergreifende_themen']  ?? '',
                ':änderungskommentar'   => $_POST['änderungskommentar']    ?? '',
                ':bearbeitet_von'       => $_POST['bearbeitet_von']        ?? '',
            ]);
            $pdo->commit();
            $info = '✅ SchiC-Eintrag erfolgreich gespeichert (ID #' . $next . ').';
            $infoClass = 'alert-success';
            $_POST = [];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $info = 'Fehler beim Speichern: ' . htmlspecialchars($e->getMessage());
            $infoClass = 'alert-danger';
        }
    }
}

function p(string $key): string { return htmlspecialchars($_POST[$key] ?? ''); }

$values = $_POST;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neuer SchiC-Eintrag – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <h1>Neuen SchiC anlegen</h1>
        </div>

        <?php if ($info): ?>
            <div class="alert <?= $infoClass ?>"><?= $info ?></div>
        <?php endif; ?>

        <form method="post">
            <section class="section">
                <h2 class="section-title">Verwaltung</h2>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Version</label>
                        <input type="text" class="form-control" value="1" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <input type="text" class="form-control" value="Entwurf" disabled>
                    </div>
                </div>
            </section>

            <?php include __DIR__ . '/_curriculum_form.php'; ?>

            <section class="section">
                <h2 class="section-title">Bearbeitung</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Bearbeitet von <span class="text-muted">(optional)</span></label><input type="text" name="bearbeitet_von" class="form-control" value="<?= p('bearbeitet_von') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Kommentar</label><textarea name="änderungskommentar" class="form-control"><?= p('änderungskommentar') ?></textarea></div>
                </div>
            </section>

            <div class="actions" style="margin-top:1.5rem;">
                <button class="btn btn-primary">Speichern</button>
                <a href="index.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>

        <?php $fachAktuell = $values['fach'] ?? ''; include __DIR__ . '/_rlp_panel.php'; ?>
    </main>
    <script src="assets/curriculum.js"></script>
</body>
</html>
