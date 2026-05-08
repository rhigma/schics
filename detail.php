<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo = schics_db();
$schic_id = isset($_GET['schic_id']) ? (int)$_GET['schic_id'] : 0;
if ($schic_id <= 0) {
    echo 'Kein SchiC angegeben.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM curricula WHERE schic_id = :id ORDER BY id DESC LIMIT 1');
$stmt->execute([':id' => $schic_id]);
$eintrag = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$eintrag) {
    echo 'SchiC nicht gefunden.';
    exit;
}

// Nachbarn im selben Fach/Jahrgang ermitteln (Sortierung identisch zu sortieren.php).
$nachbarStmt = $pdo->prepare('
    SELECT c1.schic_id, c1.thema
    FROM curricula c1
    INNER JOIN (
        SELECT schic_id, MAX(id) AS max_id
        FROM curricula
        GROUP BY schic_id
    ) c2 ON c1.id = c2.max_id
    WHERE c1.fach = :fach AND c1.jahrgang = :jahrgang
    ORDER BY c1.reihenfolge ASC, c1.thema ASC
');
$nachbarStmt->execute([':fach' => $eintrag['fach'], ':jahrgang' => (int)$eintrag['jahrgang']]);
$nachbarn = $nachbarStmt->fetchAll(PDO::FETCH_ASSOC);

$pagerIdx = -1;
foreach ($nachbarn as $i => $n) {
    if ((int)$n['schic_id'] === $schic_id) { $pagerIdx = $i; break; }
}
$pagerPrev  = $pagerIdx > 0 ? $nachbarn[$pagerIdx - 1] : null;
$pagerNext  = ($pagerIdx >= 0 && $pagerIdx < count($nachbarn) - 1) ? $nachbarn[$pagerIdx + 1] : null;
$pagerTotal = count($nachbarn);

$fach_links = [
    'Sachunterricht'              => 'Teil_C_Sachunterricht_2015_11_16.pdf',
    'Naturwissenschaften'         => 'Teil_C_Nawi_5-6_2015_11_16.pdf',
    'Musik'                       => 'Teil_C_Musik_2015_11_16.pdf',
    'Englisch'                    => 'Teil_C_Mod_Fremdsprachen_2015_11_16.pdf',
    'Kunst'                       => 'Teil_C_Kunst_2015_11_10.pdf',
    'Gesellschaftswissenschaften' => 'Teil_C_Gesellschaftswissenschaften_2015_11_10.pdf',
    'Deutsch'                     => 'rlp-deutsch_1-10-teil-c.pdf',
    'Mathematik'                  => 'rahmenlehrplan-teil-c_mathe-1-10.pdf',
];
$link_c        = $fach_links[$eintrag['fach']] ?? null;
$currentLevel  = schics_current_level();
$canEdit       = $currentLevel >= SCHICS_LEVEL_EDIT;
$canChangeStatus = $currentLevel >= SCHICS_LEVEL_ADMIN;
$flash         = schics_consume_flash();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($eintrag['thema']) ?> – Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">

        <?php if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <div class="text-muted" style="font-size:.85rem; text-transform:uppercase; letter-spacing:.05em;">
                    <?= htmlspecialchars($eintrag['fach']) ?> · Jahrgang <?= htmlspecialchars($eintrag['jahrgang']) ?>
                </div>
                <h1 style="margin-top:.25rem;"><?= htmlspecialchars($eintrag['thema']) ?></h1>
                <div class="meta-row">
                    <?= schics_status_badge($eintrag['status']) ?>
                    <?php if ($canChangeStatus): ?>
                        <form method="post" action="update_status.php" class="status-form">
                            <input type="hidden" name="id"   value="<?= (int)$eintrag['id'] ?>">
                            <input type="hidden" name="back" value="detail.php?schic_id=<?= (int)$schic_id ?>">
                            <?php if ($eintrag['status'] === 'Beschlossen'): ?>
                                <button name="status" value="Entwurf" class="btn btn-sm btn-outline-secondary">Auf Entwurf zurücksetzen</button>
                            <?php else: ?>
                                <button name="status" value="Beschlossen" class="btn btn-sm btn-primary">Als beschlossen markieren</button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    <span class="text-muted" style="font-size:.9rem;">Version <?= htmlspecialchars($eintrag['version']) ?> · Stand <?= htmlspecialchars($eintrag['stand']) ?></span>
                </div>
            </div>
            <div class="actions">
                <a href="index.php" class="btn btn-secondary">← Übersicht</a>
                <a href="alle_versionen.php?schic_id=<?= (int)$schic_id ?>" class="btn btn-outline-secondary">Alle Versionen</a>
                <a href="pdf.php?schic_id=<?= (int)$schic_id ?>&amp;dl=1" class="btn btn-outline-primary">📥 PDF herunterladen</a>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary">🖨️ Drucken</button>
                <?php if ($canEdit): ?>
                    <a href="neue_version.php?from_id=<?= (int)$eintrag['id'] ?>" class="btn btn-primary">Neue Version</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pagerTotal > 1): ?>
            <nav class="schic-pager" aria-label="Navigation innerhalb <?= htmlspecialchars($eintrag['fach']) ?> · Jahrgang <?= htmlspecialchars($eintrag['jahrgang']) ?>">
                <?php if ($pagerPrev): ?>
                    <a class="schic-pager__btn schic-pager__btn--prev"
                       href="detail.php?schic_id=<?= (int)$pagerPrev['schic_id'] ?>"
                       title="<?= htmlspecialchars($pagerPrev['thema']) ?>"
                       data-pager-key="ArrowLeft">
                        <span class="schic-pager__arrow" aria-hidden="true">←</span>
                        <span class="schic-pager__label"><?= htmlspecialchars($pagerPrev['thema']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="schic-pager__btn schic-pager__btn--prev is-disabled" aria-disabled="true">
                        <span class="schic-pager__arrow" aria-hidden="true">←</span>
                        <span class="schic-pager__label text-muted">Anfang</span>
                    </span>
                <?php endif; ?>

                <span class="schic-pager__pos text-muted">
                    <?= $pagerIdx + 1 ?> / <?= $pagerTotal ?>
                </span>

                <?php if ($pagerNext): ?>
                    <a class="schic-pager__btn schic-pager__btn--next"
                       href="detail.php?schic_id=<?= (int)$pagerNext['schic_id'] ?>"
                       title="<?= htmlspecialchars($pagerNext['thema']) ?>"
                       data-pager-key="ArrowRight">
                        <span class="schic-pager__label"><?= htmlspecialchars($pagerNext['thema']) ?></span>
                        <span class="schic-pager__arrow" aria-hidden="true">→</span>
                    </a>
                <?php else: ?>
                    <span class="schic-pager__btn schic-pager__btn--next is-disabled" aria-disabled="true">
                        <span class="schic-pager__label text-muted">Ende</span>
                        <span class="schic-pager__arrow" aria-hidden="true">→</span>
                    </span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <header class="print-header">
            <?= htmlspecialchars(schics_school_name()) ?> &ndash; Schulinternes Curriculum
        </header>

        <?php $values = $eintrag; include __DIR__ . '/_curriculum_view.php'; ?>

        <footer class="print-footer">
            Version <?= htmlspecialchars($eintrag['version']) ?>
            &middot; Status <?= htmlspecialchars($eintrag['status']) ?>
            <?php if (!empty($eintrag['bearbeitet_von'])): ?>
                &middot; Bearbeitet von <?= htmlspecialchars($eintrag['bearbeitet_von']) ?>
            <?php endif; ?>
        </footer>

        <div class="curriculum-pdfs">
            <a class="pdf-link" href="rlps/Teil_A_2015_11_16.pdf" target="_blank">📄 Rahmenlehrplan Teil A</a>
            <a class="pdf-link" href="rlps/Teil_B_2015_11_10.pdf" target="_blank">📄 Rahmenlehrplan Teil B</a>
            <?php if ($link_c): ?>
                <a class="pdf-link" href="rlps/<?= htmlspecialchars($link_c) ?>" target="_blank">📄 Rahmenlehrplan Teil C – <?= htmlspecialchars($eintrag['fach']) ?></a>
            <?php endif; ?>
        </div>

        <section class="section">
            <h2 class="section-title">Bearbeitung</h2>
            <div class="field-grid">
                <?= schics_field('Reihenfolge', (string)$eintrag['reihenfolge']) ?>
                <?php if (!empty($eintrag['bearbeitet_von'])): ?>
                    <?= schics_field('Bearbeitet von', $eintrag['bearbeitet_von']) ?>
                <?php endif; ?>
                <?= schics_field('Eingetragen am', $eintrag['erstellt_am']) ?>
            </div>
            <?php if (!empty($eintrag['änderungskommentar'])): ?>
                <div style="margin-top:1rem;">
                    <span class="field-label">Änderungskommentar</span>
                    <p class="field-value"><?= nl2br(htmlspecialchars($eintrag['änderungskommentar'])) ?></p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        document.addEventListener('keydown', (e) => {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            const tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
            if (e.target && e.target.isContentEditable) return;
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            const link = document.querySelector('.schic-pager__btn[data-pager-key="' + e.key + '"]');
            if (link && link.href) { e.preventDefault(); window.location.href = link.href; }
        });
    </script>
</body>
</html>
