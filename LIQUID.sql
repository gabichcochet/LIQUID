-- =============================================
-- Base de données PHP E-Commerce Project
-- liquid.sql
-- =============================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS liquid;
USE liquid;

-- Suppression des tables si elles existent (pour réimportation)
DROP TABLE IF EXISTS Cart;
DROP TABLE IF EXISTS Invoice;
DROP TABLE IF EXISTS Stock;
DROP TABLE IF EXISTS Article;
DROP TABLE IF EXISTS User;

-- =============================================
-- Table: User
-- =============================================
CREATE TABLE User (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Mot de passe chiffré en bcrypt',
    email VARCHAR(150) NOT NULL UNIQUE,
    solde DECIMAL(10, 2) DEFAULT 0.00,
    photo_profil VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: Article
-- =============================================
CREATE TABLE Article (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    date_publication TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_auteur INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_auteur) REFERENCES User(id) ON DELETE CASCADE,
    INDEX idx_auteur (id_auteur),
    INDEX idx_date (date_publication)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: Cart
-- =============================================
CREATE TABLE Cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    id_article INT NOT NULL,
    quantite INT DEFAULT 1,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (id_article) REFERENCES Article(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_article (id_user, id_article),
    INDEX idx_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: Stock
-- =============================================
CREATE TABLE Stock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_article INT NOT NULL UNIQUE,
    quantite INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_article) REFERENCES Article(id) ON DELETE CASCADE,
    INDEX idx_article (id_article)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: Invoice
-- =============================================
CREATE TABLE Invoice (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    montant DECIMAL(10, 2) NOT NULL,
    adresse_facturation VARCHAR(255) NOT NULL,
    ville_facturation VARCHAR(100) NOT NULL,
    code_postal_facturation VARCHAR(10) NOT NULL,
    FOREIGN KEY (id_user) REFERENCES User(id) ON DELETE CASCADE,
    INDEX idx_user (id_user),
    INDEX idx_date (date_transaction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Insertion de données de test
-- =============================================

-- Insertion d'un utilisateur admin
-- Mot de passe: admin123 (chiffré en bcrypt)
INSERT INTO User (username, password, email, solde, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@php-exam.com', 1000.00, 'admin');

-- Insertion d'un utilisateur normal
-- Mot de passe: user123 (chiffré en bcrypt)
INSERT INTO User (username, password, email, solde, role) VALUES
('john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john@example.com', 500.00, 'user'),
('jane_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane@example.com', 750.00, 'user');


-- =============================================
-- Vues utiles pour l'application
-- =============================================

-- Vue pour afficher les articles avec le nom de l'auteur
CREATE OR REPLACE VIEW vue_articles_complet AS
SELECT 
    a.id,
    a.nom,
    a.description,
    a.prix,
    a.date_publication,
    a.image_url,
    a.id_auteur,
    u.username AS auteur,
    COALESCE(s.quantite, 0) AS stock_disponible
FROM Article a
INNER JOIN User u ON a.id_auteur = u.id
LEFT JOIN Stock s ON a.id = s.id_article;

-- Vue pour afficher le contenu du panier avec les détails
CREATE OR REPLACE VIEW vue_panier_details AS
SELECT 
    c.id,
    c.id_user,
    c.id_article,
    c.quantite,
    a.nom AS nom_article,
    a.prix AS prix_unitaire,
    (c.quantite * a.prix) AS prix_total,
    a.image_url,
    u.username
FROM Cart c
INNER JOIN Article a ON c.id_article = a.id
INNER JOIN User u ON c.id_user = u.id;

-- Vue pour les statistiques utilisateurs
CREATE OR REPLACE VIEW vue_stats_users AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.solde,
    u.role,
    COUNT(DISTINCT a.id) AS nb_articles_publies,
    COUNT(DISTINCT i.id) AS nb_achats,
    COALESCE(SUM(i.montant), 0) AS total_depense
FROM User u
LEFT JOIN Article a ON u.id = a.id_auteur
LEFT JOIN Invoice i ON u.id = i.id_user
GROUP BY u.id, u.username, u.email, u.solde, u.role;

-- =============================================
-- FIN DU SCRIPT
-- =============================================
