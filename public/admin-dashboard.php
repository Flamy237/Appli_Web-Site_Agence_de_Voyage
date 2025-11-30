<?php
// Admin Dashboard - Final Code
// Data is fetched from the database

// --- PARTIE PHP : RÉCUPÉRATION DES DONNÉES ---
session_start();
// Assurez-vous que le chemin vers votre classe Database est correct
// require_once __DIR__ . '/../app/models/Database.php'; 
// Pour l'exemple, nous allons simuler getPDOConnection() si non inclus
if (!function_exists('getPDOConnection')) {
    function getPDOConnection() {
        // Remplacer ces valeurs par vos propres informations de connexion
        $host = 'localhost';
        $db = 'agences';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // En production, il est préférable de ne pas afficher l'erreur brute
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
}
$pdo = getPDOConnection();

// 1. Get total sales (all tickets)
$totalSales = $pdo->query("SELECT SUM(prix_final) as total FROM billet")->fetchColumn() ?: 0;

// 2. Get sales by type
$salesByType = [];
foreach (["Bateau", "Bus", "Train"] as $type) {
    // Les requêtes sont complexes, on les garde telles quelles
    $stmt = $pdo->prepare("SELECT SUM(b.prix_final) FROM billet b JOIN trajet t ON b.id_trajet = t.id_trajet JOIN appareil a ON t.id_appareil = a.id_appareil JOIN moyen_transport mt ON a.id_transport = mt.id_transport WHERE mt.type = ?");
    $stmt->execute([$type]);
    $salesByType[$type] = $stmt->fetchColumn() ?: 0;
}

// 3. Monthly sales for bar chart
$monthlySales = [];
for ($m = 1; $m <= 12; $m++) {
    $stmt = $pdo->prepare("SELECT SUM(b.prix_final) FROM billet b JOIN reservation r ON b.id_reservation = r.id_reservation WHERE MONTH(r.date_reservation) = ? AND YEAR(r.date_reservation) = YEAR(CURDATE())");
    $stmt->execute([$m]);
    $monthlySales[$m] = $stmt->fetchColumn() ?: 0;
}

// 4. Sales comparison: previous vs current month
$curMonth = date('n');
$prevMonth = $curMonth == 1 ? 12 : $curMonth - 1;
$stmt = $pdo->prepare("SELECT SUM(b.prix_final) FROM billet b JOIN reservation r ON b.id_reservation = r.id_reservation WHERE MONTH(r.date_reservation) = ? AND YEAR(r.date_reservation) = YEAR(CURDATE())");
$stmt->execute([$curMonth]);
$salesCurrent = $stmt->fetchColumn() ?: 0;
// Note: La requête pour le mois précédent doit gérer le changement d'année si on est en janvier
$prevYear = $curMonth == 1 ? date('Y') - 1 : date('Y');
$stmtPrev = $pdo->prepare("SELECT SUM(b.prix_final) FROM billet b JOIN reservation r ON b.id_reservation = r.id_reservation WHERE MONTH(r.date_reservation) = ? AND YEAR(r.date_reservation) = ?");
$stmtPrev->execute([$prevMonth, $prevYear]);
$salesPrev = $stmtPrev->fetchColumn() ?: 0;


// 5. Get all future departure dates with transport type for calendar
// Build a map: date(string Y-m-d) => array of transport types (e.g. ['Train','Bus'])
$departureMap = [];
$stmt = $pdo->prepare("SELECT DATE(date_depart) as d, mt.type as type FROM trajet t JOIN appareil a ON t.id_appareil = a.id_appareil JOIN moyen_transport mt ON a.id_transport = mt.id_transport WHERE DATE(date_depart) >= CURDATE()");
$stmt->execute();
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $d = $r['d'];
    $t = $r['type'] ?? 'Autre';
    if (!isset($departureMap[$d])) $departureMap[$d] = [];
    if (!in_array($t, $departureMap[$d])) $departureMap[$d][] = $t;
}

// 6. Pie chart: distribution of tickets by class
$pieData = $pdo->query("SELECT c.nom_classe, COUNT(*) as count FROM billet b LEFT JOIN classe c ON b.id_classe = c.id_classe GROUP BY c.nom_classe")->fetchAll(PDO::FETCH_ASSOC);

