<?php
session_start();

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: connexion.php');
    exit();
}

// Connexion à la base de données
try {
    $connexion = new PDO('mysql:host=localhost;dbname=gestion', 'root', '');
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $type = $_POST['type'];

    if ($type == 'admin') {
        $query = $connexion->prepare("SELECT * FROM admin WHERE nom_admin = :username");
    } else {
        $query = $connexion->prepare("SELECT * FROM caisse WHERE nom_caisse = :username");
    }

    $query->execute(['username' => $username]);
    $user = $query->fetch();

    // Vérification simple des identifiants (temporaire pour le test)
    if (($type == 'admin' && $username == 'admin' && $password == 'admin') ||
        ($type == 'caisse' && $username == 'caisse' && $password == 'caisse')) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_type'] = $type;
        $_SESSION['username'] = $username;
        header('Location: acceuil.php');
        exit();
    } else {
        $error = "Identifiants incorrects";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }
        .login-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
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
    <div class="login-form">
        <h2>Connexion</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="type">Type de compte</label>
                <select name="type" id="type" required>
                    <option value="admin">Administrateur</option>
                    <option value="caisse">Caissier</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" name="username" id="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
