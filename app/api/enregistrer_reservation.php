<?php

while (ob_get_level()) { ob_end_clean(); }
// Start a fresh output buffer to capture any accidental output during execution
ob_start();
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once __DIR__ . '/../models/Database.php';

// Register shutdown function to catch fatal errors and ensure JSON response
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $msg = sprintf("Fatal error: %s in %s on line %d", $err['message'], $err['file'], $err['line']);
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " SHUTDOWN_FATAL: " . $msg . "\n", FILE_APPEND);
        // Attempt to clean buffers and return JSON error
        $pre = '';
        if (ob_get_length()) {
            $pre = ob_get_contents();
            if (trim($pre) !== '') {
                @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " SHUTDOWN_PREOUTPUT_LEN: " . strlen($pre) . " bytes\n", FILE_APPEND);
                @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " SHUTDOWN_PREOUTPUT_HEX: " . bin2hex(substr($pre,0,512)) . (strlen($pre) > 512 ? '...': '') . "\n", FILE_APPEND);
            }
        }
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur serveur critique']);
    }
});

// Démarrer la session pour récupérer l'utilisateur connecté si disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function error_response($msg) {
    // Log error for debugging
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " ERROR: " . $msg . "\n", FILE_APPEND);
    // Log any previous output (helps debugging invalid non-JSON responses)
    $pre = '';
    if (ob_get_length()) {
        $pre = ob_get_contents();
        if (trim($pre) !== '') {
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " PRE_OUTPUT_LEN: " . strlen($pre) . " bytes\n", FILE_APPEND);
            // Dump a hex representation to reliably detect BOMs and invisible bytes
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " PRE_OUTPUT_HEX: " . bin2hex(substr($pre,0,512)) . (strlen($pre) > 512 ? '... (truncated)' : '') . "\n", FILE_APPEND);
        }
    }
    // Nettoyer tout contenu envoyé avant la réponse JSON
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function success_response($data) {
    // Log any previous output (helps debugging invalid non-JSON responses)
    $pre = '';
    if (ob_get_length()) {
        $pre = ob_get_contents();
        if (trim($pre) !== '') {
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " PRE_OUTPUT_LEN: " . strlen($pre) . " bytes\n", FILE_APPEND);
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " PRE_OUTPUT_HEX: " . bin2hex(substr($pre,0,512)) . (strlen($pre) > 512 ? '... (truncated)' : '') . "\n", FILE_APPEND);
        }
    }
    // Nettoyer tout contenu envoyé avant la réponse JSON
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// NOTE: This endpoint accepts both `type_transport` (string) and `id_transport` (integer) in the
// incoming payload. If `id_transport` is provided and >0 it will be used preferentially to
// resolve a `trajet`. If not provided, the endpoint will try to infer the transport type
// from `type_transport` or from the HTTP referer page and will lookup the corresponding
// `id_transport` in the `moyen_transport` table.

