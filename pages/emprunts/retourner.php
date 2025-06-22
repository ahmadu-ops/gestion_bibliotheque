<?php
// retourner.php - Traitement des retours
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $emprunt_id = (int)($_POST['emprunt_id'] ?? 0);
    
    if (!$emprunt_id) {
        throw new Exception('ID d\'emprunt manquant.');
    }
    
    $success = retournerLivre($pdo, $emprunt_id);
    
    if (!$success) {
        throw new Exception('Emprunt introuvable ou déjà retourné.');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Livre retourné avec succès !'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>