<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header('Location: connexion.php');
    exit();
}

try {
    $connexion = new PDO('mysql:host=localhost;dbname=gestion', 'root', '');
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$nom_produit = isset($_GET['nom_produit']) ? $_GET['nom_produit'] : null;

if (!$nom_produit) {
    header('Location: acceuil.php');
    exit();
}

// Récupération des informations actuelles du produit
$produit_query = $connexion->prepare("SELECT * FROM produit WHERE nom_produit = ?");
$produit_query->execute([$nom_produit]);
$produit = $produit_query->fetch();

// Récupération de l'historique complet des mouvements
$historique_query = $connexion->prepare("
    SELECT type_mouvement, quantite, date_mouvement
    FROM historique 
    WHERE nom_produit = ?
    ORDER BY date_mouvement DESC
");
$historique_query->execute([$nom_produit]);
$historique = $historique_query->fetchAll();

// Récupération des statistiques de vente pour le produit
$stats_query = $connexion->prepare("
    SELECT 
        DATE(date_vente) as date_vente,
        SUM(quantite_vendue) as total_vendu,
        COUNT(*) as nombre_ventes
    FROM ventes 
    WHERE nom_produit = :nom_produit 
    GROUP BY DATE(date_vente)
    ORDER BY DATE(date_vente)
");
$stats_query->execute(['nom_produit' => $nom_produit]);
$stats = $stats_query->fetchAll(PDO::FETCH_ASSOC);

// Calcul de l'évolution du stock
$stock_evolution = [];
$stock_courant = $produit['quantite']; // On commence avec le stock actuel

// Récupérer tous les mouvements triés par date décroissante (du plus récent au plus ancien)
$mouvements_query = $connexion->prepare("
    SELECT 
        DATE(date_mouvement) as date,
        type_mouvement,
        quantite
    FROM historique 
    WHERE nom_produit = ?
    ORDER BY date_mouvement DESC
");
$mouvements_query->execute([$nom_produit]);
$mouvements = $mouvements_query->fetchAll();

// Récupérer toutes les ventes triées par date décroissante
$ventes_query = $connexion->prepare("
    SELECT 
        DATE(date_vente) as date,
        SUM(quantite_vendue) as total_vendu
    FROM ventes 
    WHERE nom_produit = ?
    GROUP BY DATE(date_vente)
    ORDER BY date_vente DESC
");
$ventes_query->execute([$nom_produit]);
$ventes = $ventes_query->fetchAll();

// Combiner les mouvements et les ventes par date
$dates_mouvements = [];
foreach ($mouvements as $mouvement) {
    $date = $mouvement['date'];
    if (!isset($dates_mouvements[$date])) {
        $dates_mouvements[$date] = ['ajouts' => 0, 'ventes' => 0];
    }
    
    if ($mouvement['type_mouvement'] === 'vente') {
        $dates_mouvements[$date]['ventes'] += $mouvement['quantite'];
    } else {
        $dates_mouvements[$date]['ajouts'] += $mouvement['quantite'];
    }
}

foreach ($ventes as $vente) {
    $date = $vente['date'];
    if (!isset($dates_mouvements[$date])) {
        $dates_mouvements[$date] = ['ajouts' => 0, 'ventes' => 0];
    }
    $dates_mouvements[$date]['ventes'] += $vente['total_vendu'];
}

// Calculer l'évolution du stock
krsort($dates_mouvements); // Trier par date décroissante
$stock_evolution = [];
foreach ($dates_mouvements as $date => $mouvements) {
    $stock_evolution[$date] = $stock_courant;
    // Pour remonter dans le temps, on inverse les opérations
    $stock_courant += $mouvements['ventes']; // On ajoute les ventes car on remonte dans le temps
    $stock_courant -= $mouvements['ajouts']; // On retire les ajouts car on remonte dans le temps
}

// S'assurer que toutes les dates des stats ont une valeur
foreach ($stats as $stat) {
    $date = $stat['date_vente'];
    if (!isset($stock_evolution[$date])) {
        // Trouver la date la plus proche
        $date_proche = null;
        $stock_proche = $stock_courant;
        foreach ($stock_evolution as $d => $s) {
            if ($d > $date && ($date_proche === null || $d < $date_proche)) {
                $date_proche = $d;
                $stock_proche = $s;
            }
        }
        $stock_evolution[$date] = $stock_proche;
    }
}

// Trier les données par date croissante pour l'affichage
ksort($stock_evolution);

// Calcul des statistiques globales
$total_ventes = 0;
$total_quantite_vendue = 0;
$total_ajouts = 0;
foreach ($historique as $mouvement) {
    if ($mouvement['type_mouvement'] === 'vente') {
        $total_ventes++;
        $total_quantite_vendue += $mouvement['quantite'];
    } elseif (in_array($mouvement['type_mouvement'], ['ajout', 'ajout_stock'])) {
        $total_ajouts += $mouvement['quantite'];
    }
}

// Calcul de la moyenne des ventes par jour
$moyenne_ventes = count($stats) > 0 ? $total_quantite_vendue / count($stats) : 0;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques de <?php echo htmlspecialchars($nom_produit); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .chart-container {
            margin-top: 30px;
            position: relative;
            height: 400px;
        }
        .historique-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .historique-table th, .historique-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .historique-table th {
            background-color: #f5f5f5;
        }
        .alert {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn-retour {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Statistiques de <?php echo htmlspecialchars($nom_produit); ?></h2>
        
        <?php if ($produit['quantite'] <= 5): ?>
            <div class="alert">
                Attention : Stock faible ! Quantité actuelle : <?php echo $produit['quantite']; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Stock actuel</h3>
                <p><?php echo $produit['quantite']; ?> unités</p>
            </div>
            <div class="stat-card">
                <h3>Total des ventes</h3>
                <p><?php echo $total_quantite_vendue; ?> unités</p>
            </div>
            <div class="stat-card">
                <h3>Total des ajouts</h3>
                <p><?php echo $total_ajouts; ?> unités</p>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>

        <h3>Historique des mouvements</h3>
        <table class="historique-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type de mouvement</th>
                    <th>Quantité</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historique as $mouvement): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($mouvement['date_mouvement'])); ?></td>
                    <td><?php echo htmlspecialchars($mouvement['type_mouvement']); ?></td>
                    <td><?php echo htmlspecialchars($mouvement['quantite']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="acceuil.php" class="btn-retour">Retour à l'accueil</a>
    </div>

    <script>
        const ctx = document.getElementById('myChart').getContext('2d');
        const labels = <?php echo json_encode(array_map(function($stat) { 
            return date('d/m/Y', strtotime($stat['date_vente'])); 
        }, $stats)); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Quantité vendue par jour',
                        data: <?php echo json_encode(array_map(function($stat) { 
                            return $stat['total_vendu']; 
                        }, $stats)); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        fill: false
                    },
                    {
                        label: 'Nombre de ventes par jour',
                        data: <?php echo json_encode(array_map(function($stat) { 
                            return $stat['nombre_ventes']; 
                        }, $stats)); ?>,
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        fill: false
                    },
                    {
                        label: 'Niveau de stock',
                        data: <?php echo json_encode(array_map(function($stat) use ($stock_evolution) {
                            return isset($stock_evolution[$stat['date_vente']]) ? $stock_evolution[$stat['date_vente']] : null;
                        }, $stats)); ?>,
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 2,
                        tension: 0.1,
                        fill: false,
                        borderDash: [5, 5] // Ligne pointillée pour le stock
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: {
                            drawOnChartArea: true
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Évolution des ventes et du stock'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    </script>
</body>
</html> 