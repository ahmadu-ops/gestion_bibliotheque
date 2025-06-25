<?php
    require_once 'config/database.php';
    require_once 'includes/functions.php';

    $page_title = 'Accueil - Gestion Bibliothèque';

    // Traitement des formulaires AJAX
    gererRequetesAjax($pdo);

    // Récupération des statistiques
    $stats = recupererStats($pdo);
    $total_livres       = $stats['total_livres'] ?? 0;
    $emprunts_actifs    = $stats['emprunts_actifs'] ?? 0;
    $retards            = $stats['retards'] ?? 0;
    $total_usagers      = $stats['total_usagers'] ?? 0;
    $derniers_emprunts  = $stats['derniers_emprunts'] ?? [];
    $categories         = $stats['categories'] ?? [];
    $livres_disponibles = $stats['livres_disponibles'] ?? [];
    $usagers            = $stats['usagers'] ?? [];

    if (isset($stats['error'])) {
        $error = $stats['error'];
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

    <!-- Alertes pour les messages -->
    <div id="alertContainer"></div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card card-stats card-livres text-center">
                <div class="card-body">
                    <i class="fas fa-book fa-3x mb-3"></i>
                    <h3><?php echo $total_livres ?? 0; ?></h3>
                    <p class="mb-0">Livres disponibles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-stats card-emprunts text-center">
                <div class="card-body">
                    <i class="fas fa-hand-holding fa-3x mb-3"></i>
                    <h3><?php echo $emprunts_actifs ?? 0; ?></h3>
                    <p class="mb-0">Emprunts actifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-stats card-retards text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h3><?php echo $retards ?? 0; ?></h3>
                    <p class="mb-0">Retards</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card card-stats card-usagers text-center">
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
                    <button class="btn btn-primary btn-lg me-3 mb-2" data-bs-toggle="modal" data-bs-target="#nouvelEmpruntModal">
                        <i class="fas fa-plus-circle"></i> Nouvel Emprunt
                    </button>
                    <button class="btn btn-success btn-lg me-3 mb-2" data-bs-toggle="modal" data-bs-target="#ajouterLivreModal">
                        <i class="fas fa-book-medical"></i> Ajouter Livre
                    </button>
                    <button class="btn btn-info btn-lg mb-2" data-bs-toggle="modal" data-bs-target="#ajouterUsagerModal">
                        <i class="fas fa-user-plus"></i> Nouveau Lecteur
                    </button>
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
                    <a href="pages/emprunts/emprunts.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
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
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouvelEmpruntModal">
                                <i class="fas fa-plus"></i> Créer le premier emprunt
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Usager -->
    <div class="modal fade" id="ajouterUsagerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un usager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAjouterUsager">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter_usager">
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Livre -->
    <div class="modal fade" id="ajouterLivreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAjouterLivre">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter_livre">
                        
                        <div class="mb-3">
                            <label class="form-label">Titre *</label>
                            <input type="text" class="form-control" name="titre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Auteur *</label>
                            <input type="text" class="form-control" name="auteur" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catégorie</label>
                            <select class="form-select" name="categorie_id">
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="isbn">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date de publication</label>
                                    <input type="date" class="form-control" name="date_publication">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre d'exemplaires</label>
                            <input type="number" class="form-control" name="exemplaires" min="1" value="1">
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
                <form id="formNouvelEmprunt">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="nouvel_emprunt">
                        <div class="mb-3">
                            <label for="livre_id" class="form-label">Livre à emprunter *</label>
                            <select class="form-select" name="livre_id" id="livre_id" required>
                                <option value="">Sélectionner un livre...</option>
                                <?php foreach ($livres_disponibles as $livre): ?>
                                    <option value="<?php echo $livre['id']; ?>">
                                        <?php echo htmlspecialchars($livre['titre']); ?> 
                                        - <?php echo htmlspecialchars($livre['auteur']); ?>

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

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/main.js"></script>

    <?php include 'includes/footer.php'; ?>