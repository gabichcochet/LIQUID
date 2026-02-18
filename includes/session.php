<?php
/**
 * Fichier de session et fonctions utilitaires
 * File: includes/session.php
 */

session_start();

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /LIQUID/pages/login.php");
        exit;
    }
}

/**
 * Redirige vers l'accueil si l'utilisateur n'est pas admin
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: /LIQUID/index.php");
        exit;
    }
}

/**
 * Récupère l'utilisateur connecté
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['user_role'],
            'solde' => $_SESSION['solde']
        ];
    }
    return null;
}

/**
 * Déconnecte l'utilisateur
 */
function logout() {
    session_destroy();
    header("Location: /LIQUID/index.php");
    exit;
}

/**
 * Échappe une chaîne pour éviter les injections SQL
 */
function escape($string) {
    global $db;
    return $db->real_escape_string($string);
}

/**
 * Affiche un message de flash
 */
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Récupère et efface un message de flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>
