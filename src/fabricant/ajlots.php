<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Site de Traçabilité</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="form.css">
</head>
<body>
<div class="sidebar" id="sidebar">
        <button id="toggleSidebar" onclick="toggleSidebar()">☰</button>
        
        <nav class="nav">
            <a href="homepage.php">
                <span class="icon"><i class="fas fa-home"></i></span>
                <span class="label">Accueil</span>
            </a>

            <div class="dropdown">
                <a class="dropdown-toggle">
                    <div style="display: flex; align-items: center;">
                        <span class="icon"><i class="fas fa-map-marker-alt"></i></span>
                        <span class="label">Traçabilité</span>
                    </div>
                    <span class="arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <div class="dropdown-content">
                    <a href="afflotprod.php">Lot produit</a>
                    <a href="afflotmp.php">Lot matière première</a>
                    <a href="afftrcprod.php">Traçabilité produit fini</a>
                    <a href="afftrcmp.php">Traçabilité matière première</a>
                    <a href="ctrllotprod.php">Qualité produit</a>
                    <a href="ctrllotmp.php">Qualité matière première</a>
                    <a href="loclotprod.php">Localisation produit</a>
                </div>
            </div>

            <div class="dropdown">
                <a class="dropdown-toggle">
                    <div style="display: flex; align-items: center;">
                        <span class="icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="label">Ajout</span>
                    </div>
                    <span class="arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <div class="dropdown-content">
                    <a href="aliments.php">Aliments</a>
                    <a href="ajlots.php">Lots</a>
                    <a href="ajopprod.php">Opération de production</a>
                    <a href="ajopctrl.php">Opération de contrôle</a>
                    <a href="ajoploc.php">Opération de localisation</a>
                    <a href="ajusers.php">Utilisateurs</a>
                    <a href="ajbonlivr.php">Bon de livraison</a>
                    <a href="ajbonrecep.php">Bon de réception</a>
                </div>
            </div>

            <a href="lanceralerte.php">
                <span class="icon"><i class="fas fa-bell"></i></span>
                <span class="label">Lancer une alerte</span>
            </a>

            <a href="listealertes.php">
                <span class="icon"><i class="fas fa-list"></i></span>
                <span class="label">Liste des alertes</span>
            </a>

            <a href="compte.php">
                <span class="icon"><i class="fas fa-user"></i></span>
                <span class="label">Mon compte</span>
            </a>

            <a href="informations.php">
                <span class="icon"><i class="fas fa-info-circle"></i></span>
                <span class="label">Informations</span>
            </a>

            <a href="logout.php">
                <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                <span class="label">Déconnexion</span>
            </a>
        </nav>
    </div>

  <main>
<?php
session_start();
include "../pdodb.php";

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST['type'];
    $dateProd = $_POST['date_production'];
    $dateExp = $_POST['date_expiration'];
    $quantite = $_POST['quantite'];
    $qteInit = $quantite;
    $qteRest = $quantite;
    $etat = 'en stock'; // Default value

    try {
        if ($type === "lot_matiere_premiere") {
            $idMP = $_POST['id_mp'];
            $query = "INSERT INTO LotMatierePremiere (DateProduction, DateExpiration, QteInitiale, QteRestante, Etat, IdMP)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$dateProd, $dateExp, $qteInit, $qteRest, $etat, $idMP]);
            $success = "Lot de matière première ajouté avec succès !";
        } elseif ($type === "lot_produit_fini") {
            $idProd = $_POST['id_prod'];
            $query = "INSERT INTO LotProduitFini (DateProduction, DateExpiration, QteInitiale, QteRestante, Etat, IdProd)
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$dateProd, $dateExp, $qteInit, $qteRest, $etat, $idProd]);
            $success = "Lot de produit fini ajouté avec succès !";
        } else {
            $error = "Type invalide.";
        }

        $pdo = null;
        $stmt = null;
    } catch (PDOException $e) {
        $error = "Erreur de la base de données : " . $e->getMessage();
    }
}

// Fetch dropdown data
include "../pdodb.php";
$matierePremieres = $pdo->query("SELECT IdMP, NomMP FROM MatierePremiere")->fetchAll();
$produitsFinis = $pdo->query("SELECT IdProd, nomProd FROM ProduitFini")->fetchAll();
?>

<div class="container">
  <form method="post" action="">
    <h2>Ajouter un Lot</h2>

    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>

    <!-- Inside your form -->
<label for="type">Type de lot :</label>
<select name="type" id="type" onchange="toggleForms()" required>
  <option value="">-- Choisir --</option>
  <option value="lot_matiere_premiere">Lot Matière Première</option>
  <option value="lot_produit_fini">Lot Produit Fini</option>
</select>

<!-- Group 1: Matière première -->
<div id="form_mp" style="display:none;">
  <label for="id_mp">Matière Première</label>
  <select name="id_mp">
    <?php foreach ($matierePremieres as $mp): ?>
      <option value="<?= $mp['IdMP'] ?>"><?= $mp['NomMP'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Group 2: Produit fini -->
<div id="form_pf" style="display:none;">
  <label for="id_prod">Produit Fini</label>
  <select name="id_prod">
    <?php foreach ($produitsFinis as $prod): ?>
      <option value="<?= $prod['IdProd'] ?>"><?= $prod['nomProd'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Common fields -->
<label for="date_production">Date de production</label>
<input type="date" name="date_production" required><br>

<label for="date_expiration">Date d'expiration</label>
<input type="date" name="date_expiration" required><br>

<label for="quantite">Quantité</label>
<input type="number" name="quantite" step="1" min="0" required><br>

<button type="submit">Ajouter</button>

  </form>
</div>

<script>
function toggleForms() {
  var type = document.getElementById("type").value;
  document.getElementById("form_mp").style.display = (type === "lot_matiere_premiere") ? "block" : "none";
  document.getElementById("form_pf").style.display = (type === "lot_produit_fini") ? "block" : "none";
}
</script>

<script src="script.js"></script>

</body>
</html>
