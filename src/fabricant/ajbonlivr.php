<?php
session_start();
require_once '../pdodb.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['iduser']) || $_SESSION['role'] !== 'fabricant') {
    header("Location: ../login.php");
    exit();
}

// Get list of available product lots
$lotsQuery = "SELECT l.NumLotProd, l.QteRestante, l.QteInitiale, p.NomProd, l.DateExpiration 
              FROM lotproduitfini l 
              JOIN produitfini p ON l.IdProd = p.IdProd 
              WHERE l.Etat IN ('En stock', 'Localisé') AND l.QteRestante > 0";
$lotsStmt = $pdo->query($lotsQuery);
$lots = $lotsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of clients
$clientsQuery = "SELECT IdUser, NomComplet FROM users WHERE role = 'client'";
$clientsStmt = $pdo->query($clientsQuery);
$clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert into bonlivraison
        $insertBonLiv = "INSERT INTO bonlivraison (DateLiv, Statut, IdClient) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($insertBonLiv);
        $stmt->execute([$_POST['date_livraison'], 'En attente', $_POST['client_id']]);
        $numBonLiv = $pdo->lastInsertId();

        // Insert line items
        $insertLigne = "INSERT INTO lignelivraison (NumBonLiv, NumLotProd, QteLivree) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($insertLigne);

        foreach ($_POST['lots'] as $lot) {
            if (!empty($lot['num_lot']) && !empty($lot['quantite'])) {
                $stmt->execute([$numBonLiv, $lot['num_lot'], $lot['quantite']]);
                
                // First get current quantity
                $checkQte = "SELECT QteRestante FROM lotproduitfini WHERE NumLotProd = ?";
                $checkStmt = $pdo->prepare($checkQte);
                $checkStmt->execute([$lot['num_lot']]);
                $currentQte = $checkStmt->fetch(PDO::FETCH_ASSOC)['QteRestante'];
                
                // Calculate new quantity
                $newQte = $currentQte - $lot['quantite'];
                
                // Update remaining quantity and status
                $updateQte = "UPDATE lotproduitfini 
                             SET QteRestante = ?,
                                 Etat = ?
                             WHERE NumLotProd = ?";
                $updateStmt = $pdo->prepare($updateQte);
                $updateStmt->execute([
                    $newQte,
                    $newQte == 0 ? 'Vendu' : 'En stock',
                    $lot['num_lot']
                ]);

                // Only update operationlocalisation if quantity is now 0
                if ($newQte == 0) {
                    $updateLoc = "UPDATE operationlocalisation 
                                 SET DateSortie = CURRENT_DATE(), 
                                     HeureSortie = CURRENT_TIME()
                                 WHERE NumLotProd = ? 
                                 AND DateSortie IS NULL";
                    $updateLocStmt = $pdo->prepare($updateLoc);
                    $updateLocStmt->execute([$lot['num_lot']]);
                }
            }
        }

        $pdo->commit();
        $message = "Bon de livraison créé avec succès!";
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
        <h2>Ajouter un Bon de Livraison</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="deliveryForm">
            <div class="form-group">
                <label for="date_livraison">Date de livraison</label>
                <input type="date" class="form-control" id="date_livraison" name="date_livraison" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="client_id">Client:</label>
                <select id="client_id" name="client_id" required>
                    <option value="">Sélectionnez un client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['IdUser']; ?>">
                            <?php echo htmlspecialchars($client['NomComplet']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lot-items" id="lotItems">
                <h3>Lots à Livrer</h3>
                <div class="lot-item">
                    <div class="form-group">
                        <label>Lot:</label>
                        <select name="lots[0][num_lot]" required>
                            <option value="">Sélectionnez un lot</option>
                            <?php foreach ($lots as $lot): ?>
                                <option value="<?php echo $lot['NumLotProd']; ?>" 
                                        data-qte="<?php echo $lot['QteRestante']; ?>">
                                    <?php echo htmlspecialchars($lot['NomProd'] . ' - Lot ' . $lot['NumLotProd'] . 
                                        ' (Disponible: ' . $lot['QteRestante'] . ') - Exp: ' . $lot['DateExpiration']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantité:</label>
                        <input type="number" name="lots[0][quantite]" min="1" required>
                    </div>
                </div>
            </div>

            <button type="button" class="add-lot" onclick="addLotItem()">Ajouter un Lot</button>
            <button type="submit" class="submit-btn">Créer le Bon de Livraison</button>
        </form>
    </div>

        </main>

    <script>
        let lotCount = 1;

        function addLotItem() {
            const lotItems = document.getElementById('lotItems');
            const newLotItem = document.createElement('div');
            newLotItem.className = 'lot-item';
            newLotItem.innerHTML = `
                <div class="form-group">
                    <label>Lot:</label>
                    <select name="lots[${lotCount}][num_lot]" required>
                        <option value="">Sélectionnez un lot</option>
                        <?php foreach ($lots as $lot): ?>
                            <option value="<?php echo $lot['NumLotProd']; ?>" 
                                    data-qte="<?php echo $lot['QteRestante']; ?>">
                                <?php echo htmlspecialchars($lot['NomProd'] . ' - Lot ' . $lot['NumLotProd'] . 
                                    ' (Disponible: ' . $lot['QteRestante'] . ') - Exp: ' . $lot['DateExpiration']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantité:</label>
                    <input type="number" name="lots[${lotCount}][quantite]" min="1" required>
                </div>
                <button type="button" class="remove-lot" onclick="this.parentElement.remove()">Supprimer</button>
            `;
            lotItems.appendChild(newLotItem);
            lotCount++;
        }

        // Add validation for quantities
        document.getElementById('deliveryForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default first
            const lotSelects = document.querySelectorAll('select[name^="lots"][name$="[num_lot]"]');
            const quantities = document.querySelectorAll('input[name^="lots"][name$="[quantite]"]');
            let hasError = false;
            
            for (let i = 0; i < lotSelects.length; i++) {
                const selectedOption = lotSelects[i].options[lotSelects[i].selectedIndex];
                const maxQte = parseInt(selectedOption.dataset.qte);
                const qte = parseInt(quantities[i].value);
                
                if (qte > maxQte) {
                    hasError = true;
                    quantities[i].style.borderColor = '#ff4444';
                    quantities[i].style.boxShadow = '0 0 0 2px rgba(255, 68, 68, 0.2)';
                    
                    // Create or update error message
                    let errorMsg = quantities[i].nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        quantities[i].parentNode.insertBefore(errorMsg, quantities[i].nextSibling);
                    }
                    errorMsg.textContent = `La quantité ne peut pas dépasser ${maxQte} pour ce lot`;
                    errorMsg.style.color = '#ff4444';
                    errorMsg.style.fontSize = '12px';
                    errorMsg.style.marginTop = '5px';
                } else {
                    quantities[i].style.borderColor = '#333';
                    quantities[i].style.boxShadow = 'none';
                    const errorMsg = quantities[i].nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            }
            
            if (!hasError) {
                // Only submit if there are no errors
                this.submit();
            }
        });

        // Add real-time validation on quantity input
        document.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('[quantite]')) {
                const lotSelect = e.target.closest('.lot-item').querySelector('select[name^="lots"][name$="[num_lot]"]');
                const selectedOption = lotSelect.options[lotSelect.selectedIndex];
                const maxQte = parseInt(selectedOption.dataset.qte);
                const qte = parseInt(e.target.value);
                
                if (qte > maxQte) {
                    e.target.style.borderColor = '#ff4444';
                    e.target.style.boxShadow = '0 0 0 2px rgba(255, 68, 68, 0.2)';
                    
                    // Create or update error message
                    let errorMsg = e.target.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        e.target.parentNode.insertBefore(errorMsg, e.target.nextSibling);
                    }
                    errorMsg.textContent = `La quantité ne peut pas dépasser ${maxQte} pour ce lot`;
                    errorMsg.style.color = '#ff4444';
                    errorMsg.style.fontSize = '12px';
                    errorMsg.style.marginTop = '5px';
                } else {
                    e.target.style.borderColor = '#333';
                    e.target.style.boxShadow = 'none';
                    const errorMsg = e.target.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            }
        });
    </script>

<script src="script.js"></script>
</body>
</html> 