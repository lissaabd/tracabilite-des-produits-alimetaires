<?php
session_start();
require_once '../pdodb.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['iduser']) || $_SESSION['role'] !== 'fabricant') {
    header("Location: ../login.php");
    exit();
}

// Get list of suppliers
$suppliersQuery = "SELECT IdUser, NomComplet FROM users WHERE role = 'fournisseur'";
$suppliersStmt = $pdo->query($suppliersQuery);
$suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of available lots (only lots without a NumBonRec)
$lotsQuery = "SELECT l.NumLotMP, m.NomMP 
              FROM lotmatierepremiere l 
              JOIN matierepremiere m ON l.IdMP = m.IdMP 
              WHERE l.Etat = 'En stock' AND l.NumBonRec IS NULL";
$lotsStmt = $pdo->query($lotsQuery);
$lots = $lotsStmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate that at least one lot is selected
        if (empty($_POST['lots']) || !isset($_POST['lots'][0]['num_lot']) || empty($_POST['lots'][0]['num_lot'])) {
            throw new Exception("Au moins un lot doit être sélectionné pour créer un bon de réception.");
        }

        $pdo->beginTransaction();

        // Insert into bonreception
        $insertBonRec = "INSERT INTO bonreception (DateRec, Statut, IdFournisseur) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($insertBonRec);
        $stmt->execute([$_POST['date_reception'], 'En attente', $_POST['fournisseur_id']]);
        $numBonRec = $pdo->lastInsertId();

        // Process each lot
        foreach ($_POST['lots'] as $lot) {
            if (!empty($lot['num_lot'])) {
                // Update lotmatierepremiere with NumBonRec
                $updateLot = "UPDATE lotmatierepremiere SET NumBonRec = ? WHERE NumLotMP = ?";
                $stmt = $pdo->prepare($updateLot);
                $stmt->execute([$numBonRec, $lot['num_lot']]);
            }
        }

        $pdo->commit();
        $message = "Bon de réception créé avec succès!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur: " . $e->getMessage();
    }
}
?>

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
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #000000;
            color: #fff;
        }

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

        select, input[type="date"], input[type="number"] {
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

        .lot-items {
            margin-top: 30px;
            padding: 20px;
            background: #222;
            border-radius: 8px;
        }

        .lot-item {
            background-color: #2a2a2a;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .add-lot {
            background-color: #00fef6;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .add-lot:hover {
            background-color: #00e6de;
        }

        .remove-lot {
            background-color: #ff4444;
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .remove-lot:hover {
            background-color: #ff6666;
        }

        .submit-btn {
            background-color: #00fef6;
            color: #000;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #00e6de;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background-color: #00e6de;
            color: #000;
        }

        .error {
            background-color: #ff4444;
            color: #fff;
        }

        .lot-items h3 {
            color: #00fef6;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 500;
            padding-bottom: 10px;
            border-bottom: 2px solid #00fef6;
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
        <h2>Ajouter un Bon de Réception</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="date_reception">Date de réception:</label>
                <input type="date" id="date_reception" name="date_reception" required>
            </div>

            <div class="form-group">
                <label for="fournisseur_id">Fournisseur:</label>
                <select id="fournisseur_id" name="fournisseur_id" required>
                    <option value="">Sélectionnez un fournisseur</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['IdUser']; ?>">
                            <?php echo htmlspecialchars($supplier['NomComplet']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lot-items">
                <h3>Lots reçus</h3>
                <div id="lots-container">
                    <div class="lot-item">
                        <div class="form-group">
                            <label for="lot_1">Lot:</label>
                            <select name="lots[0][num_lot]" required>
                                <option value="">Sélectionnez un lot</option>
                                <?php foreach ($lots as $lot): ?>
                                    <option value="<?php echo $lot['NumLotMP']; ?>">
                                        <?php echo htmlspecialchars($lot['NomMP'] . ' - Lot ' . $lot['NumLotMP']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="remove-lot" onclick="removeLot(this)" style="display: none;">Supprimer</button>
                    </div>
                </div>
                <button type="button" class="add-lot" onclick="addLot()">+ Ajouter un lot</button>
            </div>

            <button type="submit" class="submit-btn">Créer le bon de réception</button>
        </form>
    </div>
  </main>

  <script>
    let lotCount = 1;
    function addLot() {
        const container = document.getElementById('lots-container');
        const newLot = document.createElement('div');
        newLot.className = 'lot-item';
        newLot.innerHTML = `
            <div class="form-group">
                <label for="lot_${lotCount + 1}">Lot:</label>
                <select name="lots[${lotCount}][num_lot]" required>
                    <option value="">Sélectionnez un lot</option>
                    <?php foreach ($lots as $lot): ?>
                        <option value="<?php echo $lot['NumLotMP']; ?>">
                            <?php echo htmlspecialchars($lot['NomMP'] . ' - Lot ' . $lot['NumLotMP']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="remove-lot" onclick="removeLot(this)">Supprimer</button>
        `;
        container.appendChild(newLot);
        lotCount++;
    }

    function removeLot(button) {
        const container = document.getElementById('lots-container');
        const lotItems = container.getElementsByClassName('lot-item');
        
        // Don't allow removing if it's the last lot
        if (lotItems.length <= 1) {
            alert("Au moins un lot doit être sélectionné.");
            return;
        }
        
        button.parentElement.remove();
    }

    // Add form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const lotItems = document.querySelectorAll('.lot-item');
        let hasSelectedLot = false;

        lotItems.forEach(item => {
            const select = item.querySelector('select');
            if (select.value) {
                hasSelectedLot = true;
            }
        });

        if (!hasSelectedLot) {
            e.preventDefault();
            alert("Au moins un lot doit être sélectionné pour créer un bon de réception.");
        }
    });
  </script>
  <script src="script.js"></script>
</body>
</html>