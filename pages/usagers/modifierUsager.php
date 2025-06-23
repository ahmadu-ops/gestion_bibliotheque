<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $statut = $_POST['statut'] ?? 'actif';
    
    // Validation des données
    $erreur_validation = validerUsager($nom, $prenom, $email);
    
    if ($erreur_validation) {
        $response = ['success' => false, 'error' => $erreur_validation];
    } else {
        // Modifier l'usager
        $response = modifierUsager($pdo, $id, $nom, $prenom, $email, $telephone, $statut);
    }
    
    // Redirection avec message (sans l'argument modifier pour éviter la réouverture du modal)
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