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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_produit = $_POST['nom_produit'];
    $quantite = $_POST['quantite'];

    try {
        $query = $connexion->prepare("INSERT INTO produit (nom_produit, quantite) VALUES (:nom_produit, :quantite)");
        $query->execute([
            'nom_produit' => $nom_produit,
            'quantite' => $quantite
        ]);
        header('Location: acceuil.php');
        exit();
    } catch(PDOException $e) {
        $error = "Erreur lors de l'ajout du produit : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un produit</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Ajouter un nouveau produit</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nom_produit">Nom du produit</label>
                <input type="text" name="nom_produit" id="nom_produit" required>
            </div>
            
            <div class="form-group">
                <label for="quantite">Quantité</label>
                <input type="number" name="quantite" id="quantite" required min="0">
            </div>
            
            <button type="submit">Ajouter le produit</button>
        </form>
    </div>
</body>
</html>
