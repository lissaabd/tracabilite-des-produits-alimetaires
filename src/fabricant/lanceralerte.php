<?php
session_start();
include '../pdodb.php';

// Check if user is logged in
if (!isset($_SESSION['iduser'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from session
    $userId = $_SESSION['iduser'];
    
    $type = $_POST['type'] ?? '';
    $lotProd = $_POST['lot_prod'] ?? '';
    $lotMP = $_POST['lot_mp'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Clean and validate inputs
    $type = htmlspecialchars(trim($type));
    $lotProd = htmlspecialchars(trim($lotProd));
    $lotMP = htmlspecialchars(trim($lotMP));
    $description = htmlspecialchars(trim($description));
    
    // Initialize lot IDs as null
    $numLotProd = null;
    $numLotMP = null;
    
    // Set the appropriate lot ID based on type
    if ($type === 'produit' && !empty($lotProd)) {
        $numLotProd = intval($lotProd);
        // Get current etat BEFORE changing it
        $stmt = $pdo->prepare("SELECT Etat FROM lotproduitfini WHERE NumLotProd = ?");
        $stmt->execute([$numLotProd]);
        $etat_old = $stmt->fetchColumn();
        if ($etat_old !== false) {
            // Insert into lot_etat_history BEFORE changing the lot
            $stmt_hist = $pdo->prepare("INSERT INTO lot_etat_history (lot_type, lot_id, etat_old, etat_new) VALUES ('produitfini', ?, ?, ?)");
            $stmt_hist->execute([$numLotProd, $etat_old, 'en quarantaine']);
            // Now change Etat to 'en quarantaine'
            $stmt_update = $pdo->prepare("UPDATE lotproduitfini SET Etat = 'en quarantaine' WHERE NumLotProd = ?");
            $stmt_update->execute([$numLotProd]);
        }
    } elseif ($type === 'matiere' && !empty($lotMP)) {
        $numLotMP = intval($lotMP);
        $stmt = $pdo->prepare("SELECT Etat FROM lotmatierepremiere WHERE NumLotMP = ?");
        $stmt->execute([$numLotMP]);
        $etat_old = $stmt->fetchColumn();
        if ($etat_old !== false) {
            $stmt_hist = $pdo->prepare("INSERT INTO lot_etat_history (lot_type, lot_id, etat_old, etat_new) VALUES ('matierepremiere', ?, ?, ?)");
            $stmt_hist->execute([$numLotMP, $etat_old, 'en quarantaine']);
            $stmt_update = $pdo->prepare("UPDATE lotmatierepremiere SET Etat = 'en quarantaine' WHERE NumLotMP = ?");
            $stmt_update->execute([$numLotMP]);
        }
    }
    
    // Debug information
    error_log("Type: " . $type);
    error_log("Lot Prod: " . $lotProd);
    error_log("Lot MP: " . $lotMP);
    error_log("NumLotProd: " . ($numLotProd ?? 'null'));
    error_log("NumLotMP: " . ($numLotMP ?? 'null'));
    
    // Prepare SQL statement
    $stmt = $pdo->prepare("INSERT INTO alerte (IdUser, NumLotProd, NumLotMP, Description, DateAlerte) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $numLotProd, $numLotMP, $description]);
    
    if ($stmt->rowCount() > 0) {
        error_log("Alerte créée avec succès");
        header("Location: listealertes.php");
        exit();
    } else {
        error_log("Erreur lors de la création de l'alerte");
        $error = "Erreur lors de la création de l'alerte. Veuillez réessayer.";
    }
}

// Récupérer les lots de produits finis
$sql_lots_prod = "SELECT NumLotProd, DateProduction, DateExpiration FROM lotproduitfini WHERE Etat = 'En stock'";
$result_lots_prod = $pdo->query($sql_lots_prod);

// Récupérer les lots de matières premières
$sql_lots_mp = "SELECT NumLotMP, DateProduction, DateExpiration FROM lotmatierepremiere WHERE Etat = 'En stock'";
$result_lots_mp = $pdo->query($sql_lots_mp);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lancer une Alerte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .alert-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 30px;
            background-color: #222222;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 254, 246, 0.15);
        }

        .alert-form h2 {
            color: #00fef6;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #00fef6;
            font-size: 16px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #333;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #00fef6;
            box-shadow: 0 0 10px rgba(0, 254, 246, 0.2);
            outline: none;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #333;
            border-radius: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .radio-option:hover {
            background-color: #444;
        }

        .radio-option input[type="radio"] {
            accent-color: #00fef6;
            width: 18px;
            height: 18px;
        }

        .lot-input {
            display: none;
            margin-top: 15px;
            animation: fadeIn 0.3s ease;
        }

        .lot-input.active {
            display: block;
        }

        .lot-input input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #333;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .lot-input input:focus {
            border-color: #00fef6;
            box-shadow: 0 0 10px rgba(0, 254, 246, 0.2);
            outline: none;
        }

        .lot-validation {
            margin-top: 8px;
            font-size: 14px;
            display: none;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .lot-validation.valid {
            color: #28a745;
            display: block;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .lot-validation.invalid {
            color: #dc3545;
            display: block;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .submit-btn {
            background-color: #00fef6;
            color: #000;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #00d6cf;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 254, 246, 0.3);
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            animation: fadeIn 0.3s ease;
        }

        .message.success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .alert-form {
                margin: 10px;
                padding: 20px;
            }

            .radio-group {
                flex-direction: column;
                gap: 15px;
            }

            .radio-option {
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

    <button id="toggleSidebar" onclick="toggleSidebar()">☰</button>

    <main>
        <div class="alert-form">
            <h2>Lancer une Alerte</h2>
            
            <?php if($message): ?>
                <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Type de Lot</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="type" value="produit" required>
                            Produit Fini
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="type" value="matiere" required>
                            Matière Première
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div id="produit-input" class="lot-input">
                        <label>Numéro de Lot Produit</label>
                        <input type="text" name="lot_prod" id="lot-prod" placeholder="Entrez le numéro de lot produit">
                        <div class="lot-validation" id="lot-prod-validation"></div>
                    </div>

                    <div id="matiere-input" class="lot-input">
                        <label>Numéro de Lot Matière Première</label>
                        <input type="text" name="lot_mp" id="lot-mp" placeholder="Entrez le numéro de lot matière première">
                        <div class="lot-validation" id="lot-mp-validation"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description de l'Alerte</label>
                    <textarea name="description" required placeholder="Décrivez le problème ou la situation..."></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Lancer l'Alerte
                </button>
            </form>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const produitInput = document.getElementById('produit-input');
            const matiereInput = document.getElementById('matiere-input');
            const lotProdInput = document.getElementById('lot-prod');
            const lotMPInput = document.getElementById('lot-mp');
            const lotProdValidation = document.getElementById('lot-prod-validation');
            const lotMPValidation = document.getElementById('lot-mp-validation');

            typeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'produit') {
                        produitInput.classList.add('active');
                        matiereInput.classList.remove('active');
                        lotMPInput.value = '';
                        lotMPValidation.textContent = '';
                        lotMPValidation.className = 'lot-validation';
                    } else if (this.value === 'matiere') {
                        matiereInput.classList.add('active');
                        produitInput.classList.remove('active');
                        lotProdInput.value = '';
                        lotProdValidation.textContent = '';
                        lotProdValidation.className = 'lot-validation';
                    }
                });
            });

            function validateLot(input, validationElement, type) {
                const lotId = input.value.trim();
                if (lotId === '') {
                    validationElement.textContent = 'Veuillez entrer un numéro de lot';
                    validationElement.className = 'lot-validation invalid';
                    return false;
                }

                fetch('../../check_lot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `type=${type}&id=${lotId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        validationElement.textContent = 'Lot valide';
                        validationElement.className = 'lot-validation valid';
                    } else {
                        validationElement.textContent = 'Lot non trouvé';
                        validationElement.className = 'lot-validation invalid';
                    }
                })
                .catch(error => {
                    validationElement.textContent = 'Erreur de validation';
                    validationElement.className = 'lot-validation invalid';
                });
            }

            lotProdInput.addEventListener('input', function() {
                validateLot(this, lotProdValidation, 'produit');
            });

            lotMPInput.addEventListener('input', function() {
                validateLot(this, lotMPValidation, 'matiere');
            });
        });
    </script>
</body>
</html>