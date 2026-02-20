# 🛒 LIQUID E-Commerce

Un site de e-commerce développé en **PHP pur** pour le projet final du module PHP.

## 📋 Spécifications du Projet

- **Langage** : PHP 8 (sans framework)
- **Base de données** : MySQL/MariaDB
- **Environnement** : XAMPP (Apache + PHP + MySQL)

## ✨ Fonctionnalités Principales

### Pages Publiques
- ✅ **Accueil** (`/`) - Affiche tous les articles
- ✅ **Détail** (`/detail`) - Affiche les détails d'un article

### Authentification
- ✅ **Connexion** (`/login`) - Formulaire de connexion
- ✅ **Inscription** (`/register`) - Création de compte + connexion automatique

### Pages Utilisateurs
- ✅ **Vendre** (`/sell`) - Créer un nouvel article à vendre
- ✅ **Panier** (`/cart`) - Gestion du panier de l'utilisateur
- ✅ **Confirmation** (`/cart/validate`) - Valider la commande et générer facture
- ✅ **Compte** (`/account`) - Voir ses articles, ses achats et modifier ses infos
- ✅ **Modifier** (`/edit`) - Modifier ou supprimer un article

### Pages Admin
- ✅ **Admin** (`/admin`) - Tableau de bord administrateur (gestion articles & utilisateurs)

## 🗄️ Structure de la Base de Données

```
liquid (base de données)
├── User (utilisateurs)
├── Article (articles à vendre)
├── Cart (panier)
├── Stock (stock des articles)
└── Invoice (factures)
```

## 🚀 Installation

### 1. Installer XAMPP
- Téléchargez XAMPP avec PHP 8 : https://www.apachefriends.org/
- Installez-le (par défaut : `C:\xampp` sur Windows)

### 2. Démarrer les services
- Ouvrez le **XAMPP Control Panel**
- Cliquez sur **Start** pour **Apache** et **MySQL**

### 3. Importer la base de données

#### Via phpMyAdmin (GUI)
1. Allez sur : `http://localhost/phpmyadmin`
2. Cliquez sur **"Nouveau"** pour créer une base `liquid`
3. Sélectionnez la base `liquid`
4. Allez dans l'onglet **"Importer"**
5. Sélectionnez le fichier `LIQUID.sql`
6. Cliquez sur **"Exécuter"**

#### Via Ligne de Commande (CLI)
```bash
mysql -u root -p < LIQUID.sql
# Laisser vide pour le mot de passe si demandé
```

### 4. Cloner le projet
```bash
cd C:\xampp\htdocs
git clone <votre-repo> LIQUID
cd LIQUID
```

### 5. Accéder au site
- Ouvrez votre navigateur
- Allez sur : `http://localhost/LIQUID`

## 📁 Structure du Projet

```
LIQUID/
├── config/
│   └── database.php          # Configuration et connexion DB
├── includes/
│   └── session.php           # Gestion sessions & fonctions utilitaires
├── pages/
│   ├── login.php             # Page de connexion
│   ├── register.php          # Page d'inscription
│   ├── logout.php            # Déconnexion
│   ├── detail.php            # Détail d'un article
│   ├── sell.php              # Créer un article
│   ├── cart.php              # Panier
│   ├── validate.php          # Validation du panier
│   ├── account.php           # Compte utilisateur
│   ├── edit.php              # Modifier/Supprimer article
│   └── admin.php             # Panel admin
├── index.php                 # Page d'accueil
├── LIQUID.sql                # Script de création de la BD
└── README.md                 # Ce fichier
```

## 🔒 Sécurité

- ✅ Mots de passe hachés en **bcrypt**
- ✅ Protection contre les injections SQL avec `escape()`
- ✅ Sessions PHP pour l'authentification
- ✅ Vérification des permissions (admin, propriétaire de l'article)
- ⚠️ À améliorer : CSRF tokens, prepared statements mysqli

## 📝 Notes de Développement

- Pas d'utilisation de framework (PHP pur uniquement)
- Pas de technologie autre que PHP pour le backend
- CSS inline pour la simplicité (peut être amélioré avec un fichier CSS externe)
- Base de données configurable dans `config/database.php`

## 🐛 Dépannage

### Erreur de connexion à la base de données
```
Erreur de connexion à la base de données : Access denied for user 'root'@'localhost'
```
**Solution** : Modifiez les identifiants dans `config/database.php`

### Les pages ne se chargent pas
- Vérifiez que Apache est démarré (XAMPP Control Panel)
- Vérifiez l'URL : `http://localhost/LIQUID` (pas `localhost:8888` sauf sur MAMP)

### "Cannot access User object after it has been closed"
- Cela signifie qu'il y a une session mysqli qui a été fermée
- Vérifiez qu'il n'y a qu'une seule connexion active

