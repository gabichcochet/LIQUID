<?php
/**
 * Page Détail - /detail
 * Affiche les détails d'un article et permet de l'ajouter au panier
 * File: pages/detail.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

$article_id = intval($_GET['id'] ?? 0);

if ($article_id <= 0) {
    header("Location: /");
    exit;
}

// Récupérer les détails de l'article
$stmt = $db->prepare("
    SELECT a.id, a.nom, a.description, a.prix, a.image_url, a.date_publication, a.id_auteur,
           u.username, COALESCE(s.quantite, 0) AS stock_disponible
    FROM Article a
    INNER JOIN User u ON a.id_auteur = u.id
    LEFT JOIN Stock s ON a.id = s.id_article
    WHERE a.id = ?
");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();
$stmt->close();

if (!$article) {
    header("Location: /");
    exit;
}

// Traiter l'ajout au panier
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $quantite = intval($_POST['quantite'] ?? 1);

    if ($quantite <= 0) {
        $error = "La quantité doit être supérieure à 0";
    } elseif ($quantite > $article['stock_disponible']) {
        $error = "Quantité insuffisante en stock";
    } else {
        $user_id = $_SESSION['user_id'];

        // Vérifier si l'article est déjà dans le panier
        $stmt = $db->prepare("SELECT id, quantite FROM Cart WHERE id_user = ? AND id_article = ?");
        $stmt->bind_param("ii", $user_id, $article_id);
        $stmt->execute();
        $cart_item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            // Mettre à jour la quantité
            $new_qty = $cart_item['quantite'] + $quantite;
            $stmt = $db->prepare("UPDATE Cart SET quantite = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_qty, $cart_item['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Ajouter au panier
            $stmt = $db->prepare("INSERT INTO Cart (id_user, id_article, quantite) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $article_id, $quantite);
            $stmt->execute();
            $stmt->close();
        }

        setFlash("Article ajouté au panier avec succès!", "success");
        header("Location: cart.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['nom']); ?> - LIQUID</title>
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
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .detail-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .image-section img {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
        }
        .info-section h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        .author {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .price {
            font-size: 32px;
            color: #e74c3c;
            font-weight: bold;
            margin: 20px 0;
        }
        .stock {
            font-size: 18px;
            color: #27ae60;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .stock.out {
            color: #c0392b;
        }
        .description {
            color: #555;
            line-height: 1.6;
            margin: 20px 0;
        }
        .form-group {
            margin: 20px 0;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="number"] {
            width: 100px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            margin-right: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .edit-btn {
            background: #f39c12;
            padding: 8px 16px;
            font-size: 14px;
            margin-top: 10px;
        }
        .edit-btn:hover {
            background: #e67e22;
        }
        @media (max-width: 768px) {
            .detail-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="LIQUID-16-02-2026.png" alt="Logo LIQUID">
        </div>
        <nav>
            <a href="/php_exam/index.php">Accueil</a>
            <?php if (isLoggedIn()): ?>
                <a href="sell.php">Vendre</a>
                <a href="cart.php">Panier</a>
                <a href="account.php">Mon Compte</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <a href="/">← Retour aux articles</a>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="detail-layout">
            <div class="image-section">
                <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['nom']); ?>">
            </div>

            <div class="info-section">
                <h1><?php echo htmlspecialchars($article['nom']); ?></h1>
                <div class="author">Vendu par: <a href="account.php?user_id=<?php echo $article['id_auteur']; ?>"><?php echo htmlspecialchars($article['username']); ?></a></div>

                <div class="price"><?php echo number_format($article['prix'], 2); ?>€</div>

                <div class="stock <?php echo $article['stock_disponible'] == 0 ? 'out' : ''; ?>">
                    Stock disponible: <?php echo $article['stock_disponible']; ?>
                </div>

                <div class="description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($article['description'])); ?></p>
                </div>

                <?php if (isLoggedIn() && $_SESSION['user_id'] == $article['id_auteur']): ?>
                    <form method="POST" action="edit.php" style="margin: 20px 0;">
                        <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                        <button type="submit" class="edit-btn">✏️ Modifier cet article</button>
                    </form>
                <?php endif; ?>

                <?php if ($article['stock_disponible'] > 0): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="quantite">Quantité:</label>
                            <input type="number" id="quantite" name="quantite" min="1" max="<?php echo $article['stock_disponible']; ?>" value="1" required>
                        </div>
                        <?php if (isLoggedIn()): ?>
                            <button type="submit">🛒 Ajouter au panier</button>
                        <?php else: ?>
                            <p><a href="login.php">Connectez-vous</a> pour ajouter cet article à votre panier</p>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <p style="color: #c0392b; font-weight: bold;">❌ Cet article n'est pas disponible</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
