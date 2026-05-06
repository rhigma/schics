<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_EDIT);

$pdo  = schics_db();
$info = '';
$infoClass = 'alert-info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['version', 'status', 'stand', 'fach', 'jahrgang', 'thema'];
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
                ':version'              => $_POST['version'],
                ':status'               => $_POST['status'],
                ':stand'                => $_POST['stand'],
                ':fach'                 => $_POST['fach'],
                ':jahrgang'             => (int)$_POST['jahrgang'],
                ':thema'                => $_POST['thema'],
                ':umfang'               => $_POST['umfang']                ?? '',
                ':reihenfolge'          => (int)($_POST['reihenfolge']     ?? 0),
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
function selected(string $key, string $value): string {
    return (($_POST[$key] ?? '') === $value) ? 'selected' : '';
}
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
                <h2 class="section-title">Allgemeine Angaben</h2>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Version *</label>
                        <input type="text" name="version" class="form-control" value="<?= p('version') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="Entwurf"     <?= selected('status','Entwurf') ?>>Entwurf</option>
                            <option value="Beschlossen" <?= selected('status','Beschlossen') ?>>Beschlossen</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stand *</label>
                        <input type="date" name="stand" class="form-control" value="<?= p('stand') ?: date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Jahrgang *</label>
                        <input type="number" name="jahrgang" class="form-control" value="<?= p('jahrgang') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fach *</label>
                        <select name="fach" class="form-select" required>
                            <option value="">– bitte wählen –</option>
                            <?php foreach (schics_faecher() as $fach): ?>
                                <option value="<?= htmlspecialchars($fach) ?>" <?= selected('fach', $fach) ?>><?= htmlspecialchars($fach) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Thema *</label>
                        <input type="text" name="thema" class="form-control" value="<?= p('thema') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Umfang (Stunden)</label>
                        <input type="text" name="umfang" class="form-control" value="<?= p('umfang') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Reihenfolge</label>
                        <input type="number" name="reihenfolge" class="form-control" value="<?= p('reihenfolge') ?>">
                    </div>
                </div>
            </section>

            <section class="section section--rlp-a">
                <h2 class="section-title">Rahmenlehrplan A · Fachübergreifende Aspekte</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Fächerverbindung</label><textarea name="fächerverbindung" class="form-control"><?= p('fächerverbindung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Heterogenität / Inklusion</label><textarea name="heterogenität" class="form-control"><?= p('heterogenität') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Schulprofil / Schwerpunktsetzung</label><textarea name="schulprofil" class="form-control"><?= p('schulprofil') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Lebensweltbezug</label><textarea name="lebensweltbezug" class="form-control"><?= p('lebensweltbezug') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Kooperationen / Lernorte</label><textarea name="kooperationen" class="form-control"><?= p('kooperationen') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Lernberatung &amp; Leistungsbewertung</label><textarea name="leistungsbewertung" class="form-control"><?= p('leistungsbewertung') ?></textarea></div>
                </div>
            </section>

            <section class="section section--rlp-b">
                <h2 class="section-title">Rahmenlehrplan B · Querschnittsaufgaben</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Sprachbildung</label><textarea name="sprachbildung" class="form-control"><?= p('sprachbildung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Medienbildung</label><textarea name="medienbildung" class="form-control"><?= p('medienbildung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Methoden und Arbeitstechniken</label><textarea name="methoden" class="form-control"><?= p('methoden') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Übergreifende Themen</label><textarea name="übergreifende_themen" class="form-control"><?= p('übergreifende_themen') ?></textarea></div>
                </div>
            </section>

            <section class="section section--rlp-c">
                <h2 class="section-title">Rahmenlehrplan C · Kompetenzen</h2>
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label">Kompetenzen und Konkretisierung</label><textarea name="kompetenzen" class="form-control"><?= p('kompetenzen') ?></textarea></div>
                </div>
            </section>

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
    </main>
</body>
</html>
