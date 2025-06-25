# 📚 Système de Gestion de Bibliothèque

Une application web complète pour la gestion d'une bibliothèque développée en PHP avec MySQL, permettant de gérer efficacement les livres, les usagers et les emprunts.

## 🎯 Objectifs du Projet

Créer une application web simple et intuitive permettant de :
- ✅ Gérer les livres (ajout, modification, suppression, consultation)
- ✅ Gérer les usagers (lecteurs)
- ✅ Gérer les emprunts (qui a emprunté quoi, quand, et pour combien de temps)

## 🚀 Fonctionnalités

### 📖 Gestion des Livres
- **Ajouter un livre** : Titre, auteur, ISBN, catégorie, nombre d'exemplaires
- **Lister les livres disponibles** : Vue d'ensemble avec filtres et recherche
- **Modifier un livre** : Mise à jour des informations
- **Supprimer un livre** : Suppression sécurisée
- **Gestion des catégories** : Organisation par catégories

### 👥 Gestion des Usagers
- **Ajouter un usager** : Nom, prénom, email, téléphone
- **Lister les usagers** : Vue d'ensemble des lecteurs inscrits
- **Modifier un usager** : Mise à jour des informations personnelles
- **Voir le profil d'un usager** : Historique des emprunts

### 📋 Gestion des Emprunts
- **Enregistrer un emprunt** : Association livre-usager avec durée
- **Marquer un livre comme retourné** : Gestion des retours
- **Voir la liste des emprunts** : Emprunts en cours et historique
- **Alertes de retard** : Suivi des dates de retour

### 🎛️ Tableau de Bord
- **Statistiques en temps réel** : Nombre de livres, emprunts actifs, retards
- **Actions rapides** : Création rapide d'emprunts, ajout de livres/usagers
- **Historique récent** : Derniers emprunts avec statuts

## 🏗️ Structure du Projet

```
gestion_bibliotheque/
│
├── 📄 index.php                    # Page d'accueil avec tableau de bord
├── 📄 README.md                    # Documentation du projet
│
├── 📁 assets/                      # Ressources statiques
│   ├── 📁 css/                     # Feuilles de style
│   │   ├── emprunts.css
│   │   ├── header-footer.css
│   │   ├── livres.css
│   │   ├── style.css
│   │   └── usagers.css
│   └── 📁 js/                      # Scripts JavaScript
│       ├── emprunts.js
│       ├── livres.js
│       ├── main.js
│       └── usagers.js
│
├── 📁 config/                      # Configuration
│   └── database.php                # Connexion à la base de données
│
├── 📁 includes/                    # Fichiers d'inclusion
│   ├── footer.php                  # Pied de page
│   ├── functions.php               # Fonctions communes
│   └── header.php                  # En-tête et navigation
│
├── 📁 pages/                       # Pages principales
│   ├── 📁 emprunts/                # Gestion des emprunts
│   │   ├── emprunts.php
│   │   ├── nouvelEmprunt.php
│   │   └── retournerLivre.php
│   ├── 📁 livres/                  # Gestion des livres
│   │   ├── ajouterCategorie.php
│   │   ├── ajouterLivre.php
│   │   ├── livres.php
│   │   ├── modifierLivre.php
│   │   └── supprimerLivre.php
│   └── 📁 usagers/                 # Gestion des usagers
│       ├── ajouterUsager.php
│       ├── modifierUsager.php
│       ├── usagers.php
│       └── voirUsager.php
│
└── 📁 sql/                         # Scripts de base de données
    ├── create_db.sql               # Création des tables
    ├── insert_data.sql             # Données de test
    └── reset_db.sql                # Réinitialisation
```

## 🗄️ Base de Données

### Tables Principales

#### 📚 `livres`
- `id` : Identifiant unique
- `titre` : Titre du livre
- `auteur` : Auteur du livre
- `isbn` : Numéro ISBN (unique)
- `date_publication` : Date de publication
- `exemplaires_total` : Nombre total d'exemplaires
- `exemplaires_disponibles` : Exemplaires disponibles
- `categorie_id` : Référence à la catégorie

#### 👤 `usagers`
- `id` : Identifiant unique
- `nom` : Nom de famille
- `prenom` : Prénom
- `email` : Adresse email (unique)
- `telephone` : Numéro de téléphone
- `date_inscription` : Date d'inscription
- `statut` : Statut (actif/suspendu)

#### 📋 `emprunts`
- `id` : Identifiant unique
- `livre_id` : Référence au livre
- `usager_id` : Référence à l'usager
- `date_emprunt` : Date d'emprunt
- `date_retour_prevue` : Date de retour prévue
- `date_retour_effective` : Date de retour effective
- `statut` : Statut (en_cours/retourne)

#### 🏷️ `categories`
- `id` : Identifiant unique
- `nom` : Nom de la catégorie
- `description` : Description de la catégorie

## 🛠️ Technologies Utilisées

- **Frontend** :
  - HTML5 / CSS3
  - Bootstrap 5.3.0
  - Font Awesome 6.0.0
  - JavaScript (ES6+)
  - AJAX pour les interactions dynamiques

- **Backend** :
  - PHP 7.4+
  - PDO pour les interactions avec la base de données
  - Programmation procédurale

- **Base de données** :
  - MySQL

- **Serveur** :
  - Apache (XAMPP recommandé)

## 📋 Prérequis

- **XAMPP** (ou WAMP/LAMP) avec :
  - PHP 7.4 ou supérieur
  - MySQL 5.7 ou supérieur
  - Apache 2.4 ou supérieur