try {
    // 1. Initialisation et Récupération des données
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) error_response('Aucune donnée reçue');

    // Champs attendus (AJOUT de reference_paiement_gateway)
    $nom = trim($data['nom'] ?? '');
    $prenom = trim($data['prenom'] ?? '');
    $type_piece = trim($data['type_piece'] ?? '');
    $num_piece = trim($data['num_piece'] ?? '');
    $montant = floatval($data['montant'] ?? 0);
    $methode = trim($data['methode'] ?? 'Mobile Money');
    $statut_paiement = 'Validé';
    $reference_paiement_gateway = trim($data['reference_paiement_gateway'] ?? ''); // <--- NOUVEAU
    $type_transport = trim($data['type_transport'] ?? ''); // <--- NOUVEAU
    // Prioritaire: id_transport numérique envoyé depuis le client (plus fiable que referer/type)
    $id_transport_payload = intval($data['id_transport'] ?? 0);
    // Priorité: prendre l'utilisateur connecté depuis la session, sinon accepter l'id envoyé en POST
    $sessionUserId = $_SESSION['user']['id_utilisateur'] ?? 0;
    $id_utilisateur = $sessionUserId > 0 ? intval($sessionUserId) : intval($data['id_utilisateur'] ?? 0);
    $trajet_id = intval($data['trajet_id'] ?? 0);
    $classe_nom = trim($data['classe'] ?? 'Standard');

    // ... (Vérification des données inchangée) ...
    // Vérification des données requises
    if (!$nom || !$prenom || !$num_piece || !$classe_nom || $montant <= 0) {
        error_response('Données manquantes : nom, prenom, num_piece, classe, ou montant.');
    }
    // La table `reservation` telle que définie dans includes/agences.sql attend un id_utilisateur NOT NULL.
    if ($id_utilisateur <= 0) {
        error_response('id_utilisateur manquant ou invalide. Fournir un id_utilisateur valide.');
    }

    // Connexion à la base de données
    // La connexion est fournie par la fonction `getPDOConnection()` dans `app/models/Database.php`.
    $pdo = getPDOConnection();

    // If trajet_id is empty, try to infer transport from referer page or payload type, then pick a matching trajet
    if ($trajet_id <= 0) {
        $referer = $_SERVER['HTTP_REFERER'] ?? ($data['referer'] ?? '');
        $refPage = '';
        if ($referer) {
            $refPath = parse_url($referer, PHP_URL_PATH);
            $refPage = basename($refPath);
        }

        // Map known pages to transport type name (use the `moyen_transport.type` values)
        $pageToTransport = [
            'bateau-page.php' => 'Bateau',
            'avion-page.php' => 'Avion',
            'train-page.php' => 'Train',
            'bus-page.php' => 'Bus'
        ];

        // If we can infer the page, set $type_transport (string) and continue to mapping logic
        if ($refPage && isset($pageToTransport[$refPage])) {
            $type_transport = $pageToTransport[$refPage];
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Inferred type_transport='{$type_transport}' from referer={$refPage}\n", FILE_APPEND);
        }

        $id_transport = null;

        // 1) If client provided numeric id_transport, use it (most reliable)
        if ($id_transport_payload > 0) {
            $id_transport = $id_transport_payload;
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Using id_transport from payload={$id_transport}\n", FILE_APPEND);
        }

        // 2) If not provided, try to resolve from page/type string
        if (!$id_transport && $type_transport) {
            // Lookup id_transport from moyen_transport table using the type string
            $getType = $pdo->prepare('SELECT id_transport FROM moyen_transport WHERE LOWER(type) = ? LIMIT 1');
            $getType->execute([strtolower($type_transport)]);
            $tRow = $getType->fetch();
            if ($tRow) {
                $id_transport = intval($tRow['id_transport']);
                @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Found id_transport={$id_transport} for type_transport='{$type_transport}'\n", FILE_APPEND);
            }
        }

        if ($id_transport) {
            // Select first trajet that uses an appareil with this id_transport
            $getTrajet = $pdo->prepare('SELECT t.id_trajet FROM trajet t JOIN appareil a ON t.id_appareil = a.id_appareil WHERE a.id_transport = ? LIMIT 1');
            $getTrajet->execute([$id_transport]);
            $row = $getTrajet->fetch();
            if ($row) {
                $trajet_id = intval($row['id_trajet']);
                @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Using first trajet id={$trajet_id} for id_transport={$id_transport}\n", FILE_APPEND);
            } else {
                error_response('Aucun trajet disponible pour le type de transport demandé.');
            }
        } else {
            // If we cannot infer id_transport or type_transport, do NOT silently pick the first global trajet
            @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " ERROR: Unable to infer id_transport or type_transport from payload or referer. Aborting to avoid wrong trajet selection.\n", FILE_APPEND);
            error_response('Impossible d\'inférer le type de transport. Fournir `id_transport` ou `trajet_id` dans la requête.');
        }
    }

    // Vérifier l'existence de l'utilisateur si un id est fourni; sinon créer un utilisateur automatique

    if ($id_utilisateur > 0) {
        $chk = $pdo->prepare('SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ? LIMIT 1');
        $chk->execute([$id_utilisateur]);
        $found = $chk->fetch();
        if (!$found) {
            $id_utilisateur = 0;
        }
    }

    if ($id_utilisateur <= 0) {
        $safeNom = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(substr($nom,0,10)));
        $safePrenom = preg_replace('/[^A-Za-z0-9]/', '', strtoupper(substr($prenom,0,10)));
        $email = strtolower($safeNom . '.' . $safePrenom . '.' . time() . '@local.test');
        $randomPass = bin2hex(random_bytes(6));
        $hash = password_hash($randomPass, PASSWORD_DEFAULT);

        // Créer l'utilisateur sans transaction
        $insUser = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)');
        $insUser->execute([$nom, $prenom, $email, $hash]);
        $id_utilisateur = $pdo->lastInsertId();
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " INFO: Created auto-user id={$id_utilisateur} for reservation\n", FILE_APPEND);

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['user'] = ['id_utilisateur' => $id_utilisateur, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email];
        }
    }
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Using id_utilisateur={$id_utilisateur} for reservation\n", FILE_APPEND);

    // Vérifier que le trajet existe et récupérer l'appareil associé
    $chkTrajet = $pdo->prepare('SELECT id_trajet, prix_base, id_appareil FROM trajet WHERE id_trajet = ? LIMIT 1');
    $chkTrajet->execute([$trajet_id]);
    $trajetRow = $chkTrajet->fetch();
    if (!$trajetRow) {
        throw new Exception('Trajet inconnu');
    }
    $id_appareil_from_trajet = isset($trajetRow['id_appareil']) ? intval($trajetRow['id_appareil']) : null;

    // 2..n: effectuer toutes les opérations critiques dans une transaction
    $pdo->beginTransaction();
    try {
        // Enregistrer le paiement
        $stmt = $pdo->prepare('INSERT INTO paiement (montant, methode, statut) VALUES (?, ?, ?)');
        $stmt->execute([$montant, $methode, $statut_paiement]);
        $id_paiement = $pdo->lastInsertId();

        // Enregistrer le passager
        $stmt = $pdo->prepare('INSERT INTO passager (nom, prenom, type_piece_identite, num_piece_identite) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nom, $prenom, $type_piece, $num_piece]);
        $id_passager = $pdo->lastInsertId();

        // Enregistrer la réservation (avec id_paiement)
        $stmt = $pdo->prepare('INSERT INTO reservation (id_utilisateur, id_paiement, statut) VALUES (?, ?, ?)');
        $stmt->execute([$id_utilisateur, $id_paiement, 'Confirmée']);
        $id_reservation = $pdo->lastInsertId();

        // 5. Trouver id_classe
    $stmt = $pdo->prepare('SELECT id_classe FROM classe WHERE nom_classe = ?');
    $stmt->execute([$classe_nom]);
    $row = $stmt->fetch();
    if (!$row) throw new Exception('Classe inconnue');
    $id_classe = $row['id_classe'];

    // 6. Générer code_billet unique
    $code_billet = strtoupper(substr($nom,0,2) . substr($prenom,0,2) . time() . rand(10,99));

    // Générer un numéro de siège unique (ex: 12A, 15B, etc.)
    // On récupère le nombre de billets déjà émis pour ce trajet
    $stmt = $pdo->prepare('SELECT COUNT(*) as nb FROM billet WHERE id_trajet = ?');
    $stmt->execute([$trajet_id]);
    $nbBillets = intval($stmt->fetchColumn());
    // Génération simple: numéro = nbBillets+1, lettre = A + (nbBillets % 6)
    $seatNumber = ($nbBillets + 1) . chr(65 + ($nbBillets % 6)); // 1A, 2B, ...

    // 7. Prix final (pour démo = montant)
    $prix_final = $montant;

    // 8. Billet (inclure id_appareil si la colonne existe)
    // Note: la colonne `id_appareil` doit avoir été ajoutée en DB via ALTER TABLE.
    $stmt = $pdo->prepare('INSERT INTO billet (id_reservation, id_passager, id_trajet, id_appareil, id_classe, code_billet, prix_final, numero_siege, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id_reservation, $id_passager, $trajet_id, $id_appareil_from_trajet, $id_classe, $code_billet, $prix_final, $seatNumber, 'Émis']);

    // 9. Diminuer la capacité de l'appareil associé au trajet (utiliser l'id_appareil récupéré précédemment)
    if ($id_appareil_from_trajet) {
        // Récupérer le type de moyen pour journalisation
        $stmt = $pdo->prepare('SELECT mt.type FROM appareil a JOIN moyen_transport mt ON a.id_transport = mt.id_transport WHERE a.id_appareil = ? LIMIT 1');
        $stmt->execute([$id_appareil_from_trajet]);
        $mtRow = $stmt->fetch();
        $type_transport_for_log = $mtRow['type'] ?? $type_transport;
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " DEBUG: Diminution capacité appareil id={$id_appareil_from_trajet} type={$type_transport_for_log} pour trajet={$trajet_id}\n", FILE_APPEND);
        // Diminuer la capacité uniquement sur cet appareil
        $pdo->prepare('UPDATE appareil SET capacite_totale = GREATEST(capacite_totale - 1, 0) WHERE id_appareil = ?')->execute([$id_appareil_from_trajet]);
    }

    // Commit transaction
    $pdo->commit();

    // Réponse de succès (IMPORTANT : renvoyer le code_billet et le numéro de siège)
    success_response(['message' => 'Réservation et billet créés avec succès.', 'id_reservation' => $id_reservation, 'code_billet' => $code_billet, 'numero_siege' => $seatNumber]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack(); // Annuler si erreur
    }
    // Log exception stack trace for debugging
    @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    error_response('Erreur lors de l\'enregistrement: ' . $e->getMessage());
}
    // Catch for outer try (ensure the outer try {...} from the top has a matching catch)
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/../../logs/api_errors.log', date('Y-m-d H:i:s') . " OUTER_EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
        error_response('Erreur serveur: ' . $e->getMessage());
    }