<?php
/**
 * Curriculum-Sheet als HTML-Fragment für die mPDF-Ausgabe.
 *
 * mPDF unterstützt CSS Grid nicht zuverlässig, deshalb bilden wir das
 * 4×4-Raster der Vorlage hier mit einer Tabelle nach (rowspan/colspan).
 * Die Zellbelegung ist dieselbe wie in _curriculum_view.php — Quelle
 * der Wahrheit bleibt schics_curriculum_cells() in helpers.php.
 *
 * Erwartet: $values (assoc-Array mit allen DB-Spalten). Wird vom Aufrufer
 * (pdf.php) inkludiert.
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

// Eck-Buchstaben werden über eine 2-zeilige Sub-Tabelle in der TD platziert:
// obere Zeile (valign=top) trägt Titel + Body, untere Zeile (valign=bottom,
// align=right) trägt den Tag. mPDF respektiert weder height:100% auf
// verschachtelten Tabellen noch position:absolute innerhalb von TDs zuver-
// lässig — der Trick ist die explizite height auf .cf-content im CSS, die
// die obere Reihe so weit hochzieht, dass die Tag-Reihe in den Restplatz
// unten fällt.
$cellHtml = function (string $pos) use ($cells): string {
    $c = $cells[$pos];
    $body  = nl2br(htmlspecialchars($c['value']));
    $title = htmlspecialchars($c['title']);
    $tag   = strtoupper($c['col']);
    return
        '<table class="cf" width="100%" height="100%"><tr height="100%">'
        . '<td class="cf-content" valign="top">'
        . '<div class="cell-title">' . $title . '</div>'
        . '<div class="cell-body">' . $body . '</div>'
        . '</td></tr><tr>'
        . '<td class="cf-tag" align="right" valign="bottom">' . $tag . '</td>'
        . '</tr></table>';
};

// Liefert die Klassen für ein <td>: Basis "g", optional "g-tall2", farb-
// codierte Variante "g--a/b/c".
$cellClass = function (string $pos, string $extra = '') use ($cells): string {
    $c = $cells[$pos];
    return trim('g g--' . $c['col'] . ($extra !== '' ? ' ' . $extra : ''));
};
?>
<table class="head" cellspacing="0" cellpadding="0">
    <tr>
        <td class="head-cell" width="22%"><span class="head-label">Fach:</span> <?= htmlspecialchars((string)($values['fach'] ?? '')) ?></td>
        <td class="head-cell" width="13%"><span class="head-label">Jahrgang:</span> <?= htmlspecialchars((string)($values['jahrgang'] ?? '')) ?></td>
        <td class="head-cell" width="35%"><span class="head-label">Thema:</span> <?= htmlspecialchars((string)($values['thema'] ?? '')) ?></td>
        <td class="head-cell" width="15%"><span class="head-label">Umfang:</span> <?= htmlspecialchars((string)($values['umfang'] ?? '')) ?></td>
        <td class="head-cell head-cell-last" width="15%"><span class="head-label">Stand:</span> <?= htmlspecialchars((string)($values['stand'] ?? '')) ?></td>
    </tr>
</table>
<table class="grid" cellspacing="0" cellpadding="0">
    <tr>
        <td class="<?= $cellClass('fachv') ?>"  width="25%"><?= $cellHtml('fachv') ?></td>
        <td class="<?= $cellClass('hetero') ?>" width="25%"><?= $cellHtml('hetero') ?></td>
        <td class="<?= $cellClass('schulp') ?>" width="25%"><?= $cellHtml('schulp') ?></td>
        <td class="<?= $cellClass('sprach', 'g-tall2') ?>" width="25%" rowspan="2"><?= $cellHtml('sprach') ?></td>
    </tr>
    <tr>
        <td class="<?= $cellClass('leben') ?>"><?= $cellHtml('leben') ?></td>
        <td class="<?= $cellClass('kompet', 'g-tall2') ?>" colspan="2" rowspan="2"><?= $cellHtml('kompet') ?></td>
    </tr>
    <tr>
        <td class="<?= $cellClass('kooper') ?>"><?= $cellHtml('kooper') ?></td>
        <td class="<?= $cellClass('uebergr', 'g-tall2') ?>" rowspan="2"><?= $cellHtml('uebergr') ?></td>
    </tr>
    <tr>
        <td class="<?= $cellClass('lernber') ?>"><?= $cellHtml('lernber') ?></td>
        <td class="<?= $cellClass('medien') ?>"><?= $cellHtml('medien') ?></td>
        <td class="<?= $cellClass('methoden') ?>"><?= $cellHtml('methoden') ?></td>
    </tr>
</table>
