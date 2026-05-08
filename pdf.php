<?php
/**
 * PDF-Export der SchiCs.
 *
 * Aufrufmuster:
 *   pdf.php?schic_id=42                        → eine SchiC (neueste Version), eine A4-Querseite
 *   pdf.php?fach=Deutsch&jahrgang=1-4&...      → Sammel-PDF mit denselben Filtern wie druck.php
 *
 * Die Filter-Grammatik (1-4, 5,6, >=3, <=6, 4) ist absichtlich identisch zu
 * druck.php / ajax_suche.php. Wenn die Grammatik geändert wird, alle drei Stellen anpassen.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

schics_require_level(SCHICS_LEVEL_READ);

$pdo = schics_db();

// Modus 1: einzelne SchiC ----------------------------------------------------
$singleSchicId = isset($_GET['schic_id']) ? (int)$_GET['schic_id'] : 0;

if ($singleSchicId > 0) {
    $stmt = $pdo->prepare(
        'SELECT * FROM curricula WHERE schic_id = :id ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([':id' => $singleSchicId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo 'SchiC nicht gefunden.';
        exit;
    }
    $einträge = [$row];
    $filename = sprintf(
        'SchiC_%s_Jg%s_%s.pdf',
        $row['fach'] ?: 'Fach',
        $row['jahrgang'] ?: '',
        $row['thema']    ?: 'ohne-Titel'
    );
} else {
    // Modus 2: gefilterte Sammel-Ausgabe -------------------------------------
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
        $felder = array_keys(schics_search_fields());
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

    if (!$einträge) {
        http_response_code(404);
        echo 'Keine passenden Einträge gefunden.';
        exit;
    }

    $filename = 'SchiCs_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', schics_school_name())
              . '_' . date('Y-m-d') . '.pdf';
}

// mPDF aufsetzen -------------------------------------------------------------
$tmpDir = __DIR__ . '/data/mpdf_tmp';
if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0775, true);
}

$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4-L',
    'margin_left'   => 10,
    'margin_right'  => 10,
    'margin_top'    => 10,
    'margin_bottom' => 10,
    'tempDir'       => $tmpDir,
    'default_font'  => 'dejavusans',
]);
$mpdf->SetTitle($filename);
$mpdf->SetCreator('SchiCs');

// Stylesheet einmal global laden ---------------------------------------------
$css = <<<CSS
body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1f2937; }
.page-header {
    font-size: 10pt; color: #555; text-align: center;
    margin: 0 0 0.2cm; border-bottom: 0.5pt solid #ccc; padding-bottom: 2pt;
}
.page-footer {
    font-size: 8.5pt; color: #555; text-align: right;
    margin: 0.2cm 0 0; border-top: 0.5pt solid #ccc; padding-top: 2pt;
}
table.head, table.grid {
    width: 100%; border-collapse: collapse; table-layout: fixed;
}
table.head { margin-bottom: 4pt; }
.head-cell {
    border: 1pt solid #6b7280; padding: 4pt 6pt; font-size: 9pt;
    background: #f6f7f9; vertical-align: middle;
}
.head-label {
    font-weight: bold; color: #4b5563; text-transform: uppercase;
    font-size: 7.5pt; letter-spacing: 0.05em; margin-right: 4pt;
}
table.grid td.g {
    border: 0.5pt solid transparent; padding: 0; vertical-align: top;
    height: 4cm;
}
.cell {
    border: 1.2pt solid #d1d5db;
    padding: 5pt 8pt;
    height: 100%; overflow: hidden;
}
.cell-title {
    font-size: 8.5pt; font-weight: bold; color: #1f2937;
    margin-bottom: 3pt; line-height: 1.2;
}
.cell-body { font-size: 8.5pt; line-height: 1.3; color: #374151; }
.cell-tag {
    font-size: 8.5pt; font-weight: bold;
    margin-right: 2pt;
}
.cell--a            { border-color: #2563eb; background: #eff6ff; }
.cell--a .cell-tag  { color: #2563eb; }
.cell--b            { border-color: #16a34a; background: #f0fdf4; }
.cell--b .cell-tag  { color: #16a34a; }
.cell--c            { border-color: #d97706; background: #fffbeb; }
.cell--c .cell-tag  { color: #d97706; }
.g-kompet  { height: 8cm; }
.g-sprach  { height: 8cm; }
.g-uebergr { height: 8cm; }
CSS;
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

// Pro SchiC eine Seite — AddPage zwischen den Einträgen verhindert die
// Phantom-Leerseite, die ein nachgestelltes "page-break-after: always" liefert.
$schoolName = schics_school_name();
foreach ($einträge as $i => $values) {
    if ($i > 0) {
        $mpdf->AddPage();
    }
    ob_start();
    ?>
    <div class="page-header">
        <?= htmlspecialchars($schoolName) ?> &ndash; Schulinternes Curriculum
    </div>
    <?php include __DIR__ . '/_curriculum_pdf.php'; ?>
    <div class="page-footer">
        Version <?= htmlspecialchars($values['version']) ?>
        &middot; Status <?= htmlspecialchars($values['status']) ?>
        <?php if (!empty($values['bearbeitet_von'])): ?>
            &middot; Bearbeitet von <?= htmlspecialchars($values['bearbeitet_von']) ?>
        <?php endif; ?>
    </div>
    <?php
    $mpdf->WriteHTML(ob_get_clean(), \Mpdf\HTMLParserMode::HTML_BODY);
}

// Browser-Vorschau (inline) als Default; Download via &dl=1 erzwingbar.
$dispo = !empty($_GET['dl']) ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
$mpdf->Output($filename, $dispo);
