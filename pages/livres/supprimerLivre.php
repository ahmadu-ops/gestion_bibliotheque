<!-- Modal Confirmer Suppression -->
<div class="modal fade" id="confirmerSuppressionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>Êtes-vous sûr ?</h5>
                    <p class="mb-0">Voulez-vous vraiment supprimer <strong id="livreASupprimer"></strong> ?</p>
                    <p class="text-muted">Cette action est irréversible.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" id="btnConfirmerSuppression" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>