<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$usager_details = null;
$historique_emprunts = [];
$error = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Récupérer les détails de l'usager
    $usager_details = getUsagerById($pdo, $id);
    
    if ($usager_details) {
        // Récupérer l'historique des emprunts
        $historique_emprunts = getHistoriqueEmprunts($pdo, $id);
    } else {
        $error = 'Usager non trouvé.';
    }
} else {
    header('Location: usagers.php');
    exit;
}

// Inclure le header si nécessaire
$page_title = 'Détails de l\'usager';
include '../../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <a href="usagers.php" class="btn btn-secondary">Retour à la liste</a>
<?php elseif ($usager_details): ?>
    <!-- Modal Détails Usager -->
    <div class="modal fade show d-block" id="detailsUsagerModal" tabindex="-1">
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
                            <?php echo getBadgeStatut($usager_details['statut']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Inscription :</strong> <?php echo formaterDate($usager_details['date_inscription']); ?>
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
                                            <td><?php echo formaterDate($emprunt['date_emprunt']); ?></td>
                                            <td><?php echo formaterDate($emprunt['date_retour_prevue']); ?></td>
                                            <td><?php echo getStatutEmprunt($emprunt); ?></td>
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
                    <a href="usagers.php?modifier=<?php echo $usager_details['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>