// Helper function to generate a full month calendar
function generateCalendar($year, $month, $departureMap) {
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $monthName = date('F', $firstDay); // Month name (English)
    $frenchMonthNames = [
        'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
        'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
        'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
        'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
    ];
    $monthNameFr = $frenchMonthNames[$monthName] ?? $monthName;
    $dayOfWeek = date('w', $firstDay); // 0 (Sun) to 6 (Sat)
    $totalDays = date('t', $firstDay);
    
    // Day names in French (Mo, Tu, We, Th, Fr, Sa, Su - adjust for 'w' being Sun=0)
    $dayNamesFr = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    $calendarHtml = "<h4 style='font-size:16px; margin-bottom:12px; text-align:center;'>$monthNameFr $year</h4>";
    $calendarHtml .= "<table class='calendar'>";
    
    // Day names header
    $calendarHtml .= "<thead><tr>";
    foreach ($dayNamesFr as $day) {
        $calendarHtml .= "<th>$day</th>";
    }
    $calendarHtml .= "</tr></thead>";
    
    $calendarHtml .= "<tbody><tr>";
    
    // Calculate the number of empty cells at the start (Monday=1, Sunday=0 -> Sunday should be last)
    $startCell = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1; // Adjusting for Monday start
    
    for ($i = 0; $i < $startCell; $i++) {
        $calendarHtml .= "<td></td>";
    }
    
    $currentDay = 1;
    $dateCheck = date('Y-m-d'); // Current date for checking
    while ($currentDay <= $totalDays) {
        if ($startCell > 0 && ($startCell % 7 == 0)) { // New row if 7 cells are filled, except for the very first row
            $calendarHtml .= "</tr><tr>";
        }

        $dateKey = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($currentDay, 2, '0', STR_PAD_LEFT);
        // Only mark departures that are in the departure map (future dates only)
        $isDeparture = isset($departureMap[$dateKey]);
        $isToday = ($dateKey == date('Y-m-d'));
        
        $cellClass = '';
        if ($isDeparture) {
            $cellClass .= ' has-departure';
        }
        if ($isToday) {
            $cellClass .= ' today';
        }

        // Render dots for each transport type present on that date
        $dotsHtml = '';
        if ($isDeparture) {
            $types = $departureMap[$dateKey];
            foreach ($types as $tp) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $tp));
                $dotsHtml .= "<span class=\"dot dot-{$slug}\" title=\"" . htmlspecialchars($tp) . "\"></span>";
            }
        }

        $calendarHtml .= "<td class='$cellClass'><div style='display:flex;flex-direction:column;align-items:center;gap:6px;'><div>$currentDay</div><div style='display:flex;gap:4px;'>$dotsHtml</div></div></td>";
        
        $currentDay++;
        $startCell++;
    }

    // Fill remaining cells
    while ($startCell % 7 != 0 && $startCell > 0) {
        $calendarHtml .= "<td></td>";
        $startCell++;
    }

    $calendarHtml .= "</tr></tbody></table>";
    
    return $calendarHtml;
}

// Function to find next 2 months with future departures
function getNextMonthsWithDepartures($departureMap, $maxMonths = 2) {
    $months = [];
    $year = date('Y');
    $month = date('n');
    
    // Check up to 12 months ahead
    for ($i = 0; $i < 12 && count($months) < $maxMonths; $i++) {
        // Check if this month has any departures
        $monthStart = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        $hasDeparts = false;
        foreach ($departureMap as $date => $types) {
            if ($date >= $monthStart && $date <= $monthEnd) {
                $hasDeparts = true;
                break;
            }
        }
        
        if ($hasDeparts) {
            $months[] = ['year' => $year, 'month' => $month];
        }
        
        // Move to next month
        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }
    }
    
    return $months;
}

$nextMonths = getNextMonthsWithDepartures($departureMap, 2);

// Generate calendar HTML for next months with departures
$calendarHtmlCurrent = '';
$calendarHtmlPrevious = '';
if (count($nextMonths) >= 2) {
    $calendarHtmlCurrent = generateCalendar($nextMonths[1]['year'], $nextMonths[1]['month'], $departureMap);
    $calendarHtmlPrevious = generateCalendar($nextMonths[0]['year'], $nextMonths[0]['month'], $departureMap);
} elseif (count($nextMonths) == 1) {
    $calendarHtmlCurrent = generateCalendar($nextMonths[0]['year'], $nextMonths[0]['month'], $departureMap);
    $calendarHtmlPrevious = '<p style="text-align:center;color:#64748b;">Aucun autre mois avec départs disponible</p>';
}

