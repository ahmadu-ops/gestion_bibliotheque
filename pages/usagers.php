<?php
require_once '../config/database.php';

$page_title = 'Gestion des Usagers';
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        
        if (empty($nom) || empty($prenom) || empty($email)) {
            $error = 'Le nom, prénom et email sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'L\'adresse email n\'est pas valide.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO usagers (nom, prenom, email, telephone) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $telephone]);
                $message = 'Usager ajouté avec succès !';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Cette adresse email est déjà utilisée.';
                } else {
                    $error = 'Erreur lors de l\'ajout : ' . $e->getMessage();
                }
            }
        }
    }
    
    elseif ($action === 'modifier') {
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $statut = $_POST['statut'] ?? 'actif';
        
        if (empty($nom) || empty($prenom) || empty($email)) {
            $error = 'Le nom, prénom et email sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'L\'adresse email n\'est pas valide.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE usagers SET nom=?, prenom=?, email=?, telephone=?, statut=? WHERE id=?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $statut, $id]);
                $message = 'Usager modifié avec succès !';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Cette adresse email est déjà utilisée.';
                } else {
                    $error = 'Erreur lors de la modification : ' . $e->getMessage();
                }
            }
        }
    }
}

// Récupération des usagers avec pagination
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
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
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des usagers : ' . $e->getMessage();
    $usagers = [];
}

// Usager à modifier
$usager_modifier = null;
if (isset($_GET['modifier'])) {
    $id = (int)$_GET['modifier'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usagers WHERE id = ?");
        $stmt->execute([$id]);
        $usager_modifier = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Erreur lors de la récupération de l\'usager.';
    }
}

// Détails d'un usager
$usager_details = null;
$historique_emprunts = [];
if (isset($_GET['voir'])) {
    $id = (int)$_GET['voir'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usagers WHERE id = ?");
        $stmt->execute([$id]);
        $usager_details = $stmt->fetch();
        
        if ($usager_details) {
            // Récupérer l'historique des emprunts
            $stmt = $pdo->prepare("
                SELECT e.*, l.titre, l.auteur
                FROM emprunts e
                JOIN livres l ON e.livre_id = l.id
                WHERE e.usager_id = ?
                ORDER BY e.date_emprunt DESC
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $historique_emprunts = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la récupération des détails.';
    }
}

include '../includes/header.php';
?>

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
                                <td>
                                    <?php if ($usager['statut'] === 'actif'): ?>
                                        <span class="status-actif">Actif</span>
                                    <?php else: ?>
                                        <span class="status-retard">Suspendu</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usager['date_inscription'])); ?></td>
                                <td>
                                    <a href="?voir=<?php echo $usager['id']; ?>" 
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
            <form method="POST" onsubmit="return validerFormulaire(this)">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?php echo $usager_modifier ? 'modifier' : 'ajouter'; ?>">
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

<!-- Modal Détails Usager -->
<?php if ($usager_details): ?>
<div class="modal fade show" id="detailsUsagerModal" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($usager_details['prenom'] . ' ' . $usager_details['nom']); ?>
                </h5>
                <a href="usagers.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Email :</strong> <?php echo htmlspecialchars($usager_details['email']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Téléphone :</strong> <?php echo htmlspecialchars($usager_details['telephone'] ?? 'Non renseigné'); ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Statut :</strong> 
                        <?php if ($usager_details['statut'] === 'actif'): ?>
                            <span class="status-actif">Actif</span>
                        <?php else: ?>
                            <span class="status-retard">Suspendu</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Inscription :</strong> <?php echo date('d/m/Y', strtotime($usager_details['date_inscription'])); ?>
                    </div>
                </div>
                
                <hr>
                <h6><i class="fas fa-history"></i> Historique des emprunts</h6>
                
                <?php if (!empty($historique_emprunts)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Date emprunt</th>
                                    <th>Retour prévu</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historique_emprunts as $emprunt): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($emprunt['titre']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($emprunt['auteur']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?></td>
                                        <td>
                                            <?php
                                            if ($emprunt['date_retour_effective']) {
                                                echo '<span class="status-actif">Retourné</span>';
                                            } elseif (strtotime($emprunt['date_retour_prevue']) < time()) {
                                                echo '<span class="status-retard">En retard</span>';
                                            } else {
                                                echo '<span class="status-bientot">En cours</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Aucun emprunt enregistré.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="usagers.php" class="btn btn-secondary">Fermer</a>
                <a href="?modifier=<?php echo $usager_details['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>

<script>
// Ouvrir automatiquement le modal si on est en mode modification
<?php if ($usager_modifier): ?>
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('ajouterUsagerModal'));
    modal.show();
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>