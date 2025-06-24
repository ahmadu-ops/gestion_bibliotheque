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

<?php
// Fonctions pour la gestion des usagers

/**
 * Valide les données d'un usager
 */
function validerUsager($nom, $prenom, $email) {
    if (empty($nom) || empty($prenom) || empty($email)) {
        return 'Le nom, prénom et email sont obligatoires.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'L\'adresse email n\'est pas valide.';
    }
    
    return null;
}

/**
 * Ajoute un nouvel usager
 */
function ajouterUsager($pdo, $nom, $prenom, $email, $telephone = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO usagers (nom, prenom, email, telephone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $telephone]);
        return ['success' => true, 'message' => 'Usager ajouté avec succès !'];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'error' => 'Cette adresse email est déjà utilisée.'];
        }
        return ['success' => false, 'error' => 'Erreur lors de l\'ajout : ' . $e->getMessage()];
    }
}

/**
 * Modifie un usager existant
 */
function modifierUsager($pdo, $id, $nom, $prenom, $email, $telephone = '', $statut = 'actif') {
    try {
        $stmt = $pdo->prepare("UPDATE usagers SET nom=?, prenom=?, email=?, telephone=?, statut=? WHERE id=?");
        $stmt->execute([$nom, $prenom, $email, $telephone, $statut, $id]);
        return ['success' => true, 'message' => 'Usager modifié avec succès !'];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'error' => 'Cette adresse email est déjà utilisée.'];
        }
        return ['success' => false, 'error' => 'Erreur lors de la modification : ' . $e->getMessage()];
    }
}

/**
 * Récupère les usagers avec pagination et recherche
 */
