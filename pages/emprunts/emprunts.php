<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = 'Gestion des Emprunts';

// Gestion des paramètres
$tab = $_GET['tab'] ?? 'actifs';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

try {
    // Récupération des données pour les formulaires
    $livres_disponibles = getLivresDisponibles($pdo);
    $usagers = getUsagersActifs($pdo);
    
    // Récupération des emprunts avec pagination
    $result = getEmprunts($pdo, $tab, $search, $page, $per_page);
    $emprunts = $result['emprunts'];
    $total_emprunts = $result['total'];
    $total_pages = $result['total_pages'];
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des données : ' . $e->getMessage();
    $emprunts = [];
    $livres_disponibles = [];
    $usagers = [];
    $total_pages = 0;
}

include '../../includes/header.php';
?>

<link rel="stylesheet" href="../../assets/css/emprunts.css">

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
                                    <?php echo formatStatutEmprunt($emprunt['statut_calcule'], $emprunt['jours_retard']); ?>
                                </td>
                                <?php if ($tab !== 'historique'): ?>
                                    <td>
                                        <?php if ($emprunt['statut'] === 'en_cours'): ?>
                                            <button type="button" class="btn btn-sm btn-success btn-retour" 
                                                    data-emprunt-id="<?php echo $emprunt['id']; ?>"
                                                    data-livre-titre="<?php echo htmlspecialchars($emprunt['titre']); ?>"
                                                    data-emprunteur="<?php echo htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']); ?>">
                                                <i class="fas fa-undo"></i> Retourner
                                            </button>
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
            <div class="empty-state">
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
            <form id="nouvelEmpruntForm">
                <div class="modal-body">
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

<!-- Modal Retour -->
<div class="modal fade" id="retourModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo"></i> Confirmer le retour
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="retourForm">
                <div class="modal-body">
                    <input type="hidden" name="emprunt_id" id="retourEmpruntId">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Confirmation de retour</strong>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Livre :</strong> <span id="retourLivreTitre"></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Emprunteur :</strong> <span id="retourEmprunteur"></span>
                    </div>
                    
                    <p class="text-muted">
                        Êtes-vous sûr de vouloir marquer ce livre comme retourné ? 
                        Cette action mettra à jour la date de retour effective.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmer le retour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/emprunts.js"></script>

<?php include '../../includes/footer.php'; ?>