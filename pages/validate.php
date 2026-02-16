<?php
/**
 * Page Validation Commande - /cart/validate
 * Valide la commande et génère une facture
 * File: pages/validate.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Récupérer les articles du panier
$stmt = $db->prepare("
    SELECT c.id, c.quantite, a.id AS article_id, a.prix,
           COALESCE(s.quantite, 0) AS stock_disponible
    FROM Cart c
    INNER JOIN Article a ON c.id_article = a.id
    LEFT JOIN Stock s ON a.id = s.id_article
    WHERE c.id_user = ?
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

// Vérifier que le panier n'est pas vide
if (count($cart_items) === 0) {
    header("Location: cart.php");
    exit;
}

// Récupérer le solde de l'utilisateur
$stmt = $db->prepare("SELECT solde FROM User WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Vérifier le solde
if ($user['solde'] < $total) {
    setFlash("Solde insuffisant pour effectuer cette commande", "error");
    header("Location: cart.php");
    exit;
}

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');

    // Validation
    if (empty($adresse)) $errors[] = "L'adresse est requise";
    if (empty($ville)) $errors[] = "La ville est requise";
    if (empty($code_postal)) $errors[] = "Le code postal est requis";

    if (empty($errors)) {
        // Commencer la transaction
        $db->begin_transaction();

        try {
            // Créer la facture
            $stmt = $db->prepare("INSERT INTO Invoice (id_user, montant, adresse_facturation, ville_facturation, code_postal_facturation) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $user_id, $total, $adresse, $ville, $code_postal);
            $stmt->execute();
            $invoice_id = $stmt->insert_id;
            $stmt->close();

            // Déduire du solde
            $new_solde = $user['solde'] - $total;
            $stmt = $db->prepare("UPDATE User SET solde = ? WHERE id = ?");
            $stmt->bind_param("di", $new_solde, $user_id);
            $stmt->execute();
            $stmt->close();

            // Mettre à jour le stock et supprimer du panier
            foreach ($cart_items as $item) {
                // Diminuer le stock
                $new_stock = $item['stock_disponible'] - $item['quantite'];
                $stmt = $db->prepare("UPDATE Stock SET quantite = ? WHERE id_article = ?");
                $stmt->bind_param("ii", $new_stock, $item['article_id']);
                $stmt->execute();
                $stmt->close();

                // Supprimer du panier
                $stmt = $db->prepare("DELETE FROM Cart WHERE id_article = ? AND id_user = ?");
                $stmt->bind_param("ii", $item['article_id'], $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Commit de la transaction
            $db->commit();

            // Mettre à jour la session
            $_SESSION['solde'] = $new_solde;

            setFlash("Commande validée avec succès! Facture #" . $invoice_id, "success");
            header("Location: account.php");
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Erreur lors de la validation de la commande";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de Commande - LIQUID</title>
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
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
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
        .order-items {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
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
        button {
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
        button:hover {
            background: #229954;
        }
        a {
            color: #3498db;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .checkout-layout {
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
        <h1>Validation de Commande</h1>

        <div class="checkout-layout">
            <div class="form-section">
                <h2>Adresse de Facturation</h2>

                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <?php foreach ($errors as $error): ?>
                            <div class="error-item">❌ <?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="adresse">Adresse *</label>
                        <input type="text" id="adresse" name="adresse" required value="<?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="ville">Ville *</label>
                        <input type="text" id="ville" name="ville" required value="<?php echo isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="code_postal">Code Postal *</label>
                        <input type="text" id="code_postal" name="code_postal" required value="<?php echo isset($_POST['code_postal']) ? htmlspecialchars($_POST['code_postal']) : ''; ?>">
                    </div>

                    <button type="submit">✅ Valider la Commande</button>
                    <a href="cart.php">← Retour au panier</a>
                </form>
            </div>

            <div class="summary">
                <h3>Résumé de Commande</h3>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <span>x<?php echo $item['quantite']; ?></span>
                            <span><?php echo number_format($item['quantite'] * $item['prix'], 2); ?>€</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="solde-info">
                    <strong>Solde actuel:</strong> <?php echo number_format($user['solde'], 2); ?>€<br>
                    <strong>Montant:</strong> -<?php echo number_format($total, 2); ?>€
                </div>

                <div class="summary-total">
                    <span>Total à payer:</span>
                    <span><?php echo number_format($total, 2); ?>€</span>
                </div>

                <div class="solde-info" style="background: #d4edda; color: #155724;">
                    <strong>Nouveau solde:</strong> <?php echo number_format($user['solde'] - $total, 2); ?>€
                </div>
            </div>
        </div>
    </div>
</body>
</html>
