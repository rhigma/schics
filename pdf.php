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
    // reihenfolge zweistellig mit führender Null, damit der Datei-Explorer
    // die SchiCs eines Faches/Jahrgangs in der richtigen Reihenfolge anzeigt.
    $filename = sprintf(
        'SchiC_%s_Jg%s_%02d_%s.pdf',
        $row['fach'] ?: 'Fach',
        $row['jahrgang'] ?: '',
        (int)($row['reihenfolge'] ?? 0),
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

// Berlin Type als Hausschrift einbinden — die TTF-Dateien liegen im
// projektgepflegten assets/fonts/, damit composer update sie nicht plättet.
$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$defaultConfigVars = (new \Mpdf\Config\ConfigVariables())->getDefaults();

// Top/Bottom-Margins lassen Platz für SetHTMLHeader/Footer (5 mm Abstand vom
// Seitenrand). Damit sitzt der Footer (Version · Status · Bearbeitet von)
// immer am unteren Rand, egal wie voll die Zellen sind.
$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4-L',
    'margin_left'   => 10,
    'margin_right'  => 10,
    'margin_top'    => 14,
    'margin_bottom' => 12,
    'margin_header' => 5,
    'margin_footer' => 5,
    'tempDir'       => $tmpDir,
    'fontDir'       => array_merge(
        $defaultConfigVars['fontDir'],
        [__DIR__ . '/assets/fonts']
    ),
    'fontdata'      => $defaultFontConfig['fontdata'] + [
        'berlintype' => [
            'R' => 'BerlinTypeOffice-Regular.ttf',
            'B' => 'BerlinTypeOffice-Bold.ttf',
        ],
    ],
    'default_font'  => 'berlintype',
]);
$mpdf->SetTitle($filename);
$mpdf->SetCreator('SchiCs');

