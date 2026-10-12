
<?php

// Pārbauda, vai administrators ir pieslēdzies
require_once __DIR__ . '/includes/auth.php';

require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query("
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
    ORDER BY applications.application_date DESC, applications.id DESC
");

$applications = $stmt->fetchAll();

// Droša teksta attēlošana HTML
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

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

    <section class="projects">

        <div class="section-heading">
            <h2>Visi pieteikumi</h2>

            <span class="count">
                <?= count($applications) ?> kopā
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
                                Pagaidām nav neviena grāmatas pieteikuma.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($applications as $application): ?>
                            <tr>
                                <td>
                                    <?= e($application['book_title']) ?>
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
