<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo                 = schics_db();
$fächer              = schics_faecher();
[$jgMin, $jgMax]     = schics_jahrgang_range();
$jahrgaenge          = range($jgMin, $jgMax);
$canEdit             = schics_current_level() >= SCHICS_LEVEL_EDIT;

// Aktuelle Versionen aller SchiCs laden — eine Zeile pro schic_id (höchste id).
$stmt = $pdo->query('
    SELECT c1.schic_id, c1.fach, c1.jahrgang, c1.thema, c1.status
    FROM curricula c1
    INNER JOIN (
        SELECT schic_id, MAX(id) AS max_id
        FROM curricula
        GROUP BY schic_id
    ) c2 ON c1.id = c2.max_id
    ORDER BY c1.reihenfolge ASC, c1.thema ASC
');
$alle = $stmt->fetchAll(PDO::FETCH_ASSOC);

// In Zellen [fach][jahrgang] = [ {schic_id, thema, status}, ... ] gruppieren.
$zellen = [];
foreach ($alle as $e) {
    $zellen[$e['fach']][(int)$e['jahrgang']][] = $e;
}
$gesamt = count($alle);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Übersicht – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <div>
                <h1>Schulinterne Curricula</h1>
                <p class="text-muted" style="margin:0;">Übersicht: in welchen Fächern und Jahrgängen schon SchiCs vorliegen. Eine Kachel anklicken, um zu den Einträgen zu springen.</p>
            </div>
        </div>

        <section class="section">
            <div class="overview-meta">
                <span><strong><?= $gesamt ?></strong> SchiC<?= $gesamt === 1 ? '' : 's' ?> insgesamt</span>
                <span class="overview-legend">
                    <span class="overview-legend-swatch overview-legend-swatch--empty"></span> keiner
                    <span class="overview-legend-swatch overview-legend-swatch--one"></span> 1
                    <span class="overview-legend-swatch overview-legend-swatch--few"></span> 2–3
                    <span class="overview-legend-swatch overview-legend-swatch--many"></span> 4+
                </span>
            </div>

            <div class="overview-scroll">
            <table class="overview-grid">
                <thead>
                    <tr>
                        <th class="overview-corner">Fach \ Jg.</th>
                        <?php foreach ($jahrgaenge as $jg): ?>
                            <th class="overview-jg"><?= (int)$jg ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fächer as $fach): ?>
                        <tr>
                            <th class="overview-fach"><?= htmlspecialchars($fach) ?></th>
                            <?php foreach ($jahrgaenge as $jg):
                                $eintraege = $zellen[$fach][(int)$jg] ?? [];
                                $anzahl    = count($eintraege);
                                $klasse    = 'overview-cell';
                                if      ($anzahl === 0) { $klasse .= ' overview-cell--empty'; }
                                elseif  ($anzahl === 1) { $klasse .= ' overview-cell--one'; }
                                elseif  ($anzahl <=  3) { $klasse .= ' overview-cell--few'; }
                                else                    { $klasse .= ' overview-cell--many'; }

                                if ($anzahl === 1) {
                                    $href = 'detail.php?schic_id=' . (int)$eintraege[0]['schic_id'];
                                } elseif ($anzahl > 1) {
                                    $href = 'suchen.php?fach=' . urlencode($fach) . '&jahrgang=' . (int)$jg;
                                } elseif ($canEdit) {
                                    $href = 'admin.php?fach=' . urlencode($fach) . '&jahrgang=' . (int)$jg;
                                } else {
                                    $href = null;
                                }
                            ?>
                                <td class="<?= $klasse ?>">
                                    <?php if ($href !== null): ?>
                                        <a class="overview-cell-link" href="<?= htmlspecialchars($href) ?>">
                                            <span class="overview-cell-count"><?= $anzahl ?: '' ?></span>
                                            <?php if ($anzahl > 0): ?>
                                                <div class="overview-tooltip" role="tooltip">
                                                    <div class="overview-tooltip-head"><?= htmlspecialchars($fach) ?> · Jg. <?= (int)$jg ?></div>
                                                    <ul>
                                                        <?php foreach ($eintraege as $e): ?>
                                                            <li>
                                                                <span class="overview-tooltip-thema"><?= htmlspecialchars($e['thema']) ?></span>
                                                                <?= schics_status_badge($e['status']) ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php elseif ($canEdit): ?>
                                                <div class="overview-tooltip" role="tooltip">
                                                    <div class="overview-tooltip-head"><?= htmlspecialchars($fach) ?> · Jg. <?= (int)$jg ?></div>
                                                    <p class="overview-tooltip-empty">Noch kein SchiC — neuen Eintrag anlegen.</p>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="overview-cell-link overview-cell-link--static"></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
    </main>
</body>
</html>
