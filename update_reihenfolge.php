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

// Eingabe streng validieren: jedes Element braucht eine ganzzahlige id
// und eine ganzzahlige reihenfolge.
$updates = [];
foreach ($data as $item) {
    if (!is_array($item) || !isset($item['id'], $item['reihenfolge'])) {
        http_response_code(400);
        echo 'Ungültiges Element.';
        exit;
    }
    $id  = filter_var($item['id'], FILTER_VALIDATE_INT);
    $ord = filter_var($item['reihenfolge'], FILTER_VALIDATE_INT);
    if ($id === false || $ord === false) {
        http_response_code(400);
        echo 'IDs und Reihenfolge müssen ganze Zahlen sein.';
        exit;
    }
    $updates[] = ['id' => $id, 'reihenfolge' => $ord];
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE curricula SET reihenfolge = :reihenfolge WHERE id = :id');
    foreach ($updates as $u) {
        $stmt->execute([':id' => $u['id'], ':reihenfolge' => $u['reihenfolge']]);
    }
    $pdo->commit();
    echo 'OK';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo 'Fehler beim Speichern.';
}
