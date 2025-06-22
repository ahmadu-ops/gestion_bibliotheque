<?php
require_once '../config/database.php';

$page_title = 'Gestion des Livres';
$message = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter') {
        $titre = trim($_POST['titre'] ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $date_publication = $_POST['date_publication'] ?? null;
        $exemplaires = (int)($_POST['exemplaires'] ?? 1);
        $categorie_id = $_POST['categorie_id'] ?? null;
        
        if (empty($titre) || empty($auteur)) {
            $error = 'Le titre et l\'auteur sont obligatoires.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, isbn, date_publication, exemplaires_total, exemplaires_disponibles, categorie_id) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titre, $auteur, $isbn, $date_publication, $exemplaires, $exemplaires, $categorie_id]);
                $message = 'Livre ajouté avec succès !';
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout : ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'modifier') {
        $id = (int)($_POST['id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $auteur = trim($_POST['auteur'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $date_publication = $_POST['date_publication'] ?? null;
        $exemplaires = (int)($_POST['exemplaires'] ?? 1);
        $categorie_id = $_POST['categorie_id'] ?? null;
        
        if (empty($titre) || empty($auteur)) {
            $error = 'Le titre et l\'auteur sont obligatoires.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE livres SET titre=?, auteur=?, isbn=?, date_publication=?, exemplaires_total=?, categorie_id=? WHERE id=?");
                $stmt->execute([$titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id, $id]);
                $message = 'Livre modifié avec succès !';
            } catch (PDOException $e) {
                $error = 'Erreur lors de la modification : ' . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'ajouter_categorie') {
        $nom_categorie = trim($_POST['nom_categorie'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($nom_categorie)) {
            $error = 'Le nom de la catégorie est obligatoire.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
                $stmt->execute([$nom_categorie, $description]);
                $message = 'Catégorie ajoutée avec succès !';
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout de la catégorie : ' . $e->getMessage();
            }
        }
    }
}

// Suppression
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    try {
        // Vérifier s'il y a des emprunts en cours
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM emprunts WHERE livre_id = ? AND statut = 'en_cours'");
        $stmt->execute([$id]);
        $emprunts_actifs = $stmt->fetch()['count'];
        
        if ($emprunts_actifs > 0) {
            $error = 'Impossible de supprimer ce livre car il est actuellement emprunté.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM livres WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Livre supprimé avec succès !';
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}

// Récupération des catégories
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Récupération des livres avec pagination et filtres
$search = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$disponible_filter = $_GET['disponible'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
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
    $total_pages = ceil($total_livres / $per_page);
    
    // Récupérer les livres avec les informations d'emprunt et catégorie
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
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des livres : ' . $e->getMessage();
    $livres = [];
}

// Livre à modifier
$livre_modifier = null;
if (isset($_GET['modifier'])) {
    $id = (int)$_GET['modifier'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = ?");
        $stmt->execute([$id]);
        $livre_modifier = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Erreur lors de la récupération du livre.';
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><i class="fas fa-book"></i> Gestion des Livres</h1>
        <p class="text-muted">Total : <?php echo $total_livres; ?> livre(s)</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#ajouterCategorieModal">
            <i class="fas fa-tags"></i> Catégorie
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#ajouterLivreModal">
            <i class="fas fa-plus"></i> Ajouter un livre
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

<!-- Filtres et recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Recherche</label>
                <input type="text" class="form-control" name="search" 
                       placeholder="Titre, auteur ou ISBN..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Catégorie</label>
                <select class="form-select" name="categorie">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $categorie_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Disponibilité</label>
                <select class="form-select" name="disponible">
                    <option value="">Tous</option>
                    <option value="disponible" <?php echo $disponible_filter === 'disponible' ? 'selected' : ''; ?>>Disponibles</option>
                    <option value="emprunte" <?php echo $disponible_filter === 'emprunte' ? 'selected' : ''; ?>>Tous empruntés</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <?php if ($search || $categorie_filter || $disponible_filter): ?>
                    <a href="livres.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Liste des livres -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($livres)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Catégorie</th>
                            <th>ISBN</th>
                            <th>Publication</th>
                            <th>Disponibilité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livres as $livre): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($livre['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($livre['auteur']); ?></td>
                                <td>
                                    <?php if ($livre['categorie_nom']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($livre['categorie_nom']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($livre['isbn'] ?? '-'); ?></td>
                                <td><?php echo $livre['date_publication'] ? date('Y', strtotime($livre['date_publication'])) : '-'; ?></td>
                                <td>
                                    <?php if ($livre['exemplaires_disponibles'] > 0): ?>
                                        <span class="status-disponible">
                                            <i class="fas fa-check-circle"></i> 
                                            <?php echo $livre['exemplaires_disponibles']; ?>/<?php echo $livre['exemplaires_total']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-emprunte">
                                            <i class="fas fa-times-circle"></i> Tous empruntés
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?modifier=<?php echo $livre['id']; ?>" 
                                       class="btn btn-sm btn-warning btn-action">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?supprimer=<?php echo $livre['id']; ?>" 
                                       class="btn btn-sm btn-danger btn-action"
                                       onclick="return confirmerSuppression('le livre', '<?php echo htmlspecialchars($livre['titre']); ?>')">
                                        <i class="fas fa-trash"></i>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&categorie=<?php echo urlencode($categorie_filter); ?>&disponible=<?php echo urlencode($disponible_filter); ?>">Précédent</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&categorie=<?php echo urlencode($categorie_filter); ?>&disponible=<?php echo urlencode($disponible_filter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&categorie=<?php echo urlencode($categorie_filter); ?>&disponible=<?php echo urlencode($disponible_filter); ?>">Suivant</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-book-open fa-3x mb-3"></i>
                <h5>Aucun livre trouvé</h5>
                <p>Commencez par ajouter des livres à votre bibliothèque.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ajouter/Modifier Livre -->
<div class="modal fade" id="ajouterLivreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo $livre_modifier ? 'Modifier le livre' : 'Ajouter un livre'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return validerFormulaire(this)">
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?php echo $livre_modifier ? 'modifier' : 'ajouter'; ?>">
                    <?php if ($livre_modifier): ?>
                        <input type="hidden" name="id" value="<?php echo $livre_modifier['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Titre *</label>
                        <input type="text" class="form-control" name="titre" required 
                               value="<?php echo $livre_modifier ? htmlspecialchars($livre_modifier['titre']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Auteur *</label>
                        <input type="text" class="form-control" name="auteur" required 
                               value="<?php echo $livre_modifier ? htmlspecialchars($livre_modifier['auteur']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" name="categorie_id">
                            <option value="">-- Sélectionner une catégorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($livre_modifier && $livre_modifier['categorie_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn" 
                                       value="<?php echo $livre_modifier ? htmlspecialchars($livre_modifier['isbn']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date de publication</label>
                                <input type="date" class="form-control" name="date_publication" 
                                       value="<?php echo $livre_modifier ? $livre_modifier['date_publication'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre d'exemplaires</label>
                        <input type="number" class="form-control" name="exemplaires" min="1" 
                               value="<?php echo $livre_modifier ? $livre_modifier['exemplaires_total'] : '1'; ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $livre_modifier ? 'Modifier' : 'Ajouter'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajouter Catégorie -->
<div class="modal fade" id="ajouterCategorieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="ajouter_categorie">
                    
                    <div class="mb-3">
                        <label class="form-label">Nom de la catégorie *</label>
                        <input type="text" class="form-control" name="nom_categorie" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($livre_modifier): ?>
<script>
    // Ouvrir automatiquement le modal de modification
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('ajouterLivreModal')).show();
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>