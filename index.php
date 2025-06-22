<?php
require_once 'config/database.php';

$page_title = 'Accueil - Gestion Bibliothèque';

// Récupération des statistiques
try {
    // Nombre total de livres
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM livres");
    $total_livres = $stmt->fetch()['total'];

    // Nombre d'emprunts en cours
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM emprunts WHERE statut = 'en_cours'");
    $emprunts_actifs = $stmt->fetch()['total'];

    // Nombre de retards
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM emprunts 
                        WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()");
    $retards = $stmt->fetch()['total'];

    // Nombre d'usagers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usagers WHERE statut = 'actif'");
    $total_usagers = $stmt->fetch()['total'];

    // Derniers emprunts
    $stmt = $pdo->query("SELECT e.*, l.titre, l.auteur, u.nom, u.prenom 
                        FROM emprunts e 
                        JOIN livres l ON e.livre_id = l.id 
                        JOIN usagers u ON e.usager_id = u.id 
                        ORDER BY e.date_emprunt DESC 
                        LIMIT 5");
    $derniers_emprunts = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1><i class="fas fa-tachometer-alt"></i> Tableau de Bord</h1>
        <p class="text-muted">Vue d'ensemble de votre bibliothèque</p>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Statistiques -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card card-stats text-center">
            <div class="card-body">
                <i class="fas fa-book fa-3x mb-3"></i>
                <h3><?php echo $total_livres ?? 0; ?></h3>
                <p class="mb-0">Livres disponibles</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white text-center">
            <div class="card-body">
                <i class="fas fa-hand-holding fa-3x mb-3"></i>
                <h3><?php echo $emprunts_actifs ?? 0; ?></h3>
                <p class="mb-0">Emprunts actifs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark text-center">
            <div class="card-body">
                <i class="fas fa-clock fa-3x mb-3"></i>
                <h3><?php echo $retards ?? 0; ?></h3>
                <p class="mb-0">Retards</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white text-center">
            <div class="card-body">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h3><?php echo $total_usagers ?? 0; ?></h3>
                <p class="mb-0">Usagers actifs</p>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Actions Rapides</h5>
            </div>
            <div class="card-body text-center">
                <a href="emprunts.php?action=nouveau" class="btn btn-primary btn-lg me-3 mb-2">
                    <i class="fas fa-plus-circle"></i> Nouvel Emprunt
                </a>
                <a href="livres.php?action=ajouter" class="btn btn-success btn-lg me-3 mb-2">
                    <i class="fas fa-book-medical"></i> Ajouter Livre
                </a>
                <a href="usagers.php?action=ajouter" class="btn btn-info btn-lg mb-2">
                    <i class="fas fa-user-plus"></i> Nouveau Lecteur
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Derniers emprunts -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history"></i> Derniers Emprunts</h5>
                <a href="emprunts.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body">
                <?php if (!empty($derniers_emprunts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Emprunteur</th>
                                    <th>Date d'emprunt</th>
                                    <th>Retour prévu</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_emprunts as $emprunt): ?>
                                    <?php
                                    $date_retour = new DateTime($emprunt['date_retour_prevue']);
                                    $aujourd_hui = new DateTime();
                                    $diff = $aujourd_hui->diff($date_retour);
                                    
                                    if ($emprunt['date_retour_effective']) {
                                        $statut_class = 'status-actif';
                                        $statut_text = 'Retourné';
                                    } elseif ($date_retour < $aujourd_hui) {
                                        $statut_class = 'status-retard';
                                        $statut_text = 'En retard';
                                    } elseif ($diff->days <= 2) {
                                        $statut_class = 'status-bientot';
                                        $statut_text = 'Bientôt dû';
                                    } else {
                                        $statut_class = 'status-actif';
                                        $statut_text = 'En cours';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($emprunt['titre']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($emprunt['auteur']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($emprunt['prenom'] . ' ' . $emprunt['nom']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?></td>
                                        <td>
                                            <span class="<?php echo $statut_class; ?>"><?php echo $statut_text; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Aucun emprunt enregistré pour le moment.</p>
                        <a href="emprunts.php?action=nouveau" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Créer le premier emprunt
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>