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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST['type'];
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $prix = $_POST['prix'];

    if (empty($type) || empty($nom) || empty($description) || empty($prix)) {
        $error = "Tous les champs sont requis";
    } else {
        try {
            // Set the table name and column name based on the type
            if ($type === 'matiere premiere') {
                $table = 'matierepremiere';
                $column_nom = 'nommp';
            } elseif ($type === 'produit fini') {
                $table = 'produitfini';
                $column_nom = 'nomprod';
            } else {
                $error = "Type invalide";
            }

            if (!isset($error)) {
                $query = "INSERT INTO $table ($column_nom, description, prix) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$nom, $description, $prix]);

                $success = "$type ajouté avec succès!";
            }

            $pdo = null;
            $stmt = null;
        } catch (PDOException $e) {
            $error = "Erreur de la base de données : " . $e->getMessage();
        }
    }
}
?>
  <div class="container">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <h2>Ajouter Un Aliment</h2>

      <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>

      <label for="type">Type d'élément</label>
      <select name="type" id="type" required>
          <option value="matiere premiere">Matière Première</option>
          <option value="produit fini">Produit Fini</option>
      </select><br>

      <label for="nom">Nom</label>
      <input type="text" name="nom" id="nom" placeholder="Nom..." required><br>

      <label for="description">Description</label>
      <input type="text" name="description" id="description" placeholder="Description..." required><br>

      <label for="prix">Prix</label>
      <input type="number" name="prix" id="prix" placeholder="Prix..." min="0" step="0.01" required><br>

      <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>

      <button type="submit">Ajouter</button>
    </form>
  </div>
</body>
</html>

  </main>

  <script src="script.js"></script>
</body>
</html>