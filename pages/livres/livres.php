<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$page_title = 'Gestion des Livres';
$message = $_GET['message'] ?? '';
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
        
        $result = ajouterLivre($pdo, $titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
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
        
        $result = modifierLivre($pdo, $id, $titre, $auteur, $isbn, $date_publication, $exemplaires, $categorie_id);
        if ($result['success']) {
            // Redirection pour éviter que le modal reste ouvert
            header('Location: livres.php?message=' . urlencode($result['message']));
            exit;
        } else {
            $error = $result['message'];
        }
    }
    
    elseif ($action === 'ajouter_categorie') {
        $nom_categorie = trim($_POST['nom_categorie'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $result = ajouterCategorie($pdo, $nom_categorie, $description);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Suppression
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $result = supprimerLivre($pdo, $id);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Récupération des catégories
$categories = getCategories($pdo);

// Récupération des livres avec pagination et filtres
$search = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$disponible_filter = $_GET['disponible'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$livres_data = getLivres($pdo, $search, $categorie_filter, $disponible_filter, $page, $per_page);
$livres = $livres_data['livres'];
$total_livres = $livres_data['total'];
$total_pages = $livres_data['total_pages'];

if (isset($livres_data['error'])) {
    $error = $livres_data['error'];
}

// Livre à modifier
$livre_modifier = null;
if (isset($_GET['modifier'])) {
    $id = (int)$_GET['modifier'];
    $livre_modifier = getLivreById($pdo, $id);
    if (!$livre_modifier) {
        $error = 'Livre non trouvé.';
    }
}

include '../../includes/header.php';
?>

<!-- CSS personnalisé -->
<link rel="stylesheet" href="../../assets/css/livres.css">

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
                                    <button type="button" 
                                            class="btn btn-sm btn-danger btn-action btn-supprimer-livre"
                                            data-livre-id="<?php echo $livre['id']; ?>"
                                            data-livre-nom="<?php echo htmlspecialchars($livre['titre']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<?php
// Inclusion des modaux
include 'ajouterLivre.php';
include 'ajouterCategorie.php';
include 'supprimerLivre.php';

// Modal de modification si nécessaire
if ($livre_modifier) {
    include 'modifierLivre.php';
}
?>

<!-- JavaScript personnalisé -->
<script src="../../assets/js/livres.js"></script>

<?php if ($livre_modifier): ?>
<script>
    // Ouvrir automatiquement le modal de modification
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('modifierLivreModal')).show();
    });
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>