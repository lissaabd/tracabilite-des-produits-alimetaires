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
    $userId = $_SESSION['iduser'];
    $lotMP = $_POST['lot_mp'] ?? '';
    $description = $_POST['description'] ?? '';

    $lotMP = htmlspecialchars(trim($lotMP));
    $description = htmlspecialchars(trim($description));

    $numLotMP = !empty($lotMP) ? intval($lotMP) : null;
    $numLotProd = null; // Always null for this form

    // Optional: Check if the selected lot belongs to the supplier
    $stmt_check = $pdo->prepare("
        SELECT lmp.NumLotMP
        FROM lotmatierepremiere lmp
        JOIN bonreception br ON br.NumBonRec = lmp.NumBonRec
        WHERE lmp.NumLotMP = ? AND br.IdFournisseur = ?
    ");
    $stmt_check->execute([$numLotMP, $userId]);
    if ($stmt_check->fetchColumn() === false) {
        $error = "Vous ne pouvez lancer une alerte que sur vos propres lots de matière première.";
    } else {
        // Insert alert
        $stmt = $pdo->prepare("INSERT INTO alerte (IdUser, NumLotProd, NumLotMP, Description, DateAlerte) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $numLotProd, $numLotMP, $description]);
        if ($stmt->rowCount() > 0) {
            header("Location: listealertes.php");
            exit();
        } else {
            $error = "Erreur lors de la création de l'alerte. Veuillez réessayer.";
        }
    }
}

// Récupérer les lots de matières premières dont l'utilisateur est le fournisseur
$sql_lots_mp = "
    SELECT lmp.NumLotMP, lmp.DateProduction, lmp.DateExpiration, mp.NomMP
    FROM lotmatierepremiere lmp
    JOIN matierepremiere mp ON mp.IdMP = lmp.IdMP
    JOIN bonreception br ON br.NumBonRec = lmp.NumBonRec
    WHERE br.IdFournisseur = ?
    ORDER BY lmp.NumLotMP DESC
";
$result_lots_mp = $pdo->prepare($sql_lots_mp);
$result_lots_mp->execute([$_SESSION['iduser']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lancer une Alerte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <link rel="stylesheet" href="../fabricant/style.css">
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
          <a href="afflotmp.php">Lot matière première</a>
          <a href="ctrllotmp.php">Qualité matière première</a>
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
            <?php if(!empty($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Numéro de Lot Matière Première</label>
                    <select name="lot_mp" id="lot-mp" required>
                        <option value="">-- Sélectionnez un lot --</option>
                        <?php while ($lot = $result_lots_mp->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $lot['NumLotMP']; ?>">
                                <?php echo htmlspecialchars($lot['NomMP']) . ' (Lot ' . htmlspecialchars($lot['NumLotMP']) . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
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
    <script src="../fabricant/script.js"></script>
</body>
</html>