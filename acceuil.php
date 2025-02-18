<?php
session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: connexion.php');
    exit();
}

try {
    $connexion = new PDO('mysql:host=localhost;dbname=gestion', 'root', '');
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];

// Récupération des produits
$query = $connexion->query("SELECT * FROM produit ORDER BY date_ajout DESC");
$produits = $query->fetchAll();

// Récupération des statistiques de vente
$stats_query = $connexion->query("SELECT nom_produit, SUM(quantite_vendue) as total_vendu, DATE(date_vente) as date_vente FROM ventes GROUP BY nom_produit, DATE(date_vente)");
$stats = $stats_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .content {
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
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        .btn-add {
            background-color: #4CAF50;
        }
        .btn-edit {
            background-color: #2196F3;
        }
        .btn-delete {
            background-color: #f44336;
        }
        .btn-sell {
            background-color: #ff9800;
        }
        .logout {
            background-color: #666;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        /* Styles pour le graphique */
        #myChart {
            max-width: 600px;
            margin: 20px auto;
        }

        .btn-statistics {
            background-color: #ff9800; /* Couleur pour le bouton de statistiques */
        }

        .alert {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Bienvenue <?php echo htmlspecialchars($username); ?></h1>
            <p>Vous êtes connecté en tant que : <?php echo $user_type === 'admin' ? 'Administrateur' : 'Caissier'; ?></p>
        </div>
        <a href="connexion.php?logout=1" class="logout">Se déconnecter</a>
    </div>

    <div class="content">
        <?php if ($user_type === 'admin'): ?>
            <!-- Interface Administrateur -->
            <div>
                <a href="ajout_produit.php" class="btn btn-add">Ajouter un produit</a>
                <a href="historique.php" class="btn btn-add">Historique des produits</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Nom du produit</th>
                        <th>Quantité</th>
                        <th>Date d'ajout</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $produit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produit['nom_produit']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($produit['quantite']); ?>
                            <?php if ($produit['quantite'] <= 5): ?>
                                <span class="alert">Stock faible!</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($produit['date_ajout']); ?></td>
                        <td>
                            <a href="modifier_produit.php?id=<?php echo $produit['id']; ?>" class="btn btn-edit">Modifier</a>
                            <a href="supprimer_produit.php?id=<?php echo $produit['id']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">Supprimer</a>
                            <a href="ajouter_quantite.php?id=<?php echo $produit['id']; ?>" class="btn btn-add">Ajouter Quantité</a>
                            <a href="statistiques_produit.php?nom_produit=<?php echo urlencode($produit['nom_produit']); ?>" class="btn btn-statistics">Voir Statistiques</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

  
        <?php else: ?>
            <!-- Interface Caissier -->
            <div>
                <a href="vente_produit.php" class="btn btn-sell">Enregistrer une vente</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Nom du produit</th>
                        <th>Quantité disponible</th>
                        <th>Date d'ajout</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $produit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produit['nom_produit']); ?></td>
                        <td><?php echo htmlspecialchars($produit['quantite']); ?></td>
                        <td><?php echo htmlspecialchars($produit['date_ajout']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
