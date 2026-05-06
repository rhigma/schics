<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo = schics_db();
$schic_id = isset($_GET['schic_id']) ? (int)$_GET['schic_id'] : 0;
if ($schic_id <= 0) {
    echo 'Keine SchiC-ID übergeben.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM curricula WHERE schic_id = :id ORDER BY id DESC');
$stmt->execute([':id' => $schic_id]);
$versionen = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$versionen) {
    echo 'Keine Einträge gefunden.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alle Versionen – SchiC #<?= htmlspecialchars((string)$schic_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <div>
                <div class="text-muted" style="font-size:.85rem; text-transform:uppercase; letter-spacing:.05em;">SchiC #<?= htmlspecialchars((string)$schic_id) ?></div>
                <h1 style="margin-top:.25rem;">Versionshistorie</h1>
            </div>
            <div class="actions">
                <a href="index.php" class="btn btn-secondary">← Übersicht</a>
                <a href="detail.php?schic_id=<?= (int)$schic_id ?>" class="btn btn-outline-primary">Aktuelle Version</a>
            </div>
        </div>

        <?php foreach ($versionen as $v): ?>
            <article class="version-card">
                <div class="version-card-header">
                    <strong>Version <?= htmlspecialchars($v['version']) ?></strong>
                    <?= schics_status_badge($v['status']) ?>
                    <span class="text-muted" style="font-size:.9rem;">Stand <?= htmlspecialchars($v['stand']) ?></span>
                </div>
                <div class="version-card-body">
                    <div class="field-grid">
                        <?= schics_field('Thema', $v['thema']) ?>
                        <?php if (!empty($v['bearbeitet_von'])): ?>
                            <?= schics_field('Bearbeitet von', $v['bearbeitet_von']) ?>
                        <?php endif; ?>
                        <?= schics_field('Eingetragen am', $v['erstellt_am']) ?>
                    </div>
                    <?php if (!empty($v['änderungskommentar'])): ?>
                        <div style="margin-top:1rem;">
                            <span class="field-label">Änderungskommentar</span>
                            <p class="field-value"><?= nl2br(htmlspecialchars($v['änderungskommentar'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>
