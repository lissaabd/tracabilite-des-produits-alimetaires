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
  <style>
    main {
      margin-left: 250px;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }

    .content-table {
      width: 100%;
      overflow-x: auto;
      margin-top: 20px;
    }

    .content-table table {
      width: 100%;
      min-width: 800px;
    }

    .search-bar {
      margin-bottom: 20px;
    }

    .search-bar form {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .search-bar input {
      flex: 1;
      padding: 8px;
      border: 1px solid #444;
      border-radius: 4px;
      background-color: #333;
      color: #fff;
    }

    .search-icon-bottun {
      padding: 8px 15px;
      background-color: #00fef6;
      border: none;
      border-radius: 4px;
      color: #333;
      cursor: pointer;
    }

    .clear-search {
      color: #00fef6;
      text-decoration: none;
    }

    .results-count {
      margin-top: 15px;
      color: #00fef6;
    }

    .no-results {
      color: #ff6b6b;
      margin-top: 20px;
    }

    @media (max-width: 768px) {
      main {
        margin-left: 0;
        padding: 10px;
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
      <div class="search-bar">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <input type="text" name="query" id="queryInput" placeholder="ID ou nom d'une matiere premiere..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
          <button type="submit" class="search-icon-bottun"><i class="fas fa-search"></i></button>
          <?php if(isset($_GET['query']) && !empty($_GET['query'])): ?>
            <a href="?" class="clear-search">Effacer</a>
          <?php endif; ?>
        </form>
      </div>
      <h1>Rechercher les lots d'une certaine matiére première</h1>

      <?php
      $host = "localhost";
      $user = "root";
      $pass = "";
      $db = "trace"; 

      try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = isset($_GET['query']) ? trim($_GET['query']) : '';

        // Base query
        $sql = "
          SELECT 
            lmp.NumLotMP AS IDLotMP,
            mp.NomMP AS NomMP,
            DATE_FORMAT(lmp.DateProduction, '%Y-%m-%d') AS DateProduction,
            DATE_FORMAT(lmp.DateExpiration, '%Y-%m-%d') AS DateExpiration,
            lmp.Etat,
            COALESCE(u.NomComplet, 'Non assigné') AS NomFournisseur,
            COALESCE(u.Email, 'Non assigné') AS ContactFournisseur
          FROM LotMatierePremiere lmp
          JOIN MatierePremiere mp ON mp.IdMP = lmp.IdMP
          LEFT JOIN BonReception br ON br.NumBonRec = lmp.NumBonRec
          LEFT JOIN Users u ON u.Iduser = br.IdFournisseur AND u.role = 'fournisseur'
        ";

        $params = [];
        $whereClauses = [];

        if (!empty($query)) {
          if (is_numeric($query)) {
            $whereClauses[] = "mp.IdMP = :queryId";
            $params[':queryId'] = $query;
          }
          
          $whereClauses[] = "mp.NomMP LIKE :queryName";
          $params[':queryName'] = "%$query%";
        }

        if (!empty($whereClauses)) {
          $sql .= " WHERE " . implode(" OR ", $whereClauses);
        }

        $sql .= " ORDER BY lmp.DateProduction DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
          echo '<table class="content-table">
                  <thead>
                    <tr>
                      <th>ID lot matiere premiere</th>
                      <th>nom matiere premiere</th>
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
            echo "<td>{$row['IDLotMP']}</td>";
            echo "<td>{$row['NomMP']}</td>";
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
