<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_EDIT);

$pdo       = schics_db();
$info      = '';
$infoClass = 'alert-info';
$vorlage   = null;

if (isset($_GET['from_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM curricula WHERE id = ?');
    $stmt->execute([(int)$_GET['from_id']]);
    $alte = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($alte) {
        $stmt = $pdo->prepare('SELECT * FROM curricula WHERE schic_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([(int)$alte['schic_id']]);
        $vorlage = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['schic_id', 'version', 'status', 'stand', 'fach', 'jahrgang', 'thema'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $info = 'Bitte alle Pflichtfelder ausfüllen.';
            $infoClass = 'alert-danger';
            break;
        }
    }

    if (!$info) {
        // Rang ist eine SchiC-Eigenschaft, nicht versionsspezifisch:
        // bestehenden Wert der Versionslinie übernehmen.
        $rangStmt = $pdo->prepare('SELECT reihenfolge FROM curricula WHERE schic_id = ? ORDER BY id DESC LIMIT 1');
        $rangStmt->execute([(int)$_POST['schic_id']]);
        $reihenfolge = (int)($rangStmt->fetchColumn() ?: 0);

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
            ':schic_id'             => (int)$_POST['schic_id'],
            ':version'              => $_POST['version'],
            ':status'               => $_POST['status'],
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
        $info = '✅ Neue Version gespeichert.';
        $infoClass = 'alert-success';
    }
}

function f(string $key, string $default = ''): string {
    global $vorlage;
    return htmlspecialchars($vorlage[$key] ?? $default);
}

// Bei Validierungs-Fehlern POST-Daten zurückspielen, sonst Vorlage.
$values = !empty($_POST) ? $_POST : ($vorlage ?: []);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Neue Version – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <h1>Neue Version erstellen</h1>
            <?php if ($vorlage): ?>
                <div class="text-muted" style="font-size:.9rem;">
                    Vorlage: <em><?= htmlspecialchars($vorlage['thema']) ?></em>
                    (SchiC #<?= (int)$vorlage['schic_id'] ?>, Version <?= htmlspecialchars($vorlage['version']) ?>)
                </div>
            <?php endif; ?>
        </div>

        <?php if ($info): ?>
            <div class="alert <?= $infoClass ?>"><?= $info ?></div>
        <?php endif; ?>

        <form method="post">
            <section class="section">
                <h2 class="section-title">Verwaltung</h2>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">SchiC-ID *</label>
                        <input type="number" name="schic_id" class="form-control" value="<?= htmlspecialchars((string)($values['schic_id'] ?? '')) ?>" required></div>
                    <div class="col-md-3"><label class="form-label">Version *</label>
                        <input type="text" name="version" class="form-control" value="<?= htmlspecialchars((string)($_POST['version'] ?? '')) ?>" placeholder="z. B. 1.1" required></div>
                    <div class="col-md-3"><label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <?php $status = $_POST['status'] ?? ($vorlage['status'] ?? 'Entwurf'); ?>
                            <option value="Entwurf"     <?= $status === 'Entwurf'     ? 'selected' : '' ?>>Entwurf</option>
                            <option value="Beschlossen" <?= $status === 'Beschlossen' ? 'selected' : '' ?>>Beschlossen</option>
                        </select></div>
                </div>
            </section>

            <?php include __DIR__ . '/_curriculum_form.php'; ?>

            <section class="section">
                <h2 class="section-title">Bearbeitung</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Bearbeitet von <span class="text-muted">(optional)</span></label><input type="text" name="bearbeitet_von" class="form-control" value="<?= htmlspecialchars((string)($_POST['bearbeitet_von'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Änderungskommentar</label><textarea name="änderungskommentar" class="form-control"><?= htmlspecialchars((string)($_POST['änderungskommentar'] ?? '')) ?></textarea></div>
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
    <script src="assets/form-persist.js"></script>
</body>
</html>
