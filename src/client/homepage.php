<?php
session_start();
require_once '../pdodb.php';

// Check if user is logged in
if (!isset($_SESSION['iduser'])) {
    header("Location: ../login.php");
    exit();
}

// Get alert statistics
$totalAlerts = 0;
$managedAlerts = 0;
$unmanagedAlerts = 0;
$weeklyAlerts = array_fill(0, 7, 0); // Initialize array for weekly alerts

try {
    // Get total alerts
    $sql = "SELECT COUNT(*) as total FROM alerte";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalAlerts = $row['total'];

    // Get managed alerts (assuming 'statut' = 'traite' means managed)
    $sql = "SELECT COUNT(*) as managed FROM alerte WHERE statut = 'en cours'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $managedAlerts = $row['managed'];

    // Get unmanaged alerts
    $sql = "SELECT COUNT(*) as unmanaged FROM alerte WHERE statut = 'en attente'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $unmanagedAlerts = $row['unmanaged'];

    // Get weekly alerts data
    $sql = "SELECT DATE(DateAlerte) as date, COUNT(*) as count 
            FROM alerte 
            WHERE DateAlerte >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(DateAlerte)
            ORDER BY date";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dayIndex = date('w', strtotime($row['date'])); // 0 (Sunday) to 6 (Saturday)
        $weeklyAlerts[$dayIndex] = $row['count'];
    }

    // Get alerts by type
    $sql = "SELECT 
            SUM(CASE WHEN NumLotProd IS NOT NULL THEN 1 ELSE 0 END) as product_alerts,
            SUM(CASE WHEN NumLotMP IS NOT NULL THEN 1 ELSE 0 END) as material_alerts
            FROM alerte";
    $stmt = $pdo->query($sql);
    $alertsByType = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit();
}
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
    <link rel="stylesheet" href="../main.css">

    <style>
        main {
            margin-left: 10%;
            padding: 20px;
        }

        /* Dashboard */
        .dashboard-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #111111;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 254, 246, 0.2);
        }

        .dashboard-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #00fef6;
        }

        .stats-cards {
            display: flex;
            gap: 20px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .card {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            min-width: 150px;
            text-align: center;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #ffffff;
        }

        .card p {
            font-size: 24px;
            color: #00fef6;
        }

        .charts {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 40px;
            justify-content: center;
        }

        .chart-box {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            flex: 1;
            min-width: 300px;
            max-width: 450px;
            text-align: center;
        }

        .chart-box h3 {
            margin-bottom: 20px;
            color: #00fef6;
        }

        canvas {
            background-color: transparent;
        }

        @media (max-width: 768px) {
            .stats-cards {
                flex-direction: column;
            }
            .charts {
                flex-direction: column;
                align-items: center;
            }
            .chart-box {
                background-color: #1a1a1a;
                padding: 20px;
                border-radius: 10px;
                flex: 1;
                max-width: 400px;
                text-align: center;
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

    <button id="toggleSidebar" onclick="toggleSidebar()">☰</button>
    <main class="content">
        <div class="dashboard-container">
            <h2>Dashboard</h2>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="card">
                    <h3>Alertes en cours</h3>
                    <p><?php echo $managedAlerts; ?></p>
                </div>

                <div class="card">
                    <h3>Alertes en attente</h3>
                    <p><?php echo $unmanagedAlerts; ?></p>
                </div>

                <div class="card">
                    <h3>Total des alertes</h3>
                    <p><?php echo $totalAlerts; ?></p>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts">
                <div class="chart-box">
                    <h3>Pourcentage des alertes traitées</h3>
                    <canvas id="doughnutChart" width="400" height="400" style="width: 100%; height: 300px;"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Évolution des alertes pendant la semaine</h3>
                    <canvas id="lineChart" width="400" height="400" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Doughnut Chart
        const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Produits', 'Matières Premières'],
                datasets: [{
                    data: [
                        <?php echo $alertsByType['product_alerts']; ?>,
                        <?php echo $alertsByType['material_alerts']; ?>
                    ],
                    backgroundColor: ['#67f9d2', '#222222'],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });

        // Line Chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
                datasets: [{
                    label: 'Nombre d\'alertes',
                    data: <?php echo json_encode($weeklyAlerts); ?>,
                    borderColor: '#00fef6',
                    backgroundColor: 'rgba(0, 254, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#ffffff'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#ffffff'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
    <script src="../main.js"></script>
</body>
</html>
