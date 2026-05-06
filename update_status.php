<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Nur POST.';
    exit;
}

$id     = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
$status = (string)($_POST['status'] ?? '');
$back   = (string)($_POST['back'] ?? 'index.php');

$allowed = ['Entwurf', 'Beschlossen'];
if ($id === false || $id <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo 'Ungültige Eingabe.';
    exit;
}

// back-Pfad nur als relative URL erlauben.
if (!preg_match('~^[A-Za-z0-9_./?=&%-]+$~', $back) || str_starts_with($back, '//')) {
    $back = 'index.php';
}

$pdo  = schics_db();
$stmt = $pdo->prepare('UPDATE curricula SET status = :status WHERE id = :id');
$stmt->execute([':status' => $status, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    schics_flash('Eintrag nicht gefunden — Status nicht geändert.');
} else {
    schics_flash('Status auf „' . $status . '" gesetzt.');
}

header('Location: ' . $back);
