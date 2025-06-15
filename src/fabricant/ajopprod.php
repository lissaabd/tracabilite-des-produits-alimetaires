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

// Check if we're returning from adding a new lot
if (isset($_GET['new_lot_added']) && $_GET['new_lot_added'] == 1) {
    $message = "Nouveau lot ajouté avec succès ! Vous pouvez maintenant créer une opération de production avec ce lot.";
}

// Get all product lots that haven't been used in production yet
$sql = "SELECT lpf.NumLotProd, lpf.NumLotProd, p.nomProd 
        FROM lotproduitfini lpf 
        JOIN produitfini p ON lpf.IdProd = p.IdProd 
        WHERE lpf.Etat = 'En stock' 
        AND lpf.NumOpProd IS NULL";
$productLots = $pdo->query($sql);

if (!$productLots) {
    error_log("Error fetching product lots: " . $pdo->errorInfo()[2]);
}

// Get all material lots that are not fully used
$sql = "SELECT DISTINCT lmp.NumLotMP, m.NomMP, lmp.QteRestante, lmp.QteInitiale, lmp.DateExpiration 
        FROM lotmatierepremiere lmp 
        JOIN matierepremiere m ON lmp.IdMP = m.IdMP 
        WHERE lmp.Etat != 'utilise' 
        AND lmp.QteRestante > 0
        ORDER BY lmp.DateExpiration ASC, lmp.NumLotMP";
$materialLots = $pdo->query($sql);

