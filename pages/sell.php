<?php
/**
 * Page de Vente - /sell
 * Permet de créer un nouvel article à mettre en vente
 * File: pages/sell.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    // Validation
    if (empty($nom)) $errors[] = "Le nom de l'article est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($prix) || !is_numeric($prix) || $prix <= 0) $errors[] = "Le prix doit être un nombre positif";
    if (empty($stock) || !is_numeric($stock) || $stock < 0) $errors[] = "Le stock doit être un nombre positif";
    if (empty($image_url)) $errors[] = "L'URL de l'image est requise";

    if (empty($errors)) {
        // Insérer l'article
        $id_auteur = $_SESSION['user_id'];
        $stmt = $db->prepare("INSERT INTO Article (nom, description, prix, id_auteur, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $nom, $description, $prix, $id_auteur, $image_url);

        if ($stmt->execute()) {
            $article_id = $stmt->insert_id;

            // Insérer le stock
            $stmt2 = $db->prepare("INSERT INTO Stock (id_article, quantite) VALUES (?, ?)");
            $stmt2->bind_param("ii", $article_id, $stock);
            $stmt2->execute();
            $stmt2->close();

            $success = true;
            setFlash("Article créé avec succès!", "success");
            header("Location: detail.php?id=" . $article_id);
            exit;
        } else {
            $errors[] = "Erreur lors de la création de l'article";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendre un Article - LIQUID</title>
    <style>
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo img {
            height: 70px;
            width: auto;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        header {
            background: #333;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav {
            display: flex;
            gap: 20px;
        }
        nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            background: #555;
            border-radius: 4px;
            transition: 0.3s;
        }
        nav a:hover {
            background: #777;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="number"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        input:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        button {
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }
        button:hover {
            background: #229954;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .error-item {
            margin: 5px 0;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="\LIQUID\src\LIQUID-16-02-2026.png" alt="Logo LIQUID">
        </div>
        <nav>
            <a href="/LIQUID/index.php">Accueil</a>
            <a href="sell.php">Vendre</a>
            <a href="cart.php">Panier</a>
            <a href="account.php">Mon Compte</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>

    <div class="container">
        <h1>Créer un Nouvel Article</h1>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">❌ <?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nom">Nom de l'article *</label>
                <input type="text" id="nom" name="nom" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="prix">Prix (€) *</label>
                <input type="number" id="prix" name="prix" step="0.01" min="0.01" required value="<?php echo isset($_POST['prix']) ? htmlspecialchars($_POST['prix']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="stock">Quantité en Stock *</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="image_url">URL de l'image *</label>
                <input type="url" id="image_url" name="image_url" required value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
            </div>

            <button type="submit">Créer l'article</button>
            <a href="/" style="margin-left: 10px;">Retour</a>
        </form>
    </div>
</body>
</html>
