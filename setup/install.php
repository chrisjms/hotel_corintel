<?php
/**
 * Installation Script
 * Hotel Corintel Admin System
 *
 * Run this script once to set up the database
 * DELETE THIS FILE AFTER INSTALLATION FOR SECURITY
 */

// Prevent running in production accidentally
if (php_sapi_name() !== 'cli' && !isset($_GET['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation | Hôtel Corintel Admin</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 800px;
                margin: 2rem auto;
                padding: 1rem;
                line-height: 1.6;
            }
            h1 { color: #8B5A2B; }
            .warning {
                background: #FEF3CD;
                border: 1px solid #F0C36D;
                padding: 1rem;
                border-radius: 8px;
                margin: 1rem 0;
            }
            .step {
                background: #f8f9fa;
                padding: 1rem;
                border-radius: 8px;
                margin: 1rem 0;
            }
            code {
                background: #e9ecef;
                padding: 0.2rem 0.4rem;
                border-radius: 4px;
                font-size: 0.9em;
            }
            pre {
                background: #1a202c;
                color: #fff;
                padding: 1rem;
                border-radius: 8px;
                overflow-x: auto;
            }
            .btn {
                display: inline-block;
                background: #8B5A2B;
                color: #fff;
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                text-decoration: none;
                margin-top: 1rem;
            }
            .btn:hover { background: #6B4423; }
        </style>
    </head>
    <body>
        <h1>Installation du système d'administration</h1>

        <div class="warning">
            <strong>Important :</strong> Suivez attentivement ces étapes avant de lancer l'installation.
        </div>

        <div class="step">
            <h3>Étape 1 : Configurer la base de données</h3>
            <p>Éditez le fichier <code>config/database.php</code> et renseignez vos informations de connexion MySQL :</p>
            <pre>
define('DB_HOST', 'localhost');       // Hôte MySQL (souvent localhost sur OVH)
define('DB_NAME', 'votre_base');      // Nom de votre base de données
define('DB_USER', 'votre_utilisateur'); // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe'); // Mot de passe MySQL
define('SITE_URL', 'https://votre-domaine.com'); // URL de votre site</pre>
        </div>

        <div class="step">
            <h3>Étape 2 : Créer la base de données</h3>
            <p>Dans phpMyAdmin (disponible depuis votre espace OVH), créez une nouvelle base de données ou utilisez une existante.</p>
        </div>

        <div class="step">
            <h3>Étape 3 : Lancer l'installation</h3>
            <p>Une fois la configuration effectuée, cliquez sur le bouton ci-dessous :</p>
            <a href="?confirm=1" class="btn">Lancer l'installation</a>
        </div>

        <div class="step">
            <h3>Étape 4 : Supprimer ce fichier</h3>
            <p>Après l'installation, <strong>supprimez ce fichier</strong> (<code>setup/install.php</code>) pour des raisons de sécurité.</p>
        </div>

        <div class="step">
            <h3>Informations de connexion par défaut</h3>
            <p>Après l'installation, connectez-vous avec :</p>
            <ul>
                <li><strong>Identifiant :</strong> admin</li>
                <li><strong>Mot de passe :</strong> admin123</li>
            </ul>
            <p><strong>Changez immédiatement ce mot de passe</strong> dans les paramètres de l'administration.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Run installation
require_once __DIR__ . '/../config/database.php';

$messages = [];
$errors = [];

try {
    $pdo = getDatabase();
    $messages[] = "Connexion à la base de données réussie.";

    // Read schema file
    $schema = file_get_contents(__DIR__ . '/schema.sql');

    // Remove SQL comments (-- style)
    $lines = explode("\n", $schema);
    $cleanedLines = [];
    foreach ($lines as $line) {
        // Remove inline comments but keep the rest of the line
        $commentPos = strpos($line, '--');
        if ($commentPos !== false) {
            $line = substr($line, 0, $commentPos);
        }
        $cleanedLines[] = $line;
    }
    $schema = implode("\n", $cleanedLines);

    // Split statements by semicolon followed by newline (safer than just semicolon)
    // This regex splits on semicolon that is followed by whitespace/newline
    $statements = preg_split('/;\s*[\r\n]+/', $schema);

    foreach ($statements as $statement) {
        $statement = trim($statement);
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Ignore duplicate key errors (expected for ON DUPLICATE KEY UPDATE)
            if (strpos($e->getMessage(), 'Duplicate') === false &&
                strpos($e->getMessage(), '1062') === false) {
                throw $e;
            }
        }
    }

    $messages[] = "Tables créées avec succès.";
    $messages[] = "Données initiales insérées.";

    // Create uploads directory
    $uploadsDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadsDir)) {
        if (mkdir($uploadsDir, 0755, true)) {
            $messages[] = "Dossier uploads créé.";
        } else {
            $errors[] = "Impossible de créer le dossier uploads. Créez-le manuellement avec les permissions 755.";
        }
    }

    // Create .htaccess for uploads
    $htaccess = $uploadsDir . '.htaccess';
    if (!file_exists($htaccess)) {
        // OVH compatible version - block PHP execution without php_flag
        $htaccessContent = "# Disable PHP execution in uploads folder\n";
        $htaccessContent .= "<FilesMatch \"\\.(php|php5|phtml|php7|phps)$\">\n";
        $htaccessContent .= "    Require all denied\n";
        $htaccessContent .= "</FilesMatch>\n";

        if (file_put_contents($htaccess, $htaccessContent)) {
            $messages[] = "Protection du dossier uploads configurée.";
        }
    }

    // Create admin .htaccess
    $adminHtaccess = __DIR__ . '/../admin/.htaccess';
    if (!file_exists($adminHtaccess)) {
        $adminContent = "# Protect admin directory\n";
        $adminContent .= "Options -Indexes\n";
        $adminContent .= "\n";
        $adminContent .= "# Prevent direct access to PHP files except allowed ones\n";
        $adminContent .= "<Files *.php>\n";
        $adminContent .= "    Order Allow,Deny\n";
        $adminContent .= "    Allow from all\n";
        $adminContent .= "</Files>\n";

        if (file_put_contents($adminHtaccess, $adminContent)) {
            $messages[] = "Protection du dossier admin configurée.";
        }
    }

} catch (PDOException $e) {
    $errors[] = "Erreur de base de données : " . $e->getMessage();
} catch (Exception $e) {
    $errors[] = "Erreur : " . $e->getMessage();
}

// Display results
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation | Hôtel Corintel Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
            line-height: 1.6;
        }
        h1 { color: #8B5A2B; }
        .success {
            background: #D4EDDA;
            border: 1px solid #C3E6CB;
            padding: 1rem;
            border-radius: 8px;
            margin: 0.5rem 0;
            color: #155724;
        }
        .error {
            background: #F8D7DA;
            border: 1px solid #F5C6CB;
            padding: 1rem;
            border-radius: 8px;
            margin: 0.5rem 0;
            color: #721C24;
        }
        .warning {
            background: #FEF3CD;
            border: 1px solid #F0C36D;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        .btn {
            display: inline-block;
            background: #8B5A2B;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 1rem;
        }
        .btn:hover { background: #6B4423; }
    </style>
</head>
<body>
    <h1>Résultat de l'installation</h1>

    <?php foreach ($messages as $msg): ?>
        <div class="success">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="error">✗ <?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <?php if (empty($errors)): ?>
        <div class="success" style="margin-top: 1rem;">
            <strong>Installation terminée avec succès !</strong>
        </div>

        <div class="warning">
            <strong>Actions importantes :</strong>
            <ol>
                <li>Supprimez le dossier <code>setup/</code> pour des raisons de sécurité</li>
                <li>Connectez-vous à l'administration : <a href="../admin/login.php">Accéder à l'admin</a></li>
                <li>Changez immédiatement le mot de passe par défaut (admin123)</li>
            </ol>
        </div>

        <a href="/corintel/admin/login.php" class="btn">Accéder à l'administration</a>
    <?php else: ?>
        <div class="warning">
            <strong>L'installation a rencontré des erreurs.</strong><br>
            Corrigez les problèmes ci-dessus et relancez l'installation.
        </div>
        <a href="install.php" class="btn">Réessayer</a>
    <?php endif; ?>
</body>
</html>
