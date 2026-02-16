<?php
/**
 * Page d'accueil - HOME /
 * File: index.php
 */

require_once 'config/database.php';
require_once 'includes/session.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIQUID - E-Commerce</title>
    <style>
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
        header h1 {
            font-size: 24px;
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
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-card h3 {
            margin: 10px 0;
            font-size: 16px;
        }
        .product-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
            margin: 10px 0;
        }
        .product-card a {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.3s;
        }
        .product-card a:hover {
            background: #2980b9;
        }
        .flash-message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
        }
        .flash-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .flash-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header>
        <h1>🛒 LIQUID E-Commerce</h1>
        <nav>
            <a href="/php_exam/index.php">Accueil</a>
            <?php if (isLoggedIn()): ?>
                <a href="/php_exam/pages/sell.php">Vendre</a>
                <a href="/php_exam/pages/cart.php">Panier</a>
                <a href="/php_exam/pages/account.php">Mon Compte</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="/php_exam/pages/admin.php">Admin</a>
                <?php endif; ?>
                <a href="/php_exam/pages/logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="/php_exam/pages/login.php">Connexion</a>
                <a href="/php_exam/pages/register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="flash-message <?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>

        <h2>Articles en Vente</h2>

        <?php
        // Récupérer tous les articles
        $result = $db->query("
            SELECT a.id, a.nom, a.description, a.prix, a.image_url, a.date_publication, u.username,
                   COALESCE(s.quantite, 0) AS stock_disponible
            FROM Article a
            INNER JOIN User u ON a.id_auteur = u.id
            LEFT JOIN Stock s ON a.id = s.id_article
            ORDER BY a.date_publication DESC
        ");

        if ($result && $result->num_rows > 0):
        ?>
            <div class="products-grid">
                <?php while ($article = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['nom']); ?>">
                        <h3><?php echo htmlspecialchars($article['nom']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($article['description'], 0, 100)) . '...'; ?></p>
                        <p style="color: #999; font-size: 12px;">Par: <?php echo htmlspecialchars($article['username']); ?></p>
                        <div class="product-price"><?php echo number_format($article['prix'], 2); ?>€</div>
                        <p style="color: #27ae60; font-weight: bold;">Stock: <?php echo $article['stock_disponible']; ?></p>
                        <a href="/php_exam/pages/detail.php?id=<?php echo $article['id']; ?>">Voir Détails</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Aucun article disponible pour le moment.</p>
        <?php endif; ?>
    </div>
</body>
</html>
