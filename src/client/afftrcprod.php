<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Traçabilité Matière Première</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../fabricant/style.css">
  <link rel="stylesheet" href="../fabricant/form.css">
    
</head>
<body>
  <!-- الشريط الجانبي -->
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

  <!-- زر تبديل الشريط الجانبي -->
  <button id="toggleSidebar" onclick="toggleSidebar()">☰</button>

  <!-- المحتوى الرئيسي -->
  <main>

  <!-- شريط البحث -->
  <div class="search-bar">
    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <input type="text" name="query" placeholder="ID d'un lot produit..." 
             value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
      <button class="search-icon-bottun"><i class="fas fa-search"></i></button>
    </form>
  </div>

    <h1>Tracer tous les lots de matières premières d'un lot produit</h1>

         <?php
    session_start();
    require_once '../pdodb.php';

    // Check if user is logged in
    if (!isset($_SESSION['iduser'])) {
        header("Location: ../login.php");
        exit();
    }

    try {
        if (isset($_GET['query']) && !empty($_GET['query'])) {
            $query = trim($_GET['query']);
            
            // DEBUG: Check if the lot is linked to the client in delivery
            $debugSql = "SELECT lpf.NumLotProd, bl.IdClient
                FROM LotProduitFini lpf
                JOIN lignelivraison ll ON ll.NumLotProd = lpf.NumLotProd
                JOIN bonlivraison bl ON bl.NumBonLiv = ll.NumBonLiv
                WHERE lpf.NumLotProd = :query";
            $debugStmt = $pdo->prepare($debugSql);
            $debugStmt->execute([':query' => $query]);
            $debugRows = $debugStmt->fetchAll();
            if (!$debugRows) {
                echo '<div class="no-results">DEBUG: Aucun lot livré trouvé pour ce NumLotProd</div>';
            } else {
                $found = false;
                foreach ($debugRows as $row) {
                    if ($row['IdClient'] == $_SESSION['iduser']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo '<div class="no-results">DEBUG: Ce lot est livré mais pas à ce client (IdClient attendu: ' . htmlspecialchars($_SESSION['iduser']) . ')</div>';
                }
            }

            $sql = "
                SELECT 
                    lpf.NumLotProd AS IDLotProd,
                    lmp.NumLotMP AS IDLotMP,
                    mp.nomMP AS NomMatierePremiere,
                    lmp.DateProduction,
                    lmp.DateExpiration,
                    lmp.Etat,
                    u.NomComplet AS NomFournisseur,
                    u.Email AS ContactFournisseur
                FROM LotProduitFini lpf
                JOIN OperationProduction op ON lpf.NumOpProd = op.NumOpProd
                JOIN QteUtilise qu ON qu.NumOpProd = op.NumOpProd
                JOIN LotMatierePremiere lmp ON lmp.NumLotMP = qu.NumLotMP
                JOIN MatierePremiere mp ON mp.IdMP = lmp.IdMP
                JOIN bonreception brmp ON brmp.NumBonRec = lmp.NumBonRec
                JOIN Users u ON u.Iduser = brmp.IdFournisseur AND u.role = 'fournisseur'
                JOIN lignelivraison ll ON ll.NumLotProd = lpf.NumLotProd
                JOIN bonlivraison bl ON bl.NumBonLiv = ll.NumBonLiv
                WHERE lpf.NumLotProd = :query
                AND bl.IdClient = :clientId";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':query' => $query,
                ':clientId' => $_SESSION['iduser']
            ]);
            $results = $stmt->fetchAll();

            if ($results) {
                echo '<table class="content-table">
                        <thead>
                          <tr>
                            <th>ID lot produit</th>
                            <th>ID lot matière première</th>
                            <th>Nom matière première</th>
                            <th>Date production</th>
                            <th>Date expiration</th>
                            <th>Etat</th>
                            <th>Nom fournisseur</th>
                            <th>Contact</th>
                          </tr>
                        </thead>
                        <tbody>';

                foreach ($results as $row) {
                    echo "<tr>";
                    echo "<td>{$row['IDLotProd']}</td>";
                    echo "<td>{$row['IDLotMP']}</td>";
                    echo "<td>{$row['NomMatierePremiere']}</td>";
                    echo "<td>{$row['DateProduction']}</td>";
                    echo "<td>{$row['DateExpiration']}</td>";
                    echo "<td>{$row['Etat']}</td>";
                    echo "<td>{$row['NomFournisseur']}</td>";
                    echo "<td>{$row['ContactFournisseur']}</td>";
                    echo "</tr>";
                }

                echo '</tbody></table>';
                
                echo '<div class="results-count">'.count($results).' résultats trouvés</div>';
            } else {
                echo '<div class="no-results">Aucun résultat pour "'.htmlspecialchars($query).'" ou vous n\'avez pas accès à ce lot</div>';
            }
        } else {
            echo '<div class="no-results">Veuillez entrer un ID de lot produit pour effectuer une recherche</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="no-results">Erreur de connexion: '.htmlspecialchars($e->getMessage()).'</div>';
    }
    ?>
      </tbody>
    </table>
  </main>

  <script src="../fabricant/script.js"></script>
</body>
</html>