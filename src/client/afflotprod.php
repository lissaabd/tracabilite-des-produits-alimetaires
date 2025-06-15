<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lots Produits</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../fabricant/style.css">
  <link rel="stylesheet" href="../fabricant/form.css">
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
  <div class="search-bar">
    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <input type="text" name="query" id="queryInput" placeholder="ID ou nom du produit..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
      <button type="submit" class="search-icon-bottun"><i class="fas fa-search"></i></button>
      <?php if(isset($_GET['query']) && !empty($_GET['query'])): ?>
        <a href="?" class="clear-search">Effacer</a>
      <?php endif; ?>
    </form>
  </div>

    <h1>Recherche de lots de produits</h1>


    <?php
    session_start();
    require_once '../pdodb.php';

    // Check if user is logged in
    if (!isset($_SESSION['iduser'])) {
        header("Location: ../login.php");
        exit();
    }

    // Get all product lots for this client (avoid duplicates)
    $sql = "SELECT lpf.NumLotProd, p.nomProd, lpf.DateProduction, lpf.DateExpiration, lpf.Etat
            FROM lotproduitfini lpf 
            JOIN produitfini p ON lpf.IdProd = p.IdProd 
            JOIN lignelivraison ll ON ll.NumLotProd = lpf.NumLotProd
            JOIN bonlivraison bl ON bl.NumBonLiv = ll.NumBonLiv
            WHERE bl.IdClient = :clientId
            GROUP BY lpf.NumLotProd, p.nomProd, lpf.DateProduction, lpf.DateExpiration, lpf.Etat
            ORDER BY lpf.DateProduction DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':clientId' => $_SESSION['iduser']]);
    $lots = $stmt->fetchAll();

    if ($lots) {
      echo '<table class="content-table">
              <thead>
                <tr>
                  <th>N° Lot</th>
                  <th>Produit</th>
                  <th>Date production</th>
                  <th>Date expiration</th>
                  <th>État</th>
                </tr>
              </thead>
              <tbody>';

      foreach ($lots as $row) {
        echo "<tr>";
        echo "<td>{$row['NumLotProd']}</td>";
        echo "<td>{$row['nomProd']}</td>";
        echo "<td>{$row['DateProduction']}</td>";
        echo "<td>{$row['DateExpiration']}</td>";
        echo "<td>{$row['Etat']}</td>";
        echo "</tr>";
      }

      echo '</tbody></table>';
      
      echo '<div class="results-count">'.count($lots).' résultats trouvés</div>';
    } else {
      echo '<div class="no-results">Aucune donnée disponible</div>';
    }
    ?>
  </main>

  <script src="../fabricant/script.js"></script>
</body>
</html>