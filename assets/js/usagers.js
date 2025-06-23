// Gestion des usagers - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir automatiquement le modal si on est en mode modification
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('modifier')) {
        const modal = new bootstrap.Modal(document.getElementById('ajouterUsagerModal'));
        modal.show();
    }
    
    // Validation du formulaire
    window.validerFormulaire = function(form) {
        const nom = form.querySelector('#nom').value.trim();
        const prenom = form.querySelector('#prenom').value.trim();
        const email = form.querySelector('#email').value.trim();
        
        if (!nom || !prenom || !email) {
            alert('Le nom, prénom et email sont obligatoires.');
            return false;
        }
        
        // Validation basique de l'email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('L\'adresse email n\'est pas valide.');
            return false;
        }
        
        return true;
    };
    
    // Gestion des alertes automatiques
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Auto-fermeture après 5 secondes
        setTimeout(function() {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(function() {
                    alert.remove();
                }, 150);
            }
        }, 5000);
    });
    
    // Animation des boutons d'action
    const actionButtons = document.querySelectorAll('.btn-action');
    actionButtons.forEach(function(button) {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Confirmation pour les actions sensibles si nécessaire
    const deleteButtons = document.querySelectorAll('.btn-danger');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                e.preventDefault();
            }
        });
    });
});