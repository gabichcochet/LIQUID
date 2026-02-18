<?php
/**
 * Page Modifier Article - /edit
 * Permet de modifier ou supprimer un article
 * File: pages/edit.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$article_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$errors = [];

if ($article_id <= 0) {
    header("Location: /");
    exit;
}

// Récupérer l'article
$stmt = $db->prepare("SELECT id, nom, description, prix, id_auteur, image_url FROM Article WHERE id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
$stmt->close();

if (!$article) {
    header("Location: /");
    exit;
}

// Vérifier les permissions
if ($article['id_auteur'] !== $user_id && $_SESSION['user_role'] !== 'admin') {
    setFlash("Vous n'avez pas le droit de modifier cet article", "error");
    header("Location: detail.php?id=" . $article_id);
    exit;
}

// Récupérer le stock
$stmt = $db->prepare("SELECT quantite FROM Stock WHERE id_article = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$stock_result = $stmt->get_result()->fetch_assoc();
$stock = $stock_result['quantite'] ?? 0;
$stmt->close();

// Traiter la suppression
if (isset($_POST['delete']) && $_POST['delete'] === 'yes') {
    $stmt = $db->prepare("DELETE FROM Stock WHERE id_article = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM Cart WHERE id_article = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM Article WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $stmt->close();

    setFlash("Article supprimé avec succès", "success");
    header("Location: /");
    exit;
}

// Traiter la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = trim($_POST['prix'] ?? '');
    $new_stock = trim($_POST['stock'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

    // Validation
    if (empty($nom)) $errors[] = "Le nom de l'article est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($prix) || !is_numeric($prix) || $prix <= 0) $errors[] = "Le prix doit être un nombre positif";
    if (empty($new_stock) || !is_numeric($new_stock) || $new_stock < 0) $errors[] = "Le stock doit être un nombre positif";
    if (empty($image_url)) $errors[] = "L'URL de l'image est requise";

    if (empty($errors)) {
        // Mettre à jour l'article
        $stmt = $db->prepare("UPDATE Article SET nom = ?, description = ?, prix = ?, image_url = ? WHERE id = ?");
        $stmt->bind_param("ssdsi", $nom, $description, $prix, $image_url, $article_id);

        if ($stmt->execute()) {
            $stmt->close();

            // Mettre à jour le stock
            $stmt = $db->prepare("UPDATE Stock SET quantite = ? WHERE id_article = ?");
            $stmt->bind_param("ii", $new_stock, $article_id);
            $stmt->execute();
            $stmt->close();

            setFlash("Article mis à jour avec succès!", "success");
            header("Location: detail.php?id=" . $article_id);
            exit;
        } else {
            $errors[] = "Erreur lors de la mise à jour de l'article";
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
    <title>Modifier Article - LIQUID</title>
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
        .button-group {
            display: flex;
            gap: 10px;
        }
        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }
        .save-btn {
            background: #27ae60;
            color: white;
        }
        .save-btn:hover {
            background: #229954;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        .delete-btn:hover {
            background: #c0392b;
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
        <h1>Modifier l'Article</h1>

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
                <input type="text" id="nom" name="nom" required value="<?php echo htmlspecialchars($article['nom']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($article['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="prix">Prix (€) *</label>
                <input type="number" id="prix" name="prix" step="0.01" min="0.01" required value="<?php echo $article['prix']; ?>">
            </div>

            <div class="form-group">
                <label for="stock">Quantité en Stock *</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?php echo $stock; ?>">
            </div>

            <div class="form-group">
                <label for="image_url">URL de l'image *</label>
                <input type="url" id="image_url" name="image_url" required value="<?php echo htmlspecialchars($article['image_url']); ?>">
            </div>

            <div class="button-group">
                <button type="submit" class="save-btn">💾 Enregistrer</button>
                <button type="button" class="delete-btn" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) { document.getElementById('delete-form').submit(); }">🗑️ Supprimer</button>
            </div>

            <a href="detail.php?id=<?php echo $article_id; ?>" style="display: block; margin-top: 10px;">← Retour à l'article</a>
        </form>

        <form id="delete-form" method="POST" style="display: none;">
            <input type="hidden" name="delete" value="yes">
        </form>
    </div>
</body>
</html>
