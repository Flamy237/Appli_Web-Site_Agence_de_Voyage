<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/Database.php';

$pdo = getPDOConnection();

// Récupérer les paramètres de filtrage/recherche
$search = $_GET['search'] ?? '';
$statut = $_GET['statut'] ?? '';
$tri = $_GET['tri'] ?? 'date_desc';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construction de la requête
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (b.code_billet LIKE ? OR p.nom LIKE ? OR p.prenom LIKE ? OR mt.nom_compagnie LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($statut) {
    $where .= " AND b.statut = ?";
    $params[] = $statut;
}

// Déterminer l'ordre de tri
$order = "b.id_billet DESC";
switch ($tri) {
    case 'date_asc':
        $order = "t.date_depart ASC";
        break;
    case 'date_desc':
        $order = "t.date_depart DESC";
        break;
    case 'prix_asc':
        $order = "b.prix_final ASC";
        break;
    case 'prix_desc':
        $order = "b.prix_final DESC";
        break;
    case 'nom_asc':
        $order = "p.nom ASC, p.prenom ASC";
        break;
}

// Requête pour compter le total
$count_sql = "SELECT COUNT(*) as total FROM billet b
              JOIN passager p ON b.id_passager = p.id_passager
              JOIN trajet t ON b.id_trajet = t.id_trajet
              JOIN appareil a ON t.id_appareil = a.id_appareil
              JOIN moyen_transport mt ON a.id_transport = mt.id_transport
              WHERE $where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

// Requête principale pour les billets
$sql = "SELECT b.id_billet, b.code_billet, b.prix_final, b.numero_siege, b.statut,
               p.nom AS passager_nom, p.prenom AS passager_prenom,
               t.ville_depart, t.ville_arrivee, t.date_depart, t.heure_depart, t.heure_arrivee,
               mt.type AS moyen_type, mt.nom_compagnie,
               c.nom_classe, r.date_reservation,
               a.numero_identification AS appareil_numero
        FROM billet b
        JOIN passager p ON b.id_passager = p.id_passager
        JOIN trajet t ON b.id_trajet = t.id_trajet
        JOIN appareil a ON t.id_appareil = a.id_appareil
        JOIN moyen_transport mt ON a.id_transport = mt.id_transport
        JOIN reservation r ON b.id_reservation = r.id_reservation
        LEFT JOIN classe c ON b.id_classe = c.id_classe
        WHERE $where
        ORDER BY $order
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$per_page, $offset]));
$billets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statuts disponibles
$statuts_sql = "SELECT DISTINCT statut FROM billet ORDER BY statut";
$statuts_stmt = $pdo->query($statuts_sql);
$statuts = $statuts_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les billets vendus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #475569;
            --accent-color: #3b82f6;
            --danger-color: #ff6b6b;
            --success-color: #51cf66;
            --warning-color: #ffd93d;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --bg-tertiary: #e2e8f0;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #cbd5e1;
            --accent-color: #3b82f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .theme-toggle {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: var(--accent-color);
            color: white;
        }

        .filters {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .filter-group input,
        .filter-group select {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent-color);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 8px;
        }

        .table-wrapper {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--bg-tertiary);
            padding: 15px;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        tr:hover {
            background: var(--bg-tertiary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: rgba(81, 207, 102, 0.2); color: #51cf66; }
        .badge-warning { background: rgba(255, 217, 61, 0.2); color: #ffd93d; }
        .badge-danger { background: rgba(255, 107, 107, 0.2); color: #ff6b6b; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

        .badge-train { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; }
        .badge-bus { background: rgba(34, 197, 94, 0.2); color: #86efac; }
        .badge-avion { background: rgba(249, 115, 22, 0.2); color: #fdba74; }

        .transport-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin-right: 8px;
            font-weight: bold;
        }

        .transport-icon.train { background: rgba(168, 85, 247, 0.2); color: #d8b4fe; }
        .transport-icon.bus { background: rgba(34, 197, 94, 0.2); color: #86efac; }
        .transport-icon.avion { background: rgba(249, 115, 22, 0.2); color: #fdba74; }

        .action-link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .action-link:hover {
            color: #60a5fa;
            text-decoration: underline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-color);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .page-link:hover, .page-link.active {
            background: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .info-box i {
            color: var(--accent-color);
            margin-top: 2px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 13px;
            }

            td, th {
                padding: 10px;
            }

            .header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-ticket-alt"></i>
                Tous les billets vendus
            </h1>
            <div class="header-actions">
                <a href="admin-dashboard.php" class="btn btn-secondary" title="Retour au dashboard">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button id="theme-toggle" class="theme-toggle" title="Changer le thème">
                    <i class="fas fa-sun"></i>
                </button>
            </div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Affichage des billets:</strong> Cette page montre tous les billets vendus avec les informations du passager, du trajet et du statut.
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <form method="GET" id="filterForm" style="display: contents;">
                <div class="filter-group">
                    <label for="search">Rechercher</label>
                    <input type="text" id="search" name="search" placeholder="Code billet, passager, compagnie..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-group">
                    <label for="statut">Statut</label>
                    <select id="statut" name="statut">
                        <option value="">Tous les statuts</option>
                        <?php foreach ($statuts as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $statut === $s ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="tri">Trier par</label>
                    <select id="tri" name="tri">
                        <option value="date_desc" <?php echo $tri === 'date_desc' ? 'selected' : ''; ?>>Date (récent)</option>
                        <option value="date_asc" <?php echo $tri === 'date_asc' ? 'selected' : ''; ?>>Date (ancien)</option>
                        <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix (élevé)</option>
                        <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix (bas)</option>
                        <option value="nom_asc" <?php echo $tri === 'nom_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                    </select>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                    <a href="all-tickets.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total; ?></div>
                <div class="stat-label">Total de billets</div>
            </div>
            <?php
            $emis_sql = "SELECT COUNT(*) as count FROM billet WHERE statut = 'Émis'";
            $emis = $pdo->query($emis_sql)->fetch(PDO::FETCH_ASSOC)['count'];
            $utilise_sql = "SELECT COUNT(*) as count FROM billet WHERE statut = 'Utilisé'";
            $utilise = $pdo->query($utilise_sql)->fetch(PDO::FETCH_ASSOC)['count'];
            $annule_sql = "SELECT COUNT(*) as count FROM billet WHERE statut = 'Annulé'";
            $annule = $pdo->query($annule_sql)->fetch(PDO::FETCH_ASSOC)['count'];
            ?>
            <div class="stat-card">
                <div class="stat-value" style="color: #51cf66;"><?php echo $emis; ?></div>
                <div class="stat-label">Émis</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ffd93d;"><?php echo $utilise; ?></div>
                <div class="stat-label">Utilisés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ff6b6b;"><?php echo $annule; ?></div>
                <div class="stat-label">Annulés</div>
            </div>
        </div>

        <!-- Tableau des billets -->
        <?php if (count($billets) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Code Billet</th>
                            <th>Passager</th>
                            <th>Trajet</th>
                            <th>Transport</th>
                            <th>Date</th>
                            <th>Classe</th>
                            <th>Siège</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billets as $b): ?>
                            <tr>
                                <td>
                                    <code style="background: var(--bg-tertiary); padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($b['code_billet']); ?>
                                    </code>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($b['passager_prenom'] . ' ' . $b['passager_nom']); ?>
                                </td>
                                <td>
                                    <span style="white-space: nowrap;">
                                        <?php echo htmlspecialchars($b['ville_depart']); ?> → <?php echo htmlspecialchars($b['ville_arrivee']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span class="badge badge-<?php echo strtolower($b['moyen_type']); ?>">
                                            <?php
                                            $icon_map = ['Train' => 'fa-train', 'Bus' => 'fa-bus', 'Avion' => 'fa-plane'];
                                            $icon = $icon_map[strtolower($b['moyen_type'])] ?? 'fa-box';
                                            ?>
                                            <i class="fas <?php echo $icon; ?>" style="margin-right: 6px;"></i>
                                            <?php echo htmlspecialchars($b['moyen_type']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span style="white-space: nowrap;">
                                        <?php echo date('d/m/Y', strtotime($b['date_depart'])); ?>
                                        <br>
                                        <span style="color: var(--text-muted); font-size: 12px;">
                                            <?php echo htmlspecialchars($b['heure_depart']); ?> - <?php echo htmlspecialchars($b['heure_arrivee']); ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($b['nom_classe'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($b['numero_siege'] ?? '-'); ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($b['prix_final'], 0, ',', ' '); ?> FCFA</strong>
                                </td>
                                <td>
                                    <?php
                                    $statut = $b['statut'];
                                    $badge_class = match($statut) {
                                        'Émis' => 'badge-success',
                                        'Utilisé' => 'badge-warning',
                                        'Annulé' => 'badge-danger',
                                        default => 'badge-info'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ticket.php?code=<?php echo urlencode($b['code_billet']); ?>" class="action-link" title="Voir le billet">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&tri=<?php echo urlencode($tri); ?>&page=1" class="page-link"><i class="fas fa-angle-double-left"></i></a>
                        <a href="?search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&tri=<?php echo urlencode($tri); ?>&page=<?php echo $page - 1; ?>" class="page-link"><i class="fas fa-angle-left"></i></a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&tri=<?php echo urlencode($tri); ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&tri=<?php echo urlencode($tri); ?>&page=<?php echo $page + 1; ?>" class="page-link"><i class="fas fa-angle-right"></i></a>
                        <a href="?search=<?php echo urlencode($search); ?>&statut=<?php echo urlencode($statut); ?>&tri=<?php echo urlencode($tri); ?>&page=<?php echo $total_pages; ?>" class="page-link"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p><strong>Aucun billet trouvé</strong></p>
                <p style="font-size: 14px;">Essayez de modifier vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        function updateTheme(theme) {
            htmlElement.setAttribute('data-theme', theme);
            themeToggle.innerHTML = `<i class="fas ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;
            localStorage.setItem('theme', theme);
        }

        const savedTheme = localStorage.getItem('theme') || 'dark';
        updateTheme(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            updateTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Auto-submit filter form on change
        document.getElementById('statut').addEventListener('change', () => {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('tri').addEventListener('change', () => {
            document.getElementById('filterForm').submit();
        });
    </script>
</body>
</html>
