<?php
// /models/TrajetModel.php

require_once __DIR__ . '/Database.php'; 

class TrajetModel {
    private PDO $pdo;

    public function __construct() {
        try {
            // Assurez-vous que getPDOConnection() retourne une instance de PDO
            $this->pdo = getPDOConnection(); 
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            // Laissez l'application gérer l'erreur d'une manière sécurisée
            throw new Exception("Le service de base de données est actuellement indisponible.");
        }
    }

    /**
     * Récupère TOUS les trajets (debug seulement) - aucun filtrage
     */
    public function getAllTrajets(): array {
        $sql = "SELECT t.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, t.heure_depart, 
                       a.capacite_totale, mt.nom_compagnie, mt.type
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                ORDER BY t.date_depart DESC
                LIMIT 100";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ======================================================================
    // 1. FONCTIONS POUR LE REMPLISSAGE INITIAL DU FORMULAIRE ET FILTRES AJAX
    // ======================================================================

    /**
     * Récupère les villes de départ distinctes DONT AU MOINS UN TRAJET EST DISPONIBLE (>= 1 place).
     * @param string $typeTransport Le type de transport.
     * @return array La liste des villes de départ.
     */
    public function getDistinctDepartCities(string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.ville_depart 
            FROM (
                SELECT 
                    t.ville_depart,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.ville_depart, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.ville_depart ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }
    
    /**
     * Récupère toutes les villes d'arrivée distinctes pour un type de transport donné,
     * pour les trajets où il reste au moins 1 place.
     * Utilisé pour l'initialisation de la liste des arrivées SANS filtre de départ.
     * @param string $typeTransport Le type de transport.
     * @return array La liste des villes d'arrivée.
     */
    public function getDistinctArrivalCities(string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.ville_arrivee 
            FROM (
                SELECT 
                    t.ville_arrivee,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                -- Jointures corrigées
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.ville_arrivee, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.ville_arrivee ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }

    /**
     * Récupère toutes les classes disponibles.
     */
    public function getAllClasses(): array {
        $sql = "SELECT nom_classe FROM classe ORDER BY coefficient_prix ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupère les villes d'arrivée distinctes DÉPENDANT d'une ville de départ donnée,
     * pour les trajets où il reste au moins 1 place.
     * @param string $depart Ville de départ sélectionnée.
     * @param string $typeTransport Le type de transport.
     * @return array La liste filtrée des villes d'arrivée.
     */
    public function getAvailableArrivals(string $depart, string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.ville_arrivee 
            FROM (
                SELECT 
                    t.ville_arrivee,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.ville_arrivee, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.ville_arrivee ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN); 
    }

    /**
     * Variante de getAvailableArrivals sans filtrage sur la date (utile en fallback/debug)
     */
    public function getAvailableArrivalsNoDate(string $depart, string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.ville_arrivee 
            FROM (
                SELECT 
                    t.ville_arrivee,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.ville_arrivee, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.ville_arrivee ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère toutes les dates et heures disponibles pour une liaison spécifique
     * qui ont au moins 1 place restante.
     * @param string $depart Ville de départ.
     * @param string $arrivee Ville d'arrivée.
     * @param string $typeTransport Le type de transport.
     */
    public function getDatesAndTimes(string $depart, string $arrivee, string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.date_depart, T1.heure_depart
            FROM (
                SELECT 
                    t.date_depart, 
                    t.heure_depart,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    LOWER(t.ville_arrivee) = LOWER(:arrivee) AND
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.date_depart, t.heure_depart, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.date_depart ASC, T1.heure_depart ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':arrivee', $arrivee);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Extraction distincte des colonnes (même si le SQL utilise déjà DISTINCT, la logique PHP est plus sûre pour la structure)
        $dates = array_unique(array_column($results, 'date_depart'));
        $heures = array_unique(array_column($results, 'heure_depart'));
        
        return [
            'dates' => array_values($dates), 
            'heures' => array_values($heures)
        ];
    }

    /**
     * Variante de getDatesAndTimes sans filtrage sur la date (fallback)
     */
    public function getDatesAndTimesNoDate(string $depart, string $arrivee, string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.date_depart, T1.heure_depart
            FROM (
                SELECT 
                    t.date_depart, 
                    t.heure_depart,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    LOWER(t.ville_arrivee) = LOWER(:arrivee) AND
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, t.date_depart, t.heure_depart, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.date_depart ASC, T1.heure_depart ASC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':arrivee', $arrivee);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dates = array_unique(array_column($results, 'date_depart'));
        $heures = array_unique(array_column($results, 'heure_depart'));
        return [ 'dates' => array_values($dates), 'heures' => array_values($heures) ];
    }

    /**
     * Récupère les noms des compagnies disponibles pour une liaison spécifique
     * qui ont au moins 1 place restante.
     */
    public function getAvailableCompanies(string $depart, string $arrivee, string $typeTransport): array {
        
        $sql = "
            SELECT DISTINCT T1.nom_compagnie
            FROM (
                SELECT 
                    mt.nom_compagnie,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    LOWER(t.ville_arrivee) = LOWER(:arrivee) AND
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, mt.nom_compagnie, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.nom_compagnie ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':arrivee', $arrivee);
        $stmt->bindParam(':typeTransport', $typeTransport);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Variante de getAvailableCompanies sans filtrage sur la date (fallback)
     */
    public function getAvailableCompaniesNoDate(string $depart, string $arrivee, string $typeTransport): array {
        $sql = "
            SELECT DISTINCT T1.nom_compagnie
            FROM (
                SELECT 
                    mt.nom_compagnie,
                    (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
                FROM trajet t
                JOIN appareil a ON t.id_appareil = a.id_appareil
                JOIN moyen_transport mt ON a.id_transport = mt.id_transport
                LEFT JOIN billet b ON t.id_trajet = b.id_trajet
                WHERE 
                    LOWER(t.ville_depart) = LOWER(:depart) AND 
                    LOWER(t.ville_arrivee) = LOWER(:arrivee) AND
                    mt.type = :typeTransport
                GROUP BY t.id_trajet, mt.nom_compagnie, a.capacite_totale
                HAVING places_restantes >= 1
            ) AS T1
            ORDER BY T1.nom_compagnie ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':arrivee', $arrivee);
        $stmt->bindParam(':typeTransport', $typeTransport);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // ======================================================================
    // 3. FONCTION DE RECHERCHE PRINCIPALE
    // ======================================================================
    
    /**
     * Recherche les trajets disponibles pour 1 passager (fixé) avec tous les filtres.
     * Note: La variable $heureDepart n'est plus utilisée dans la requête finale car elle est généralement filtrée côté client,
     * mais on la laisse dans la signature pour la compatibilité si vous en avez besoin plus tard.
     */
    public function searchTrajets(string $depart, string $arrivee, string $dateDepart, string $typeTransport, string $nomCompagnie = null): array {
        
        $sql = "
            SELECT 
                t.*, 
                a.capacite_totale,                                          
                mt.nom_compagnie,
                c.id_classe,
                c.nom_classe,
                c.coefficient_prix,
                -- Calcule le nombre de billets (sièges) vendus pour ce trajet
                COALESCE(COUNT(b.id_billet), 0) AS places_vendues,
                -- Calcule le nombre de places restantes
                (a.capacite_totale - COALESCE(COUNT(b.id_billet), 0)) AS places_restantes
            FROM trajet t
            -- Jointure vers Appareil (pour la capacité)
            JOIN appareil a ON t.id_appareil = a.id_appareil
            -- Jointure vers Moyen_Transport (pour le type et la compagnie)
            JOIN moyen_transport mt ON a.id_transport = mt.id_transport
            -- Jointure des classes pour lister toutes les options (Standard/Première)
            CROSS JOIN classe c 
            -- Jointure des Billets pour compter les places vendues (LEFT JOIN pour inclure les trajets sans réservation)
            LEFT JOIN billet b ON t.id_trajet = b.id_trajet
            WHERE 
                t.ville_depart = :depart AND 
                t.ville_arrivee = :arrivee AND 
                t.date_depart = :dateDepart AND
                mt.type = :typeTransport
                " . ($nomCompagnie ? " AND mt.nom_compagnie = :nomCompagnie " : "") . " -- Filtre optionnel par compagnie
            GROUP BY 
                t.id_trajet, a.capacite_totale, mt.nom_compagnie, c.id_classe, c.nom_classe, c.coefficient_prix
            HAVING 
                places_restantes >= 1 -- FIXÉ À 1 PASSAGER (par défaut)
            ORDER BY 
                t.heure_depart ASC, c.coefficient_prix DESC
        ";

        $stmt = $this->pdo->prepare($sql);

        // Liaison des paramètres
        $stmt->bindParam(':depart', $depart);
        $stmt->bindParam(':arrivee', $arrivee);
        $stmt->bindParam(':dateDepart', $dateDepart);
        $stmt->bindParam(':typeTransport', $typeTransport);
        
        if ($nomCompagnie) {
            $stmt->bindParam(':nomCompagnie', $nomCompagnie);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}