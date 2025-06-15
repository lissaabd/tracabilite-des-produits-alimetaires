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

  <main>

  <div class="search-bar">
    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
      <input type="text" name="query" placeholder="ID ou nom du produit..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>" required>
     <button class="search-icon-bottun"><i class="fas fa-search"></i></button>
    </form>
  </div>

            <h1>Consulter les controles qualité des lots matieres premieres</h1>

        <table class="content-table">
            <thead>
                <thead>
  <tr>
    <th>Id lot matiere premiere</th>
    <th>nom matiere premiere</th>
    <th>Date contrôle</th>
    <th>Heure contrôle</th>
    <th>Type</th>
    <th>Résultat</th>
    <th>Nom contrôleur</th>
    <th>Contact</th>
  </tr>
</thead>
            </thead>
            <tbody>
               <?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$db = "trace";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    // Base query for all controls
    $sql = "
        SELECT 
            rcmp.NumLotMP AS IDLotMP,
            mp.NomMP AS NomMatierePremiere,
            oc.DateControle,
            oc.HeureControle,
            rcmp.Resultat,
            rcmp.Type,
            u.NomComplet AS NomControleur,
            u.Email AS ContactControleur
        FROM ResultatControleMP rcmp
        JOIN LotMatierePremiere lmp ON lmp.NumLotMP = rcmp.NumLotMP
        JOIN MatierePremiere mp ON mp.IdMP = lmp.IdMP
        JOIN OperationControle oc ON oc.NumOpCtrl = rcmp.NumOpCtrl
        JOIN Users u ON u.Iduser = oc.IdResCtrl AND u.role = 'controleur'
        JOIN BonReception br ON lmp.NumBonRec = br.NumBonRec
        WHERE br.IdFournisseur = :supplierId
    ";

    $params = [':supplierId' => $_SESSION['iduser']];

    if ($query !== '') {
        // Search by either NumLotMP exact or NomMatierePremiere LIKE
        $sql .= " AND (rcmp.NumLotMP = :queryExact OR mp.NomMP LIKE :queryLike)";
        $params = [
            ':supplierId' => $_SESSION['iduser'],
            ':queryExact' => $query,
            ':queryLike' => "%$query%"
        ];
    }

    $sql .= " ORDER BY oc.DateControle DESC, oc.HeureControle DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['IDLotMP']}</td>";
            echo "<td>{$row['NomMatierePremiere']}</td>";
            echo "<td>{$row['DateControle']}</td>";
            echo "<td>{$row['HeureControle']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Resultat']}</td>";
            echo "<td>{$row['NomControleur']}</td>";
            echo "<td>{$row['ContactControleur']}</td>";
            echo "</tr>";
        }
    } else {
        if ($query !== '') {
            echo "<tr><td colspan='8'>Aucun résultat trouvé.</td></tr>";
        }
    }

} catch (PDOException $e) {
    echo "<tr><td colspan='8'>Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
            </tbody>

        </table>
    </main>

<script src="../fabricant/script.js"></script>
</body>
</html>
