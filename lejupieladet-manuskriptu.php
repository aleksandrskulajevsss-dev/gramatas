
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Nederīgs pieteikuma ID.');
}

// Atrod pieteikuma manuskriptu
$stmt = $pdo->prepare("
    SELECT manuscript_path, book_title
    FROM applications
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$application = $stmt->fetch();

if (!$application || !$application['manuscript_path']) {
    http_response_code(404);
    exit('Manuskripts nav atrasts.');
}

// Drošības nolūkos izmanto tikai faila nosaukumu
$fileName = basename($application['manuscript_path']);

$directory = realpath(__DIR__ . '/uploads/manuscripts');

if ($directory === false) {
    http_response_code(404);
    exit('Manuskriptu mape nav atrasta.');
}

$filePath = realpath($directory . DIRECTORY_SEPARATOR . $fileName);

// Pārbauda, vai fails atrodas pareizajā mapē
if (
    $filePath === false ||
    dirname($filePath) !== $directory ||
    !is_file($filePath) ||
    !is_readable($filePath)
) {
    http_response_code(404);
    exit('Manuskripta fails nav atrasts.');
}

$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$mimeTypes = [
    'pdf' => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if (!isset($mimeTypes[$extension])) {
    http_response_code(403);
    exit('Neatļauts faila formāts.');
}

// Drošs lejupielādes faila nosaukums
$downloadName = 'manuskripts-' . $id . '.' . $extension;

// Nosūta failu lejupielādei
header('Content-Type: ' . $mimeTypes[$extension]);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($filePath);
exit;
