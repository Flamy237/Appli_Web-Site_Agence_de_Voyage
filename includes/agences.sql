-- ####################################################################
-- SCHÉMA COMPLET (Ajout de la table APPAREIL et mise à jour de TRAJET)
-- ####################################################################

-- 1. Table UTILISATEUR
CREATE TABLE IF NOT EXISTS `utilisateur` (
    `id_utilisateur` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(25) NOT NULL,
    `prenom` VARCHAR(30) NOT NULL,
    `email` VARCHAR(30) NOT NULL UNIQUE,
    `mot_de_passe` VARCHAR(50) NOT NULL, 
    `telephone` VARCHAR(20) NULL,
    PRIMARY KEY (`id_utilisateur`)
) ENGINE = InnoDB;

-- 2. Table MOYEN_TRANSPORT (Bus, Train, Avion)
CREATE TABLE IF NOT EXISTS `moyen_transport` (
    `id_transport` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('Train', 'Bus', 'Avion') NOT NULL, 
    `nom_compagnie` VARCHAR(100) NOT NULL,
    -- Remarque: le champ 'numero' est conservé, mais il devrait peut-être être déplacé dans la table 'appareil'
    `numero` VARCHAR(50) UNIQUE NOT NULL, 
    PRIMARY KEY (`id_transport`)
) ENGINE = InnoDB;

