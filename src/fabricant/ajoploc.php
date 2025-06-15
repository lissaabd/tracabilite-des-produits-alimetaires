<?php
session_start();
require_once '../pdodb.php';

// Check if user is logged in
if (!isset($_SESSION['iduser'])) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Get all product lots that are in stock or localized
$sql = "SELECT lpf.NumLotProd, lpf.NumLotProd as lot_id, p.nomProd as nom, 'produit' as type
        FROM lotproduitfini lpf 
        JOIN produitfini p ON lpf.IdProd = p.IdProd 
        WHERE lpf.Etat IN ('En stock', 'Localisé')";
$lots = $pdo->query($sql);

// Get all depots
$sql_depots = "SELECT NumDep, Adresse FROM depot";
$depots = $pdo->query($sql_depots);
$depots_data = $depots->fetchAll(PDO::FETCH_ASSOC);

// Get all logisticiens (users with role 'logisticien')
$sql_logisticiens = "SELECT IdUser, NomComplet FROM users WHERE role = 'logisticien'";
$logisticiens = $pdo->query($sql_logisticiens);
$logisticiens_data = $logisticiens->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $dateEntree = $_POST['date_entree'] ?? date('Y-m-d');
    $heureEntree = !empty($_POST['heure_entree']) ? $_POST['heure_entree'] . ':00' : date('H:i:s');
    $lotId = $_POST['lot_id'] ?? '';
    $depotId = $_POST['depot_id'] ?? '';
    $logisticienId = $_POST['logisticien_id'] ?? '';

    // Validate inputs
    if (empty($lotId) || empty($depotId) || empty($logisticienId)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Check if lot is already assigned to a depot
            $checkStmt = $pdo->prepare("SELECT NumDep FROM operationlocalisation WHERE NumLotProd = ? AND DateSortie IS NULL");
            $checkStmt->execute([$lotId]);
            $currentDepot = $checkStmt->fetch(PDO::FETCH_ASSOC);

            // Start transaction
            $pdo->beginTransaction();

            try {
                if ($currentDepot) {
                    // Update the old depot's exit date and time
                    $updateOldStmt = $pdo->prepare("UPDATE operationlocalisation SET DateSortie = ?, HeureSortie = ? WHERE NumLotProd = ? AND NumDep = ? AND DateSortie IS NULL");
                    $updateOldStmt->execute([$dateEntree, $heureEntree, $lotId, $currentDepot['NumDep']]);
                }

                // Insert into operationlocalisation for the new depot
                $stmt = $pdo->prepare("INSERT INTO operationlocalisation (NumLotProd, NumDep, DateEntree, HeureEntree) VALUES (?, ?, ?, ?)");
                $stmt->execute([$lotId, $depotId, $dateEntree, $heureEntree]);

                // Check if affectationdepot already exists
                $checkAffectation = $pdo->prepare("SELECT COUNT(*) FROM affectationdepot WHERE IdResDep = ? AND NumDep = ?");
                $checkAffectation->execute([$logisticienId, $depotId]);
                $affectationExists = $checkAffectation->fetchColumn();

                if (!$affectationExists) {
                    // Insert into affectationdepot only if it doesn't exist
                    $stmt = $pdo->prepare("INSERT INTO affectationdepot (IdResDep, NumDep, DateDesignation) VALUES (?, ?, ?)");
                    $stmt->execute([$logisticienId, $depotId, $dateEntree]);
                }

                // Update lot status
                $updateStmt = $pdo->prepare("UPDATE lotproduitfini SET Etat = 'Localisé' WHERE NumLotProd = ?");
                $updateStmt->execute([$lotId]);

                // Commit transaction
                $pdo->commit();
                $message = "Opération de localisation ajoutée avec succès!";
            } catch (PDOException $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                $error = "Erreur lors de l'opération: " . $e->getMessage();
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la vérification: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Opération de Localisation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="form.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #1a1a1a;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 254, 246, 0.1);
        }

        h2 {
            color: #00fef6;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 500;
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #00fef6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #00fef6;
            font-weight: 500;
        }

        select, input[type="date"], input[type="time"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            font-size: 14px;
            color: #fff;
            background-color: #1a1a1a;
            transition: border-color 0.3s;
        }

        select:focus, input:focus {
            border-color: #00fef6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 254, 246, 0.2);
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: rgba(0, 254, 246, 0.1);
            color: #00fef6;
            border: 1px solid #00fef6;
        }

        .error {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
        }

        .submit-btn {
            background-color: #00fef6;
            color: #000;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #00e6de;
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
        <div class="form-container">
            <h2>Ajouter une Opération de Stockage</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="lot_id">Lot à stocker</label>
                    <select name="lot_id" id="lot_id" required>
                        <option value="">Sélectionnez un lot</option>
                        <?php while ($lot = $lots->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $lot['lot_id']; ?>">
                                <?php echo htmlspecialchars($lot['nom'] . ' (' . $lot['lot_id'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="depot_id">Dépôt</label>
                    <select name="depot_id" id="depot_id" required>
                        <option value="">Sélectionner un dépôt</option>
                        <?php foreach ($depots_data as $depot): ?>
                            <option value="<?php echo $depot['NumDep']; ?>">
                                <?php echo $depot['Adresse']; ?> (ID: <?php echo $depot['NumDep']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="logisticien_id">Logisticien</label>
                    <select name="logisticien_id" id="logisticien_id" required>
                        <option value="">Sélectionner un logisticien</option>
                        <?php foreach ($logisticiens_data as $logisticien): ?>
                            <option value="<?php echo $logisticien['IdUser']; ?>">
                                <?php echo $logisticien['NomComplet']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date_entree">Date d'entrée</label>
                    <input type="date" name="date_entree" id="date_entree" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="heure_entree">Heure d'entrée</label>
                    <input type="time" name="heure_entree" id="heure_entree" value="<?php echo date('H:i'); ?>" required>
                </div>

                <button type="submit" class="submit-btn">Enregistrer l'opération</button>
            </form>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html> 