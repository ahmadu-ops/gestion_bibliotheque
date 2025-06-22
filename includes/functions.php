<?php
// functions.php - Fonctions pour la gestion des emprunts

/**
 * Vérifie la disponibilité d'un livre
 */
function verifierDisponibiliteLivre($pdo, $livre_id) {
    $stmt = $pdo->prepare("
        SELECT l.titre, l.exemplaires_total, 
               COALESCE(COUNT(e.id), 0) as empruntes_actuels
        FROM livres l
        LEFT JOIN emprunts e ON l.id = e.livre_id AND e.statut = 'en_cours'
        WHERE l.id = ?
        GROUP BY l.id
    ");
    $stmt->execute([$livre_id]);
    return $stmt->fetch();
}

/**
 * Crée un nouvel emprunt
 */
function creerEmprunt($pdo, $livre_id, $usager_id, $duree_jours = 14) {
    // Vérifier la disponibilité
    $livre_info = verifierDisponibiliteLivre($pdo, $livre_id);
    
    if (!$livre_info) {
        throw new Exception('Livre introuvable.');
    }
    
    if ($livre_info['empruntes_actuels'] >= $livre_info['exemplaires_total']) {
        throw new Exception('Ce livre n\'est plus disponible (tous les exemplaires sont empruntés).');
    }
    
    // Créer l'emprunt
    $date_retour = date('Y-m-d', strtotime("+$duree_jours days"));
    $stmt = $pdo->prepare("INSERT INTO emprunts (livre_id, usager_id, date_retour_prevue) VALUES (?, ?, ?)");
    $stmt->execute([$livre_id, $usager_id, $date_retour]);
    
    return $pdo->lastInsertId();
}

/**
 * Retourne un livre
 */
function retournerLivre($pdo, $emprunt_id) {
    $stmt = $pdo->prepare("UPDATE emprunts SET date_retour_effective = NOW(), statut = 'retourne' WHERE id = ?");
    $stmt->execute([$emprunt_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Récupère les livres disponibles
 */
function getLivresDisponibles($pdo) {
    $stmt = $pdo->query("
        SELECT l.id, l.titre, l.auteur, l.exemplaires_total,
               COALESCE(COUNT(e.id), 0) as empruntes_actuels,
               (l.exemplaires_total - COALESCE(COUNT(e.id), 0)) as disponibles
        FROM livres l
        LEFT JOIN emprunts e ON l.id = e.livre_id AND e.statut = 'en_cours'
        GROUP BY l.id
        HAVING disponibles > 0
        ORDER BY l.titre
    ");
    return $stmt->fetchAll();
}

/**
 * Récupère les usagers actifs
 */
function getUsagersActifs($pdo) {
    $stmt = $pdo->query("SELECT id, nom, prenom, email FROM usagers WHERE statut = 'actif' ORDER BY nom, prenom");
    return $stmt->fetchAll();
}

/**
 * Récupère les emprunts avec pagination et filtres
 */
function getEmprunts($pdo, $tab = 'actifs', $search = '', $page = 1, $per_page = 15) {
    $offset = ($page - 1) * $per_page;
    $where_conditions = [];
    $params = [];
    
    // Filtres selon l'onglet
    if ($tab === 'actifs') {
        $where_conditions[] = "e.statut = 'en_cours'";
    } elseif ($tab === 'retards') {
        $where_conditions[] = "e.statut = 'en_cours' AND e.date_retour_prevue < CURDATE()";
    } elseif ($tab === 'historique') {
        $where_conditions[] = "e.statut = 'retourne'";
    }
    
    // Recherche
    if (!empty($search)) {
        $where_conditions[] = "(l.titre LIKE ? OR l.auteur LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Compter le total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.id
        JOIN usagers u ON e.usager_id = u.id
        $where_clause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Récupérer les emprunts
    $stmt = $pdo->prepare("
        SELECT e.*, l.titre, l.auteur, u.nom, u.prenom, u.email,
               CASE 
                   WHEN e.statut = 'retourne' THEN 'retourne'
                   WHEN e.date_retour_prevue < CURDATE() THEN 'retard'
                   WHEN DATEDIFF(e.date_retour_prevue, CURDATE()) <= 2 THEN 'bientot'
                   ELSE 'actif'
               END as statut_calcule,
               DATEDIFF(CURDATE(), e.date_retour_prevue) as jours_retard
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.id
        JOIN usagers u ON e.usager_id = u.id
        $where_clause
        ORDER BY 
            CASE WHEN e.statut = 'en_cours' THEN e.date_retour_prevue ELSE e.date_retour_effective END DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $emprunts = $stmt->fetchAll();
    
    return [
        'emprunts' => $emprunts,
        'total' => $total,
        'total_pages' => ceil($total / $per_page)
    ];
}

/**
 * Formate le statut d'un emprunt pour l'affichage
 */
function formatStatutEmprunt($statut_calcule, $jours_retard = 0) {
    switch ($statut_calcule) {
        case 'retard':
            return '<span class="status-retard"><i class="fas fa-exclamation-triangle"></i> En retard (' . $jours_retard . ' j.)</span>';
        case 'bientot':
            return '<span class="status-bientot"><i class="fas fa-clock"></i> Bientôt dû</span>';
        case 'actif':
            return '<span class="status-actif"><i class="fas fa-check-circle"></i> En cours</span>';
        case 'retourne':
            return '<span class="status-actif"><i class="fas fa-check"></i> Retourné</span>';
        default:
            return '<span class="status-actif">Inconnu</span>';
    }
}
?>