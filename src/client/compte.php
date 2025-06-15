<?php
session_start();
include '../pdodb.php';

// Check if user is logged in
if (!isset($_SESSION['iduser'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE IdUser = ?");
$stmt->execute([$_SESSION['iduser']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomComplet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $numTel = trim($_POST['num_tel']);
    $adresse = trim($_POST['adresse']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    try {
        $pdo->beginTransaction();

        // Verify current password if changing password
        if (!empty($currentPassword)) {
            if (!password_verify($currentPassword, $user['MotDePasse'])) {
                throw new Exception("Le mot de passe actuel est incorrect.");
            }

            if (empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("Veuillez remplir tous les champs du mot de passe.");
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
            }

            // Log password change
            $stmt = $pdo->prepare("INSERT INTO logs (IdUser, action, details, date_action) VALUES (?, 'Changement de mot de passe', 'Mot de passe modifié', NOW())");
            $stmt->execute([$_SESSION['iduser']]);

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET MotDePasse = ? WHERE IdUser = ?");
            $stmt->execute([$hashedPassword, $_SESSION['iduser']]);
        }

        // Check for changes in other fields
        $changes = [];
        if ($nomComplet !== $user['NomComplet']) {
            $changes[] = "Nom complet: {$user['NomComplet']} -> {$nomComplet}";
        }
        if ($email !== $user['Email']) {
            $changes[] = "Email: {$user['Email']} -> {$email}";
        }
        if ($numTel !== $user['NumTel']) {
            $changes[] = "Numéro de téléphone: {$user['NumTel']} -> {$numTel}";
        }
        if ($adresse !== $user['Adresse']) {
            $changes[] = "Adresse: {$user['Adresse']} -> {$adresse}";
        }

        // If there are changes, update the user and log them
        if (!empty($changes)) {
            $stmt = $pdo->prepare("UPDATE users SET NomComplet = ?, Email = ?, NumTel = ?, Adresse = ? WHERE IdUser = ?");
            $stmt->execute([$nomComplet, $email, $numTel, $adresse, $_SESSION['iduser']]);

            // Log the changes
            $details = implode(", ", $changes);
            $stmt = $pdo->prepare("INSERT INTO logs (IdUser, action, details, date_action) VALUES (?, 'Modification du profil', ?, NOW())");
            $stmt->execute([$_SESSION['iduser'], $details]);

            $message = "Profil mis à jour avec succès.";
        }

        $pdo->commit();
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE IdUser = ?");
        $stmt->execute([$_SESSION['iduser']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get user's recent activity logs
$stmt = $pdo->prepare("SELECT * FROM logs WHERE IdUser = ? ORDER BY date_action DESC LIMIT 10");
$stmt->execute([$_SESSION['iduser']]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <link rel="stylesheet" href="../fabricant/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #222;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }

        .profile-header h2 {
            color: #00fef6;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #888;
            font-size: 16px;
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .profile-section h3 {
            color: #00fef6;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #ddd;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #00fef6;
            outline: none;
        }

        .btn-update {
            background-color: #00fef6;
            color: #000;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }

        .btn-update:hover {
            background-color: #00e6de;
        }

        .activity-logs {
            margin-top: 40px;
        }

        .log-item {
            padding: 15px;
            border-bottom: 1px solid #333;
            color: #ddd;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-date {
            color: #888;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .log-action {
            color: #00fef6;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .log-details {
            color: #aaa;
            font-size: 14px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .message.success {
            background-color: #00fef6;
            color: #fff;
        }

        .message.error {
            background-color: #c0392b;
            color: #fff;
        }

        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #333;
        }

        .password-section h3 {
            color: #00fef6;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }
    </style>
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
                    <a href="afftrcprod.php">Traçabilité produit fini</a>
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
        <div class="profile-container">
            <div class="profile-header">
                <h2>Mon Profil</h2>
                <p>Gérez vos informations personnelles et votre mot de passe</p>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="profile-section">
                    <h3>Informations Personnelles</h3>
                    
                    <div class="form-group">
                        <label for="nom_complet">Nom Complet</label>
                        <input type="text" id="nom_complet" name="nom_complet" value="<?php echo htmlspecialchars($user['NomComplet']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="num_tel">Numéro de Téléphone</label>
                        <input type="tel" id="num_tel" name="num_tel" value="<?php echo htmlspecialchars($user['NumTel']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($user['Adresse']); ?>" required>
                    </div>
                </div>

                <div class="password-section">
                    <h3>Changer le Mot de Passe</h3>
                    
                    <div class="form-group">
                        <label for="current_password">Mot de Passe Actuel</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau Mot de Passe</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmer le Nouveau Mot de Passe</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <button type="submit" class="btn-update">Mettre à Jour le Profil</button>
            </form>
        </div>
    </main>

    <script src="../fabricant/script.js"></script>
</body>
</html>

