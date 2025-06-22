<?php
// nouvelEmprunt.php - Traitement des nouveaux emprunts
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $livre_id = (int)($_POST['livre_id'] ?? 0);
    $usager_id = (int)($_POST['usager_id'] ?? 0);
    $duree_jours = (int)($_POST['duree_jours'] ?? 14);
    
    if (!$livre_id || !$usager_id) {
        throw new Exception('Veuillez sélectionner un livre et un usager.');
    }
    
    $emprunt_id = creerEmprunt($pdo, $livre_id, $usager_id, $duree_jours);
    
    echo json_encode([
        'success' => true,
        'message' => 'Emprunt enregistré avec succès !',
        'emprunt_id' => $emprunt_id
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>