// --- FIN PARTIE PHP ---
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Agence de Voyage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #0ea5e9; /* Sky Blue */
            --secondary-color: #f59e42; /* Orange */
            --dark-color: #111827;
            --background-color: #f3f4f6;
            --card-shadow: 0 4px 16px rgba(2, 6, 23, 0.08);
            --sidebar-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 100%);
        }

        /* Base styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background-color);
            margin: 0;
            display: flex; 
            min-height: 100vh; 
        }

        /* Sidebar - Navigation on the left */
        .sidebar {
            background: var(--sidebar-gradient);
            color: #fff;
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            padding: 32px 0;
            position: sticky; 
            top: 0;
            height: 100vh; 
            box-shadow: 8px 0 24px rgba(2, 6, 23, 0.12);
            box-sizing: border-box; 
            z-index: 100;
        }

        .sidebar .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 2px 8px var(--primary-color);
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 4px;
        }

        .sidebar .email {
            text-align: center;
            font-size: 13px;
            margin-bottom: 32px;
            color: #cbd5e1;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin: 10px 0;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 32px;
            border-left: 4px solid transparent;
            transition: background 0.2s, border-left-color 0.2s;
        }

        .sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
        }
        
        .sidebar a:not(.active):hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* Main Content Area */
        .main {
            flex: 1;
            padding: 40px;
            overflow-y: auto; 
        }

        .main h1 {
            margin: 0 0 32px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        /* Info Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .card .icon {
            font-size: 36px;
            color: var(--primary-color);
            background: rgba(14, 165, 233, 0.1); 
            padding: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card .label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .card .value {
            font-size: 24px; 
            font-weight: 700;
            color: var(--dark-color);
        }

        /* Charts Layout */
        .charts-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 24px;
            flex: 4; /* Augmenté pour donner plus de place aux graphiques */
            min-width: 0; 
        }

        .pie-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 24px;
            flex: 1; /* Réduit pour laisser plus d'espace à l'autre colonne */
            max-width: 300px; 
            min-width: 20px;
            min-height: 0;
            max-height: 22rem;
        }

        /* Right column wrapper to stack pie + comparison */
        .right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: stretch;
            flex: 1;
            max-width: 320px;
        }

        .comparison-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 18px;
            min-height: 160px;
        }

        .chart-box h3, .pie-box h3, .calendar-box h3 {
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 10px;
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        /* Nested Charts and Calendar Layout */
        .bottom-row {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .line-chart-container {
            flex: 1;
            min-width: 0;
            /* Pas besoin de bordures si on le met dans la même .chart-box principale */
            border-bottom: 1px solid #f3f4f6;
            padding: 24px; 
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }

        /* Calendar Box - Corrigé et stylé */
        .calendar-box {
            padding: 24px;
            flex: 1; 
            min-width: 35rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }
        
        .calendar-container {
            display: flex;
            gap: 15px;
            justify-content: space-between;
        }

        .calendar-container > div {
            flex: 1;
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            font-size: 12px; /* L'espace est réduit, on réduit la police */
            table-layout: fixed; 
        }
        
        .calendar th {
            font-weight: 600;
            color: var(--primary-color);
            padding: 8px 0;
        }

        .calendar td {
            padding: 6px 0;
            height: 30px;
            line-height: 1;
            border-radius: 6px;
            transition: background 0.2s;
            cursor: default;
        }

        .calendar td.has-departure {
            /* Keep text styling but don't fill the whole cell - we'll show colored dots instead */
            color: var(--dark-color);
            font-weight: 700;
        }
        
        .calendar td.today {
            border: 2px solid var(--secondary-color);
            font-weight: 700;
        }
        
        .calendar td.today.has-departure {
            border: 2px solid var(--secondary-color);
        }

        /* Legend */
        .legend {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .legend > div {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: var(--dark-color);
        }

        .legend span {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 4px;
            margin-right: 8px;
        }
        
        /* Dots for calendar transport types */
        .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
        .dot-train { background:#0ea5e9; }
        .dot-bateau { background:#f97316; }
        .dot-bus { background:#10b981; }
        .dot-avion { background:#6366f1; }
        .dot-autre { background:#94a3b8; }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-row {
                flex-direction: column;
            }
            .pie-box {
                max-width: 100%;
                min-width: unset;
                order: 3; /* Le met en bas */
            }
            .bottom-row {
                flex-direction: column;
            }
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                min-width: unset;
            }
            .main {
                padding: 20px;
            }
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="avatar"><i class="bi bi-person-circle" style="font-size:48px;color:var(--primary-color);"></i></div>
        <h2>Admin</h2>
        <div class="email">admin@agence.com</div>
        <ul>
            <li><a href="#" class="active"><i class="bi bi-house-door"></i> Home</a></li>
            <li><a href="./add-trajet.php"><i class="bi bi-plus-circle"></i> Ajouter trajet</a></li>
            <li><a href="./add-transport.php"><i class="bi bi-truck"></i> Moyen transport</a></li>
            <li><a href="./add-appareil.php"><i class="bi bi-gear"></i> Appareil</a></li>
            <li><a href="./all-tickets.php"><i class="bi bi-ticket-detailed"></i> Tous les billets</a></li>
            <li><a href="./add-classe.php"><i class="bi bi-award"></i> Classe voyage</a></li>
            <li><a href="#"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
        </ul>
    </div>
    <div class="main">
        <h1>Dashboard Admin</h1>

        <div class="cards">
            <div class="card"><span class="icon"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="label">Ventes totales</div>
                    <div class="value">
                        <?php echo number_format($totalSales,0,',',' '); ?> FCFA
                    </div>
                </div>
            </div>
            <div class="card"><span class="icon"><i class="bi bi-boat"></i></span>
                <div>
                    <div class="label">Ventes Bateau</div>
                    <div class="value">
                        <?php echo number_format($salesByType['Bateau'],0,',',' '); ?> FCFA
                    </div>
                </div>
            </div>
            <div class="card"><span class="icon"><i class="bi bi-bus-front"></i></span>
                <div>
                    <div class="label">Ventes Bus</div>
                    <div class="value">
                        <?php echo number_format($salesByType['Bus'],0,',',' '); ?> FCFA
                    </div>
                </div>
            </div>
            <div class="card"><span class="icon"><i class="bi bi-train-front"></i></span>
                <div>
                    <div class="label">Ventes Train</div>
                    <div class="value">
                        <?php echo number_format($salesByType['Train'],0,',',' '); ?> FCFA
                    </div>
                </div>
            </div>
        </div>
        
        <div class="charts-row">
            <div class="chart-box">
                <h3 style="font-size:18px;">Résultat des ventes par mois (Année en cours)</h3>
                <canvas id="barChart" height="100"></canvas>

                <div class="bottom-row">
                    <div class="calendar-box">
                        <h3 style="font-size:16px; margin-top:0;">Calendrier des départs</h3>
                        <div class="calendar-container">
                            <div><?php echo $calendarHtmlPrevious; ?></div>
                            <div><?php echo $calendarHtmlCurrent; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="pie-box">
                    <h3 style="font-size:18px;">Répartition des billets par classe</h3>
                    <canvas id="pieChart" height="220"></canvas>
                    <div class="legend">
                        <?php 
                        // Les couleurs utilisées par Chart.js
                        $colors = ['#0ea5e9', '#f59e42', '#10b981', '#e11d48', '#6366f1', '#f97316'];
                        $colorIndex = 0;
                        foreach ($pieData as $row): 
                            $color = $colors[$colorIndex % count($colors)];
                            $colorIndex++;
                        ?>
                        <div><span style="background:<?php echo $color; ?>;"></span>
                            <?php echo htmlspecialchars($row['nom_classe'] ?? 'Inconnu'); ?> (
                            <?php echo $row['count']; ?>)
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="comparison-box">
                    <h3 style="font-size:16px; margin-top:0;">Comparaison Mois Précédent vs Actuel</h3>
                    <canvas id="lineChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Bar chart: monthly sales
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"],
                datasets: [{
                    label: 'Ventes',
                    data: <?php echo json_encode(array_values($monthlySales)); ?>,
                    backgroundColor: '#0ea5e9',
                    borderRadius: 6,
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Line chart: sales comparison
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Mois précédent', 'Mois actuel'],
                datasets: [{
                    label: 'Ventes',
                    data: [<?php echo $salesPrev; ?>, <?php echo $salesCurrent; ?>],
                    borderColor: '#f59e42',
                    backgroundColor: 'rgba(14,165,233,0.15)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointBackgroundColor: '#0ea5e9',
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Pie chart: ticket class distribution
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($pieData, 'nom_classe')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($pieData, 'count')); ?>,
                    backgroundColor: ['#0ea5e9', '#f59e42', '#10b981', '#e11d48', '#6366f1', '#f97316'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { 
                        display: false // Using custom HTML legend
                    } 
                }
            }
        });
    </script>
</body>

</html>