<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo    = schics_db();
$where  = [];
$params = [];

if (!empty($_GET['fach'])) {
    $where[] = 'fach = :fach';
    $params[':fach'] = $_GET['fach'];
}

if (!empty($_GET['jahrgang'])) {
    $jg = trim($_GET['jahrgang']);
    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $jg, $m)) {
        $where[] = 'jahrgang BETWEEN :jg_start AND :jg_end';
        $params[':jg_start'] = (int)$m[1];
        $params[':jg_end']   = (int)$m[2];
    } elseif (preg_match('/^(\d+(,\s*\d+)+)$/', $jg)) {
        $arr = array_map('intval', explode(',', $jg));
        $ph = [];
        foreach ($arr as $i => $v) {
            $key = ":jg_in_$i";
            $ph[] = $key;
            $params[$key] = $v;
        }
        $where[] = 'jahrgang IN (' . implode(',', $ph) . ')';
    } elseif (preg_match('/^(<=?|>=?)\s*(\d+)$/', $jg, $m)) {
        $where[] = "jahrgang $m[1] :jahrgang_cmp";
        $params[':jahrgang_cmp'] = (int)$m[2];
    } elseif (is_numeric($jg)) {
        $where[] = 'jahrgang = :jahrgang';
        $params[':jahrgang'] = (int)$jg;
    }
}

if (!empty($_GET['suchbegriff'])) {
    $felder = [
        'thema', 'kompetenzen', 'medienbildung', 'sprachbildung',
        'übergreifende_themen', 'fächerverbindung', 'heterogenität',
        'schulprofil', 'lebensweltbezug', 'kooperationen',
        'leistungsbewertung', 'methoden',
    ];
    $teile = array_map(fn($f) => schics_quote_ident($f) . ' LIKE :such', $felder);
    $where[] = '(' . implode(' OR ', $teile) . ')';
    $params[':such'] = '%' . $_GET['suchbegriff'] . '%';
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

$schoolName = schics_school_name();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drucken &ndash; <?= htmlspecialchars($schoolName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="druck">
    <?php
        $pdfQuery = $_GET;
        unset($pdfQuery['dl']);
        $pdfHref  = 'pdf.php?' . http_build_query($pdfQuery + ['dl' => 1]);
    ?>
    <div class="druck-toolbar">
        <a href="<?= htmlspecialchars($pdfHref) ?>" class="btn btn-primary">📥 PDF herunterladen</a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary">🖨️ Drucken</button>
        <a href="javascript:history.back()" class="btn btn-secondary">Zurück</a>
        <span class="text-muted"><?= count($einträge) ?> Einträge</span>
    </div>

    <main class="druck-main">
        <?php if (!$einträge): ?>
            <div class="alert alert-warning">Keine passenden Einträge gefunden.</div>
        <?php endif; ?>

        <?php foreach ($einträge as $values): ?>
            <article class="druck-page">
                <header class="print-header">
                    <?= htmlspecialchars($schoolName) ?> &ndash; Schulinternes Curriculum
                </header>
                <?php include __DIR__ . '/_curriculum_view.php'; ?>
                <footer class="print-footer">
                    Version <?= htmlspecialchars($values['version']) ?>
                    &middot; Status <?= htmlspecialchars($values['status']) ?>
                    <?php if (!empty($values['bearbeitet_von'])): ?>
                        &middot; Bearbeitet von <?= htmlspecialchars($values['bearbeitet_von']) ?>
                    <?php endif; ?>
                </footer>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>
