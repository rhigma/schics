<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_EDIT);

$pdo  = schics_db();
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!is_array($data) || empty($data)) {
    http_response_code(400);
    echo 'Keine gültigen Daten erhalten.';
    exit;
}

// Eingabe streng validieren: jedes Element braucht eine ganzzahlige
// schic_id und eine ganzzahlige reihenfolge. Der Sortierrang gilt für alle
// Versionen eines SchiCs gemeinsam, daher wird per schic_id aktualisiert.
$updates = [];
foreach ($data as $item) {
    if (!is_array($item) || !isset($item['schic_id'], $item['reihenfolge'])) {
        http_response_code(400);
        echo 'Ungültiges Element.';
        exit;
    }
    $schicId = filter_var($item['schic_id'], FILTER_VALIDATE_INT);
    $ord     = filter_var($item['reihenfolge'], FILTER_VALIDATE_INT);
    if ($schicId === false || $ord === false) {
        http_response_code(400);
        echo 'SchiC-IDs und Reihenfolge müssen ganze Zahlen sein.';
        exit;
    }
    $updates[] = ['schic_id' => $schicId, 'reihenfolge' => $ord];
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE curricula SET reihenfolge = :reihenfolge WHERE schic_id = :schic_id');
    foreach ($updates as $u) {
        $stmt->execute([':schic_id' => $u['schic_id'], ':reihenfolge' => $u['reihenfolge']]);
    }
    $pdo->commit();
    echo 'OK';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo 'Fehler beim Speichern.';
}
