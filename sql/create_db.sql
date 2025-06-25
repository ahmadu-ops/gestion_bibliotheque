-- Création de la base de données (si elle n'existe pas déjà)
CREATE DATABASE IF NOT EXISTS gestion_bibliotheque;
USE gestion_bibliotheque;


CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- Table des livres
CREATE TABLE livres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    date_publication DATE,
    exemplaires_total INT DEFAULT 1,
    exemplaires_disponibles INT DEFAULT 1,
    categorie_id INT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id)    
);

-- Table des usagers
CREATE TABLE usagers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'suspendu') DEFAULT 'actif'
);

-- Table des emprunts
CREATE TABLE emprunts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    livre_id INT NOT NULL,
    usager_id INT NOT NULL,
    date_emprunt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_retour_prevue DATE NOT NULL,
    date_retour_effective DATE NULL,
    statut ENUM('en_cours', 'retourne') DEFAULT 'en_cours',
    FOREIGN KEY (livre_id) REFERENCES livres(id),
    FOREIGN KEY (usager_id) REFERENCES usagers(id)
);