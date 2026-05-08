<?php
/**
 * Curriculum-Sheet als HTML-Fragment für die mPDF-Ausgabe.
 *
 * mPDF unterstützt CSS Grid nicht zuverlässig, deshalb bilden wir das
 * 4×4-Raster der Vorlage hier mit einer Tabelle nach (rowspan/colspan).
 * Die Zellbelegung ist dieselbe wie in _curriculum_view.php — Quelle
 * der Wahrheit bleibt schics_curriculum_cells() in helpers.php.
 *
 * Erwartet: $values (assoc-Array mit allen DB-Spalten), optional
 * $pdfShowFooter (bool, default true). Wird vom Aufrufer (pdf.php) inkludiert.
 */
require_once __DIR__ . '/helpers.php';

$cells = [];
foreach (schics_curriculum_cells() as [$pos, $col, $title, $field]) {
    $cells[$pos] = [
        'col'   => $col,
        'title' => $title,
        'value' => (string)($values[$field] ?? ''),
    ];
}

$cellHtml = function (string $pos) use ($cells): string {
    $c = $cells[$pos];
    $body  = nl2br(htmlspecialchars($c['value']));
    $title = htmlspecialchars($c['title']);
    $tag   = strtoupper($c['col']);
    return
        '<div class="cell cell--' . $c['col'] . '">'
        . '<div class="cell-title"><span class="cell-tag">[' . $tag . ']</span> ' . $title . '</div>'
        . '<div class="cell-body">' . $body . '</div>'
        . '</div>';
};
?>
<table class="head" cellspacing="0" cellpadding="0">
    <tr>
        <td class="head-cell"><span class="head-label">Fach:</span> <?= htmlspecialchars((string)($values['fach'] ?? '')) ?></td>
        <td class="head-cell"><span class="head-label">Jahrgang:</span> <?= htmlspecialchars((string)($values['jahrgang'] ?? '')) ?></td>
        <td class="head-cell head-cell-thema" colspan="2"><span class="head-label">Thema:</span> <?= htmlspecialchars((string)($values['thema'] ?? '')) ?></td>
        <td class="head-cell"><span class="head-label">Umfang:</span> <?= htmlspecialchars((string)($values['umfang'] ?? '')) ?></td>
        <td class="head-cell"><span class="head-label">Stand:</span> <?= htmlspecialchars((string)($values['stand'] ?? '')) ?></td>
    </tr>
</table>
<table class="grid" cellspacing="0" cellpadding="0">
    <tr>
        <td class="g g-fachv"><?= $cellHtml('fachv') ?></td>
        <td class="g g-hetero"><?= $cellHtml('hetero') ?></td>
        <td class="g g-schulp"><?= $cellHtml('schulp') ?></td>
        <td class="g g-sprach" rowspan="2"><?= $cellHtml('sprach') ?></td>
    </tr>
    <tr>
        <td class="g g-leben"><?= $cellHtml('leben') ?></td>
        <td class="g g-kompet" colspan="2" rowspan="2"><?= $cellHtml('kompet') ?></td>
    </tr>
    <tr>
        <td class="g g-kooper"><?= $cellHtml('kooper') ?></td>
        <td class="g g-uebergr" rowspan="2"><?= $cellHtml('uebergr') ?></td>
    </tr>
    <tr>
        <td class="g g-lernber"><?= $cellHtml('lernber') ?></td>
        <td class="g g-medien"><?= $cellHtml('medien') ?></td>
        <td class="g g-methoden"><?= $cellHtml('methoden') ?></td>
    </tr>
</table>
