<?php
/**
 * Page de Connexion - LOGIN
 * File: pages/login.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Si l'utilisateur est déjà connecté, le rediriger
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email_value = $email;

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier l'utilisateur dans la base de données
        $result = $db->query("SELECT id, username, email, password, role, solde FROM User WHERE email = '" . escape($email) . "'");

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Vérifier le mot de passe
            if (password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['solde'] = $user['solde'];

                setFlash("Bienvenue " . $user['username'] . " !", "success");
                header("Location: ../index.php");
                exit;
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - LIQUID</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
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
        <h1>Connexion</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_value); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Se Connecter</button>
        </form>

        <div class="links">
            Pas de compte ? <a href="register.php">S'inscrire</a>
        </div>
    </div>
</body>
</html>
