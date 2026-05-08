<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo            = schics_db();
$erlaubteFelder = schics_content_fields();

$feld = $_GET['feld'] ?? '';
if (!in_array($feld, $erlaubteFelder, true)) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Ungültiges Feld.</div>';
    exit;
}

$suchwert       = trim($_GET['suchwert'] ?? '');
$fachfilter     = trim($_GET['fach'] ?? '');
$jahrgangfilter = trim($_GET['jahrgang'] ?? '');

$feldQuoted = schics_quote_ident($feld);
$sql = "
    SELECT c1.*
    FROM curricula c1
    INNER JOIN (
        SELECT schic_id, MAX(id) AS max_id
        FROM curricula
        GROUP BY schic_id
    ) c2 ON c1.id = c2.max_id
    WHERE $feldQuoted IS NOT NULL AND $feldQuoted != ''";
$params = [];

if ($suchwert !== '') {
    $sql .= " AND $feldQuoted LIKE ?";
    $params[] = '%' . $suchwert . '%';
}
if ($fachfilter !== '') {
    $sql .= ' AND fach LIKE ?';
    $params[] = '%' . $fachfilter . '%';
}
if ($jahrgangfilter !== '' && is_numeric($jahrgangfilter)) {
    $sql .= ' AND jahrgang = ?';
    $params[] = (int)$jahrgangfilter;
}

$sql .= ' ORDER BY jahrgang ASC, fach ASC, reihenfolge ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$treffer = $stmt->fetchAll(PDO::FETCH_ASSOC);

$feldnamenLesbar = [
    'sprachbildung'        => 'Sprachbildung',
    'medienbildung'        => 'Medienbildung',
    'methoden'             => 'Methoden und Arbeitstechniken',
    'kompetenzen'          => 'Kompetenzen und Konkretisierung',
    'kooperationen'        => 'Kooperationen / Lernorte',
    'übergreifende_themen' => 'Übergreifende Themen',
    'leistungsbewertung'   => 'Lernberatung & Leistungsbewertung',
    'fächerverbindung'     => 'Fächerverbindung',
    'heterogenität'        => 'Heterogenität / Inklusion',
    'schulprofil'          => 'Schulprofil / Schwerpunktsetzung',
    'lebensweltbezug'      => 'Lebensweltbezug',
];

if (!$treffer) {
    echo '<div class="alert alert-warning">Keine passenden Einträge gefunden.</div>';
    exit;
}

$zeigeBearbeiter = false;
foreach ($treffer as $t) { if (!empty($t['bearbeitet_von'])) { $zeigeBearbeiter = true; break; } }
?>
<div class="text-muted mb-2" style="font-size:.85rem;"><?= count($treffer) ?> Treffer</div>
<table class="table-app">
    <thead>
        <tr>
            <th>Jg.</th>
            <th>Fach</th>
            <th>Thema</th>
            <th><?= htmlspecialchars($feldnamenLesbar[$feld] ?? $feld) ?></th>
            <th>Status</th>
            <th>Version</th>
            <?php if ($zeigeBearbeiter): ?><th>Bearbeitet von</th><?php endif; ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($treffer as $eintrag): ?>
            <tr>
                <td><?= htmlspecialchars($eintrag['jahrgang']) ?></td>
                <td><?= htmlspecialchars($eintrag['fach']) ?></td>
                <td><?= htmlspecialchars($eintrag['thema']) ?></td>
                <td class="cell-snippet"><?= nl2br(htmlspecialchars($eintrag[$feld])) ?></td>
                <td><?= schics_status_badge($eintrag['status']) ?></td>
                <td><?= htmlspecialchars($eintrag['version']) ?></td>
                <?php if ($zeigeBearbeiter): ?>
                    <td class="text-muted"><?= htmlspecialchars($eintrag['bearbeitet_von']) ?></td>
                <?php endif; ?>
                <td><a href="detail.php?schic_id=<?= (int)$eintrag['schic_id'] ?>" class="btn btn-sm btn-outline-primary">Details</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