## 🚀 Installation

### 1. Cloner le projet
```bash
git clone https://github.com/ahmadu-ops/gestion_bibliotheque
cd gestion_bibliotheque
```

### 2. Déplacer vers XAMPP
```bash
# Copier le dossier dans htdocs
cp -r gestion_bibliotheque C:/xampp/htdocs/
```

### 3. Démarrer les services
- Lancer XAMPP Control Panel
- Démarrer **Apache** et **MySQL**

### 4. Créer la base de données
```sql
-- Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
-- Exécuter le script sql/create_db.sql
-- Optionnel : Insérer les données de test avec sql/insert_data.sql
```

### 5. Configuration
```php
// Vérifier config/database.php
$host = 'localhost';
$dbname = 'gestion_bibliotheque';
$username = 'root';
$password = '';
```

### 6. Accéder à l'application
```
http://localhost/gestion_bibliotheque
```

## 🔧 Bonnes Pratiques Implémentées

### Sécurité
- ✅ **Protection XSS** : `htmlspecialchars()` sur toutes les sorties
- ✅ **Injection SQL** : Utilisation de PDO avec requêtes préparées
- ✅ **Validation des données** : Validation côté client et serveur
- ✅ **Sanitisation** : Nettoyage des entrées utilisateur

### Architecture
- ✅ **Séparation des préoccupations** : PHP/HTML séparés
- ✅ **Réutilisabilité** : Header/Footer inclus
- ✅ **Organisation** : Structure de fichiers claire
- ✅ **Fonctions communes** : Centralisation dans `functions.php`

### Interface Utilisateur
- ✅ **Responsive Design** : Compatible mobile/tablet/desktop
- ✅ **Navigation intuitive** : Menu clair et accessible
- ✅ **Feedback utilisateur** : Messages de confirmation/erreur
- ✅ **Modales** : Actions rapides sans rechargement

### Performance
- ✅ **AJAX** : Interactions dynamiques
- ✅ **Pagination** : Gestion des grandes listes
- ✅ **Optimisation SQL** : Requêtes efficaces
- ✅ **Cache** : Réduction des requêtes redondantes

## 🎨 Fonctionnalités Avancées

### Interface Moderne
- **Design responsive** avec Bootstrap 5
- **Icônes Font Awesome** pour une meilleure UX
- **Cartes statistiques** colorées sur le tableau de bord
- **Modales** pour les actions rapides
- **Alertes dynamiques** pour le feedback utilisateur

### Gestion Intelligente
- **Calcul automatique** des dates de retour
- **Alertes de retard** avec codes couleur
- **Gestion des exemplaires** multiples
- **Système de catégories** pour l'organisation
- **Recherche et filtrage** avancés

### Expérience Utilisateur
- **Actions rapides** depuis le tableau de bord
- **Navigation breadcrumb** pour l'orientation
- **Confirmations** avant suppression
- **Pagination** pour les grandes listes
- **Tri** par colonnes dans les tableaux

## 🔄 Utilisation

### Premiers pas
1. **Ajouter des catégories** : Organiser votre collection
2. **Enregistrer des livres** : Construire votre catalogue
3. **Inscrire des usagers** : Créer la base d'utilisateurs
4. **Gérer les emprunts** : Suivre les prêts et retours

### Workflow typique
1. **Tableau de bord** → Vue d'ensemble quotidienne
2. **Nouvel emprunt** → Sélection livre + usager
3. **Suivi des retours** → Gestion des échéances
4. **Maintenance** → Mise à jour des informations

## 🐛 Dépannage

### Erreurs courantes

**Erreur de connexion à la base de données**
```
Solution : Vérifier que MySQL est démarré et que les paramètres dans config/database.php sont corrects
```

**Page blanche**
```
Solution : Activer l'affichage des erreurs PHP ou vérifier les logs Apache
```

**CSS/JS non chargés**
```
Solution : Vérifier les chemins absolus/relatifs dans les includes
```

### Logs et Debug
- **Logs Apache** : `C:/xampp/apache/logs/error.log`
- **Logs MySQL** : `C:/xampp/mysql/data/mysql_error.log`
- **Debug PHP** : Activer `display_errors` dans `php.ini`

## 🔮 Évolutions Possibles

### Fonctionnalités à ajouter
- [ ] **Système d'authentification** : Gestion des rôles (admin/bibliothécaire)
- [ ] **Réservations** : Permettre la réservation de livres
- [ ] **Amendes** : Calcul automatique des pénalités de retard
- [ ] **Notifications** : Email/SMS pour les rappels
- [ ] **Statistiques avancées** : Graphiques et rapports
- [ ] **Import/Export** : CSV, Excel pour la gestion des données
- [ ] **Code-barres** : Scan pour l'ajout rapide de livres
- [ ] **API REST** : Intégration avec d'autres systèmes

### Améliorations techniques
- [ ] **Migration vers MVC** : Architecture plus structurée
- [ ] **ORM** : Eloquent ou Doctrine pour la gestion des données
- [ ] **Tests unitaires** : PHPUnit pour la qualité du code
- [ ] **Cache** : Redis ou Memcached pour les performances
- [ ] **Logs** : Système de logging avancé
- [ ] **Docker** : Containerisation pour le déploiement

## 👨‍💻 Auteur

**Développeur** : Cheikhouna Modou Bamba FAA  
**Email** : cmbamba.fall@univ-thies.sn
**Date** : Juin 2025
