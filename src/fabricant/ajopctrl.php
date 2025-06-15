<?php
session_start();
require_once '../pdodb.php';

// Check if user is logged in and is a controleur
if (!isset($_SESSION['iduser'])) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Get all product lots that are in stock or in quarantine and don't have control operations
$sql = "SELECT lpf.NumLotProd, lpf.NumLotProd as lot_id, p.nomProd as nom, 'produit' as type
        FROM lotproduitfini lpf 
        JOIN produitfini p ON lpf.IdProd = p.IdProd 
        WHERE (LOWER(lpf.Etat) = 'en stock' OR LOWER(lpf.Etat) = 'en quarantaine')
        AND NOT EXISTS (
            SELECT 1 FROM resultatcontroleprod rcp 
            WHERE rcp.NumLotProd = lpf.NumLotProd
        )
        UNION
        SELECT lmp.NumLotMP, lmp.NumLotMP as lot_id, m.NomMP as nom, 'matiere' as type
        FROM lotmatierepremiere lmp 
        JOIN matierepremiere m ON lmp.IdMP = m.IdMP 
        WHERE (LOWER(lmp.Etat) = 'en stock' OR LOWER(lmp.Etat) = 'en quarantaine')
        AND NOT EXISTS (
            SELECT 1 FROM resultatcontrolemp rcm 
            WHERE rcm.NumLotMP = lmp.NumLotMP
        )";
$lots = $pdo->query($sql);

