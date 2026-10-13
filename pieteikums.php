
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Nederīgs pieteikuma ID.');
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// CSRF aizsardzība
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Visi projekta statusi
$statuses = $pdo->query("
    SELECT id, name
    FROM statuses
    ORDER BY sort_order ASC
")->fetchAll();

// Statusa maiņas apstrāde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Nederīgs drošības tokens.');
    }

    $statusId = filter_input(
        INPUT_POST,
        'status_id',
        FILTER_VALIDATE_INT
    );

    $validStatusIds = array_map(
        'intval',
        array_column($statuses, 'id')
    );

    if (!$statusId || !in_array($statusId, $validStatusIds, true)) {
        $error = 'Lūdzu, izvēlieties derīgu statusu.';
    } else {
        $update = $pdo->prepare("
            UPDATE applications
            SET status_id = ?
            WHERE id = ?
        ");

        $update->execute([$statusId, $id]);

        header('Location: pieteikums.php?id=' . $id . '&saved=1');
        exit;
    }
}

// Pieteikuma informācija
$stmt = $pdo->prepare("
    SELECT
        applications.id,
        applications.book_title,
        applications.genre,
        applications.application_date,
        applications.manuscript_path,
        applications.status_id,
        authors.first_name,
        authors.last_name,
        authors.email,
        authors.phone,
        statuses.name AS status_name
    FROM applications
    INNER JOIN authors
        ON authors.id = applications.author_id
    INNER JOIN statuses
        ON statuses.id = applications.status_id
    WHERE applications.id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$application = $stmt->fetch();

if (!$application) {
    http_response_code(404);
    exit('Pieteikums nav atrasts.');
}

// Pieteikuma piezīmes
$notesStmt = $pdo->prepare("
    SELECT
        notes.id,
        notes.note,
        notes.created_at,
        users.username
    FROM notes
    INNER JOIN users
        ON users.id = notes.user_id
    WHERE notes.application_id = ?
    ORDER BY notes.created_at DESC, notes.id DESC
");

$notesStmt->execute([$id]);
$notes = $notesStmt->fetchAll();

$saved = ($_GET['saved'] ?? '') === '1';
$noteSaved = ($_GET['note_saved'] ?? '') === '1';
$manuscriptSaved = ($_GET['manuscript_saved'] ?? '') === '1';

?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($application['book_title']) ?> | Iedvesmas grāmata</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <p class="brand">IEDVESMAS GRĀMATA</p>

        <div class="header-actions">
            <span class="header-label">Administrācijas panelis</span>

            <a href="logout.php" class="logout-button">
                Izrakstīties
            </a>
        </div>
    </div>
</header>

<main class="container">

    <div class="page-heading">
        <a href="index.php" class="back-link">
            ← Atpakaļ uz projektiem
        </a>

        <p class="eyebrow">GRĀMATAS PIETEIKUMS</p>

        <h1><?= e($application['book_title']) ?></h1>

        <span class="status">
            <?= e($application['status_name']) ?>
        </span>
    </div>

    <?php if ($saved): ?>
        <div class="success-message">
            Projekta statuss veiksmīgi saglabāts!
        </div>
    <?php endif; ?>

    <?php if ($noteSaved): ?>
        <div class="success-message">
            Piezīme veiksmīgi pievienota!
        </div>
    <?php endif; ?>

    <?php if ($manuscriptSaved): ?>
        <div class="success-message">
            Manuskripts veiksmīgi pievienots!
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="login-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="details-grid">

        <section class="detail-card">
            <h2>Grāmatas informācija</h2>

            <div class="detail-row">
                <span>Nosaukums</span>
                <strong><?= e($application['book_title']) ?></strong>
            </div>

            <div class="detail-row">
                <span>Žanrs</span>
                <strong><?= e($application['genre']) ?></strong>
            </div>

            <div class="detail-row">
                <span>Pieteikuma datums</span>
                <strong><?= e($application['application_date']) ?></strong>
            </div>

            <div class="detail-row">
                <span>Manuskripts</span>

                <strong>
                    <?= $application['manuscript_path']
                        ? 'Pievienots'
                        : 'Nav pievienots' ?>
                </strong>
            </div>
        </section>

        <section class="detail-card">
            <h2>Autora kontaktinformācija</h2>

            <div class="detail-row">
                <span>Vārds, uzvārds</span>

                <strong>
                    <?= e(
                        $application['first_name'] . ' ' .
                        $application['last_name']
                    ) ?>
                </strong>
            </div>

            <div class="detail-row">
                <span>E-pasts</span>
                <strong><?= e($application['email']) ?></strong>
            </div>

            <div class="detail-row">
                <span>Tālrunis</span>

                <strong>
                    <?= e($application['phone'] ?: 'Nav norādīts') ?>
                </strong>
            </div>
        </section>

        <section class="detail-card">
            <h2>Projekta statusa maiņa</h2>

            <form
                method="POST"
                action="pieteikums.php?id=<?= (int) $id ?>"
                class="status-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <label for="status_id">
                    Izvēlieties projekta statusu
                </label>

                <select name="status_id" id="status_id" required>
                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= (int) $status['id'] ?>"
                            <?= (int) $status['id'] === (int) $application['status_id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($status['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">
                    Saglabāt statusu
                </button>
            </form>
        </section>

        <section class="detail-card">
            <h2>Manuskripts</h2>

            <?php if ($application['manuscript_path']): ?>

                <p class="description">
                    Šim pieteikumam manuskripts ir pievienots.
                </p>

                <a
                    href="lejupieladet-manuskriptu.php?id=<?= (int) $id ?>"
                    class="download-button"
                >
                    Lejupielādēt manuskriptu
                </a>

            <?php else: ?>

                <p class="description">
                    Pievienojiet manuskriptu PDF vai DOCX formātā.
                    Maksimālais faila izmērs — 10 MB.
                </p>

                <form
                    method="POST"
                    action="augsupieladet-manuskriptu.php"
                    enctype="multipart/form-data"
                    class="status-form"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']) ?>"
                    >

                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= (int) $id ?>"
                    >

                    <label for="manuscript">
                        Izvēlieties manuskripta failu
                    </label>

                    <input
                        type="file"
                        id="manuscript"
                        name="manuscript"
                        accept=".pdf,.docx"
                        required
                    >

                    <button type="submit">
                        Pievienot manuskriptu
                    </button>
                </form>

            <?php endif; ?>
        </section>

        <section class="detail-card">
            <h2>Pievienot piezīmi</h2>

            <form
                method="POST"
                action="saglabat-piezimi.php"
                class="status-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <input
                    type="hidden"
                    name="application_id"
                    value="<?= (int) $id ?>"
                >

                <label for="note">Piezīmes teksts</label>

                <textarea
                    name="note"
                    id="note"
                    rows="6"
                    maxlength="5000"
                    placeholder="Ierakstiet piezīmi par šo grāmatas projektu..."
                    required
                ></textarea>

                <button type="submit">
                    Pievienot piezīmi
                </button>
            </form>
        </section>

    </div>

    <section class="detail-card notes-section">
        <h2>Piezīmju vēsture</h2>

        <?php if (empty($notes)): ?>

            <p class="description">
                Šim pieteikumam vēl nav pievienotu piezīmju.
            </p>

        <?php else: ?>

            <?php foreach ($notes as $note): ?>

                <article class="note-item">
                    <div class="note-meta">
                        <strong>
                            <?= e($note['username']) ?>
                        </strong>

                        <span>
                            <?= e(
                                date(
                                    'd.m.Y H:i',
                                    strtotime($note['created_at'])
                                )
                            ) ?>
                        </span>
                    </div>

                    <p><?= nl2br(e($note['note'])) ?></p>
                </article>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

</main>

</body>
</html>
