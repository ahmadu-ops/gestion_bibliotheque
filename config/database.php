<?php
/**
 * Configuration de la base de données
 * Gestion de la connexion PDO avec gestion d'erreurs
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_bibliotheque');
define('DB_USER', 'root');
define('DB_PASS', '   ');
define('DB_CHARSET', 'utf8mb4');

try {
    // Création de la connexion PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // En production, ne pas afficher les détails de l'erreur
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Contactez l'administrateur.");
}

/**
 * Fonction utilitaire pour exécuter une requête simple
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        throw new Exception("Erreur lors de l'opération sur la base de données.");
    }
}
?>