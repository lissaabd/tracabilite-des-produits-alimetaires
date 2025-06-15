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
    // Get & sanitize inputs
    $nomComplet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $numTel = trim($_POST['num_tel']);
    $motDePasse = $_POST['mot_de_passe'];
    $adresse = trim($_POST['adresse']);
    $role = $_POST['role'];

    // Simple validation (add more as needed)
    if (empty($nomComplet) || empty($email) || empty($motDePasse) || empty($role)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($motDePasse, PASSWORD_DEFAULT);

        try {
            $query = "INSERT INTO users (NomComplet, Email, NumTel, MotDePasse, Adresse, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$nomComplet, $email, $numTel, $hashedPassword, $adresse, $role]);

            $success = "Utilisateur ajouté avec succès !";
        } catch (PDOException $e) {
            $error = "Erreur base de données : " . $e->getMessage();
        }
    }
}
?>

<div class="container">
  <form method="POST" action="">
    <h2>Ajouter un nouvel utilisateur</h2>

    <?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
    <?php if ($success) echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; ?>

    <label for="nom_complet">Nom complet</label>
    <input type="text" name="nom_complet" id="nom_complet" required>

    <label for="email">Email</label>
    <input type="email" name="email" id="email" required>

    <label for="num_tel">Numéro de téléphone</label>
    <input type="text" name="num_tel" id="num_tel">

    <label for="mot_de_passe">Mot de passe</label>
    <input type="password" name="mot_de_passe" id="mot_de_passe" required>

    <label for="adresse">Adresse</label>
    <input type="text" name="adresse" id="adresse">

    <label for="role">Rôle</label>
    <select name="role" id="role" required>
      <option value="">--Sélectionnez un rôle--</option>
      <option value="fournisseur">Fournisseur</option>
      <option value="fabricant">Fabricant</option>
      <option value="client">Client</option>
      <option value="controleur">Controleur</option>
      <option value="admin">Logisticien</option>
    </select>

    <button type="submit">Ajouter utilisateur</button>
  </form>
</div>

<script src="script.js"></script>

