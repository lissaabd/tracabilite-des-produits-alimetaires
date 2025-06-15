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
  <link rel="stylesheet" href="style.css">
    
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
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "trace"; 

    try {
      $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

      if (isset($_GET['query']) && !empty($_GET['query'])) {
        $query = trim($_GET['query']);
        
        $sql = "
              SELECT 
                lpf.NumLotProd AS IDLotProd,
                lmp.NumLotMP AS IDLotMP,
                mp.nomMP AS NomMatierePremiere,
                lmp.DateProduction,
                lmp.DateExpiration,
                lmp.Etat,
                u.NomComplet AS NomFournisseur,
                u.Email AS ContactFournisseur,
                qu.QteUtilise
              FROM LotProduitFini lpf
              JOIN OperationProduction op ON lpf.NumOpProd = op.NumOpProd
              JOIN QteUtilise qu ON qu.NumOpProd = op.NumOpProd
              JOIN LotMatierePremiere lmp ON lmp.NumLotMP = qu.NumLotMP
              JOIN MatierePremiere mp ON mp.IdMP = lmp.IdMP
              LEFT JOIN BonReception br ON br.NumBonRec = lmp.NumBonRec
              LEFT JOIN Users u ON u.Iduser = br.IdFournisseur AND u.role = 'fournisseur'
              WHERE lpf.NumLotProd = :query
              ORDER BY lmp.NumLotMP
           ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':query' => $query]);
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
                  <th>Quantité utilisée</th>
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
                echo "<td>{$row['QteUtilise']}</td>";
                echo "</tr>";
              }
             echo '</tbody></table>';
          
          echo '<div class="results-count">'.count($results).' résultats trouvés</div>';
        } else {
          echo '<div class="no-results">Aucun résultat pour "'.htmlspecialchars($query).'"</div>';
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

  <!-- استيراد ملف JavaScript الرئيسي -->
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
    
    <script>
function toggleForms() {
  var type = document.getElementById("type").value;
  document.getElementById("form_mp").style.display = (type === "lot_matiere_premiere") ? "block" : "none";
  document.getElementById("form_pf").style.display = (type === "lot_produit_fini") ? "block" : "none";
}
</script>

<script src="script.js"></script>
</body>
</html>