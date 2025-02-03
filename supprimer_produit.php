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

if ($id) {
    try {
        $query = $connexion->prepare("SELECT nom_produit, quantite FROM produit WHERE id = ?");
        $query->execute([$id]);
        $produit = $query->fetch();

        if ($produit) {
            $historique_query = $connexion->prepare("INSERT INTO historique (type_mouvement, nom_produit, quantite) VALUES ('suppression', :nom_produit, :quantite)");
            $historique_query->execute([
                'nom_produit' => $produit['nom_produit'],
                'quantite' => $produit['quantite']
            ]);
        }

        $query = $connexion->prepare("DELETE FROM produit WHERE id = ?");
        $query->execute([$id]);
    } catch(PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

header('Location: acceuil.php');
exit(); 