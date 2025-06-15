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
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "trace"; 

    try {
      $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

      $query = isset($_GET['query']) ? trim($_GET['query']) : '';
      
      $sql = "
        SELECT 
          lp.NumLotProd AS IDLotProduit,
          pf.nomProd AS nomProduit,
          DATE_FORMAT(lp.DateProduction, '%Y-%m-%d') AS DateProduction,
          DATE_FORMAT(lp.DateExpiration, '%Y-%m-%d') AS DateExpiration,
          lp.Etat,
          fab.NomComplet AS NomFabricant,
          fab.Email AS ContactFabricant,
          cli.NomComplet AS NomClient,
          cli.Email AS ContactClient
        FROM LotProduitFini lp
        JOIN ProduitFini pf ON pf.IdProd = lp.IdProd
        JOIN OperationProduction op ON op.NumOpProd = lp.NumOpProd
        JOIN Users fab ON fab.Iduser = op.IdResFab AND fab.role = 'fabricant'
        LEFT JOIN BonLivraison bl ON bl.NumBonLiv = lp.NumLotProd
        LEFT JOIN Users cli ON cli.Iduser = bl.IdClient AND cli.role = 'client'
      ";

      $params = [];
      $whereClauses = [];

      if (!empty($query)) {
        if (is_numeric($query)) {
          $whereClauses[] = "pf.IdProd = :queryId";
          $params[':queryId'] = $query;
        }
        
        $whereClauses[] = "pf.nomProd LIKE :queryName";
        $params[':queryName'] = "%$query%";
      }

      if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" OR ", $whereClauses);
      }

      $sql .= " ORDER BY lp.DateProduction DESC";

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $results = $stmt->fetchAll();

      if ($results) {
        echo '<table class="content-table">
                <thead>
                  <tr>
                    <th>N° Lot</th>
                    <th>Produit</th>
                    <th>Date production</th>
                    <th>Date expiration</th>
                    <th>État</th>
                    <th>Fabricant</th>
                    <th>Contact</th>
                    <th>Client</th>
                    <th>Contact client</th>
                  </tr>
                </thead>
                <tbody>';

        foreach ($results as $row) {
          echo "<tr>";
          echo "<td>{$row['IDLotProduit']}</td>";
          echo "<td>{$row['nomProduit']}</td>";
          echo "<td>{$row['DateProduction']}</td>";
          echo "<td>{$row['DateExpiration']}</td>";
          echo "<td>{$row['Etat']}</td>";
          echo "<td>{$row['NomFabricant']}</td>";
          echo "<td>{$row['ContactFabricant']}</td>";
          echo "<td>".($row['NomClient'] ?: '-')."</td>";
          echo "<td>".($row['ContactClient'] ?: '-')."</td>";
          echo "</tr>";
        }

        echo '</tbody></table>';
        
        echo '<div class="results-count">'.count($results).' résultats trouvés</div>';
      } else {
        if (!empty($query)) {
          echo '<div class="no-results">Aucun résultat pour "'.htmlspecialchars($query).'"</div>';
        } else {
          echo '<div class="no-results">Aucune donnée disponible</div>';
        }
      }

    } catch (PDOException $e) {
      echo '<div class="no-results">Erreur de connexion: '.htmlspecialchars($e->getMessage()).'</div>';
    }
    ?>
  </main>

  <script src="script.js"></script>
</body>
</html>