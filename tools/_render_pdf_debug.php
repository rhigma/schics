<?php
/**
 * Lokaler CLI-Helfer zum Inspizieren der PDF-Ausgabe ohne Webserver/Auth.
 * Rendert die SchiC mit schic_id=1 nach data/_debug_render.pdf, sodass man
 * Layout-Änderungen an pdf.php / _curriculum_pdf.php direkt anschauen kann.
 *
 * Verwendung:  php tools/_render_pdf_debug.php
 *
 * Falls jemand das Debug-Raster wieder braucht (10-mm-Lineal + Cell-Marker),
 * setze $_GET['debug'] = '1' und füge den Renderblock am Ende von
 * _curriculum_pdf.php wieder ein — siehe Git-History für die ursprüngliche
 * Variante.
 */
$_GET['schic_id'] = 1;

// Auth umgehen: SESSION-Variablen vorab setzen, sodass schics_require_level()
// durchgeht. SCHICS_LEVEL_ADMIN ist 3, wir setzen großzügiger.
require_once __DIR__ . '/../db.php';
$pdo = schics_db();
session_id('cli-debug');
@session_start();
$_SESSION['schics_level'] = 99;

// pdf.php gibt das PDF direkt nach stdout aus — wir capturen es.
ob_start();
require __DIR__ . '/../pdf.php';
$out = ob_get_clean();

$dst = __DIR__ . '/../data/_debug_render.pdf';
file_put_contents($dst, $out);
echo "PDF saved: $dst (" . strlen($out) . " bytes)\n";
