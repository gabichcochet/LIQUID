<?php
/**
 * Page Administration - /admin
 * Permet de gérer les articles et les utilisateurs
 * File: pages/admin.php
 */

require_once '../config/database.php';
require_once '../includes/session.php';

requireAdmin();

$user_id = $_SESSION['user_id'];
$errors = [];

// Traiter les actions de suppression/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'delete_article') {
            $article_id = intval($_POST['article_id'] ?? 0);

            if ($article_id > 0) {
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
            }

            header("Location: admin.php?tab=articles");
            exit;
        } elseif ($action === 'delete_user') {
            $delete_user_id = intval($_POST['user_id'] ?? 0);

            if ($delete_user_id > 0 && $delete_user_id !== $user_id) {
                // Supprimer les données associées
                $stmt = $db->prepare("DELETE FROM Invoice WHERE id_user = ?");
                $stmt->bind_param("i", $delete_user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $db->prepare("DELETE FROM Cart WHERE id_user = ?");
                $stmt->bind_param("i", $delete_user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $db->prepare("DELETE FROM Article WHERE id_auteur = ?");
                $stmt->bind_param("i", $delete_user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $db->prepare("DELETE FROM User WHERE id = ?");
                $stmt->bind_param("i", $delete_user_id);
                $stmt->execute();
                $stmt->close();

                setFlash("Utilisateur supprimé avec succès", "success");
            }

            header("Location: admin.php?tab=users");
            exit;
        }
    }
}

$flash = getFlash();
$tab = $_GET['tab'] ?? 'articles';

// Récupérer tous les articles
$articles = [];
if ($tab === 'articles') {
    $result = $db->query("
        SELECT a.id, a.nom, a.prix, a.date_publication, u.username,
               COALESCE(s.quantite, 0) AS stock_disponible
        FROM Article a
        INNER JOIN User u ON a.id_auteur = u.id
        LEFT JOIN Stock s ON a.id = s.id_article
        ORDER BY a.date_publication DESC
    ");
    $articles = $result->fetch_all(MYSQLI_ASSOC);
}

// Récupérer tous les utilisateurs
$users = [];
if ($tab === 'users') {
    $result = $db->query("
        SELECT id, username, email, solde, role, date_creation
        FROM User
        ORDER BY date_creation DESC
    ");
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - LIQUID</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab-button {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: 0.3s;
        }
        .tab-button.active {
            color: #333;
            border-bottom-color: #3498db;
        }
        .tab-button:hover {
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
        .table-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        a, button {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }
        .edit-btn {
            background: #f39c12;
            color: white;
        }
        .edit-btn:hover {
            background: #e67e22;
        }
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .role-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
        }
        .role-badge.admin {
            background: #e74c3c;
        }
        .no-data {
            padding: 40px;
            text-align: center;
            color: #666;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
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
            <a href="account.php">Mon Compte</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>

    <div class="container">
        <h1>⚙️ Tableau d'Administration</h1>

        <?php if ($flash): ?>
            <div class="flash-message">✅ <?php echo $flash['message']; ?></div>
        <?php endif; ?>

        <!-- Onglets -->
        <div class="tabs">
            <button class="tab-button <?php echo $tab === 'articles' ? 'active' : ''; ?>" onclick="location.href='admin.php?tab=articles'">
                📦 Articles
            </button>
            <button class="tab-button <?php echo $tab === 'users' ? 'active' : ''; ?>" onclick="location.href='admin.php?tab=users'">
                👥 Utilisateurs
            </button>
        </div>

        <!-- Articles -->
        <?php if ($tab === 'articles'): ?>
            <div class="table-section">
                <h2>Gestion des Articles</h2>

                <?php
                $total_articles = count($articles);
                $total_stock = 0;
                foreach ($articles as $article) {
                    $total_stock += $article['stock_disponible'];
                }
                ?>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">Total Articles</div>
                        <div class="stat-value"><?php echo $total_articles; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Stock Total</div>
                        <div class="stat-value"><?php echo $total_stock; ?></div>
                    </div>
                </div>

                <?php if (count($articles) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Article</th>
                                <th>Vendeur</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Date Publication</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($article['nom']); ?></td>
                                    <td><a href="account.php?user_id=<?php echo $article['id_auteur']; ?>"><?php echo htmlspecialchars($article['username']); ?></a></td>
                                    <td><?php echo number_format($article['prix'], 2); ?>€</td>
                                    <td><?php echo $article['stock_disponible']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($article['date_publication'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="detail.php?id=<?php echo $article['id']; ?>" class="edit-btn">Voir</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                                <input type="hidden" name="action" value="delete_article">
                                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                <button type="submit" class="delete-btn">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun article</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Utilisateurs -->
        <?php if ($tab === 'users'): ?>
            <div class="table-section">
                <h2>Gestion des Utilisateurs</h2>

                <?php
                $total_users = count($users);
                $total_solde = 0;
                foreach ($users as $u) {
                    $total_solde += $u['solde'];
                }
                ?>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">Total Utilisateurs</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Solde Total</div>
                        <div class="stat-value"><?php echo number_format($total_solde, 2); ?>€</div>
                    </div>
                </div>

                <?php if (count($users) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Solde</th>
                                <th>Membre depuis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><span class="role-badge <?php echo $u['role']; ?>"><?php echo strtoupper($u['role']); ?></span></td>
                                    <td><?php echo number_format($u['solde'], 2); ?>€</td>
                                    <td><?php echo date('d/m/Y', strtotime($u['date_creation'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="account.php?user_id=<?php echo $u['id']; ?>" class="edit-btn">Voir</a>
                                            <?php if ($u['id'] !== $user_id): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="delete-btn">Supprimer</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">Aucun utilisateur</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
