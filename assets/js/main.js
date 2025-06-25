// Fonction pour afficher les alertes
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" id="${alertId}">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHTML;
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Fonction pour afficher/cacher le spinner de chargement
function toggleLoadingSpinner(button, show) {
    const spinner = button.querySelector('.loading-spinner');
    const text = button.querySelector('.btn-text');
    
    if (show) {
        if (!spinner) {
            button.innerHTML = '<span class="loading-spinner me-2"></span>Chargement...';
        }
        button.disabled = true;
    } else {
        button.disabled = false;
        // Restaurer le texte original basé sur l'ID du formulaire
        const form = button.closest('form');
        if (form) {
            if (form.id === 'formAjouterUsager') {
                button.innerHTML = '<i class="fas fa-save"></i> Ajouter';
            } else if (form.id === 'formAjouterLivre') {
                button.innerHTML = 'Ajouter';
            } else if (form.id === 'formNouvelEmprunt') {
                button.innerHTML = '<i class="fas fa-check"></i> Enregistrer l\'emprunt';
            }
        }
    }
}

// Fonction générique pour traiter les formulaires
function handleFormSubmit(form, modal) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    toggleLoadingSpinner(submitButton, true);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(modal).hide();
            form.reset();
            // Recharger la page après un délai pour voir le message
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.error || 'Une erreur est survenue', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert('Erreur de communication avec le serveur', 'danger');
    })
    .finally(() => {
        toggleLoadingSpinner(submitButton, false);
    });
}

// Initialisation des événements
document.addEventListener('DOMContentLoaded', function() {
    
    // Formulaire Ajouter Usager
    const formAjouterUsager = document.getElementById('formAjouterUsager');
    if (formAjouterUsager) {
        formAjouterUsager.addEventListener('submit', function(e) {
            e.preventDefault();
            const modal = document.getElementById('ajouterUsagerModal');
            handleFormSubmit(this, modal);
        });
    }
    
    // Formulaire Ajouter Livre
    const formAjouterLivre = document.getElementById('formAjouterLivre');
    if (formAjouterLivre) {
        formAjouterLivre.addEventListener('submit', function(e) {
            e.preventDefault();
            const modal = document.getElementById('ajouterLivreModal');
            handleFormSubmit(this, modal);
        });
    }
    
    // Formulaire Nouvel Emprunt
    const formNouvelEmprunt = document.getElementById('formNouvelEmprunt');
    if (formNouvelEmprunt) {
        formNouvelEmprunt.addEventListener('submit', function(e) {
            e.preventDefault();
            const modal = document.getElementById('nouvelEmpruntModal');
            handleFormSubmit(this, modal);
        });
    }
    
    // Réinitialiser les formulaires quand les modaux se ferment
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) {
                form.reset();
                // Remettre les boutons à l'état normal
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    toggleLoadingSpinner(submitButton, false);
                }
            }
        });
    });
    
    // Validation en temps réel pour l'email
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !isValidEmail(email)) {
                this.classList.add('is-invalid');
                let feedback = this.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    this.parentNode.appendChild(feedback);
                }
                feedback.textContent = 'Format d\'email invalide';
            } else {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
        });
    }
});

// Fonction de validation email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Fonction pour nettoyer les alertes existantes
function clearAlerts() {
    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
        alertContainer.innerHTML = '';
    }
}