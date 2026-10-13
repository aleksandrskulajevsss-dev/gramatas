
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Atļauj tikai POST pieprasījumus
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Neatļauta pieprasījuma metode.');
}

// Pārbauda CSRF tokenu
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Nederīgs drošības tokens.');
}

// Pārbauda pieteikuma ID un piezīmes tekstu
$applicationId = filter_input(
    INPUT_POST,
    'application_id',
    FILTER_VALIDATE_INT
);

$note = trim($_POST['note'] ?? '');

if (!$applicationId || $applicationId < 1) {
    http_response_code(400);
    exit('Nederīgs pieteikuma ID.');
}

if ($note === '' || mb_strlen($note, 'UTF-8') > 5000) {
    http_response_code(400);
    exit('Piezīmei jābūt no 1 līdz 5000 rakstzīmēm.');
}

// Pārbauda, vai pieteikums eksistē
$check = $pdo->prepare(
    'SELECT id FROM applications WHERE id = ? LIMIT 1'
);
$check->execute([$applicationId]);

if (!$check->fetch()) {
    http_response_code(404);
    exit('Pieteikums nav atrasts.');
}

// Saglabā piezīmi datubāzē
$stmt = $pdo->prepare("
    INSERT INTO notes (application_id, user_id, note)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $applicationId,
    $_SESSION['user_id'],
    $note
]);

header('Location: pieteikums.php?id=' . $applicationId . '&note_saved=1');
exit;
