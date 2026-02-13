<?php
/**
 * Configuration et connexion à la base de données
 * File: config/database.php
 */

// Informations de connexion
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "liquid";

// Connexion à la base de données
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Vérification de la connexion
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
}

// Définir le charset en UTF-8
$mysqli->set_charset("utf8mb4");

// Variable globale pour accès à la base de données
$db = $mysqli;
?>
