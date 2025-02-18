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

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    header('Location: acceuil.php');
    exit();
}

// Récupération des données du produit
try {
    $query = $connexion->prepare("SELECT * FROM produit WHERE id = ?");
    $query->execute([$id]);
    $produit = $query->fetch();
    
    if (!$produit) {
        header('Location: acceuil.php');
        exit();
    }
} catch(PDOException $e) {
    die("Erreur lors de la récupération du produit : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantite_ajoutee = $_POST['quantite_ajoutee'];
    $nouvelle_quantite = $produit['quantite'] + $quantite_ajoutee;

    try {
        // Mise à jour de la quantité
        $update_query = $connexion->prepare("UPDATE produit SET quantite = :nouvelle_quantite WHERE id = :id");
        $update_query->execute([
            'nouvelle_quantite' => $nouvelle_quantite,
            'id' => $id
        ]);

        // Enregistrement dans l'historique
        $historique_query = $connexion->prepare("INSERT INTO historique (type_mouvement, nom_produit, quantite) VALUES ('ajout_stock', :nom_produit, :quantite)");
        $historique_query->execute([
            'nom_produit' => $produit['nom_produit'],
            'quantite' => $quantite_ajoutee
        ]);

        header('Location: acceuil.php');
        exit();
    } catch(PDOException $e) {
        $error = "Erreur lors de l'ajout de la quantité : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une quantité</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e7f3fe;
            border-left: 3px solid #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ajouter une quantité</h2>
        
        <div class="info">
            <p><strong>Produit :</strong> <?php echo htmlspecialchars($produit['nom_produit']); ?></p>
            <p><strong>Quantité actuelle :</strong> <?php echo htmlspecialchars($produit['quantite']); ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="quantite_ajoutee">Quantité à ajouter</label>
                <input type="number" name="quantite_ajoutee" id="quantite_ajoutee" required min="1">
            </div>
            
            <button type="submit">Ajouter la quantité</button>
        </form>
    </div>
</body>
</html> 