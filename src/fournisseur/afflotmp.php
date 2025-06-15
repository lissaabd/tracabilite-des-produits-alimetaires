<?php
session_start();
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
  <link rel="stylesheet" href="../fabricant/style.css">
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
  
  <main class="content">
    <div class="container">
      <div class="search-bar">
        <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          <input type="text" name="query" placeholder="ID ou nom d'une matiere premiere..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
          <button class="search-icon-bottun"><i class="fas fa-search"></i></button>
        </form>
      </div>

      <h1 class="page-title">Rechercher les lots d'une certaine matiére première</h1>

      <table class="content-table">
            <thead>
                <tr>
                    <th>Id lot matiere premiere</th>
                    <th>nom matiere premiere</th>
                    <th>Date production</th>
                    <th>Date expiration</th>
                    <th>Quantité initiale</th>
                    <th>Quantité restante</th>
                    <th>Etat</th>
                </tr>
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

  // Base query
  $sql = "
    SELECT 
      lmp.NumLotMP AS IDLotMP,
      mp.NomMP AS NomMP,
      lmp.DateProduction,
      lmp.DateExpiration,
      lmp.QteInitiale,
      lmp.QteRestante,
      lmp.Etat,
      u.NomComplet AS NomFournisseur,
      u.Email AS ContactFournisseur
    FROM LotMatierePremiere lmp
    JOIN MatierePremiere mp ON mp.IdMP = lmp.IdMP
    JOIN BonReception br ON br.NumBonRec = lmp.NumBonRec
    JOIN Users u ON u.Iduser = br.IdFournisseur AND u.role = 'fournisseur'
    WHERE u.Iduser = :supplierId
  ";

  $params = [':supplierId' => $_SESSION['iduser']];

  if ($query !== '') {
    $sql .= " AND (mp.IdMP = :query OR mp.NomMP LIKE :likeQuery)";
    $params[':query'] = $query;
    $params[':likeQuery'] = "%$query%";
  }

  $sql .= " ORDER BY lmp.NumLotMP";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if ($results) {
    foreach ($results as $row) {
      echo "<tr>";
      echo "<td>{$row['IDLotMP']}</td>";
      echo "<td>{$row['NomMP']}</td>";
      echo "<td>{$row['DateProduction']}</td>";
      echo "<td>{$row['DateExpiration']}</td>";
      echo "<td>{$row['QteInitiale']}</td>";
      echo "<td>{$row['QteRestante']}</td>";
      echo "<td>{$row['Etat']}</td>";
      echo "</tr>";
    }
  } else {
    if ($query !== '') {
      echo "<tr><td colspan='7'>Aucun résultat trouvé.</td></tr>";
    }
  }

} catch (PDOException $e) {
  echo "<tr><td colspan='7'>Erreur de connexion : " . $e->getMessage() . "</td></tr>";
}
?>

            </tbody>

        </table>
    </div>
  </main>

<script src="../fabricant/script.js"></script>
</body>
</html>

