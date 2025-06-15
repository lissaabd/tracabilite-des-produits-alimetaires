<?php
session_start();
include '../pdodb.php';

// Check if user is logged in
if (!isset($_SESSION['iduser'])) {
    header("Location: login.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $alert_id = $_POST['alert_id'];
    $new_status = $_POST['status'];
    $comment = $_POST['comment'] ?? null;
    $etat_lot = $_POST['etat_lot'] ?? null;
    $lot_type = $_POST['lot_type'] ?? null;
    $lot_id = $_POST['lot_id'] ?? null;

    try {
        if ($new_status === 'résolu') {
            // Update lot state if requested
            if ($etat_lot && $lot_type && $lot_id) {
                if ($etat_lot === 'etat_precedent') {
                    // Restore previous state from lot_etat_history
                    $stmt_hist = $pdo->prepare("SELECT etat_old FROM lot_etat_history WHERE lot_type = ? AND lot_id = ? ORDER BY changed_at DESC, id DESC LIMIT 1");
                    $stmt_hist->execute([$lot_type, $lot_id]);
                    $etat_precedent = $stmt_hist->fetchColumn();
                    if ($etat_precedent !== false) {
                        if ($lot_type === 'produitfini') {
                            $stmt_update = $pdo->prepare("UPDATE lotproduitfini SET Etat = ? WHERE NumLotProd = ?");
                            $stmt_update->execute([$etat_precedent, $lot_id]);
                        } else if ($lot_type === 'matierepremiere') {
                            $stmt_update = $pdo->prepare("UPDATE lotmatierepremiere SET Etat = ? WHERE NumLotMP = ?");
                            $stmt_update->execute([$etat_precedent, $lot_id]);
                        }
                    }
                } elseif ($etat_lot === 'retiré' || $etat_lot === 'rappelé') {
                    if ($lot_type === 'produitfini') {
                        $stmt_update = $pdo->prepare("UPDATE lotproduitfini SET Etat = ? WHERE NumLotProd = ?");
                        $stmt_update->execute([$etat_lot, $lot_id]);
                    } else if ($lot_type === 'matierepremiere') {
                        $stmt_update = $pdo->prepare("UPDATE lotmatierepremiere SET Etat = ? WHERE NumLotMP = ?");
                        $stmt_update->execute([$etat_lot, $lot_id]);
                    }
                }
            }
            $stmt = $pdo->prepare("UPDATE alerte SET statut = ?, dateResolution = NOW(), commentaireResolution = ? WHERE idAlerte = ?");
            $stmt->execute([$new_status, $comment, $alert_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE alerte SET statut = ? WHERE idAlerte = ?");
            $stmt->execute([$new_status, $alert_id]);
        }
        header("Location: listealertes.php");
        exit();
    } catch(PDOException $e) {
        $error = "Erreur lors de la mise à jour du statut: " . $e->getMessage();
    }
}

// Get all alerts with user information
$sql = "SELECT a.*, u.NomComplet, 
        'Matière Première' as TypeAlerte,
        m.NomMP as NomProduit,
        a.dateAlerte as DateAlerte,
        a.description as Description
        FROM alerte a
        LEFT JOIN users u ON a.IdUser = u.IdUser
        LEFT JOIN lotmatierepremiere lmp ON a.NumLotMP = lmp.NumLotMP
        LEFT JOIN matierepremiere m ON lmp.IdMP = m.IdMP
        LEFT JOIN bonreception br ON lmp.NumBonRec = br.NumBonRec
        WHERE 
            (a.IdUser = ? AND a.NumLotMP IS NOT NULL)
            OR (
                a.NumLotMP IS NOT NULL
                AND lmp.Etat = 'rappelé'
                AND br.IdFournisseur = ?
            )
            OR (
                a.NumLotMP IS NOT NULL
                AND lmp.Etat = 'rappelé'
                AND u.role = 'fabricant'
                AND br.IdFournisseur = ?
            )
        GROUP BY a.idAlerte
        ORDER BY a.dateAlerte DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['iduser'], $_SESSION['iduser'], $_SESSION['iduser']]);
$result = $stmt;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Alertes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
    <link rel="stylesheet" href="../fabricant/style.css">
    <style>
        .alerts-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .alerts-container h2 {
            color: #00fef6;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 500;
            padding: 20px 0;
            border-bottom: 2px solid #00fef6;
        }

        .alerts-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .alert-card {
            background-color: #222222;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s ease;
            border-left: 4px solid #00fef6;
            position: relative;
        }

        .alert-card:hover {
            background-color: #2a2a2a;
            transform: translateX(5px);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(0, 254, 246, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-icon i {
            color: #00fef6;
            font-size: 1.2em;
        }

        .alert-content {
            flex: 1;
            min-width: 0;
        }

        .alert-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .alert-user {
            color: #00fef6;
            font-weight: 500;
            font-size: 0.95em;
        }

        .alert-date {
            color: #888;
            font-size: 0.85em;
        }

        .alert-description {
            color: #ddd;
            font-size: 0.95em;
            margin: 0;
            line-height: 1.4;
        }

        .alert-lot {
            color: #888;
            font-size: 0.85em;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .alert-lot i {
            color: #00fef6;
            font-size: 0.9em;
        }

        .alert-status {
            margin-left: auto;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            cursor: pointer;
            background-color: #333;
            color: #00fef6;
            border: 1px solid #00fef6;
        }

        .alert-status.en-cours {
            background-color: #333;
            color: #3D90D7;
            border-color: #3D90D7;
        }

        .alert-status.en-attente {
            background-color: #333;
            color: #ffa500;
            border-color: #ffa500;
        }

        .alert-status.résolu {
            background-color: #333;
            color: #00fef6;
            border-color: #00fef6;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #222;
            margin: 15% auto;
            padding: 20px;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            border: 1px solid #00fef6;
        }

        .modal-header {
            color: #00fef6;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body label.etat-lot-label {
            display: block;
            margin-bottom: 10px;
            color: #00fef6;
            font-size: 16px;
            font-weight: 500;
        }

        .modal-body select#etatLotSelect {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #444;
            border-radius: 8px;
            background-color: #333;
            color: #fff;
            font-size: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .modal-body select#etatLotSelect:focus {
            border-color: #00fef6;
            box-shadow: 0 0 10px rgba(0, 254, 246, 0.2);
            outline: none;
        }

        .modal-body textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #333;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            margin-top: 10px;
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            text-align: right;
        }

        .modal-footer button {
            padding: 8px 16px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-footer .cancel {
            background-color: #666;
            color: #fff;
        }

        .modal-footer .confirm {
            background-color: #00fef6;
            color: #000;
        }

        .resolution-info {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #333;
            font-size: 0.85em;
            color: #888;
        }

        .resolution-info .date {
            color: #00fef6;
        }

        .resolution-info .comment {
            color: #ddd;
            margin-top: 4px;
        }

        .no-alerts {
            text-align: center;
            color: #888;
            padding: 40px;
            font-size: 18px;
            background-color: #222222;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 800px;
        }

        .etat-lot-label {
            display: block;
            font-weight: 500;
            color: #00fef6;
            margin-bottom: 10px;
            font-size: 1.08em;
            letter-spacing: 0.01em;
        }

        .etat-lot-select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 5px;
            border: 1.5px solid #00fef6;
            background: #222;
            color: #00fef6;
            font-size: 1em;
            margin-bottom: 10px;
            outline: none;
            transition: border-color 0.2s;
            box-shadow: 0 2px 8px rgba(0,254,246,0.05);
        }

        .etat-lot-select:focus {
            border-color: #3D90D7;
            background: #232b2b;
        }

        @media (max-width: 768px) {
            .alert-card {
                padding: 12px;
                gap: 12px;
            }

            .alert-icon {
                width: 32px;
                height: 32px;
            }

            .alert-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
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

    <main>
        <div class="alerts-container">
            <h2>Liste des Alertes</h2>
            
            <?php if ($result->rowCount() > 0): ?>
                <div class="alerts-grid">
                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="alert-card" data-lot-type="<?php echo $row['NumLotProd'] ? 'produitfini' : 'matierepremiere'; ?>" data-lot-id="<?php echo $row['NumLotProd'] ?: $row['NumLotMP']; ?>">
                            <div class="alert-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-header">
                                    <span class="alert-user"><?php echo htmlspecialchars($row['NomComplet']); ?></span>
                                    <span class="alert-date"><?php echo date('d/m/Y H:i', strtotime($row['DateAlerte'])); ?></span>
                                </div>
                                <p class="alert-description"><?php echo nl2br(htmlspecialchars($row['Description'])); ?></p>
                                <div class="alert-lot">
                                    <i class="fas fa-box"></i>
                                    <?php 
                                        $lotId = $row['NumLotProd'] ?: $row['NumLotMP'];
                                        echo htmlspecialchars($row['TypeAlerte'] . ' - ' . $row['NomProduit'] . ' (Lot ID: ' . $lotId . ')'); 
                                    ?>
                                </div>
                                <?php if ($row['statut'] === 'résolu' && $row['commentaireResolution']): ?>
                                    <div class="resolution-info">
                                        <div class="date">Résolu le <?php echo date('d/m/Y H:i', strtotime($row['dateResolution'])); ?></div>
                                        <div class="comment"><?php echo nl2br(htmlspecialchars($row['commentaireResolution'])); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($row['statut'] !== 'résolu'): ?>
                                <select class="alert-status <?php echo str_replace(' ', '-', $row['statut']); ?>" onchange="handleStatusChange(this, <?php echo $row['idAlerte']; ?>)">
                                    <option value="en cours" <?php echo $row['statut'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="en attente" <?php echo $row['statut'] === 'en attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="résolu" <?php echo $row['statut'] === 'résolu' ? 'selected' : ''; ?>>Résolu</option>
                                </select>
                            <?php else: ?>
                                <div class="alert-status résolu">Résolu</div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-alerts">
                    Aucune alerte n'a été créée.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal for resolution lot state only -->
    <div id="resolutionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Résoudre l'alerte</h3>
            </div>
            <form id="resolutionForm" method="POST" action="listealertes.php">
                <div class="modal-body">
                    <label for="etatLotSelect" class="etat-lot-label">Choisissez le nouvel état du lot :</label>
                    <select id="etatLotSelect" name="etat_lot" class="etat-lot-select" required>
                        <option value="">-- Sélectionnez l'état --</option>
                        <option value="etat_precedent">Restaurer l'état précédent</option>
                        <option value="retiré">Retiré</option>
                        <option value="rappelé">Rappelé</option>
                    </select>
                    <input type="hidden" id="resolutionAlertId" name="alert_id">
                    <input type="hidden" id="resolutionLotType" name="lot_type">
                    <input type="hidden" id="resolutionLotId" name="lot_id">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="status" value="résolu">
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="confirm">Confirmer</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        let currentAlertId = null;
        let currentStatus = null;

        function handleStatusChange(select, alertId) {
            const newStatus = select.value;
            currentAlertId = alertId;
            currentStatus = newStatus;

            if (newStatus === 'résolu') {
                // Get lot type and lot id from the DOM
                const card = select.closest('.alert-card');
                const lotType = card.getAttribute('data-lot-type');
                const lotId = card.getAttribute('data-lot-id');
                document.getElementById('resolutionAlertId').value = alertId;
                document.getElementById('resolutionLotType').value = lotType;
                document.getElementById('resolutionLotId').value = lotId;
                document.getElementById('resolutionModal').style.display = 'block';
                document.getElementById('etatLotSelect').value = '';
            } else {
                updateStatus(alertId, newStatus);
            }
        }

        function closeModal() {
            document.getElementById('resolutionModal').style.display = 'none';
            // Reset the select to its previous value
            const select = document.querySelector(`select[data-alert-id="${currentAlertId}"]`);
            if (select) {
                select.value = currentStatus === 'résolu' ? 'en cours' : currentStatus;
            }
        }

        function confirmResolution() {
            const comment = document.getElementById('resolutionComment').value.trim();
            if (comment === '') {
                alert('Veuillez fournir un commentaire pour la résolution.');
                return;
            }

            updateStatus(currentAlertId, 'résolu', comment);
            document.getElementById('resolutionModal').style.display = 'none';
        }

        function updateStatus(alertId, status, comment = null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'listealertes.php';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_status';
            form.appendChild(actionInput);

            const alertIdInput = document.createElement('input');
            alertIdInput.type = 'hidden';
            alertIdInput.name = 'alert_id';
            alertIdInput.value = alertId;
            form.appendChild(alertIdInput);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = status;
            form.appendChild(statusInput);

            if (comment) {
                const commentInput = document.createElement('input');
                commentInput.type = 'hidden';
                commentInput.name = 'comment';
                commentInput.value = comment;
                form.appendChild(commentInput);
            }

            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resolutionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
    <script src="../fabricant/script.js"></script>
</body>
</html>