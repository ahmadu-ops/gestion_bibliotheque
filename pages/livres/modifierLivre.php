<!-- Modal Modifier Livre -->
<div class="modal fade" id="modifierLivreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le livre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formModifierLivre" onsubmit="return validerFormulaire(this)">
                <div class="modal-body">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="id" value="<?php echo $livre_modifier ? $livre_modifier['id'] : ''; ?>">
                    
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
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</div>