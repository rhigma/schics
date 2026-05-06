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
        $info = '✅ Neue Version gespeichert.';
        $infoClass = 'alert-success';
    }
}

function f(string $key, string $default = ''): string {
    global $vorlage;
    return htmlspecialchars($vorlage[$key] ?? $default);
}

[$jgMin, $jgMax] = schics_jahrgang_range();
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
                <h2 class="section-title">Allgemeine Angaben</h2>
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">SchiC-ID *</label>
                        <input type="number" name="schic_id" class="form-control" value="<?= f('schic_id') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Version *</label>
                        <input type="text" name="version" class="form-control" placeholder="z. B. 1.1" required></div>
                    <div class="col-md-3"><label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <?php $status = f('status', 'Entwurf'); ?>
                            <option value="Entwurf"     <?= $status === 'Entwurf'     ? 'selected' : '' ?>>Entwurf</option>
                            <option value="Beschlossen" <?= $status === 'Beschlossen' ? 'selected' : '' ?>>Beschlossen</option>
                        </select></div>
                    <div class="col-md-3"><label class="form-label">Stand *</label>
                        <input type="date" name="stand" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Jahrgang *</label>
                        <input type="number" name="jahrgang" class="form-control"
                               value="<?= f('jahrgang') ?>"
                               min="<?= (int)$jgMin ?>" max="<?= (int)$jgMax ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Fach *</label>
                        <select name="fach" class="form-select" required>
                            <?php foreach (schics_faecher() as $fach): ?>
                                <option value="<?= htmlspecialchars($fach) ?>" <?= f('fach') === $fach ? 'selected' : '' ?>><?= htmlspecialchars($fach) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-8"><label class="form-label">Thema *</label>
                        <input type="text" name="thema" class="form-control" value="<?= f('thema') ?>" required></div>
                    <div class="col-md-2"><label class="form-label">Umfang</label>
                        <input type="text" name="umfang" class="form-control" value="<?= f('umfang') ?>"></div>
                    <div class="col-md-2"><label class="form-label">Reihenfolge</label>
                        <input type="number" name="reihenfolge" class="form-control" value="<?= f('reihenfolge', '0') ?>"></div>
                </div>
            </section>

            <section class="section section--rlp-a">
                <h2 class="section-title">Rahmenlehrplan A · Fachübergreifende Aspekte</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Fächerverbindung</label><textarea name="fächerverbindung" class="form-control"><?= f('fächerverbindung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Heterogenität / Inklusion</label><textarea name="heterogenität" class="form-control"><?= f('heterogenität') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Schulprofil</label><textarea name="schulprofil" class="form-control"><?= f('schulprofil') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Lebensweltbezug</label><textarea name="lebensweltbezug" class="form-control"><?= f('lebensweltbezug') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Kooperationen</label><textarea name="kooperationen" class="form-control"><?= f('kooperationen') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Leistungsbewertung</label><textarea name="leistungsbewertung" class="form-control"><?= f('leistungsbewertung') ?></textarea></div>
                </div>
            </section>

            <section class="section section--rlp-b">
                <h2 class="section-title">Rahmenlehrplan B · Querschnittsaufgaben</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Sprachbildung</label><textarea name="sprachbildung" class="form-control"><?= f('sprachbildung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Medienbildung</label><textarea name="medienbildung" class="form-control"><?= f('medienbildung') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Methoden</label><textarea name="methoden" class="form-control"><?= f('methoden') ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Übergreifende Themen</label><textarea name="übergreifende_themen" class="form-control"><?= f('übergreifende_themen') ?></textarea></div>
                </div>
            </section>

            <section class="section section--rlp-c">
                <h2 class="section-title">Rahmenlehrplan C · Kompetenzen</h2>
                <div class="row g-3">
                    <div class="col-md-12"><label class="form-label">Kompetenzen</label><textarea name="kompetenzen" class="form-control"><?= f('kompetenzen') ?></textarea></div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Bearbeitung</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Bearbeitet von <span class="text-muted">(optional)</span></label><input type="text" name="bearbeitet_von" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Änderungskommentar</label><textarea name="änderungskommentar" class="form-control"></textarea></div>
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
