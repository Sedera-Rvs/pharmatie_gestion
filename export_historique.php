<?php
require('libs/fpdf/fpdf.php');
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if (!$historique) {
    die("Aucun historique trouvé.");
}

// Création du PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Historique des mouvements', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 10, 'Type de mouvement', 1);
$pdf->Cell(60, 10, 'Nom du produit', 1);
$pdf->Cell(30, 10, 'Quantité', 1);
$pdf->Cell(50, 10, 'Date', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
foreach ($historique as $mouvement) {
    $pdf->Cell(40, 10, $mouvement['type_mouvement'], 1);
    $pdf->Cell(60, 10, $mouvement['nom_produit'], 1);
    $pdf->Cell(30, 10, $mouvement['quantite'], 1);
    $pdf->Cell(50, 10, $mouvement['date_mouvement'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'historique.pdf');
?> 