if (!$materialLots) {
    error_log("Error fetching material lots: " . $pdo->errorInfo()[2]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $numLotProd = $_POST['num_lot_prod'] ?? '';
    $dateOperation = $_POST['date_operation'] ?? date('Y-m-d');
    $heureDebut = $_POST['heure_debut'] ?? date('H:i:s');
    $heureFin = $_POST['heure_fin'] ?? date('H:i:s');
    $description = $_POST['description'] ?? '';
    $materials = $_POST['materials'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $idresfab = $_SESSION['iduser']; // Get the logged-in user's ID

    // Validate inputs
    if (empty($numLotProd) || empty($materials) || empty($quantities)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert into operationproduction with idresfab
            $stmt = $pdo->prepare("INSERT INTO operationproduction (DateOpProd, HeureDebut, HeureFin, idresfab) VALUES (?, ?, ?, ?)");
            $stmt->execute([$dateOperation, $heureDebut, $heureFin, $idresfab]);
            
            $operationId = $pdo->lastInsertId();

            // Update lotproduitfini with the new operation ID
            $updateLotStmt = $pdo->prepare("UPDATE lotproduitfini SET NumOpProd = ? WHERE NumLotProd = ?");
            $updateLotStmt->execute([$operationId, $numLotProd]);

            // Insert into qteutilise and update lotmatierepremiere
            $stmt = $pdo->prepare("INSERT INTO qteutilise (NumOpProd, NumLotMP, QteUtilise) VALUES (?, ?, ?)");
            $updateStmt = $pdo->prepare("UPDATE lotmatierepremiere SET QteRestante = QteRestante - ? WHERE NumLotMP = ?");

            foreach ($materials as $index => $numLotMP) {
                $quantity = floatval($quantities[$index]);

                // Check current QteRestante before updating
                $checkStmt = $pdo->prepare("SELECT QteRestante FROM lotmatierepremiere WHERE NumLotMP = ?");
                $checkStmt->execute([$numLotMP]);
                $lot = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if (!$lot) {
                    throw new Exception("Lot matière première non trouvé.");
                }
                if ($quantity > $lot['QteRestante']) {
                    throw new Exception("La quantité utilisée pour le lot $numLotMP dépasse la quantité restante disponible.");
                }

                // Insert into qteutilise
                $stmt->execute([$operationId, $numLotMP, $quantity]);

                // Update lotmatierepremiere, never let QteRestante go below 0
                $updateStmt->execute([$quantity, $numLotMP]);
                $pdo->exec("UPDATE lotmatierepremiere SET QteRestante = GREATEST(QteRestante, 0) WHERE NumLotMP = " . intval($numLotMP));

                // Check if lot is fully used
                $checkStmt = $pdo->prepare("SELECT QteInitiale, QteRestante FROM lotmatierepremiere WHERE NumLotMP = ?");
                $checkStmt->execute([$numLotMP]);
                $lot = $checkStmt->fetch(PDO::FETCH_ASSOC);
                if ($lot['QteRestante'] == 0) {
                    $updateEtatStmt = $pdo->prepare("UPDATE lotmatierepremiere SET Etat = 'utilise' WHERE NumLotMP = ?");
                    $updateEtatStmt->execute([$numLotMP]);
                }
            }

            // Commit transaction
            $pdo->commit();
            $message = "Opération de production ajoutée avec succès!";

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
    <title>Ajouter une Opération de Production</title>
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

        select, input[type="date"], input[type="time"], textarea {
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

        .materials-container {
            margin-top: 30px;
            padding: 20px;
            background: #222;
            border-radius: 8px;
        }

        .materials-container h3 {
            color: #00fef6;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 500;
        }

        .material-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .material-row select {
            flex: 2;
        }

        .material-row input {
            flex: 1;
            background-color: #1a1a1a;
            color: #fff;
            border: 1px solid #333;
            padding: 10px;
            border-radius: 4px;
        }

        .material-row input.error {
            border-color: #ff4444;
            background-color: rgba(255, 68, 68, 0.1);
        }

        .error-message {
            color: #ff4444;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        .btn-remove-material {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-remove-material:hover {
            background: #cc0000;
        }

        .btn-add-material {
            background: #00fef6;
            color: #000;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-add-material:hover {
            background: #00d6cf;
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

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: rgba(0, 254, 246, 0.1);
            color: #00fef6;
            border: 1px solid #00fef6;
        }

        .message.error {
            background-color: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
        }

        .message.info {
            background-color: #1a1a1a;
            border: 1px solid #00fef6;
            color: #00fef6;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .btn-create-lot {
            display: inline-block;
            background-color: #00fef6;
            color: #000;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .btn-create-lot:hover {
            background-color: #00d6cf;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                margin: 10px;
                padding: 15px;
            }

            .material-row {
                flex-direction: column;
                gap: 5px;
            }

            .material-row select,
            .material-row input {
                width: 100%;
            }
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
            <h2>Ajouter une Opération de Production</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($productLots && $productLots->rowCount() == 0): ?>
                <div class="message info">
                    <p>Aucun lot produit disponible pour une nouvelle opération de production.</p>
                    <a href="ajlots.php" class="btn-create-lot">Créer un nouveau lot</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="num_lot_prod">Lot Produit:</label>
                        <select name="num_lot_prod" id="num_lot_prod" required>
                            <option value="">Sélectionnez un lot produit</option>
                            <?php 
                            if ($productLots) {
                                while ($lot = $productLots->fetch()): 
                            ?>
                                <option value="<?php echo $lot['NumLotProd']; ?>">
                                    <?php echo htmlspecialchars($lot['nomProd'] . ' - Lot ' . $lot['NumLotProd']); ?>
                                </option>
                            <?php 
                                endwhile;
                            } else {
                                error_log("No product lots found or query failed");
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date_operation">Date de début:</label>
                        <input type="date" id="date_operation" name="date_operation" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="heure_debut">Heure de début:</label>
                        <input type="time" id="heure_debut" name="heure_debut" value="<?php echo date('H:i'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="heure_fin">Heure de fin:</label>
                        <input type="time" id="heure_fin" name="heure_fin" value="<?php echo date('H:i'); ?>" required>
                    </div>

                    <div class="materials-container">
                        <h3>Matières Premières Utilisées</h3>
                        <div id="materials-list">
                            <div class="material-row">
                                <select name="materials[]" required>
                                    <option value="">Sélectionnez une matière première</option>
                                    <?php 
                                    if ($materialLots) {
                                        while ($lot = $materialLots->fetch(PDO::FETCH_ASSOC)): 
                                    ?>
                                        <option value="<?php echo $lot['NumLotMP']; ?>">
                                            <?php echo htmlspecialchars($lot['NomMP'] . ' - Lot ' . $lot['NumLotMP'] . 
                                                ' (Reste: ' . $lot['QteRestante'] . '/' . $lot['QteInitiale'] . 
                                                ' - Expire le: ' . date('d/m/Y', strtotime($lot['DateExpiration'])) . ')'); ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    }
                                    ?>
                                </select>
                                <input type="number" name="quantities[]" step="1" min="0" placeholder="Quantité" required>
                                <button type="button" class="btn-remove-material" onclick="removeMaterial(this)">×</button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-material" onclick="addMaterial()">+ Ajouter une matière première</button>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Enregistrer l'Opération
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function addMaterial() {
            const container = document.getElementById('materials-list');
            const newRow = document.createElement('div');
            newRow.className = 'material-row';
            
            // Create new select element
            const select = document.createElement('select');
            select.name = 'materials[]';
            select.required = true;
            select.innerHTML = `
                <option value="">Sélectionnez une matière première</option>
                <?php 
                if ($materialLots) {
                    $materialLots->execute(); // Re-execute the query
                    while ($lot = $materialLots->fetch(PDO::FETCH_ASSOC)): 
                ?>
                    <option value="<?php echo $lot['NumLotMP']; ?>">
                        <?php echo htmlspecialchars($lot['NomMP'] . ' - Lot ' . $lot['NumLotMP'] . 
                            ' (Reste: ' . $lot['QteRestante'] . '/' . $lot['QteInitiale'] . 
                            ' - Expire le: ' . date('d/m/Y', strtotime($lot['DateExpiration'])) . ')'); ?>
                    </option>
                <?php 
                    endwhile;
                }
                ?>
            `;
            
            // Create new input element
            const input = document.createElement('input');
            input.type = 'number';
            input.name = 'quantities[]';
            input.step = '1';
            input.min = '0';
            input.placeholder = 'Quantité';
            input.required = true;
            
            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-material';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = function() { removeMaterial(this); };
            
            // Append all elements to the new row
            newRow.appendChild(select);
            newRow.appendChild(input);
            newRow.appendChild(removeBtn);
            
            container.appendChild(newRow);
        }

        function removeMaterial(button) {
            const row = button.parentElement;
            if (document.querySelectorAll('.material-row').length > 1) {
                row.remove();
            }
        }

        // Add quantity validation
        document.addEventListener('DOMContentLoaded', function() {
            const materialsList = document.getElementById('materials-list');
            
            materialsList.addEventListener('input', function(e) {
                if (e.target.name === 'quantities[]') {
                    const row = e.target.closest('.material-row');
                    const select = row.querySelector('select');
                    const quantity = parseFloat(e.target.value);
                    
                    // Get the remaining quantity from the option text
                    const optionText = select.options[select.selectedIndex].text;
                    const match = optionText.match(/Reste: ([\d.]+)/);
                    
                    if (match) {
                        const remainingQty = parseFloat(match[1]);
                        const errorMessage = row.querySelector('.error-message') || document.createElement('div');
                        errorMessage.className = 'error-message';
                        
                        if (quantity > remainingQty) {
                            e.target.classList.add('error');
                            errorMessage.textContent = `La quantité ne peut pas dépasser ${remainingQty}`;
                            if (!row.querySelector('.error-message')) {
                                row.appendChild(errorMessage);
                            }
                            errorMessage.style.display = 'block';
                        } else {
                            e.target.classList.remove('error');
                            if (row.querySelector('.error-message')) {
                                errorMessage.style.display = 'none';
                            }
                        }
                    }
                }
            });
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
