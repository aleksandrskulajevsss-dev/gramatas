
<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Meklēšanas un filtrēšanas vērtības
$search = trim($_GET['search'] ?? '');
$statusId = filter_input(INPUT_GET, 'status_id', FILTER_VALIDATE_INT);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Iegūst visus statusus filtra izvēlnei
$statuses = $pdo->query("
    SELECT id, name
    FROM statuses
    ORDER BY sort_order ASC
")->fetchAll();

// SQL vaicājums ar filtriem
$sql = "
    SELECT
        applications.id,
        applications.book_title,
        applications.genre,
        applications.application_date,
        CONCAT(authors.first_name, ' ', authors.last_name) AS author_name,
        statuses.name AS status_name
    FROM applications
    INNER JOIN authors
        ON authors.id = applications.author_id
    INNER JOIN statuses
        ON statuses.id = applications.status_id
    WHERE 1 = 1
";

$params = [];

// Meklēšana pēc grāmatas nosaukuma vai autora
if ($search !== '') {
    $sql .= "
        AND (
            applications.book_title LIKE ?
            OR authors.first_name LIKE ?
            OR authors.last_name LIKE ?
            OR CONCAT(authors.first_name, ' ', authors.last_name) LIKE ?
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Filtrēšana pēc statusa
if ($statusId && $statusId > 0) {
    $sql .= " AND applications.status_id = ?";
    $params[] = $statusId;
}

// Datuma formāta pārbaude
function validDate($date) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date;
}

// Filtrēšana no datuma
if ($dateFrom !== '' && validDate($dateFrom)) {
    $sql .= " AND applications.application_date >= ?";
    $params[] = $dateFrom;
}

// Filtrēšana līdz datumam
if ($dateTo !== '' && validDate($dateTo)) {
    $sql .= " AND applications.application_date <= ?";
    $params[] = $dateTo;
}

$sql .= "
    ORDER BY applications.application_date DESC, applications.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Grāmatu projekti | Iedvesmas grāmata</title>

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
        <p class="eyebrow">IZDEVNIECĪBAS PĀRVALDĪBA</p>

        <h1>Grāmatu projekti</h1>

        <p class="description">
            Pārskats par autoru pieteikumiem un grāmatu izdošanas procesu.
        </p>
    </div>

    <!-- Meklēšanas un filtrēšanas sadaļa -->

    <section class="filter-section">
        <h2>Meklēšana un filtrēšana</h2>

        <form method="GET" action="index.php" class="filter-form">

            <div class="filter-field filter-search">
                <label for="search">Autors vai grāmatas nosaukums</label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Ievadiet autora vārdu vai grāmatas nosaukumu"
                >
            </div>

            <div class="filter-field">
                <label for="status_id">Projekta statuss</label>

                <select id="status_id" name="status_id">
                    <option value="">Visi statusi</option>

                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= (int) $status['id'] ?>"
                            <?= (int) $status['id'] === (int) $statusId
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($status['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="date_from">Datums no</label>

                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    value="<?= e($dateFrom) ?>"
                >
            </div>

            <div class="filter-field">
                <label for="date_to">Datums līdz</label>

                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    value="<?= e($dateTo) ?>"
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="filter-button">
                    Meklēt
                </button>

                <a href="index.php" class="reset-button">
                    Notīrīt filtrus
                </a>
            </div>

        </form>
    </section>

    <!-- Grāmatu projektu tabula -->

    <section class="projects">

        <div class="section-heading">
            <h2>Visi pieteikumi</h2>

            <span class="count">
                <?= count($applications) ?> atrasti
            </span>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Grāmatas nosaukums</th>
                        <th>Autors</th>
                        <th>Žanrs</th>
                        <th>Pieteikuma datums</th>
                        <th>Statuss</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($applications)): ?>

                        <tr>
                            <td colspan="5" class="empty-state">
                                Neviens pieteikums neatbilst izvēlētajiem filtriem.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td>
                                    <a
                                        href="pieteikums.php?id=<?= (int) $application['id'] ?>"
                                        class="book-link"
                                    >
                                        <?= e($application['book_title']) ?>
                                    </a>
                                </td>

                                <td>
                                    <?= e($application['author_name']) ?>
                                </td>

                                <td>
                                    <?= e($application['genre']) ?>
                                </td>

                                <td>
                                    <?= e($application['application_date']) ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= e($application['status_name']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

</body>
</html>
