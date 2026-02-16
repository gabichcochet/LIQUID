<?php
/**
 * Page Compte - /account
 * Affiche les informations du compte et les articles publiés/achetés
 * File: pages/account.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

// Déterminer quel utilisateur afficher
$view_user_id = intval($_GET['user_id'] ?? $_SESSION['user_id']);
$is_own_account = ($view_user_id === $_SESSION['user_id']);

// Récupérer les informations de l'utilisateur
$stmt = $db->prepare("SELECT id, username, email, solde, role, date_creation FROM User WHERE id = ?");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();
$stmt->close();

if (!$user_info) {
    header("Location: /");
    exit;
}

$errors = [];
$success_msg = '';

// Traiter les modifications (uniquement si c'est son propre compte)
if ($is_own_account && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $email = trim($_POST['email'] ?? '');
            $new_password = trim($_POST['new_password'] ?? '');

            if (empty($email)) {
                $errors[] = "L'email est requis";
            }

            if (empty($errors)) {
                // Vérifier si l'email existe déjà
                $stmt = $db->prepare("SELECT id FROM User WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $view_user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $errors[] = "Cet email est déjà utilisé";
                }
                $stmt->close();

                if (empty($errors)) {
                    if (!empty($new_password)) {
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE User SET email = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $email, $hashed_password, $view_user_id);
                    } else {
                        $stmt = $db->prepare("UPDATE User SET email = ? WHERE id = ?");
                        $stmt->bind_param("si", $email, $view_user_id);
                    }

                    if ($stmt->execute()) {
                        $_SESSION['email'] = $email;
                        $user_info['email'] = $email;
                        $success_msg = "Profil mis à jour avec succès!";
                    } else {
                        $errors[] = "Erreur lors de la mise à jour";
                    }
                    $stmt->close();
                }
            }
        } elseif ($_POST['action'] === 'add_funds') {
            $amount = floatval($_POST['amount'] ?? 0);

            if ($amount <= 0) {
                $errors[] = "Le montant doit être positif";
            } else {
                $new_solde = $user_info['solde'] + $amount;
                $stmt = $db->prepare("UPDATE User SET solde = ? WHERE id = ?");
                $stmt->bind_param("di", $new_solde, $view_user_id);

                if ($stmt->execute()) {
                    $_SESSION['solde'] = $new_solde;
                    $user_info['solde'] = $new_solde;
                    $success_msg = "Fonds ajoutés avec succès! Nouveau solde: " . number_format($new_solde, 2) . "€";
                } else {
                    $errors[] = "Erreur lors de l'ajout de fonds";
                }
                $stmt->close();
            }
        }
    }
}

// Récupérer les articles publiés par l'utilisateur
$stmt = $db->prepare("
    SELECT a.id, a.nom, a.prix, a.image_url, a.date_publication,
           COALESCE(s.quantite, 0) AS stock_disponible
    FROM Article a
    LEFT JOIN Stock s ON a.id = s.id_article
    WHERE a.id_auteur = ?
    ORDER BY a.date_publication DESC
");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$published_articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Récupérer les factures (uniquement si c'est son propre compte)
$invoices = [];
if ($is_own_account) {
    $stmt = $db->prepare("
        SELECT id, montant, date_transaction, adresse_facturation, ville_facturation, code_postal_facturation
        FROM Invoice
        WHERE id_user = ?
        ORDER BY date_transaction DESC
    ");
    $stmt->bind_param("i", $view_user_id);
    $stmt->execute();
    $invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte <?php echo htmlspecialchars($user_info['username']); ?> - LIQUID</title>
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
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .user-info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .user-detail {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .user-detail-label {
            color: #666;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-detail-value {
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .solde {
            color: #27ae60;
            font-size: 24px;
        }
        .role-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        .role-badge.admin {
            background: #e74c3c;
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
            border: 1px solid #f5c6cb;
        }
        .error-item {
            margin: 5px 0;
        }
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-section h2 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="email"],
        input[type="password"],
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
        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .article-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background: #f9f9f9;
        }
        .article-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .article-card-info {
            padding: 10px;
            font-size: 12px;
        }
        .article-card-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .article-card-price {
            color: #e74c3c;
            font-weight: bold;
        }
        .article-card-stock {
            color: #27ae60;
        }
        .invoices-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .invoices-table th,
        .invoices-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        .invoices-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .no-data {
            padding: 20px;
            text-align: center;
            color: #666;
        }
        @media (max-width: 768px) {
            .user-details {
                grid-template-columns: 1fr;
            }
            .articles-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
        <?php if ($success_msg): ?>
            <div class="flash-message">✅ <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div class="error-item">❌ <?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Informations Utilisateur -->
        <div class="user-info-card">
            <div class="user-header">
                <h1>👤 <?php echo htmlspecialchars($user_info['username']); ?> <span class="role-badge <?php echo $user_info['role']; ?>"><?php echo strtoupper($user_info['role']); ?></span></h1>
            </div>

            <div class="user-details">
                <div class="user-detail">
                    <div class="user-detail-label">Email</div>
                    <div class="user-detail-value"><?php echo htmlspecialchars($user_info['email']); ?></div>
                </div>
                <div class="user-detail">
                    <div class="user-detail-label">Solde</div>
                    <div class="user-detail-value solde"><?php echo number_format($user_info['solde'], 2); ?>€</div>
                </div>
                <div class="user-detail">
                    <div class="user-detail-label">Articles Publiés</div>
                    <div class="user-detail-value"><?php echo count($published_articles); ?></div>
                </div>
                <div class="user-detail">
                    <div class="user-detail-label">Membre depuis</div>
                    <div class="user-detail-value"><?php echo date('d/m/Y', strtotime($user_info['date_creation'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Modification de Profil (uniquement pour le compte personnel) -->
        <?php if ($is_own_account): ?>
            <div class="form-section">
                <h2>Modifier mon Profil</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user_info['email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <button type="submit">💾 Mettre à jour</button>
                </form>
            </div>

            <!-- Ajouter des fonds -->
            <div class="form-section">
                <h2>Ajouter des Fonds</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_funds">

                    <div class="form-group">
                        <label for="amount">Montant (€)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                    </div>

                    <button type="submit">✅ Ajouter des fonds</button>
                </form>
            </div>

            <!-- Factures -->
            <div class="section">
                <h2>📋 Mes Factures</h2>
                <?php if (count($invoices) > 0): ?>
                    <table class="invoices-table">
                        <thead>
                            <tr>
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Adresse</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td>#<?php echo $invoice['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($invoice['date_transaction'])); ?></td>
                                    <td><?php echo number_format($invoice['montant'], 2); ?>€</td>
                                    <td><?php echo htmlspecialchars($invoice['adresse_facturation']); ?>, <?php echo htmlspecialchars($invoice['code_postal_facturation']); ?> <?php echo htmlspecialchars($invoice['ville_facturation']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucune facture pour le moment</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Articles Publiés -->
        <div class="section">
            <h2><?php echo $is_own_account ? '📦 Mes Articles en Vente' : '📦 Articles de ' . htmlspecialchars($user_info['username']); ?></h2>
            <?php if (count($published_articles) > 0): ?>
                <div class="articles-grid">
                    <?php foreach ($published_articles as $article): ?>
                        <div class="article-card">
                            <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['nom']); ?>">
                            <div class="article-card-info">
                                <a href="detail.php?id=<?php echo $article['id']; ?>" class="article-card-title"><?php echo htmlspecialchars($article['nom']); ?></a>
                                <div class="article-card-price"><?php echo number_format($article['prix'], 2); ?>€</div>
                                <div class="article-card-stock">Stock: <?php echo $article['stock_disponible']; ?></div>
                                <?php if ($is_own_account): ?>
                                    <a href="edit.php?id=<?php echo $article['id']; ?>" style="font-size: 12px;">Modifier</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data"><?php echo $is_own_account ? 'Vous n\'avez pas encore publié d\'articles' : 'Cet utilisateur n\'a pas publié d\'articles'; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