if (!$lots) {
    error_log("Error fetching lots: " . $pdo->errorInfo()[2]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $dateControle = $_POST['date_controle'] ?? date('Y-m-d');
    $heureControle = $_POST['heure_controle'] ?? date('H:i:s');
    $conforme = $_POST['conforme'] ?? '';
    $commentaire = $_POST['commentaire'] ?? '';
    $lotId = $_POST['lot_id'] ?? '';
    $lotType = $_POST['lot_type'] ?? '';
    $resultats = $_POST['resultats'] ?? [];
    $types = $_POST['types'] ?? [];

    // Validate inputs
    if (empty($lotId) || empty($lotType) || empty($conforme) || empty($resultats)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Check if lot already has a control operation
            if ($lotType === 'produit') {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM resultatcontroleprod WHERE NumLotProd = ?");
            } else {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM resultatcontrolemp WHERE NumLotMP = ?");
            }
            $checkStmt->execute([$lotId]);
            $count = $checkStmt->fetchColumn();

            if ($count > 0) {
                throw new Exception("Ce lot a déjà une opération de contrôle.");
            }

            // Insert into operationcontrole and get the auto-generated ID
            $stmt = $pdo->prepare("INSERT INTO operationcontrole (DateControle, HeureControle, conforme, commentaire, IdResCtrl) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$dateControle, $heureControle, $conforme, $commentaire, $_SESSION['iduser']]);
            $nextId = $pdo->lastInsertId();

            // Insert results based on lot type
            if ($lotType === 'produit') {
                $stmt = $pdo->prepare("INSERT INTO resultatcontroleprod (NumOpCtrl, NumLotProd, Resultat, Type) VALUES (?, ?, ?, ?)");
                foreach ($resultats as $index => $resultat) {
                    $stmt->execute([$nextId, $lotId, $resultat, $types[$index]]);
                }

                // Update lot status if non-conforme
                if ($conforme === 'non') {
                    $updateStmt = $pdo->prepare("UPDATE lotproduitfini SET Etat = 'En quarantaine' WHERE NumLotProd = ?");
                    $updateStmt->execute([$lotId]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO resultatcontrolemp (NumOpCtrl, NumLotMP, Resultat, Type) VALUES (?, ?, ?, ?)");
                foreach ($resultats as $index => $resultat) {
                    $stmt->execute([$nextId, $lotId, $resultat, $types[$index]]);
                }

                // Update lot status if non-conforme
                if ($conforme === 'non') {
                    $updateStmt = $pdo->prepare("UPDATE lotmatierepremiere SET Etat = 'En quarantaine' WHERE NumLotMP = ?");
                    $updateStmt->execute([$lotId]);
                }
            }

            // Commit transaction
            $pdo->commit();
            $message = "Opération de contrôle ajoutée avec succès!";
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Opération de Contrôle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        select, input[type="date"], input[type="time"], input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            font-size: 14px;
            color: #fff;
            background-color: #1a1a1a;
            transition: border-color 0.3s;
        }

        select:focus, input:focus, textarea:focus {
            border-color: #00fef6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 254, 246, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .results-container {
            margin-top: 20px;
            padding: 20px;
            background: #222;
            border-radius: 8px;
        }

        .result-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .result-row input {
            flex: 1;
            background-color: #1a1a1a;
            color: #fff;
            border: 1px solid #333;
        }

        .result-row input:focus {
            border-color: #00fef6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 254, 246, 0.2);
        }

        .results-container h3 {
            color: #00fef6;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .add-result-btn {
            background-color: #00fef6;
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .add-result-btn:hover {
            background-color: #00fef6;
        }

        .remove-result-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .remove-result-btn:hover {
            background-color: #cc0000;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background-color: #00fef6;
            color: white;
        }

        .error {
            background-color: #ff3d00;
            color: white;
        }

        .submit-btn {
            background-color: #00fef6;
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #00e6de;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 254, 246, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .form-group h3 {
            color: #00fef6;
            margin-bottom: 15px;
            margin-top: 20px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
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

    <div class="form-container">
        <h2>Ajouter une Opération de Contrôle</h2>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="lot_id">Lot à contrôler:</label>
                <select id="lot_id" name="lot_id" required>
                    <option value="">Sélectionnez un lot</option>
                    <?php while ($lot = $lots->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $lot['lot_id']; ?>" data-type="<?php echo $lot['type']; ?>">
                            <?php echo htmlspecialchars($lot['nom'] . ' (Lot #' . $lot['lot_id'] . ') - ' . ucfirst($lot['type'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="lot_type" id="lot_type">
            </div>

            <div class="form-group">
                <label for="date_controle">Date de contrôle:</label>
                <input type="date" id="date_controle" name="date_controle" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="heure_controle">Heure de contrôle:</label>
                <input type="time" id="heure_controle" name="heure_controle" value="<?php echo date('H:i'); ?>" required>
            </div>

            <div class="form-group">
                <label for="conforme">Conformité:</label>
                <select id="conforme" name="conforme" required>
                    <option value="">Sélectionnez</option>
                    <option value="oui">Oui</option>
                    <option value="non">Non</option>
                </select>
            </div>

            <div class="form-group">
                <label for="commentaire">Commentaire:</label>
                <textarea id="commentaire" name="commentaire" rows="4"></textarea>
            </div>

            <div class="results-container">
                <h3>Résultats du contrôle</h3>
                <div id="results">
                    <div class="result-row">
                        <input type="text" name="types[]" placeholder="Type de contrôle" required>
                        <input type="text" name="resultats[]" placeholder="Résultat" required>
                        <button type="button" class="remove-result-btn" onclick="removeResult(this)">-</button>
                    </div>
                </div>
                <button type="button" class="add-result-btn" onclick="addResult()">+ Ajouter un résultat</button>
            </div>

            <button type="submit" class="submit-btn">Enregistrer l'opération</button>
        </form>
    </div>

    <script>
        // Update lot type when lot is selected
        document.getElementById('lot_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('lot_type').value = selectedOption.dataset.type;
        });

        // Add new result row
        function addResult() {
            const resultsDiv = document.getElementById('results');
            const newRow = document.createElement('div');
            newRow.className = 'result-row';
            newRow.innerHTML = `
                <input type="text" name="types[]" placeholder="Type de contrôle" required>
                <input type="text" name="resultats[]" placeholder="Résultat" required>
                <button type="button" class="remove-result-btn" onclick="removeResult(this)">-</button>
            `;
            resultsDiv.appendChild(newRow);
        }

        // Remove result row
        function removeResult(button) {
            const resultsDiv = document.getElementById('results');
            if (resultsDiv.children.length > 1) {
                button.parentElement.remove();
            }
        }
    </script>

<script src="script.js"></script>
</body>
</html>