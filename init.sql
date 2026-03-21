-- Fichier: init.sql
-- Base de données pour FarmsConnect (MVP)

CREATE DATABASE IF NOT EXISTS farmsconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmsconnect;

-- 1. Table Utilisateurs (Agriculteur)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL, -- Sera hashé via password_hash()
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table Équipements (Capteurs & Actionneurs)
CREATE TABLE IF NOT EXISTS equipements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    type ENUM('capteur', 'actionneur') NOT NULL,
    unite VARCHAR(10) DEFAULT '',
    valeur_actuelle DECIMAL(10,2) DEFAULT 0.00,
    statut ENUM('normal', 'alerte', 'critique', 'arret', 'marche') DEFAULT 'normal',
    seuil_min DECIMAL(10,2) DEFAULT NULL,
    seuil_max DECIMAL(10,2) DEFAULT NULL,
    icone VARCHAR(50) DEFAULT 'thermometer', -- Nom de l'icône Lucide
    couleur VARCHAR(20) DEFAULT 'green', -- green, red, orange, blue, grey
    latitude DECIMAL(10,8) DEFAULT NULL, -- Coordonnées GPS
    longitude DECIMAL(11,8) DEFAULT NULL, -- Coordonnées GPS
    mis_a_jour_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Table d'Historique des données (Pour les graphiques 7 jours)
CREATE TABLE IF NOT EXISTS historique_donnees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT NOT NULL,
    valeur DECIMAL(10,2) NOT NULL,
    enregistre_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE
);

-- 4. Table des Alertes
CREATE TABLE IF NOT EXISTS alertes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT NOT NULL,
    niveau ENUM('important', 'critique') NOT NULL,
    message VARCHAR(255) NOT NULL,
    est_lu BOOLEAN DEFAULT FALSE,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE
);

-- 5. Table des Commandes Offline (File d'attente pour synchro)
CREATE TABLE IF NOT EXISTS commandes_offline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT NOT NULL,
    nouvelle_valeur DECIMAL(10,2) NOT NULL, -- 0 pour arrêt, 1 pour marche
    synced BOOLEAN DEFAULT FALSE,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE
);

-- 6. Table des Zones de Ferme (Polygones cartographiques)
CREATE TABLE IF NOT EXISTS zone_ferme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) DEFAULT 'Zone principale',
    coordonnees JSON NOT NULL COMMENT 'Array de points [lat, lng]',
    couleur VARCHAR(20) DEFAULT '#22c55e',
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- INSERTION DES DONNÉES PAR DÉFAUT (MVP Demo)
-- ==========================================

-- Création d'un utilisateur de test (mot de passe: 'password123' hashé)
INSERT IGNORE INTO utilisateurs (nom, email, mot_de_passe) VALUES 
('Jean', 'jean@ferme.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertion des capteurs et actionneurs
INSERT IGNORE INTO equipements (id, nom, type, unite, valeur_actuelle, statut, seuil_min, seuil_max, icone, couleur, latitude, longitude) VALUES 
(1, 'Serre 1', 'capteur', '°C', 19.8, 'normal', 5.0, 35.0, 'thermometer', 'green', 48.8566, 2.3522),
(2, 'Humidité sol', 'capteur', '%', 65.6, 'normal', 30.0, 80.0, 'droplets', 'green', 48.8568, 2.3520),
(3, 'Réservoir eau', 'capteur', '%', 71.3, 'normal', 20.0, 100.0, 'droplet', 'green', 48.8570, 2.3525),
(4, 'Batterie Nord', 'capteur', '%', 87.8, 'normal', 15.0, 100.0, 'battery-medium', 'orange', 48.8564, 2.3528),
(5, 'Pompe arrosage', 'actionneur', '', 0, 'arret', NULL, NULL, 'power', 'grey', 48.8569, 2.3523),
(6, 'Chauffage serre', 'actionneur', '', 0, 'arret', NULL, NULL, 'flame', 'grey', 48.8565, 2.3521),
(7, 'Mouvement (Zone A)', 'capteur', '', 0, 'normal', NULL, NULL, 'shield', 'blue', 48.8572, 2.3526);

-- 6. Peuplement historique (Simulation d'activité sur les dernières 24h)
INSERT IGNORE INTO historique_donnees (equipement_id, valeur, enregistre_le) VALUES 
(1, 18.5, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 19.2, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 20.1, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 60.5, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 62.1, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 65.4, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 85.0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 82.3, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(3, 78.1, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, 98.0, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(4, 95.5, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(4, 92.1, DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Zone de la ferme (polygone délimitant la propriété)
INSERT IGNORE INTO zone_ferme (nom, coordonnees, couleur) VALUES 
('Zone principale', '[[48.8560, 2.3515], [48.8575, 2.3515], [48.8575, 2.3535], [48.8560, 2.3535], [48.8560, 2.3515]]', '#22c55e');
