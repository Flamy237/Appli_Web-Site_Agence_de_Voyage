<?php
// Affichage du billet après paiement
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/lib/QRCode.php';

// Récupérer code de billet à partir de la querystring
$code = $_GET['code'] ?? '';
$ref = $_GET['ref'] ?? '';

// Sécurité basique
if (!$code) {
    http_response_code(400);
    echo "Billet non spécifié.";
    exit;
}

$pdo = getPDOConnection();

// Chercher le billet et joindre trajet, appareil, moyen_transport, passager et classe
$sql = "SELECT b.code_billet, b.prix_final, b.numero_siege, b.statut,
               p.nom AS passager_nom, p.prenom AS passager_prenom,
               t.ville_depart, t.ville_arrivee, t.date_depart, t.heure_depart,
               a.numero_identification AS appareil_numero, mt.type AS moyen_type, mt.nom_compagnie,
               c.nom_classe AS classe
        FROM billet b
        JOIN passager p ON b.id_passager = p.id_passager
        JOIN trajet t ON b.id_trajet = t.id_trajet
        JOIN appareil a ON t.id_appareil = a.id_appareil
        JOIN moyen_transport mt ON a.id_transport = mt.id_transport
        LEFT JOIN classe c ON b.id_classe = c.id_classe
        WHERE b.code_billet = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo "Billet introuvable.";
    exit;
}

// Choisir icône selon moyen_type
$iconSvg = '';
switch (strtolower($row['moyen_type'])) {
    case 'avion':
        $iconSvg = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 16c4 0 6-1 9-3s4-4 7-4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 3l-6 4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $bgColor = '#e11d48';
        break;
    case 'train':
        $iconSvg = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="5" width="18" height="11" rx="2" stroke="#fff" stroke-width="1.5"/><path d="M7 17v2" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/><path d="M17 17v2" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>';
        $bgColor = '#0ea5e9';
        break;
    case 'bus':
        $iconSvg = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="12" rx="2" stroke="#fff" stroke-width="1.5"/><path d="M7 20v-3" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/><path d="M17 20v-3" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>';
        $bgColor = '#10b981';
        break;
    case 'bateau':
    case 'navire':
        $iconSvg = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 15s1.5 3 9 3 9-3 9-3v-2s-2 1-9 1-9-1-9-1v2z" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 8l5 3 5-3" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $bgColor = '#f97316';
        break;
    default:
        $iconSvg = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="#fff" stroke-width="1.5"/></svg>';
        $bgColor = '#6b7280';
}

// Small helper to format date
function formatDatePretty($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d) return $date;
    return $d->format('d/m/Y');
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Votre billet - <?php echo htmlspecialchars($row['nom_compagnie']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background:#f3f4f6; margin:0; padding:40px; display:flex; justify-content:center; }
        .ticket { width: 920px; background: #fff; border-radius:12px; box-shadow:0 10px 30px rgba(2,6,23,0.12); overflow:hidden; display:flex; }
        .ticket-left { width: 680px; padding:28px 32px; background: linear-gradient(135deg, <?php echo $bgColor; ?> 0%, #111827 100%); color:#fff; position:relative }
        .ticket-right { width:240px; background:#fff; padding:20px; border-left:8px dashed #f3f4f6; display:flex; flex-direction:column; justify-content:space-between }
        .brand { display:flex; align-items:center; gap:16px }
        .brand h1 { font-size:28px; margin:0; letter-spacing:1px }
        .big { font-size:40px; font-weight:700; }
        .sub { opacity:0.9; font-weight:500 }
        .row { display:flex; justify-content:space-between; align-items:center; margin-top:18px }
        .col { display:flex; flex-direction:column }
        .label { font-size:12px; opacity:0.85 }
        .value { font-size:18px; font-weight:600 }
        .seat { font-size:28px; font-weight:800 }
        .qr-placeholder { width:100%; height:90px; background:#f3f4f6; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#9ca3af }
        .small-muted { color: rgba(255,255,255,0.85); font-size:13px }
        .passenger { margin-top:20px }
        .company { font-size:14px; font-weight:600; margin-top:8px }
        .right-top { text-align:center }
        .right-bottom { text-align:center }
        .btn-download { display:inline-block; padding:10px 14px; background:<?php echo $bgColor; ?>; color:#fff; border-radius:8px; text-decoration:none; font-weight:600 }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-left">
            <div class="brand">
                <div style="width:72px;height:72px;border-radius:14px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center"><?php echo $iconSvg; ?></div>
                <div>
                    <h1><?php echo htmlspecialchars($row['nom_compagnie']); ?></h1>
                    <div class="small-muted">Classe: <?php echo htmlspecialchars($row['classe'] ?? 'Standard'); ?></div>
                </div>
            </div>

            <div class="passenger">
                <div class="label">Passager</div>
                <div class="value big"><?php echo htmlspecialchars($row['passager_nom'] . ' ' . $row['passager_prenom']); ?></div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="label">Départ</div>
                    <div class="value"><?php echo htmlspecialchars($row['ville_depart']); ?></div>
                    <div class="small-muted"><?php echo formatDatePretty($row['date_depart']); ?> • <?php echo htmlspecialchars(substr($row['heure_depart'],0,5)); ?></div>
                </div>
                <div class="col" style="text-align:right">
                    <div class="label">Destination</div>
                    <div class="value"><?php echo htmlspecialchars($row['ville_arrivee']); ?></div>
                </div>
            </div>

            <div class="row" style="margin-top:26px">
                <div class="col">
                    <div class="label">Appareil</div>
                    <div class="value"><?php echo htmlspecialchars($row['appareil_numero']); ?></div>
                </div>
                <div class="col">
                    <div class="label">Siège</div>
                    <div class="seat"><?php echo htmlspecialchars($row['numero_siege']); ?></div>
                </div>
                <div class="col" style="text-align:right">
                    <div class="label">Prix</div>
                    <div class="value"><?php echo number_format($row['prix_final'],0,',',' '); ?> FCFA</div>
                </div>
            </div>

            <div style="margin-top:22px;clear:both;">
                <div class="small-muted">Référence paiement</div>
                <div style="font-weight:700;letter-spacing:1px;max-width:320px;word-break:break-word;"><?php echo htmlspecialchars($ref ?: '—'); ?></div>
            </div>
        </div>

        <div class="ticket-right">
            <div class="right-top">
                <div style="font-size:12px;color:#374151">Billet</div>
                <div style="font-size:20px;font-weight:700;margin-top:6px"><?php echo htmlspecialchars($row['code_billet']); ?></div>
                <div class="company" style="color:#374151;margin-top:12px"><?php echo htmlspecialchars($row['nom_compagnie']); ?></div>
            </div>

            <div>
                <?php
                // Generate QR code from the ticket code (code_billet)
                $qr = new SimpleQRCode($row['code_billet']);
                $qrDataURI = $qr->toDataURI(6);
                ?>
                <div class="qr-placeholder"><img src="<?php echo htmlspecialchars($qrDataURI); ?>" alt="QR code billet" style="width:120px;height:120px;border-radius:6px;" /></div>
            </div>

            <div class="right-bottom">
                <div style="font-size:12px;color:#6b7280">Imprimé le</div>
                <div style="font-weight:600;margin-top:6px"><?php echo date('d/m/Y H:i'); ?></div>
                <div style="margin-top:12px">
                    <a class="btn-download" href="#" onclick="window.print();return false;">Imprimer / Télécharger</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
