<?php
/**
 * Super Admin Login Page
 * Platform Administration
 */

require_once __DIR__ . '/includes/super-auth.php';

if (superIsLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$csrfToken = superGenerateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!superVerifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $result = superAttemptLogin($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            header('Location: index.php');
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Super Admin | Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="super-admin-style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h1>Platform Admin</h1>
            <p>Espace de gestion de la plateforme</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label for="username">Identifiant</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    autofocus
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                Se connecter
            </button>
        </form>
    </div>
</body>
</html>
