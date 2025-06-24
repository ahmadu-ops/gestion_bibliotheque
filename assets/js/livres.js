// livres.js - JavaScript pour la gestion des livres

// Variables globales
let modalSuppressionBootstrap;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le modal de suppression
    const modalSuppression = document.getElementById('confirmerSuppressionModal');
    if (modalSuppression) {
        modalSuppressionBootstrap = new bootstrap.Modal(modalSuppression);
    }
    
    // Ouvrir automatiquement le modal de modification si paramètre présent
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modifier')) {
        const modalModifier = document.getElementById('modifierLivreModal');
        if (modalModifier) {
            new bootstrap.Modal(modalModifier).show();
        }
    }
    
    // Gestionnaire pour les boutons de suppression
    document.querySelectorAll('.btn-supprimer-livre').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const livreId = this.dataset.livreId;
            const livreNom = this.dataset.livreNom;
            confirmerSuppression(livreId, livreNom);
        });
    });
});

/**
 * Confirmer la suppression d'un livre
 */
function confirmerSuppression(livreId, livreNom) {
    const elementNom = document.getElementById('livreASupprimer');
    const btnConfirmer = document.getElementById('btnConfirmerSuppression');
    
    if (elementNom && btnConfirmer) {
        elementNom.textContent = livreNom;
        btnConfirmer.href = `?supprimer=${livreId}`;
        modalSuppressionBootstrap.show();
    }
}

/**
 * Valider le formulaire avant soumission
 */
function validerFormulaire(form) {
    const titre = form.querySelector('input[name="titre"]').value.trim();
    const auteur = form.querySelector('input[name="auteur"]').value.trim();
    
    if (!titre || !auteur) {
        alert('Le titre et l\'auteur sont obligatoires.');
        return false;
    }
    
    // Validation de l'ISBN (optionnel mais si présent, format basique)
    const isbn = form.querySelector('input[name="isbn"]').value.trim();
    if (isbn && isbn.length > 0) {
        // Validation simple : au moins 10 caractères
        if (isbn.length < 10) {
            alert('L\'ISBN doit contenir au moins 10 caractères.');
            return false;
        }
    }
    
    // Validation du nombre d'exemplaires
    const exemplaires = parseInt(form.querySelector('input[name="exemplaires"]').value);
    if (exemplaires < 1) {
        alert('Le nombre d\'exemplaires doit être au moins de 1.');
        return false;
    }
    
    return true;
}

/**
 * Réinitialiser les formulaires des modaux
 */
function reinitialiserFormulaires() {
    // Réinitialiser le formulaire d'ajout
    const formAjout = document.getElementById('formAjouterLivre');
    if (formAjout) {
        formAjout.reset();
    }
    
    // Réinitialiser le formulaire de catégorie
    const formCategorie = document.getElementById('formAjouterCategorie');
    if (formCategorie) {
        formCategorie.reset();
    }
}

/**
 * Filtrer automatiquement lors de la saisie dans la recherche
 */
function initSearchAutoFilter() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let timeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // Auto-submit le formulaire de recherche après 500ms
                this.closest('form').submit();
            }, 500);
        });
    }
}

/**
 * Confirmer la suppression (version alternative pour les liens directs)
 */
function confirmerSuppressionLien(type, nom) {
    return confirm(`Êtes-vous sûr de vouloir supprimer ${type} "${nom}" ?\nCette action est irréversible.`);
}

// Fonction utilitaire pour afficher/masquer le loading
function toggleLoading(show) {
    const loadingElements = document.querySelectorAll('.loading-spinner');
    loadingElements.forEach(element => {
        element.style.display = show ? 'block' : 'none';
    });
}

// Gestionnaire pour fermer les alertes automatiquement
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // Fermer après 5 secondes
    });
});