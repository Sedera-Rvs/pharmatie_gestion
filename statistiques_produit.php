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

// Récupération des statistiques de vente pour le produit
$stats_query = $connexion->prepare("SELECT SUM(quantite_vendue) as total_vendu, DATE(date_vente) as date_vente FROM ventes WHERE nom_produit = ? GROUP BY DATE(date_vente)");
$stats_query->execute([$nom_produit]);
$stats = $stats_query->fetchAll(PDO::FETCH_ASSOC);
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
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        #myChart {
            max-width: 600px;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Statistiques de <?php echo htmlspecialchars($nom_produit); ?></h2>
        <canvas id="myChart"></canvas>
        <script>
            const ctx = document.getElementById('myChart').getContext('2d');
            const labels = <?php echo json_encode(array_map(function($stat) { return $stat['date_vente']; }, $stats)); ?>;
            const data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Quantité vendue',
                        data: <?php echo json_encode(array_map(function($stat) { return $stat['total_vendu']; }, $stats)); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            };
            const config = {
                type: 'line',
                data: data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            const myChart = new Chart(ctx, config);
        </script>
    </div>
</body>
</html> 