<?php
/**
 * Page Panier - /cart
 * Affiche le panier et permet de modifier les quantités
 * File: pages/cart.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$errors = [];

// Traiter les modifications du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $cart_id = intval($_POST['cart_id'] ?? 0);
        $action = $_POST['action'];

        if ($action === 'remove') {
            $stmt = $db->prepare("DELETE FROM Cart WHERE id = ? AND id_user = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
            setFlash("Article supprimé du panier", "success");
        } elseif ($action === 'update') {
            $quantite = intval($_POST['quantite'] ?? 1);
            if ($quantite <= 0) {
                $errors[] = "La quantité doit être supérieure à 0";
            } else {
                $stmt = $db->prepare("UPDATE Cart SET quantite = ? WHERE id = ? AND id_user = ?");
                $stmt->bind_param("iii", $quantite, $cart_id, $user_id);
                $stmt->execute();
                $stmt->close();
                setFlash("Quantité mise à jour", "success");
            }
        }

        if (empty($errors)) {
            header("Location: cart.php");
            exit;
        }
    }
}

// Récupérer les articles du panier
$stmt = $db->prepare("
    SELECT c.id, c.quantite, a.id AS article_id, a.nom, a.prix, a.image_url,
           COALESCE(s.quantite, 0) AS stock_disponible
    FROM Cart c
    INNER JOIN Article a ON c.id_article = a.id
    LEFT JOIN Stock s ON a.id = s.id_article
    WHERE c.id_user = ?
    ORDER BY c.date_ajout DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['quantite'] * $row['prix'];
}
$stmt->close();

// Récupérer le solde de l'utilisateur
$stmt = $db->prepare("SELECT solde FROM User WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - LIQUID</title>
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .flash-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        .cart-items {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            margin-bottom: 15px;
            align-items: center;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .item-info {
            flex: 1;
        }
        .item-info h3 {
            margin-bottom: 5px;
        }
        .item-price {
            color: #e74c3c;
            font-weight: bold;
            margin: 5px 0;
        }
        .item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .item-actions input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .item-actions button {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .item-actions button:hover {
            background: #2980b9;
        }
        .remove-btn {
            background: #e74c3c;
        }
        .remove-btn:hover {
            background: #c0392b;
        }
        .summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .summary h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }
        .solde-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .solde-info.insufficient {
            background: #f8d7da;
            color: #721c24;
        }
        a {
            color: #3498db;
            text-decoration: none;
            display: inline-block;
            padding: 8px 0;
        }
        a:hover {
            text-decoration: underline;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            background: #27ae60;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
            transition: 0.3s;
        }
        .checkout-btn:hover:not(:disabled) {
            background: #229954;
        }
        .checkout-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .empty-message {
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>🛒 LIQUID</h1>
        <nav>
            <a href="/php_exam/index.php">Accueil</a>
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
        <h1>Mon Panier</h1>

        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="flash-message">✅ <?php echo $flash['message']; ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    ❌ <?php echo $error; ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>">
                            <div class="item-info">
                                <h3><a href="detail.php?id=<?php echo $item['article_id']; ?>"><?php echo htmlspecialchars($item['nom']); ?></a></h3>
                                <div class="item-price"><?php echo number_format($item['prix'], 2); ?>€ x <?php echo $item['quantite']; ?> = <strong><?php echo number_format($item['quantite'] * $item['prix'], 2); ?>€</strong></div>
                                <div class="item-actions">
                                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="number" name="quantite" value="<?php echo $item['quantite']; ?>" min="1" max="<?php echo $item['stock_disponible']; ?>">
                                        <button type="submit">Mettre à jour</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="remove-btn">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary">
                    <h3>Résumé du panier</h3>
                    <div class="solde-info <?php echo $user['solde'] < $total ? 'insufficient' : ''; ?>">
                        <strong>Votre solde:</strong> <?php echo number_format($user['solde'], 2); ?>€
                    </div>
                    <div class="summary-line">
                        <span>Nombre d'articles:</span>
                        <span><?php echo array_sum(array_column($cart_items, 'quantite')); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Sous-total:</span>
                        <span><?php echo number_format($total, 2); ?>€</span>
                    </div>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span><?php echo number_format($total, 2); ?>€</span>
                    </div>
                    <?php if ($user['solde'] >= $total): ?>
                        <a href="validate.php" class="checkout-btn">Passer la commande</a>
                    <?php else: ?>
                        <button class="checkout-btn" disabled>Solde insuffisant</button>
                        <p style="font-size: 12px; color: #c0392b; margin-top: 10px;">Vous avez besoin de <?php echo number_format($total - $user['solde'], 2); ?>€ de plus</p>
                        <a href="account.php" style="display: block; text-align: center; margin-top: 10px;">Ajouter des fonds</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <h2>Votre panier est vide</h2>
                <p style="margin: 20px 0;">Découvrez nos articles en vente</p>
                <a href="/">← Retour aux articles</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
