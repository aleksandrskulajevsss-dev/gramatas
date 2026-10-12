
<?php

session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        header('Location: index.php');
        exit;
    }

    $error = 'Nepareizs lietotājvārds vai parole.';
}

?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieslēgšanās | Iedvesmas grāmata</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <p class="brand">IEDVESMAS GRĀMATA</p>
        <p class="header-label">Administrācijas panelis</p>
    </div>
</header>

<main class="login-container">
    <div class="login-card">
        <p class="eyebrow">ADMINISTRĀCIJAS PANELIS</p>
        <h1>Pieslēgšanās</h1>
        <p class="description">
            Ievadiet savus piekļuves datus, lai pārvaldītu grāmatu projektus.
        </p>

        <?php if ($error): ?>
            <div class="login-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Lietotājvārds</label>
            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
            >

            <label for="password">Parole</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Pieslēgties</button>
        </form>
    </div>
</main>

</body>
</html>
