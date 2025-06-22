// assets/js/emprunts.js - JavaScript pour la gestion des emprunts

class EmpruntManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAutoOpenModal();
    }

    bindEvents() {
        // Soumission du formulaire de nouvel emprunt
        const nouvelEmpruntForm = document.getElementById('nouvelEmpruntForm');
        if (nouvelEmpruntForm) {
            nouvelEmpruntForm.addEventListener('submit', (e) => this.handleNouvelEmprunt(e));
        }

        // Boutons de retour
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-retour')) {
                this.handleRetourClick(e);
            }
        });

        // Soumission du formulaire de retour
        const retourForm = document.getElementById('retourForm');
        if (retourForm) {
            retourForm.addEventListener('submit', (e) => this.handleRetour(e));
        }
    }

    checkAutoOpenModal() {
        // Ouvrir automatiquement le modal si demandé
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'nouveau') {
            const modal = new bootstrap.Modal(document.getElementById('nouvelEmpruntModal'));
            modal.show();
        }
    }

    async handleNouvelEmprunt(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Validation côté client
        if (!this.validateEmpruntForm(form)) {
            return;
        }

        // Désactiver le bouton et afficher le loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';

        try {
            const formData = new FormData(form);
            const response = await fetch('nouvelEmprunt.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', result.message);
                this.closeModal('nouvelEmpruntModal');
                form.reset();
                
                // Recharger la page pour afficher le nouvel emprunt
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showAlert('danger', result.error || 'Une erreur est survenue');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showAlert('danger', 'Erreur de communication avec le serveur');
        } finally {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    handleRetourClick(e) {
        e.preventDefault();
        
        const empruntId = e.target.dataset.empruntId;
        const livreTitre = e.target.dataset.livreTitre;
        const emprunteur = e.target.dataset.emprunteur;
        
        // Remplir les informations dans le modal
        document.getElementById('retourEmpruntId').value = empruntId;
        document.getElementById('retourLivreTitre').textContent = livreTitre;
        document.getElementById('retourEmprunteur').textContent = emprunteur;
        
        // Ouvrir le modal
        const modal = new bootstrap.Modal(document.getElementById('retourModal'));
        modal.show();
    }

    async handleRetour(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Désactiver le bouton et afficher le loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Traitement...';

        try {
            const formData = new FormData(form);
            const response = await fetch('retourner.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', result.message);
                this.closeModal('retourModal');
                
                // Recharger la page pour mettre à jour la liste
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.showAlert('danger', result.error || 'Une erreur est survenue');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showAlert('danger', 'Erreur de communication avec le serveur');
        } finally {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    validateEmpruntForm(form) {
        const livreId = form.querySelector('#livre_id').value;
        const usagerId = form.querySelector('#usager_id').value;

        if (!livreId) {
            this.showAlert('warning', 'Veuillez sélectionner un livre');
            return false;
        }

        if (!usagerId) {
            this.showAlert('warning', 'Veuillez sélectionner un usager');
            return false;
        }

        return true;
    }

    showAlert(type, message) {
        // Supprimer les anciennes alertes
        const existingAlerts = document.querySelectorAll('.alert-auto');
        existingAlerts.forEach(alert => alert.remove());

        // Créer la nouvelle alerte
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto`;
        alertDiv.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insérer l'alerte au début du contenu principal
        const mainContent = document.querySelector('.container-fluid') || document.body;
        mainContent.insertBefore(alertDiv, mainContent.firstChild);

        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    closeModal(modalId) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if (modal) {
            modal.hide();
        }
    }
}

// Initialiser le gestionnaire d'emprunts
document.addEventListener('DOMContentLoaded', function() {
    new EmpruntManager();
});

// Fonction utilitaire pour confirmer les actions
function confirmerAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}