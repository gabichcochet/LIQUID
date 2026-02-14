<?php
/**
 * Page d'Inscription - REGISTER
 * File: pages/register.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Si l'utilisateur est déjà connecté
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';
$username_value = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    $username_value = $username;
    $email_value = $email;

    // Validations
    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (strlen($username) < 3) {
        $error = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'email existe déjà
        $result = $db->query("SELECT id FROM User WHERE email = '" . escape($email) . "'");
        if ($result && $result->num_rows > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Vérifier si le username existe déjà
            $result = $db->query("SELECT id FROM User WHERE username = '" . escape($username) . "'");
            if ($result && $result->num_rows > 0) {
                $error = "Ce nom d'utilisateur est déjà pris.";
            } else {
                // Hasher le mot de passe
                $password_hashed = password_hash($password, PASSWORD_BCRYPT);

                // Insérer l'utilisateur
                $insert_query = "INSERT INTO User (username, password, email, solde, role) 
                                 VALUES ('" . escape($username) . "', '" . escape($password_hashed) . "', '" . escape($email) . "', 0.00, 'user')";

                if ($db->query($insert_query)) {
                    $user_id = $db->insert_id;

                    // Connecter automatiquement l'utilisateur
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['solde'] = 0.00;

                    setFlash("Bienvenue " . $username . " ! Compte créé avec succès.", "success");
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = "Erreur lors de la création du compte. Veuillez réessayer.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LIQUID</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 5px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inscription</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_value); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_value); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>

            <button type="submit">S'inscrire</button>
        </form>

        <div class="links">
            Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
    </div>
</body>
</html>
