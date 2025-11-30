CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(25) NOT NULL,
  `prenom` VARCHAR(30) NOT NULL,
  `email` VARCHAR(30) NOT NULL UNIQUE,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `telephone` VARCHAR(20) NULL,
  PRIMARY KEY (`id_utilisateur`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 3. Table MOYEN_TRANSPORT (Bus, Train, Avion)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `moyen_transport` (
  `id_transport` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('Train', 'Bus', 'Avion') NOT NULL, -- Type de transport
  `nom_compagnie` VARCHAR(100) NOT NULL,
  `numero` VARCHAR(50) UNIQUE NOT NULL,
  PRIMARY KEY (`id_transport`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 4. Table TRAJET (Liaison Spécifique)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `trajet` (
  `id_trajet` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_transport` INT UNSIGNED NOT NULL,
  `ville_depart` VARCHAR(100) NOT NULL,
  `ville_arrivee` VARCHAR(100) NOT NULL,
  `date_depart` DATE NOT NULL,
  `heure_depart` TIME NOT NULL,
  `heure_arrivee` TIME NOT NULL,
  `duree` INT UNSIGNED NULL, -- Durée en minutes
  `prix_base` DECIMAL(10, 2) NOT NULL, -- Prix du billet standard
  `places_disponibles` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_trajet`),
  INDEX `fk_trajet_transport_idx` (`id_transport` ASC),
  CONSTRAINT `fk_trajet_transport`
    FOREIGN KEY (`id_transport`)
    REFERENCES `moyen_transport` (`id_transport`)
    ON DELETE RESTRICT -- Empêche la suppression d'un transport s'il a des trajets
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 5. Table CLASSE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `classe` (
  `id_classe` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_classe` VARCHAR(50) NOT NULL UNIQUE, -- Ex: Standard, VIP, Business
  `coefficient_prix` DECIMAL(3, 2) NOT NULL DEFAULT 1.00, -- 1.00 pour standard, 1.50 pour VIP
  `description` TEXT NULL,
  PRIMARY KEY (`id_classe`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 6. Table PAIEMENT
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `paiement` (
  `id_paiement` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `montant` DECIMAL(10, 2) NOT NULL,
  `date_paiement` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `methode` VARCHAR(50) NOT NULL, -- Ex: Carte, PayPal, Mobile Money
  `statut` ENUM('En_Attente', 'Validé', 'Échec') NOT NULL DEFAULT 'En_Attente',
  PRIMARY KEY (`id_paiement`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 7. Table PASSAGER (La personne qui utilise le billet)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `passager` (
  `id_passager` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `type_piece_identite` VARCHAR(50) NULL,
  `num_piece_identite` VARCHAR(100) NULL,
  PRIMARY KEY (`id_passager`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 8. Table RÉSERVATION (La commande)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservation` (
  `id_reservation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_utilisateur` INT UNSIGNED NOT NULL,
  `id_paiement` INT UNSIGNED NULL, -- Paiement est optionnel si la réservation est en attente
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
    ON DELETE SET NULL -- Si un paiement est supprimé (rare), on garde la trace
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- 9. Table BILLET (Le produit final)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `billet` (
  `id_billet` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_reservation` INT UNSIGNED NOT NULL,
  `id_passager` INT UNSIGNED NOT NULL,
  `id_trajet` INT UNSIGNED NOT NULL,
  `id_classe` INT UNSIGNED NOT NULL,
  `code_billet` VARCHAR(100) UNIQUE NOT NULL,
  `prix_final` DECIMAL(10, 2) NOT NULL, -- Prix stocké après calcul dans PHP
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
    ON DELETE CASCADE -- Si la réservation est supprimée, les billets le sont aussi
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













-- Insertion dans la table CLASSE
INSERT INTO `classe` (`nom_classe`, `coefficient_prix`, `description`) VALUES
('Standard', 1.00, 'Classe économique standard.'),
('VIP', 1.50, 'Classe de luxe avec 50% de supplément.'),
('Business', 1.25, 'Classe affaires avec 25% de supplément.');

-- Insertion dans la table MOYEN_TRANSPORT
INSERT INTO `moyen_transport` (`type`, `nom_compagnie`, `numero`) VALUES
('Train', 'Camrail Express', 'TGR-YDE-201'),
('Train', 'Camrail Express', 'TGR-DLA-305'),
('Bus', 'Général Voyage', 'BUS-G-45'),
('Avion', 'Cameroun Airlines', 'CA-502');

-- Insertion dans la table TRAJET
-- ID 1: Train Yaoundé -> Douala (Départ tardif)
INSERT INTO `trajet` (`id_transport`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`, `places_disponibles`) VALUES
(1, 'YAOUNDE', 'DOUALA', '2025-10-19', '18:00:00', '23:30:00', 330, 9500.00, 150);

-- ID 2: Train Douala -> Yaoundé (Départ matinal)
INSERT INTO `trajet` (`id_transport`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`, `places_disponibles`) VALUES
(2, 'DOUALA', 'YAOUNDE', '2025-10-19', '07:30:00', '13:00:00', 330, 8500.00, 50); -- Places limitées pour test

-- ID 3: Train Yaoundé -> Douala (Jour suivant)
INSERT INTO `trajet` (`id_transport`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`, `places_disponibles`) VALUES
(1, 'YAOUNDE', 'DOUALA', '2025-10-20', '10:00:00', '15:30:00', 330, 9000.00, 200);

-- ID 4: Bus Yaoundé -> Bafoussam (Autre type de transport)
INSERT INTO `trajet` (`id_transport`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`, `places_disponibles`) VALUES
(3, 'YAOUNDE', 'BAFOUSSAM', '2025-10-19', '12:00:00', '17:00:00', 300, 6000.00, 30);

-- ID 5: Avion Douala -> Maroua
INSERT INTO `trajet` (`id_transport`, `ville_depart`, `ville_arrivee`, `date_depart`, `heure_depart`, `heure_arrivee`, `duree`, `prix_base`, `places_disponibles`) VALUES
(4, 'DOUALA', 'MAROUA', '2025-10-25', '09:00:00', '10:30:00', 90, 45000.00, 100);



