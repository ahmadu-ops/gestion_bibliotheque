<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = 'Gestion des Usagers';

// Récupération des messages depuis l'URL
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Paramètres de recherche et pagination
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

// Récupération des usagers
$result = getUsagers($pdo, $search, $page, $per_page);
$usagers = $result['usagers'];
$total_usagers = $result['total'];
$total_pages = $result['total_pages'];

// Gestion des erreurs de récupération
if (!$result['success']) {
    $error = $result['error'];
}

// Usager à modifier
$usager_modifier = null;
if (isset($_GET['modifier'])) {
    $id = (int)$_GET['modifier'];
    $usager_modifier = getUsagerById($pdo, $id);
    if (!$usager_modifier) {
        $error = 'Usager non trouvé.';
    }
}

include '../../includes/header.php';
?>

<!-- Inclusion des styles CSS -->
<link rel="stylesheet" href="../../assets/css/usagers.css">

<div class="row mb-4">
    <div class="col-md-8">
        <h1><i class="fas fa-users"></i> Gestion des Usagers</h1>
        <p class="text-muted">Total : <?php echo $total_usagers; ?> usager(s)</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ajouterUsagerModal">
            <i class="fas fa-user-plus"></i> Ajouter un usager
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

<!-- Barre de recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher par nom, prénom ou email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <?php if ($search): ?>
                    <a href="usagers.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Effacer
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Liste des usagers -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($usagers)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Emprunts actifs</th>
                            <th>Statut</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usagers as $usager): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usager['prenom'] . ' ' . $usager['nom']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($usager['email']); ?></td>
                                <td><?php echo htmlspecialchars($usager['telephone'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($usager['emprunts_actifs'] > 0): ?>
                                        <span class="badge bg-warning">
                                            <?php echo $usager['emprunts_actifs']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getBadgeStatut($usager['statut']); ?></td>
                                <td><?php echo formaterDate($usager['date_inscription']); ?></td>
                                <td>
                                    <a href="voirUsager.php?id=<?php echo $usager['id']; ?>" 
                                       class="btn btn-sm btn-info btn-action">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?modifier=<?php echo $usager['id']; ?>" 
                                       class="btn btn-sm btn-warning btn-action">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5>Aucun usager trouvé</h5>
                <p>Commencez par ajouter des usagers à votre système.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajouter/Modifier Usager -->
<div class="modal fade" id="ajouterUsagerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo $usager_modifier ? 'Modifier l\'usager' : 'Ajouter un usager'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo $usager_modifier ? 'modifierUsager.php' : 'ajouterUsager.php'; ?>" onsubmit="return validerFormulaire(this)">
                <div class="modal-body">
                    <?php if ($usager_modifier): ?>
                        <input type="hidden" name="id" value="<?php echo $usager_modifier['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required
                               value="<?php echo $usager_modifier ? htmlspecialchars($usager_modifier['prenom']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?php echo $usager_modifier ? htmlspecialchars($usager_modifier['nom']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $usager_modifier ? htmlspecialchars($usager_modifier['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone"
                               value="<?php echo $usager_modifier ? htmlspecialchars($usager_modifier['telephone']) : ''; ?>">
                    </div>
                    
                    <?php if ($usager_modifier): ?>
                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-control" id="statut" name="statut">
                                <option value="actif" <?php echo $usager_modifier['statut'] === 'actif' ? 'selected' : ''; ?>>Actif</option>
                                <option value="suspendu" <?php echo $usager_modifier['statut'] === 'suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $usager_modifier ? 'Modifier' : 'Ajouter'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inclusion du JavaScript -->
<script src="../../assets/js/usagers.js"></script>

<?php include '../../includes/footer.php'; ?>