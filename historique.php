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

// Récupération de l'historique des mouvements
$query = $connexion->query("SELECT * FROM historique ORDER BY date_mouvement DESC");
$historique = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des mouvements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }

        .retour {
            background-color: #666;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }

        .btn-export {
            background-color: #666;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Historique des mouvements</h2>
        <a href="export_historique.php" class="btn btn-export">Exporter en PDF</a>
        <table>
            <thead>
                <tr>
                    <th>Type de mouvement</th>
                    <th>Nom du produit</th>
                    <th>Quantité</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historique as $mouvement): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mouvement['type_mouvement']); ?></td>
                    <td><?php echo htmlspecialchars($mouvement['nom_produit']); ?></td>
                    <td><?php echo htmlspecialchars($mouvement['quantite']); ?></td>
                    <td><?php echo htmlspecialchars($mouvement['date_mouvement']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="acceuil.php" class="retour">Retour a l'acceuil</a>
    </div>
</body>
</html> 