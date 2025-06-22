<?php
require_once '../config/database.php';

$page_title = 'Gestion des Emprunts';
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'emprunter') {
        $livre_id = (int)($_POST['livre_id'] ?? 0);
        $usager_id = (int)($_POST['usager_id'] ?? 0);
        $duree_jours = (int)($_POST['duree_jours'] ?? 14);
        
        if (!$livre_id || !$usager_id) {
            $error = 'Veuillez sélectionner un livre et un usager.';
        } else {
            try {
                // Vérifier la disponibilité du livre
                $stmt = $pdo->prepare("
                    SELECT l.titre, l.exemplaires_total, 
                           COALESCE(COUNT(e.id), 0) as empruntes_actuels
                    FROM livres l
                    LEFT JOIN emprunts e ON l.id = e.livre_id AND e.statut = 'en_cours'
                    WHERE l.id = ?
                    GROUP BY l.id
                ");
                $stmt->execute([$livre_id]);
                $livre_info = $stmt->fetch();
                
                if (!$livre_info) {
                    $error = 'Livre introuvable.';
                } elseif ($livre_info['empruntes_actuels'] >= $livre_info['exemplaires_total']) {
                    $error = 'Ce livre n\'est plus disponible (tous les exemplaires sont empruntés).';
                } else {
                    // Créer l'emprunt
                    $date_retour = date('Y-m-d', strtotime("+$duree_jours days"));
                    $stmt = $pdo->prepare("INSERT INTO emprunts (livre_id, usager_id, date_retour_prevue) VALUES (?, ?, ?)");
                    $stmt->execute([$livre_id, $usager_id, $date_retour]);
                    $message = 'Emprunt enregistré avec succès !';
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'retourner') {
        $emprunt_id = (int)($_POST['emprunt_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("UPDATE emprunts SET date_retour_effective = NOW(), statut = 'retourne' WHERE id = ?");
            $stmt->execute([$emprunt_id]);
            $message = 'Livre retourné avec succès !';
        } catch (PDOException $e) {
            $error = 'Erreur lors du retour : ' . $e->getMessage();
        }
    }
}

// Récupération des données pour les formulaires
try {
    // Livres disponibles
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
    $livres_disponibles = $stmt->fetchAll();
    
    // Usagers actifs
    $stmt = $pdo->query("SELECT id, nom, prenom, email FROM usagers WHERE statut = 'actif' ORDER BY nom, prenom");
    $usagers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des données : ' . $e->getMessage();
}

// Gestion des onglets
$tab = $_GET['tab'] ?? 'actifs';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

try {
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
    $total_emprunts = $stmt->fetch()['total'];
    $total_pages = ceil($total_emprunts / $per_page);
    
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
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des emprunts : ' . $e->getMessage();
    $emprunts = [];
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><i class="fas fa-exchange-alt"></i> Gestion des Emprunts</h1>
        <p class="text-muted">Suivi des prêts et retours</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#nouvelEmpruntModal">
            <i class="fas fa-plus"></i> Nouvel Emprunt
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Onglets -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'actifs' ? 'active' : ''; ?>" 
           href="?tab=actifs">
            <i class="fas fa-clock"></i> Emprunts Actifs
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'retards' ? 'active' : ''; ?>" 
           href="?tab=retards">
            <i class="fas fa-exclamation-triangle text-danger"></i> Retards
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'historique' ? 'active' : ''; ?>" 
           href="?tab=historique">
            <i class="fas fa-history"></i> Historique
        </a>
    </li>
</ul>

<!-- Barre de recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher par livre, auteur ou emprunteur..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <?php if ($search): ?>
                    <a href="?tab=<?php echo $tab; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Effacer
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Liste des emprunts -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($emprunts)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Livre</th>
                            <th>Emprunteur</th>
                            <th>Date d'emprunt</th>
                            <th>Retour prévu</th>
                            <?php if ($tab === 'historique'): ?>
                                <th>Retour effectif</th>
                            <?php endif; ?>
                            <th>Statut</th>
                            <?php if ($tab !== 'historique'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emprunts as $emprunt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($emprunt['titre']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($emprunt['auteur']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($emprunt['email']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?></td>
                                <?php if ($tab === 'historique'): ?>
                                    <td><?php echo $emprunt['date_retour_effective'] ? date('d/m/Y', strtotime($emprunt['date_retour_effective'])) : '-'; ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    switch ($emprunt['statut_calcule']) {
                                        case 'retard':
                                            echo '<span class="status-retard"><i class="fas fa-exclamation-triangle"></i> En retard (' . $emprunt['jours_retard'] . ' j.)</span>';
                                            break;
                                        case 'bientot':
                                            echo '<span class="status-bientot"><i class="fas fa-clock"></i> Bientôt dû</span>';
                                            break;
                                        case 'actif':
                                            echo '<span class="status-actif"><i class="fas fa-check-circle"></i> En cours</span>';
                                            break;
                                        case 'retourne':
                                            echo '<span class="status-actif"><i class="fas fa-check"></i> Retourné</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <?php if ($tab !== 'historique'): ?>
                                    <td>
                                        <?php if ($emprunt['statut'] === 'en_cours'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="retourner">
                                                <input type="hidden" name="emprunt_id" value="<?php echo $emprunt['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Confirmer le retour de ce livre ?')">
                                                    <i class="fas fa-undo"></i> Retourner
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?tab=<?php echo $tab; ?>&page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                <h5>Aucun emprunt trouvé</h5>
                <p>
                    <?php if ($tab === 'actifs'): ?>
                        Aucun emprunt en cours.
                    <?php elseif ($tab === 'retards'): ?>
                        Aucun retard, félicitations !
                    <?php else: ?>
                        Aucun historique d'emprunt.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nouvel Emprunt -->
<div class="modal fade" id="nouvelEmpruntModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Nouvel Emprunt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return validerFormulaire(this)">
                <div class="modal-body">
                    <input type="hidden" name="action" value="emprunter">
                    
                    <div class="mb-3">
                        <label for="livre_id" class="form-label">Livre à emprunter *</label>
                        <select class="form-select" name="livre_id" id="livre_id" required>
                            <option value="">Sélectionner un livre...</option>
                            <?php foreach ($livres_disponibles as $livre): ?>
                                <option value="<?php echo $livre['id']; ?>">
                                    <?php echo htmlspecialchars($livre['titre']); ?> 
                                    - <?php echo htmlspecialchars($livre['auteur']); ?>
                                    (<?php echo $livre['disponibles']; ?> dispo.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usager_id" class="form-label">Emprunteur *</label>
                        <select class="form-select" name="usager_id" id="usager_id" required>
                            <option value="">Sélectionner un usager...</option>
                            <?php foreach ($usagers as $usager): ?>
                                <option value="<?php echo $usager['id']; ?>">
                                    <?php echo htmlspecialchars($usager['prenom'] . ' ' . $usager['nom']); ?>
                                    (<?php echo htmlspecialchars($usager['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="duree_jours" class="form-label">Durée de l'emprunt</label>
                        <select class="form-select" name="duree_jours" id="duree_jours">
                            <option value="7">1 semaine</option>
                            <option value="14" selected>2 semaines</option>
                            <option value="21">3 semaines</option>
                            <option value="30">1 mois</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Information :</strong> La date de retour sera automatiquement calculée à partir d'aujourd'hui.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Enregistrer l'emprunt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ouvrir automatiquement le modal si demandé
<?php if (isset($_GET['action']) && $_GET['action'] === 'nouveau'): ?>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('nouvelEmpruntModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>