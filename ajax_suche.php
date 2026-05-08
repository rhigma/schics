<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo    = schics_db();
$where  = [];
$params = [];

if (!empty($_POST['fach'])) {
    $where[] = 'fach = :fach';
    $params[':fach'] = $_POST['fach'];
}

if (!empty($_POST['jahrgang'])) {
    $jahrgangInput = trim($_POST['jahrgang']);

    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $jahrgangInput, $matches)) {
        $where[] = 'jahrgang BETWEEN :jg_start AND :jg_end';
        $params[':jg_start'] = (int)$matches[1];
        $params[':jg_end']   = (int)$matches[2];

    } elseif (preg_match('/^(\d+(,\s*\d+)+)$/', $jahrgangInput)) {
        $jahrgangArray = array_map('intval', explode(',', $jahrgangInput));
        $placeholders  = [];
        foreach ($jahrgangArray as $i => $value) {
            $key = ":jg_in_$i";
            $placeholders[] = $key;
            $params[$key] = $value;
        }
        $where[] = 'jahrgang IN (' . implode(',', $placeholders) . ')';

    } elseif (preg_match('/^(<=?|>=?)\s*(\d+)$/', $jahrgangInput, $matches)) {
        $operator = $matches[1];
        $where[]  = "jahrgang $operator :jahrgang_cmp";
        $params[':jahrgang_cmp'] = (int)$matches[2];

    } elseif (is_numeric($jahrgangInput)) {
        $where[] = 'jahrgang = :jahrgang';
        $params[':jahrgang'] = (int)$jahrgangInput;
    }
}

$suchbegriff = trim($_POST['suchbegriff'] ?? '');
if ($suchbegriff !== '') {
    $felder = array_keys(schics_search_fields());
    $teile  = array_map(fn($f) => schics_quote_ident($f) . ' LIKE :such', $felder);
    $where[] = '(' . implode(' OR ', $teile) . ')';
    $params[':such'] = '%' . $suchbegriff . '%';
}

$sql = "
    SELECT c1.*
    FROM curricula c1
    INNER JOIN (
        SELECT schic_id, MAX(id) AS max_id
        FROM curricula
        GROUP BY schic_id
    ) c2 ON c1.id = c2.max_id
";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY jahrgang ASC, fach ASC, reihenfolge ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$einträge = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$einträge) {
    echo '<div class="alert alert-warning">Keine passenden Einträge gefunden.</div>';
    exit;
}
?>
<?php
$searchFields = schics_search_fields();
$snippetFor = function (array $eintrag) use ($suchbegriff, $searchFields): string {
    if ($suchbegriff === '') return '';
    foreach ($searchFields as $col => $label) {
        $snip = schics_snippet((string)($eintrag[$col] ?? ''), $suchbegriff);
        if ($snip !== null) {
            return '<span class="snippet-field">' . htmlspecialchars($label) . ':</span> ' . $snip;
        }
    }
    return '';
};
?>
<div class="text-muted mb-2" style="font-size:.85rem;"><?= count($einträge) ?> Treffer</div>
<table class="table-app">
    <thead>
        <tr>
            <th>Fach</th>
            <th>Jg.</th>
            <th>Thema</th>
            <th><?= $suchbegriff !== '' ? 'Treffer' : 'Kompetenzen' ?></th>
            <th>Status</th>
            <th>Version</th>
            <th>Stand</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($einträge as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['fach']) ?></td>
                <td><?= htmlspecialchars($e['jahrgang']) ?></td>
                <td>
                    <a href="detail.php?schic_id=<?= (int)$e['schic_id'] ?>"><?= htmlspecialchars($e['thema']) ?></a>
                </td>
                <td class="cell-snippet">
                    <?php if ($suchbegriff !== ''): ?>
                        <?= $snippetFor($e) ?>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($e['kompetenzen'])) ?>
                    <?php endif; ?>
                </td>
                <td><?= schics_status_badge($e['status']) ?></td>
                <td><?= htmlspecialchars($e['version']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($e['stand']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