function getUsagers($pdo, $search = '', $page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    try {
        $where_clause = '';
        $params = [];
        
        if (!empty($search)) {
            $where_clause = "WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        // Compter le total
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usagers $where_clause");
        $stmt->execute($params);
        $total_usagers = $stmt->fetch()['total'];
        $total_pages = ceil($total_usagers / $per_page);
        
        // Récupérer les usagers avec le nombre d'emprunts actifs
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COALESCE(e.emprunts_actifs, 0) as emprunts_actifs
            FROM usagers u
            LEFT JOIN (
                SELECT usager_id, COUNT(*) as emprunts_actifs 
                FROM emprunts 
                WHERE statut = 'en_cours' 
                GROUP BY usager_id
            ) e ON u.id = e.usager_id
            $where_clause
            ORDER BY u.nom, u.prenom
            LIMIT $per_page OFFSET $offset
        ");
        $stmt->execute($params);
        $usagers = $stmt->fetchAll();
        
        return [
            'success' => true,
            'usagers' => $usagers,
            'total' => $total_usagers,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Erreur lors de la récupération des usagers : ' . $e->getMessage(),
            'usagers' => [],
            'total' => 0,
            'total_pages' => 0,
            'current_page' => 1
        ];
    }
}

/**
 * Récupère un usager par son ID
 */
function getUsagerById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usagers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère l'historique des emprunts d'un usager
 */
function getHistoriqueEmprunts($pdo, $usager_id, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, l.titre, l.auteur
            FROM emprunts e
            JOIN livres l ON e.livre_id = l.id
            WHERE e.usager_id = ?
            ORDER BY e.date_emprunt DESC
            LIMIT ?
        ");
        $stmt->execute([$usager_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Formate une date pour l'affichage
 */
function formaterDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Retourne le badge de statut HTML
 */
function getBadgeStatut($statut) {
    if ($statut === 'actif') {
        return '<span class="status-actif">Actif</span>';
    } else {
        return '<span class="status-retard">Suspendu</span>';
    }
}

/**
 * Retourne le statut d'un emprunt
 */
function getStatutEmprunt($emprunt) {
    if ($emprunt['date_retour_effective']) {
        return '<span class="status-actif">Retourné</span>';
    } elseif (strtotime($emprunt['date_retour_prevue']) < time()) {
        return '<span class="status-retard">En retard</span>';
    } else {
        return '<span class="status-bientot">En cours</span>';
    }
}

/**
 * Ajouter un nouveau livre
 */
function ajouterLivre($pdo, $titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id) {
    if (empty($titre) || empty($auteur)) {
        return ['success' => false, 'message' => 'Le titre et l\'auteur sont obligatoires.'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, isbn, date_publication, exemplaires_total, exemplaires_disponibles, categorie_id) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $auteur, $isbn, $date_publication, $exemplaires, $exemplaires, $categorie_id]);
        return ['success' => true, 'message' => 'Livre ajouté avec succès !'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout : ' . $e->getMessage()];
    }
}

/**
 * Modifier un livre existant
 */
function modifierLivre($pdo, $id, $titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id) {
    if (empty($titre) || empty($auteur)) {
        return ['success' => false, 'message' => 'Le titre et l\'auteur sont obligatoires.'];
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE livres SET titre=?, auteur=?, isbn=?, date_publication=?, exemplaires_total=?, categorie_id=? WHERE id=?");
        $stmt->execute([$titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id, $id]);
        return ['success' => true, 'message' => 'Livre modifié avec succès !'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la modification : ' . $e->getMessage()];
    }
}

/**
 * Supprimer un livre
 */
function supprimerLivre($pdo, $id) {
    try {
        // Vérifier s'il y a des emprunts en cours
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM emprunts WHERE livre_id = ? AND statut = 'en_cours'");
        $stmt->execute([$id]);
        $emprunts_actifs = $stmt->fetch()['count'];
        
        if ($emprunts_actifs > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer ce livre car il est actuellement emprunté.'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM livres WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => 'Livre supprimé avec succès !'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()];
    }
}

/**
 * Ajouter une nouvelle catégorie
 */
function ajouterCategorie($pdo, $nom_categorie, $description) {
    if (empty($nom_categorie)) {
        return ['success' => false, 'message' => 'Le nom de la catégorie est obligatoire.'];
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
        $stmt->execute([$nom_categorie, $description]);
        return ['success' => true, 'message' => 'Catégorie ajoutée avec succès !'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de la catégorie : ' . $e->getMessage()];
    }
}

/**
 * Récupérer toutes les catégories
 */
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupérer un livre par son ID
 */
function getLivreById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Récupérer les livres avec pagination et filtres
 */
function getLivres($pdo, $search = '', $categorie_filter = '', $disponible_filter = '', $page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    try {
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(l.titre LIKE ? OR l.auteur LIKE ? OR l.isbn LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        
        if (!empty($categorie_filter)) {
            $where_conditions[] = "l.categorie_id = ?";
            $params[] = $categorie_filter;
        }
        
        if ($disponible_filter === 'disponible') {
            $where_conditions[] = "(l.exemplaires_total - COALESCE(e.empruntes, 0)) > 0";
        } elseif ($disponible_filter === 'emprunte') {
            $where_conditions[] = "(l.exemplaires_total - COALESCE(e.empruntes, 0)) = 0";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Compter le total
        $count_query = "
            SELECT COUNT(DISTINCT l.id) as total 
            FROM livres l
            LEFT JOIN categories c ON l.categorie_id = c.id
            LEFT JOIN (
                SELECT livre_id, COUNT(*) as empruntes 
                FROM emprunts 
                WHERE statut = 'en_cours' 
                GROUP BY livre_id
            ) e ON l.id = e.livre_id
            $where_clause
        ";
        $stmt = $pdo->prepare($count_query);
        $stmt->execute($params);
        $total_livres = $stmt->fetch()['total'];
        
        // Récupérer les livres
        $query = "
            SELECT l.*, 
                   c.nom as categorie_nom,
                   (l.exemplaires_total - COALESCE(e.empruntes, 0)) as exemplaires_disponibles
            FROM livres l
            LEFT JOIN categories c ON l.categorie_id = c.id
            LEFT JOIN (
                SELECT livre_id, COUNT(*) as empruntes 
                FROM emprunts 
                WHERE statut = 'en_cours' 
                GROUP BY livre_id
            ) e ON l.id = e.livre_id
            $where_clause
            ORDER BY l.titre
            LIMIT $per_page OFFSET $offset
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $livres = $stmt->fetchAll();
        
        return [
            'livres' => $livres,
            'total' => $total_livres,
            'total_pages' => ceil($total_livres / $per_page)
        ];
        
    } catch (PDOException $e) {
        return [
            'livres' => [],
            'total' => 0,
            'total_pages' => 0,
            'error' => 'Erreur lors de la récupération des livres : ' . $e->getMessage()
        ];
    }
}