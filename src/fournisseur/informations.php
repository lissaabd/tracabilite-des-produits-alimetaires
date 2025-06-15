<?php
session_start();
require_once '../pdodb.php';

// Vérifier si l'utilisateur est connecté et est un fournisseur
if (!isset($_SESSION['iduser']) || $_SESSION['role'] !== 'fournisseur') {
    header('Location: ../login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE IdUser = ?");
$stmt->execute([$_SESSION['iduser']]);
$user = $stmt->fetch();

// Récupérer les statistiques
$stats = [
    'total_alertes' => $pdo->query("SELECT COUNT(*) FROM alerte")->fetchColumn(),
    'alertes_en_cours' => $pdo->query("SELECT COUNT(*) FROM alerte WHERE statut = 'en cours'")->fetchColumn(),
    'alertes_en_attente' => $pdo->query("SELECT COUNT(*) FROM alerte WHERE statut = 'en attente'")->fetchColumn(),
    'alertes_resolues' => $pdo->query("SELECT COUNT(*) FROM alerte WHERE statut = 'résolu'")->fetchColumn()
];
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
    <link rel="stylesheet" href="../main.css">
    <style>
        main {
            margin-left: 10%;
            padding: 20px;
        }

        .info-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #111111;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 254, 246, 0.2);
        }

        .info-container h2 {
            color: #00fef6;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .info-section {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-section h3 {
            color: #00fef6;
            margin-bottom: 15px;
            font-size: 1.4em;
        }

        .info-section p {
            color: #fff;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .info-section ul {
            list-style: none;
            padding-left: 20px;
        }

        .info-section li {
            color: #fff;
            margin-bottom: 10px;
            position: relative;
            padding-left: 20px;
        }

        .info-section li:before {
            content: "•";
            color: #00fef6;
            position: absolute;
            left: 0;
        }

        .user-info {
            text-align: center;
            padding: 15px 0;
            border-bottom: 1px solid #444;
            margin-bottom: 15px;
        }

        .user-info h3 {
            color: #00fef6;
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .user-info p {
            color: #fff;
            font-size: 0.9em;
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
        <div class="info-container">
            <h2>Guide d'Utilisation</h2>

            <div class="info-section">
                <h3>Traçabilité</h3>
                <p>Le module de traçabilité vous permet de suivre l'ensemble du cycle de vie de vos produits :</p>
                <ul>
                    <li><strong>Lots Produits :</strong> Consultez les informations détaillées sur chaque lot de produit fini</li>
                    <li><strong>Lots Matières Premières :</strong> Suivez l'origine et les caractéristiques des matières premières</li>
                    <li><strong>Traçabilité Produit Fini :</strong> Visualisez l'historique complet de transformation</li>
                    <li><strong>Traçabilité Matière Première :</strong> Suivez le parcours des matières premières</li>
                    <li><strong>Qualité :</strong> Accédez aux contrôles qualité des produits et matières premières</li>
                    <li><strong>Localisation :</strong> Géolocalisez vos produits en temps réel</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Gestion des Opérations</h3>
                <p>Le système vous permet de gérer l'ensemble des opérations de production :</p>
                <ul>
                    <li><strong>Opérations de Production :</strong> Enregistrez et suivez chaque étape de production</li>
                    <li><strong>Opérations de Contrôle :</strong> Documentez les contrôles qualité effectués</li>
                    <li><strong>Bons de Livraison :</strong> Gérez les expéditions de vos produits</li>
                    <li><strong>Bons de Réception :</strong> Suivez les réceptions de matières premières</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Gestion des Alertes</h3>
                <p>Le système d'alerte vous permet de signaler et suivre les anomalies :</p>
                <ul>
                    <li><strong>Lancer une Alerte :</strong> Signalez un problème concernant un lot de produit ou une matière première</li>
                    <li><strong>Liste des Alertes :</strong> Consultez et gérez l'ensemble des alertes</li>
                    <li><strong>Statuts :</strong> Suivez l'évolution des alertes (en cours, en attente, résolu)</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Gestion des Données</h3>
                <p>Vous pouvez gérer les informations essentielles du système :</p>
                <ul>
                    <li><strong>Aliments :</strong> Gérez le catalogue des produits</li>
                    <li><strong>Lots :</strong> Créez et suivez les lots de production</li>
                    <li><strong>Utilisateurs :</strong> Gérez les accès au système</li>
                </ul>
            </div>

            <div class="info-section">
                <h3>Bonnes Pratiques</h3>
                <ul>
                    <li>Mettez à jour régulièrement les informations de traçabilité</li>
                    <li>Effectuez les contrôles qualité aux étapes clés</li>
                    <li>Documentez précisément les opérations de production</li>
                    <li>Signalez immédiatement toute anomalie via le système d'alerte</li>
                    <li>Vérifiez régulièrement la localisation des produits</li>
                </ul>
            </div>
        </div>
    </main>

    <script src="../fabricant/script.js"></script>
</body>
</html> 