-- NOUVELLE TABLE
-- 3. Table APPAREIL (L'équipement physique : Train #101, Avion A320)
-- Gère la capacité totale.
CREATE TABLE IF NOT EXISTS `appareil` (
    `id_appareil` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_transport` INT UNSIGNED NOT NULL,                 -- Lien vers le type de transport (Train/Avion)
    `numero_identification` VARCHAR(100) NOT NULL UNIQUE, -- Numéro unique de l'équipement (Ex: "Rame TGV 45", "Airbus A380")
    `capacite_totale` INT UNSIGNED NOT NULL,              -- Capacité MAXIMALE de l'appareil
    PRIMARY KEY (`id_appareil`),
    INDEX `fk_appareil_transport_idx` (`id_transport` ASC),
    CONSTRAINT `fk_appareil_transport`
        FOREIGN KEY (`id_transport`)
        REFERENCES `moyen_transport` (`id_transport`)
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE = InnoDB;


-- 4. Table TRAJET (Liaison Spécifique)
-- MODIFICATION CLÉ : Remplace `id_transport` par `id_appareil` et supprime `places_disponibles`.
CREATE TABLE IF NOT EXISTS `trajet` (
    `id_trajet` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_appareil` INT UNSIGNED NOT NULL,           -- Clé étrangère vers l'appareil physique utilisé
    `ville_depart` VARCHAR(100) NOT NULL,
    `ville_arrivee` VARCHAR(100) NOT NULL,
    `date_depart` DATE NOT NULL,
    `heure_depart` TIME NOT NULL,
    `heure_arrivee` TIME NOT NULL,
    `duree` INT UNSIGNED NULL, 
    `prix_base` DECIMAL(10, 2) NOT NULL, 
    -- `places_disponibles` est supprimé
    PRIMARY KEY (`id_trajet`),
    INDEX `fk_trajet_appareil_idx` (`id_appareil` ASC),
    -- L'index UNIQUE garantit qu'un appareil ne peut faire qu'un seul voyage à la fois
    UNIQUE KEY `unique_appareil_date_heure` (`id_appareil`, `date_depart`, `heure_depart`), 
    CONSTRAINT `fk_trajet_appareil`
        FOREIGN KEY (`id_appareil`)
        REFERENCES `appareil` (`id_appareil`)
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 5. Table CLASSE
CREATE TABLE IF NOT EXISTS `classe` (
    `id_classe` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom_classe` VARCHAR(50) NOT NULL UNIQUE, 
    `coefficient_prix` DECIMAL(3, 2) NOT NULL DEFAULT 1.00, 
    `description` TEXT NULL,
    PRIMARY KEY (`id_classe`)
) ENGINE = InnoDB;

-- 6. Table PAIEMENT
CREATE TABLE IF NOT EXISTS `paiement` (
    `id_paiement` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `montant` DECIMAL(10, 2) NOT NULL,
    `date_paiement` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `methode` VARCHAR(50) NOT NULL, 
    `statut` ENUM('En_Attente', 'Validé', 'Échec') NOT NULL DEFAULT 'En_Attente',
    PRIMARY KEY (`id_paiement`)
) ENGINE = InnoDB;

-- 7. Table PASSAGER
CREATE TABLE IF NOT EXISTS `passager` (
    `id_passager` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `type_piece_identite` VARCHAR(50) NULL,
    `num_piece_identite` VARCHAR(100) NULL,
    PRIMARY KEY (`id_passager`)
) ENGINE = InnoDB;

-- 8. Table RÉSERVATION
CREATE TABLE IF NOT EXISTS `reservation` (
    `id_reservation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_utilisateur` INT UNSIGNED NOT NULL,
    `id_paiement` INT UNSIGNED NULL, 
    `date_reservation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `statut` ENUM('En_Cours', 'Confirmée', 'Annulée') NOT NULL DEFAULT 'En_Cours',
    PRIMARY KEY (`id_reservation`),
    INDEX `fk_reservation_utilisateur_idx` (`id_utilisateur` ASC),
    INDEX `fk_reservation_paiement_idx` (`id_paiement` ASC),
    CONSTRAINT `fk_reservation_utilisateur`
        FOREIGN KEY (`id_utilisateur`)
        REFERENCES `utilisateur` (`id_utilisateur`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_reservation_paiement`
        FOREIGN KEY (`id_paiement`)
        REFERENCES `paiement` (`id_paiement`)
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE = InnoDB;

-- 9. Table BILLET
CREATE TABLE IF NOT EXISTS `billet` (
    `id_billet` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_reservation` INT UNSIGNED NOT NULL,
    `id_passager` INT UNSIGNED NOT NULL,
    `id_trajet` INT UNSIGNED NOT NULL,
    `id_classe` INT UNSIGNED NOT NULL,
    `code_billet` VARCHAR(100) UNIQUE NOT NULL,
    `prix_final` DECIMAL(10, 2) NOT NULL, 
    `numero_siege` VARCHAR(10) NULL,
    `statut` ENUM('Émis', 'Utilisé', 'Annulé') NOT NULL DEFAULT 'Émis',
    PRIMARY KEY (`id_billet`),
    INDEX `fk_billet_reservation_idx` (`id_reservation` ASC),
    INDEX `fk_billet_passager_idx` (`id_passager` ASC),
    INDEX `fk_billet_trajet_idx` (`id_trajet` ASC),
    INDEX `fk_billet_classe_idx` (`id_classe` ASC),
    CONSTRAINT `fk_billet_reservation`
        FOREIGN KEY (`id_reservation`)
        REFERENCES `reservation` (`id_reservation`)
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    CONSTRAINT `fk_billet_passager`
        FOREIGN KEY (`id_passager`)
        REFERENCES `passager` (`id_passager`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_billet_trajet`
        FOREIGN KEY (`id_trajet`)
        REFERENCES `trajet` (`id_trajet`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_billet_classe`
        FOREIGN KEY (`id_classe`)
        REFERENCES `classe` (`id_classe`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE = InnoDB;






-- -----------------------------------------
-- A. Insertion dans MOYEN_TRANSPORT (2 Enregistrements)
-- -----------------------------------------
INSERT INTO `moyen_transport` (`type`, `nom_compagnie`, `numero`) VALUES
('Train', 'CAMRAIL Intercity', 'CR2025'),
('Avion', 'Camair-Co', 'QC2024');

-- -----------------------------------------
-- B. Insertion dans CLASSE (2 Enregistrements)
-- -----------------------------------------
INSERT INTO `classe` (`nom_classe`, `coefficient_prix`, `description`) VALUES
('Standard', 1.00, 'Classe économique standard.'),
('Première', 1.50, 'Classe supérieure avec plus de confort.');

-- -----------------------------------------
-- C. Insertion dans APPAREIL (3 Enregistrements)
-- NOTE : id_transport 1 = Train, id_transport 2 = Avion
-- -----------------------------------------
INSERT INTO `appareil` (`id_transport`, `numero_identification`, `capacite_totale`) VALUES
(1, 'Rame Voyageur 1', 300),  -- Un train de 300 places
(1, 'Rame Voyageur 2', 300),  -- Un autre train de 300 places
(2, 'Boeing 737-800', 180);  -- Un avion de 180 places

-- -----------------------------------------
-- D. Insertion dans TRAJET (3 Enregistrements)
-- NOTE : id_appareil 1 = Train 300p, id_appareil 3 = Avion 180p
-- -----------------------------------------
INSERT INTO `trajet` (`id_appareil`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`) VALUES
-- 1. Trajet Train Douala -> Yaoundé (Aujourd'hui)
(1, 'DOUALA', 'YAOUNDE', '2025-10-21', '08:00:00', '13:00:00', 300, 6000.00),

-- 2. Trajet Avion Douala -> Maroua (Aujourd'hui)
(3, 'DOUALA', 'MAROUA', '2025-10-21', '14:30:00', '15:45:00', 75, 45000.00),

-- 3. Trajet Train Yaoundé -> Douala (Demain)
(1, 'YAOUNDE', 'DOUALA', '2025-10-22', '08:00:00', '13:00:00', 300, 6000.00);


-- -----------------------------------------
-- E. Insertion dans UTILISATEUR (1 Enregistrement)
-- -----------------------------------------
INSERT INTO `utilisateur` (`nom`, `prenom`, `email`, `mot_de_passe`, `telephone`) VALUES
('DUPONT', 'Alice', 'alice.dupont@test.com', 'motdepassehash', '699112233');

-- -----------------------------------------
-- F. Insertion dans PAIEMENT (1 Enregistrement)
-- -----------------------------------------
INSERT INTO `paiement` (`montant`, `methode`, `statut`) VALUES
(18000.00, 'Mobile Money', 'Validé');

-- -----------------------------------------
-- G. Insertion dans PASSAGER (2 Enregistrements)
-- -----------------------------------------
INSERT INTO `passager` (`nom`, `prenom`, `type_piece_identite`, `num_piece_identite`) VALUES
('DUPONT', 'Alice', 'CNI', '001A'),
('SMITH', 'Bob', 'Passeport', '002B');

-- -----------------------------------------
-- H. Insertion dans RESERVATION (1 Enregistrement)
-- NOTE : id_utilisateur 1, id_paiement 1
-- -----------------------------------------
INSERT INTO `reservation` (`id_utilisateur`, `id_paiement`, `statut`) VALUES
(1, 1, 'Confirmée');

-- -----------------------------------------
-- I. Insertion dans BILLET (2 Enregistrements)
-- NOTE :
-- - id_reservation 1, id_trajet 1 (Train Douala->Yaounde), 
-- - id_classe 1 (Standard), id_classe 2 (Première)
-- -----------------------------------------
INSERT INTO `billet` (`id_reservation`, `id_passager`, `id_trajet`, `id_classe`, `code_billet`, `prix_final`, `numero_siege`, `statut`) VALUES
(1, 1, 1, 1, 'TR001ST', 6000.00, 'A12', 'Émis'),  -- Alice, Train, Standard
(1, 2, 1, 2, 'TR002PR', 9000.00, 'V01', 'Émis');  -- Bob, Train, Première