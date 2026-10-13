
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Neatļauta pieprasījuma metode.');
}

// CSRF pārbaude
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Nederīgs drošības tokens.');
}

$applicationId = filter_input(
    INPUT_POST,
    'application_id',
    FILTER_VALIDATE_INT
);

if (!$applicationId || $applicationId < 1) {
    http_response_code(400);
    exit('Nederīgs pieteikuma ID.');
}

// Pārbauda, vai pieteikums eksistē
$stmt = $pdo->prepare("
    SELECT id, manuscript_path
    FROM applications
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$applicationId]);
$application = $stmt->fetch();

if (!$application) {
    http_response_code(404);
    exit('Pieteikums nav atrasts.');
}

// Neļauj nejauši pārrakstīt esošu manuskriptu
if ($application['manuscript_path']) {
    http_response_code(409);
    exit('Šim pieteikumam manuskripts jau ir pievienots.');
}

// Pārbauda augšupielādēto failu
if (
    !isset($_FILES['manuscript']) ||
    !is_array($_FILES['manuscript']) ||
    $_FILES['manuscript']['error'] !== UPLOAD_ERR_OK
) {
    http_response_code(400);
    exit('Neizdevās augšupielādēt failu.');
}

$file = $_FILES['manuscript'];

// Maksimālais izmērs: 10 MB
$maxSize = 10 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    http_response_code(400);
    exit('Faila izmērs nedrīkst pārsniegt 10 MB.');
}

// Atļautie failu formāti
$allowedTypes = [
    'application/pdf' => 'pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$extension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);

if (
    !isset($allowedTypes[$mimeType]) ||
    $allowedTypes[$mimeType] !== $extension
) {
    http_response_code(400);
    exit('Atļauti tikai PDF un DOCX faili.');
}

// Izveido nejaušu, drošu faila nosaukumu
$fileName = bin2hex(random_bytes(16)) . '.' . $extension;

$uploadDirectory = __DIR__ . '/uploads/manuscripts/';
$destination = $uploadDirectory . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    exit('Neizdevās saglabāt manuskriptu.');
}

// Datubāzē saglabā tikai relatīvo ceļu
$relativePath = 'uploads/manuscripts/' . $fileName;

try {
    $update = $pdo->prepare("
        UPDATE applications
        SET manuscript_path = ?
        WHERE id = ?
          AND manuscript_path IS NULL
    ");

    $update->execute([$relativePath, $applicationId]);

    if ($update->rowCount() !== 1) {
        unlink($destination);
        http_response_code(409);
        exit('Manuskripts šim pieteikumam jau ir pievienots.');
    }
} catch (Throwable $e) {
    unlink($destination);
    error_log($e->getMessage());
    http_response_code(500);
    exit('Neizdevās saglabāt manuskripta informāciju.');
}

header(
    'Location: pieteikums.php?id=' .
    $applicationId .
    '&manuscript_saved=1'
);
exit;