// Stylesheet einmal global laden ---------------------------------------------
// Spaltenbreiten: 4 gleiche Spalten à 25 %. Der Header darüber spannt sich
// über dieselben 4 Spaltengrenzen — Fach=Spalte 1, Jahrgang=Spalte 2,
// Thema=Spalten 3+4 weniger Umfang/Stand am Rand. Das "Thema"-Feld nimmt
// den dicken Mittelblock ein.
// Höhen, Schriftgrößen und Padding sind so abgestimmt, dass Header-Zeile,
// Body-Grid und Footer auch bei dichtem Inhalt (volle Kompetenzliste mit
// vielen Stichpunkten) auf eine A4-Querseite passen. Bei wenig Inhalt
// füllen die Mindesthöhen die Zellen aus, sodass die Seite nicht halbleer
// wirkt.
// Farbpalette wie auf der Webseite (--rlp-a/b/c). Die Eck-Buchstaben
// werden in einem aufgehellten Ton ausgegeben statt mit opacity: mPDF
// rendert opacity auf normalem Text nicht zuverlässig, ein vorgemischter
// Farbwert sieht dagegen identisch aus.
$css = <<<CSS
body { font-family: berlintype, sans-serif; font-size: 8.5pt; color: #1f2937; }
.page-header {
    font-size: 10pt; color: #555; text-align: center;
    padding-bottom: 2pt; border-bottom: 0.4pt solid #ccc;
}
.page-footer {
    font-size: 8pt; color: #555; text-align: right;
    padding-top: 2pt; border-top: 0.4pt solid #ccc;
}
table.head, table.grid {
    width: 100%; table-layout: fixed;
    border-collapse: separate;
}
/* Header-Zeile lehnt sich an die Word-Vorlage an: kräftige rote Außenkante,
   feinere rote Trennlinien zwischen den Feldern, Labels schwarz und in
   normaler Schreibweise (nicht uppercase). */
table.head {
    margin: 0; border-spacing: 0;
    border: 1.8pt solid #c1121f;
}
table.head td { padding: 0; }
.head-cell {
    padding: 9pt 9pt; font-size: 9pt;
    background: #ffffff; vertical-align: middle; line-height: 1.3;
    border-right: 0.8pt solid #c1121f;
}
.head-cell-last { border-right: 0; }
.head-label {
    font-weight: bold; color: #1f2937;
    font-size: 9pt; margin-right: 4pt;
}
table.grid { margin-top: 4pt; border-spacing: 4pt; page-break-inside: avoid; }
/* Zellhöhe wird über die innere Sub-Tabelle (.cf) verteilt: obere Zeile mit
   Titel + Body, untere Zeile (cf-tag) mit dem Eck-Buchstaben. Mit
   width/height:100% spannt sich .cf über die volle Zellfläche, sodass der
   Tag immer am tatsächlichen Cell-Bottom landet — auch wenn mPDF die
   Reihen unterschiedlich hoch rendert. */
table.grid td.g {
    border: 1.5pt solid #d1d5db;
    padding: 0; vertical-align: top;
    height: 4.15cm; line-height: 1.3;
}
table.grid td.g-tall2 { height: 8.3cm; } /* rowspan=2 — sprach, uebergr, kompet */
table.cf {
    width: 100%; height: 100%;
    border-collapse: collapse; border-spacing: 0;
}
/* Explizite Höhe auf der Content-Row erzwingt, dass die zweite Row (Tag)
   in den Restplatz unten fällt. mPDF respektiert height auf TDs zuverlässiger
   als height:100% auf einer verschachtelten Tabelle. Werte sind empirisch:
   knapp unter der gerenderten Zellhöhe (~35 mm normal, ~75 mm tall),
   sodass der Tag möglichst nah an die untere Zellkante rutscht. */
table.cf td.cf-content {
    padding: 5pt 8pt 0pt 8pt; vertical-align: top;
    height: 32mm;
}
.g-tall2 table.cf td.cf-content { height: 75mm; }
table.cf td.cf-tag {
    padding: 0pt 8pt 2pt 8pt; vertical-align: bottom;
    font-size: 18pt; font-weight: bold; line-height: 1;
}
.cell-title {
    font-size: 8.5pt; font-weight: bold; color: #1f2937;
    margin: 0 0 4pt; line-height: 1.25;
}
.cell-body { font-size: 8pt; line-height: 1.4; color: #374151; }
/* Eck-Buchstaben in vorgemischter heller Farbe (opacity rendert mPDF auf Text
   nicht zuverlässig). */
.g--a { border-color: #2563eb !important; background: #eff6ff; }
.g--a .cf-tag { color: #9db9f6; }
.g--b { border-color: #16a34a !important; background: #f0fdf4; }
.g--b .cf-tag { color: #96d6ae; }
.g--c { border-color: #d97706 !important; background: #fffbeb; }
.g--c .cf-tag { color: #eec28f; }
CSS;
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

// Header (Schulname) ist auf jeder Seite gleich — einmal setzen reicht.
// Footer (Version · Status · Bearbeitet von) variiert pro SchiC, deshalb
// im Loop neu setzen. SetHTMLFooter wirkt auf die jeweils nächste Seite,
// also vor AddPage aufrufen, damit die richtige Version unten steht.
$schoolName = schics_school_name();
$mpdf->SetHTMLHeader(
    '<div class="page-header">' . htmlspecialchars($schoolName) . ' &ndash; Schulinternes Curriculum</div>'
);

foreach ($einträge as $i => $values) {
    $footerParts = [
        'Version ' . htmlspecialchars($values['version']),
        'Status ' . htmlspecialchars($values['status']),
    ];
    if (!empty($values['bearbeitet_von'])) {
        $footerParts[] = 'Bearbeitet von ' . htmlspecialchars($values['bearbeitet_von']);
    }
    $mpdf->SetHTMLFooter(
        '<div class="page-footer">' . implode(' &middot; ', $footerParts) . '</div>'
    );

    if ($i > 0) {
        $mpdf->AddPage();
    }
    ob_start();
    include __DIR__ . '/_curriculum_pdf.php';
    $mpdf->WriteHTML(ob_get_clean(), \Mpdf\HTMLParserMode::HTML_BODY);
}

// Browser-Vorschau (inline) als Default; Download via &dl=1 erzwingbar.
$dispo = !empty($_GET['dl']) ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
$mpdf->Output($filename, $dispo);
