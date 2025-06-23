<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    
    // Validation des données
    $erreur_validation = validerUsager($nom, $prenom, $email);
    
    if ($erreur_validation) {
        $response = ['success' => false, 'error' => $erreur_validation];
    } else {
        // Ajouter l'usager
        $response = ajouterUsager($pdo, $nom, $prenom, $email, $telephone);
    }
    
    // Redirection avec message
    $redirect_url = 'usagers.php?';
    if ($response['success']) {
        $redirect_url .= 'message=' . urlencode($response['message']);
    } else {
        $redirect_url .= 'error=' . urlencode($response['error']);
    }
    
    header('Location: ' . $redirect_url);
    exit;
}

// Si on arrive ici sans POST, rediriger vers la page principale
header('Location: usagers.php');
exit;
?>