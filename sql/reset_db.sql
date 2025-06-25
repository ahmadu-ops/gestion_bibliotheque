-- Active: 1750856283653@@127.0.0.1@3306
USE gestion_bibliotheque;

-- Désactivation temporaire des contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression des données (ordre inverse de dépendance)
DELETE FROM emprunts;
DELETE FROM livres;
DELETE FROM usagers;
DELETE FROM categories;

-- Réinitialisation des compteurs AUTO_INCREMENT
ALTER TABLE emprunts AUTO_INCREMENT = 1;
ALTER TABLE livres AUTO_INCREMENT = 1;
ALTER TABLE usagers AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;

-- Réactivation des contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;
