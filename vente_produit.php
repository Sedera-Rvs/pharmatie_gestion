<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'caisse') {
    header('Location: connexion.php');
    exit();
}

try {
    $connexion = new PDO('mysql:host=localhost;dbname=gestion', 'root', '');
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des produits pour le formulaire
$query = $connexion->query("SELECT * FROM produit ORDER BY nom_produit ASC");
$produits = $query->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_produit = $_POST['nom_produit'];
    $quantite_vendue = $_POST['quantite_vendue'];

    // Vérification de l'existence du produit
    $query = $connexion->prepare("SELECT * FROM produit WHERE nom_produit = ?");
    $query->execute([$nom_produit]);
    $produit = $query->fetch();

    if ($produit) {
        // Vérification de la quantité disponible
        if ($produit['quantite'] >= $quantite_vendue) {
            // Mise à jour de la quantité dans la base de données
            $nouvelle_quantite = $produit['quantite'] - $quantite_vendue;
            $update_query = $connexion->prepare("UPDATE produit SET quantite = :quantite WHERE id = :id");
            $update_query->execute([
                'quantite' => $nouvelle_quantite,
                'id' => $produit['id']
            ]);
            $insert_query = $connexion->prepare("INSERT INTO ventes (nom_produit, quantite_vendue) VALUES (:nom_produit, :quantite_vendue)");
            $insert_query->execute([
                'nom_produit' => $nom_produit,
                'quantite_vendue' => $quantite_vendue
            ]);
            $message = "Vente enregistrée avec succès.";
            // Redirection vers la page d'accueil après l'enregistrement
            header('Location: acceuil.php');
            exit();
        } else {
            $error = "Quantité vendue supérieure à la quantité disponible.";
        }
    } else {
        $error = "Produit non trouvé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Enregistrer une vente</title>
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
        input, select {
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
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enregistrer une vente</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nom_produit">Nom du produit</label>
                <select name="nom_produit" id="nom_produit" required>
                    <option value="">Sélectionnez un produit</option>
                    <?php foreach ($produits as $produit): ?>
                        <option value="<?php echo htmlspecialchars($produit['nom_produit']); ?>"><?php echo htmlspecialchars($produit['nom_produit']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantite_vendue">Quantité vendue</label>
                <input type="number" name="quantite_vendue" id="quantite_vendue" required min="1">
            </div>
            
            <button type="submit">Enregistrer la vente</button>
        </form>
    </div>
</body>
</html> 