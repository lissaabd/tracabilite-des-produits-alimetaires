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

      <button id="toggleSidebar" onclick="toggleSidebar()">☰</button>

 
  <main>

  <div class="search-bar">
    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <input type="text" name="query" placeholder="ID d'un lot produit..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
      <button class="search-icon-bottun"><i class="fas fa-search"></i></button>
    </form>
 </div> 

            <h1>Localiser un lot Produit</h1>

        <table class="content-table">
            <thead>
                <thead>
  <tr>
    <th>Id lot produit</th>
    <th>nom produit</th>
    <th>Id depot</th>
    <th>Adresse</th>
    <th>Date entree</th>
    <th>Date sortie</th>
    <th>Nom Logisticien</th>
    <th>Contact</th>
  </tr>
</thead>
            </thead>
            <tbody>
               <?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "trace";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    // Base query: get all localization info
    $sql = "
    SELECT 
      lp.NumLotProd AS IDLotProduit,
      pf.nomProd AS NomProduit,
      d.NumDep AS IDDepot,
      d.Adresse,
      ol.DateEntree,
      ol.DateSortie,
      u.NomComplet AS NomLogisticien,
      u.Email AS ContactLogisticien
    FROM LotProduitFini lp
    JOIN ProduitFini pf ON pf.IdProd = lp.IdProd
    JOIN OperationLocalisation ol ON ol.NumLotProd = lp.NumLotProd
    JOIN Depot d ON d.NumDep = ol.NumDep
    JOIN AffectationDepot ad ON ad.NumDep = d.NumDep
    JOIN Users u ON u.Iduser = ad.IdResDep AND u.role = 'logisticien'
    ";

    $params = [];

    if ($query !== '') {
        // Filter by exact lot ID OR partial product name
        $sql .= " WHERE (lp.NumLotProd = :queryExact OR pf.nomProd LIKE :queryLike)";
        $params = [
            ':queryExact' => $query,
            ':queryLike' => "%$query%"
        ];
    }

    $sql .= " ORDER BY ol.DateEntree DESC, ol.DateSortie DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['IDLotProduit']}</td>";
            echo "<td>{$row['NomProduit']}</td>";
            echo "<td>{$row['IDDepot']}</td>";
            echo "<td>{$row['Adresse']}</td>";
            echo "<td>{$row['DateEntree']}</td>";
            echo "<td>{$row['DateSortie']}</td>";
            echo "<td>{$row['NomLogisticien']}</td>";
            echo "<td>{$row['ContactLogisticien']}</td>";
            echo "</tr>";
        }
    } else {
        if ($query !== '') {
            echo "<tr><td colspan='8'>Aucun résultat trouvé.</td></tr>";
        }
        // No query + no results = just empty table, no message needed.
    }

} catch (PDOException $e) {
    echo "<tr><td colspan='8'>Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>

            </tbody>

        </table>
    </main>
<script src="script.js"></script>
</body>
</html>

