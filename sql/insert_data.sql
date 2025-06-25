-- Insertion des catégories (important de faire avant les livres)
INSERT INTO categories (nom, description) VALUES
('Littérature Africaine', 'Romans, nouvelles et œuvres littéraires d''auteurs africains'),
('Histoire et Civilisation', 'Livres sur l''histoire de l''Afrique et du monde'),
('Sciences et Technologie', 'Ouvrages scientifiques et techniques'),
('Développement Personnel', 'Livres de motivation et développement personnel'),
('Littérature Française', 'Classiques et œuvres contemporaines de la littérature française');

-- Insertion des livres
INSERT INTO livres (titre, auteur, isbn, date_publication, exemplaires_total, exemplaires_disponibles, categorie_id) VALUES
('Une si longue lettre', 'Mariama Bâ', '9782738400011', '1979-01-01', 3, 3, 1),
('Le Ventre de l''Atlantique', 'Fatou Diome', '9782253114428', '2003-01-01', 2, 2, 1),
('Les Soleils des Indépendances', 'Ahmadou Kourouma', '9782020005623', '1970-01-01', 4, 4, 1),
('L''Étrange Destin de Wangrin', 'Amadou Hampâté Bâ', '9782264005874', '1973-01-01', 2, 2, 1),

('Nations nègres et culture', 'Cheikh Anta Diop', '9782708400012', '1954-01-01', 3, 3, 2),
('L''Afrique noire précoloniale', 'Cheikh Anta Diop', '9782708400029', '1960-01-01', 2, 2, 2),
('Histoire générale de l''Afrique', 'Joseph Ki-Zerbo', '9789232017086', '1980-01-01', 5, 5, 2),
('Le Sénégal dans l''histoire', 'Boubacar Barry', '9782865372345', '1988-01-01', 2, 2, 2),

('Introduction à la physique moderne', 'Serge Haroche', '9782701145321', '2010-01-01', 3, 3, 3),
('Mathématiques pour l''ingénieur', 'Claude Aslangul', '9782711721568', '2008-01-01', 4, 4, 3),
('Biologie cellulaire', 'Jean-Claude Callen', '9782100548907', '2011-01-01', 2, 2, 3),
('Chimie générale', 'Raymond Chang', '9782761312345', '2009-01-01', 3, 3, 3),

('Les 7 habitudes de ceux qui réalisent tout ce qu''ils entreprennent', 'Stephen Covey', '978-2-7540-0987-4', '1989-01-01', 2, 2, 4),
('Comment se faire des amis', 'Dale Carnegie', '9782253009142', '1936-01-01', 3, 3, 4),
('Réfléchissez et devenez riche', 'Napoleon Hill', '9782892256998', '1937-01-01', 2, 2, 4),
('L''Homme est l''avenir de l''homme', 'Pierre Teilhard de Chardin', '9782020028479', '1959-01-01', 1, 1, 4),

('Le Petit Prince', 'Antoine de Saint-Exupéry', '9782070408496', '1943-01-01', 5, 5, 5),
('L''Étranger', 'Albert Camus', '9782070360021', '1942-01-01', 3, 3, 5),
('Madame Bovary', 'Gustave Flaubert', '9782070418259', '1857-01-01', 2, 2, 5),
('Les Misérables', 'Victor Hugo', '9782070419621', '1862-01-01', 4, 4, 5);

-- Insertion des usagers
INSERT INTO usagers (nom, prenom, email, telephone) VALUES
('Diop', 'Aminata', 'aminata.diop@gmail.com', '771234567'),
('Ndiaye', 'Mamadou', 'mamadou.ndiaye@yahoo.fr', '762345678'),
('Fall', 'Fatou', 'fatou.fall@hotmail.com', '783456789'),
('Sarr', 'Ibrahima', 'ibrahima.sarr@gmail.com', '774567890'),
('Ba', 'Aissatou', 'aissatou.ba@outlook.com', '765678901'),
('Gueye', 'Ousmane', 'ousmane.gueye@gmail.com', '786789012'),
('Sy', 'Marième', 'marieme.sy@yahoo.fr', '777890123'),
('Cissé', 'Moussa', 'moussa.cisse@gmail.com', '768901234'),
('Kane', 'Khadija', 'khadija.kane@hotmail.com', '789012345'),
('Seck', 'Abdoulaye', 'abdoulaye.seck@gmail.com', '770123456');

-- Insertion des emprunts (15 emprunts)
INSERT INTO emprunts (livre_id, usager_id, date_emprunt, date_retour_prevue, date_retour_effective, statut) VALUES
(1, 1, '2025-05-15 10:30:00', '2025-05-29', '2025-05-28', 'retourne'),
(3, 2, '2025-05-20 14:15:00', '2025-06-03', '2025-06-02', 'retourne'),
(5, 3, '2025-05-25 09:45:00', '2025-06-08', '2025-06-07', 'retourne'),
(7, 4, '2025-06-01 16:20:00', '2025-06-15', '2025-06-14', 'retourne'),
(9, 5, '2025-06-05 11:10:00', '2025-06-19', '2025-06-18', 'retourne'),

(2, 6, '2025-06-10 08:30:00', '2025-06-24', NULL, 'en_cours'),
(4, 7, '2025-06-12 13:45:00', '2025-06-26', NULL, 'en_cours'),
(6, 8, '2025-06-14 15:20:00', '2025-06-28', NULL, 'en_cours'),
(8, 9, '2025-06-16 10:15:00', '2025-06-30', NULL, 'en_cours'),
(10, 10, '2025-06-18 14:30:00', '2025-07-02', NULL, 'en_cours'),
(11, 1, '2025-06-19 09:45:00', '2025-07-03', NULL, 'en_cours'),
(12, 2, '2025-06-20 16:10:00', '2025-07-04', NULL, 'en_cours'),

(13, 3, '2025-05-28 11:30:00', '2025-06-11', NULL, 'en_cours'),
(14, 4, '2025-06-02 14:20:00', '2025-06-16', NULL, 'en_cours'),
(15, 5, '2025-06-05 09:15:00', '2025-06-19', NULL, 'en_cours');

-- Mise à jour des exemplaires disponibles
UPDATE livres 
LEFT JOIN (
    SELECT livre_id, COUNT(*) AS nb_emprunts_en_cours
    FROM emprunts
    WHERE statut = 'en_cours'
    GROUP BY livre_id
) AS e ON livres.id = e.livre_id
SET livres.exemplaires_disponibles = livres.exemplaires_total - IFNULL(e.nb_emprunts_en_cours, 0);

-- Vérification des données insérées
SELECT 'Catégories' AS Table_Name, COUNT(*) AS Count FROM categories
UNION ALL
SELECT 'Livres', COUNT(*) FROM livres
UNION ALL
SELECT 'Usagers', COUNT(*) FROM usagers
UNION ALL
SELECT 'Emprunts', COUNT(*) FROM emprunts
UNION ALL
SELECT 'Emprunts en cours', COUNT(*) FROM emprunts WHERE statut = 'en_cours'
UNION ALL
SELECT 'Emprunts retournés', COUNT(*) FROM emprunts WHERE statut = 'retourne'
UNION ALL
SELECT 'Emprunts en retard', COUNT(*) FROM emprunts 
WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE();