-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 11 juin 2025 à 07:16
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `trace`
--

-- --------------------------------------------------------

--
-- Structure de la table `affectationdepot`
--

CREATE TABLE `affectationdepot` (
  `IdResDep` int(11) NOT NULL,
  `NumDep` int(11) NOT NULL,
  `DateDesignation` date DEFAULT NULL,
  `DateFin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `affectationdepot`
--

INSERT INTO `affectationdepot` (`IdResDep`, `NumDep`, `DateDesignation`, `DateFin`) VALUES
(5, 1, '2025-04-01', '0000-00-00'),
(6, 2, '2025-04-01', '0000-00-00');

-- --------------------------------------------------------

--
-- Structure de la table `alerte`
--

CREATE TABLE `alerte` (
  `idAlerte` int(11) NOT NULL,
  `description` text NOT NULL,
  `dateAlerte` datetime NOT NULL DEFAULT current_timestamp(),
  `statut` enum('en cours','résolu','en attente') NOT NULL DEFAULT 'en cours',
  `priorite` enum('basse','moyenne','haute','critique') NOT NULL DEFAULT 'moyenne',
  `NumLotProd` int(11) DEFAULT NULL,
  `NumLotMP` int(11) DEFAULT NULL,
  `IdUser` int(11) NOT NULL,
  `dateResolution` datetime DEFAULT NULL,
  `commentaireResolution` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `alerte`
--

INSERT INTO `alerte` (`idAlerte`, `description`, `dateAlerte`, `statut`, `priorite`, `NumLotProd`, `NumLotMP`, `IdUser`, `dateResolution`, `commentaireResolution`) VALUES
(1, 'Détection de bactéries pathogènes dans le lot de matière première', '2025-06-02 10:00:00', 'en cours', 'critique', NULL, 1, 4, NULL, NULL),
(2, 'Température de stockage hors normes pour le lot de produit fini', '2025-06-02 11:30:00', 'résolu', 'haute', 1, NULL, 5, '2025-06-02 12:00:00', 'Système de réfrigération réparé'),
(3, 'Quantité restante faible pour le lot de matière première', '2025-06-02 14:15:00', 'en attente', 'moyenne', NULL, 2, 6, NULL, NULL),
(5, 'référence X123 non conforme aux tolérances dimensionnelles', '2025-06-07 00:03:26', 'en cours', 'moyenne', 3, NULL, 1, NULL, NULL),
(6, 'référence X123 non conforme aux tolérances dimensionnelles', '2025-06-07 00:05:07', 'en cours', 'moyenne', 2, NULL, 1, NULL, NULL),
(7, 'conditions de stockage non respectées (température &gt; 8°C)', '2025-06-07 10:44:58', 'en cours', 'moyenne', 3, NULL, 1, NULL, NULL),
(8, 'contamination bactérienne', '2025-06-07 14:03:34', 'en cours', 'moyenne', 2, NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `bonlivraison`
--

CREATE TABLE `bonlivraison` (
  `NumBonLiv` int(11) NOT NULL,
  `DateLiv` date DEFAULT NULL,
  `Statut` varchar(50) DEFAULT NULL,
  `IdClient` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `bonlivraison`
--

INSERT INTO `bonlivraison` (`NumBonLiv`, `DateLiv`, `Statut`, `IdClient`) VALUES
(1, '2025-06-01', 'Vendu', 2);

-- --------------------------------------------------------

--
-- Structure de la table `bonreception`
--

CREATE TABLE `bonreception` (
  `NumBonRec` int(11) NOT NULL,
  `DateRec` date DEFAULT NULL,
  `Statut` varchar(50) DEFAULT NULL,
  `IdFournisseur` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `bonreception`
--

INSERT INTO `bonreception` (`NumBonRec`, `DateRec`, `Statut`, `IdFournisseur`) VALUES
(1, '2025-05-01', 'validé', 3),
(2, '2025-05-10', 'validé', 1);

-- --------------------------------------------------------

--
-- Structure de la table `depot`
--

CREATE TABLE `depot` (
  `NumDep` int(11) NOT NULL,
  `Capacite` int(11) DEFAULT NULL,
  `Adresse` varchar(255) DEFAULT NULL,
  `QteOccupe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `depot`
--

INSERT INTO `depot` (`NumDep`, `Capacite`, `Adresse`, `QteOccupe`) VALUES
(1, 8000, 'Zone Industrielle Rouiba, Alger', 5400),
(2, 6000, 'Zone Industrielle Es Sénia, Oran', 4100);

-- --------------------------------------------------------

--
-- Structure de la table `lignelivraison`
--

CREATE TABLE `lignelivraison` (
  `NumBonLiv` int(11) NOT NULL,
  `NumLotProd` int(11) NOT NULL,
  `QteLivree` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `lignelivraison`
--

INSERT INTO `lignelivraison` (`NumBonLiv`, `NumLotProd`, `QteLivree`) VALUES
(1, 1, 10);

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `IdUser` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lotmatierepremiere`
--

CREATE TABLE `lotmatierepremiere` (
  `NumLotMP` int(11) NOT NULL,
  `DateProduction` date DEFAULT NULL,
  `DateExpiration` date DEFAULT NULL,
  `QteInitiale` int(11) DEFAULT NULL,
  `QteRestante` int(11) DEFAULT NULL,
  `Etat` varchar(50) DEFAULT NULL,
  `IdMP` int(11) DEFAULT NULL,
  `NumBonRec` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `lotmatierepremiere`
--

INSERT INTO `lotmatierepremiere` (`NumLotMP`, `DateProduction`, `DateExpiration`, `QteInitiale`, `QteRestante`, `Etat`, `IdMP`, `NumBonRec`) VALUES
(1, '2025-05-01', '2025-06-10', 100, 0, 'En stock', 1, 1),
(2, '2025-05-01', '2025-06-10', 100, 0, 'En stock', 1, 1),
(3, '2025-05-01', '2025-06-02', 300, 0, 'En stock', 3, 1),
(4, '2025-05-01', '2025-06-20', 60, 0, 'En stock', 6, 2),
(5, '2025-05-20', '2025-06-20', 100, 0, 'En stock', 3, 2),
(6, '2025-05-22', '2025-06-15', 80, 0, 'En stock', 4, 2);

-- --------------------------------------------------------

--
-- Structure de la table `lotproduitfini`
--

CREATE TABLE `lotproduitfini` (
  `NumLotProd` int(11) NOT NULL,
  `DateProduction` date DEFAULT NULL,
  `DateExpiration` date DEFAULT NULL,
  `QteInitiale` int(11) DEFAULT NULL,
  `QteRestante` int(11) DEFAULT NULL,
  `Etat` varchar(50) DEFAULT NULL,
  `NumOpProd` int(11) DEFAULT NULL,
  `IdProd` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `lotproduitfini`
--

INSERT INTO `lotproduitfini` (`NumLotProd`, `DateProduction`, `DateExpiration`, `QteInitiale`, `QteRestante`, `Etat`, `NumOpProd`, `IdProd`) VALUES
(1, '2025-05-01', '2025-06-01', 100, 50, 'Vendu', 1, 1),
(2, '2025-05-02', '2025-06-02', 70, 20, 'En quarantaine', 2, 1),
(3, '2025-05-20', '2025-06-20', 100, 100, 'En stock', 3, 5),
(203, '2025-06-08', '2025-07-08', 120, 120, 'en stock', 503, 6),
(204, '2025-06-10', '2025-08-10', 80, 80, 'en stock', 504, 4),
(205, '2025-06-10', '2025-08-10', 100, 100, 'en stock', NULL, 5);

-- --------------------------------------------------------

--
-- Structure de la table `matierepremiere`
--

CREATE TABLE `matierepremiere` (
  `IdMP` int(11) NOT NULL,
  `NomMP` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `matierepremiere`
--

INSERT INTO `matierepremiere` (`IdMP`, `NomMP`, `description`, `prix`) VALUES
(1, 'Farine', 'Farine de blé type 55', 2.00),
(2, 'Sucre', 'Sucre blanc cristallisé', 1.50),
(3, 'Oeufs', 'Œufs de poule frais', 0.30),
(4, 'Beurre', 'Beurre doux pasteurisé', 3.00),
(5, 'Lait', 'Lait entier en bouteille', 1.20),
(6, 'Chocolat', 'Chocolat noir en tablette pour pâtisserie', 4.50);

-- --------------------------------------------------------

--
-- Structure de la table `operationcontrole`
--

CREATE TABLE `operationcontrole` (
  `NumOpCtrl` int(11) NOT NULL,
  `DateControle` date DEFAULT NULL,
  `HeureControle` time DEFAULT NULL,
  `conforme` varchar(255) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `IdResCtrl` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `operationcontrole`
--

INSERT INTO `operationcontrole` (`NumOpCtrl`, `DateControle`, `HeureControle`, `conforme`, `commentaire`, `IdResCtrl`) VALUES
(1, '2025-06-02', '09:00:00', 'oui', 'Température conforme', 4),
(2, '2025-06-02', '10:00:00', 'non', 'Présence de bactéries pathogènes', 4),
(3, '2025-06-02', '11:00:00', 'oui', 'Taux d’humidité acceptable', 4),
(4, '2025-06-02', '12:00:00', 'non', 'pH hors tolérance, risque de détérioration', 4),
(5, '2025-06-08', '14:13:00', 'non', 'test', 1),
(6, '2025-06-08', '14:23:00', 'oui', 'test', 1),
(7, '2025-06-08', '14:23:00', 'oui', 'test', 1),
(8, '2025-06-08', '14:38:00', 'oui', 'ttttttttttt', 1),
(9, '2025-06-08', '15:33:00', 'oui', 'test', 1);

-- --------------------------------------------------------

--
-- Structure de la table `operationlocalisation`
--

CREATE TABLE `operationlocalisation` (
  `NumLotProd` int(11) NOT NULL,
  `NumDep` int(11) NOT NULL,
  `DateEntree` date DEFAULT NULL,
  `HeureEntree` time DEFAULT NULL,
  `DateSortie` date DEFAULT NULL,
  `HeureSortie` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `operationlocalisation`
--

INSERT INTO `operationlocalisation` (`NumLotProd`, `NumDep`, `DateEntree`, `HeureEntree`, `DateSortie`, `HeureSortie`) VALUES
(1, 1, '2025-05-01', '08:30:00', '2025-05-03', '14:45:00'),
(1, 2, '2025-05-03', '19:15:00', '0000-00-00', '00:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `operationproduction`
--

CREATE TABLE `operationproduction` (
  `NumOpProd` int(11) NOT NULL,
  `DateOpProd` date DEFAULT NULL,
  `HeureDebut` time DEFAULT NULL,
  `HeureFin` time DEFAULT NULL,
  `IdResFab` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `operationproduction`
--

INSERT INTO `operationproduction` (`NumOpProd`, `DateOpProd`, `HeureDebut`, `HeureFin`, `IdResFab`) VALUES
(1, '2025-05-01', '08:00:00', '12:00:00', 1),
(2, '2025-05-02', '08:00:00', '12:00:00', 1),
(3, '2025-06-01', '08:00:00', '12:00:00', 1),
(503, '2025-06-08', '00:14:00', '00:14:00', 1),
(504, '2025-06-10', '22:20:00', '22:20:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `produitfini`
--

CREATE TABLE `produitfini` (
  `IdProd` int(11) NOT NULL,
  `nomProd` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `produitfini`
--

INSERT INTO `produitfini` (`IdProd`, `nomProd`, `description`, `prix`) VALUES
(1, 'Gâteau au chocolat', 'Gâteau moelleux avec glaçage cacao', 15.00),
(2, 'Crêpes sucrées', 'Crêpes fines à la vanille', 10.00),
(3, 'Flan pâtissier', 'Flan nature avec croûte', 12.00),
(4, 'Cookies aux pépites', 'Cookies croustillants aux pépites de chocolat', 8.50),
(5, 'Pain de mie', 'Pain de mie moelleux', 6.00),
(6, 'Madeleines', 'Petites madeleines à la vanille', 9.00);

-- --------------------------------------------------------

--
-- Structure de la table `qteutilise`
--

CREATE TABLE `qteutilise` (
  `NumLotMP` int(11) NOT NULL,
  `NumOpProd` int(11) NOT NULL,
  `QteUtilise` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `qteutilise`
--

INSERT INTO `qteutilise` (`NumLotMP`, `NumOpProd`, `QteUtilise`) VALUES
(1, 1, 100),
(2, 1, 50),
(2, 2, 40),
(2, 503, 10),
(3, 1, 155),
(3, 2, 100),
(3, 503, 15),
(4, 1, 35),
(4, 503, 15),
(4, 504, 10),
(5, 504, 45),
(6, 2, 80);

-- --------------------------------------------------------

--
-- Structure de la table `resultatcontrolemp`
--

CREATE TABLE `resultatcontrolemp` (
  `id` int(11) NOT NULL,
  `NumOpCtrl` int(11) NOT NULL,
  `NumLotMP` int(11) NOT NULL,
  `Resultat` varchar(50) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `resultatcontrolemp`
--

INSERT INTO `resultatcontrolemp` (`id`, `NumOpCtrl`, `NumLotMP`, `Resultat`, `Type`) VALUES
(1, 1, 1, '75°C', 'temperature de cuisson'),
(2, 2, 3, 'E. coli détecté', 'microbiologique'),
(3, 9, 2, 'ttttt', 't1'),
(4, 9, 2, 'ttttt', 't2');

-- --------------------------------------------------------

--
-- Structure de la table `resultatcontroleprod`
--

CREATE TABLE `resultatcontroleprod` (
  `id` int(11) NOT NULL,
  `NumOpCtrl` int(11) NOT NULL,
  `NumLotProd` int(11) NOT NULL,
  `Resultat` varchar(50) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `resultatcontroleprod`
--

INSERT INTO `resultatcontroleprod` (`id`, `NumOpCtrl`, `NumLotProd`, `Resultat`, `Type`) VALUES
(1, 3, 1, '12%', 'humidité'),
(2, 4, 2, '7.1', 'pH alimentaire'),
(3, 5, 2, 'Présence de moisissures sur la surface du produit', 'visuel ');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `IdUser` int(11) NOT NULL,
  `NomComplet` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `NumTel` varchar(20) DEFAULT NULL,
  `MotDePasse` varchar(255) NOT NULL,
  `Adresse` varchar(255) DEFAULT NULL,
  `role` enum('fournisseur','fabricant','client','controleur','logisticien') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`IdUser`, `NomComplet`, `Email`, `NumTel`, `MotDePasse`, `Adresse`, `role`, `created_at`) VALUES
(1, 'abdous melissa', 'abdousmelissa@gmail.com', '0759452855', '$2y$10$cS9uXYxg/4AHlXj8mBmnL.VRW6cG8wVZP.34lGutsXxv4sdBf48/K', 'hlm gambetta', 'fabricant', '2025-06-01 21:01:22'),
(2, 'patisserie le carré', 'patisserielecarre@gmail.com', '0566885346', '$2y$10$mu/nHVpv9YtIu3DD/BZHv.mxUiYUejKMiia8OM3/pcQPLYXzhoJb6', 'gambetta oran', 'client', '2025-06-01 21:01:22'),
(3, 'rezzak ikhlasse', 'rezzakikhlasse@gmail.com', '0685669874', '$2y$10$OYuCi2hQK/g/nSdn9q69Q.ve0iGxWMFJZAyANMhlMywpuQd7KR9Yi', 'Es senia oran', 'fournisseur', '2025-06-02 00:01:10'),
(4, 'nadji anfel', 'nadjianfel@gmail.com', '0785348960', '$2y$10$JjiiM9mwAJeVLqv3.Eq7G.JcW/FKqmsUoV3pt/mmuvc', 'oran gambetta', 'controleur', '2025-06-02 03:49:21'),
(5, 'abdous fella', 'abdousfella@gmail.com', '0587443695', '$2y$10$o7Lq9bdZBYygWe/jKNNd.OpYBVDUb/95UhYbpCgMALl', 'hlm gambetta', 'logisticien', '2025-06-03 05:43:16'),
(6, 'agoudjil hibba', 'agoudjilhibba@gmail.com', '0587446932', '$2y$10$/1YqFTHC28vfbObUwdnUJeqe6aqWR/V6jVJQy.shdtL', 'essedikia oran', 'logisticien', '2025-06-03 06:01:39');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `affectationdepot`
--
ALTER TABLE `affectationdepot`
  ADD PRIMARY KEY (`IdResDep`,`NumDep`),
  ADD KEY `NumDep` (`NumDep`);

--
-- Index pour la table `alerte`
--
ALTER TABLE `alerte`
  ADD PRIMARY KEY (`idAlerte`),
  ADD KEY `NumLotProd` (`NumLotProd`),
  ADD KEY `NumLotMP` (`NumLotMP`),
  ADD KEY `IdUser` (`IdUser`);

--
-- Index pour la table `bonlivraison`
--
ALTER TABLE `bonlivraison`
  ADD PRIMARY KEY (`NumBonLiv`),
  ADD KEY `IdClient` (`IdClient`);

--
-- Index pour la table `bonreception`
--
ALTER TABLE `bonreception`
  ADD PRIMARY KEY (`NumBonRec`),
  ADD KEY `IdFournisseur` (`IdFournisseur`);

--
-- Index pour la table `depot`
--
ALTER TABLE `depot`
  ADD PRIMARY KEY (`NumDep`);

--
-- Index pour la table `lignelivraison`
--
ALTER TABLE `lignelivraison`
  ADD PRIMARY KEY (`NumBonLiv`,`NumLotProd`),
  ADD KEY `NumLotProd` (`NumLotProd`);

--
-- Index pour la table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IdUser` (`IdUser`);

--
-- Index pour la table `lotmatierepremiere`
--
ALTER TABLE `lotmatierepremiere`
  ADD PRIMARY KEY (`NumLotMP`),
  ADD KEY `IdMP` (`IdMP`),
  ADD KEY `fk_lot_bonrec` (`NumBonRec`);

--
-- Index pour la table `lotproduitfini`
--
ALTER TABLE `lotproduitfini`
  ADD PRIMARY KEY (`NumLotProd`),
  ADD KEY `NumOpProd` (`NumOpProd`),
  ADD KEY `IdProd` (`IdProd`);

--
-- Index pour la table `matierepremiere`
--
ALTER TABLE `matierepremiere`
  ADD PRIMARY KEY (`IdMP`);

--
-- Index pour la table `operationcontrole`
--
ALTER TABLE `operationcontrole`
  ADD PRIMARY KEY (`NumOpCtrl`),
  ADD KEY `IdResCtrl` (`IdResCtrl`);

--
-- Index pour la table `operationlocalisation`
--
ALTER TABLE `operationlocalisation`
  ADD PRIMARY KEY (`NumLotProd`,`NumDep`),
  ADD KEY `NumDep` (`NumDep`);

--
-- Index pour la table `operationproduction`
--
ALTER TABLE `operationproduction`
  ADD PRIMARY KEY (`NumOpProd`),
  ADD KEY `IdResFab` (`IdResFab`);

--
-- Index pour la table `produitfini`
--
ALTER TABLE `produitfini`
  ADD PRIMARY KEY (`IdProd`);

--
-- Index pour la table `qteutilise`
--
ALTER TABLE `qteutilise`
  ADD PRIMARY KEY (`NumLotMP`,`NumOpProd`),
  ADD KEY `NumOpProd` (`NumOpProd`);

--
-- Index pour la table `resultatcontrolemp`
--
ALTER TABLE `resultatcontrolemp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `NumLotMP` (`NumLotMP`),
  ADD KEY `resultatcontrolemp_ibfk_1` (`NumOpCtrl`);

--
-- Index pour la table `resultatcontroleprod`
--
ALTER TABLE `resultatcontroleprod`
  ADD PRIMARY KEY (`id`),
  ADD KEY `NumLotProd` (`NumLotProd`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`IdUser`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `alerte`
--
ALTER TABLE `alerte`
  MODIFY `idAlerte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `bonlivraison`
--
ALTER TABLE `bonlivraison`
  MODIFY `NumBonLiv` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `bonreception`
--
ALTER TABLE `bonreception`
  MODIFY `NumBonRec` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `depot`
--
ALTER TABLE `depot`
  MODIFY `NumDep` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lotmatierepremiere`
--
ALTER TABLE `lotmatierepremiere`
  MODIFY `NumLotMP` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

--
-- AUTO_INCREMENT pour la table `lotproduitfini`
--
ALTER TABLE `lotproduitfini`
  MODIFY `NumLotProd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT pour la table `matierepremiere`
--
ALTER TABLE `matierepremiere`
  MODIFY `IdMP` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `operationcontrole`
--
ALTER TABLE `operationcontrole`
  MODIFY `NumOpCtrl` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `operationproduction`
--
ALTER TABLE `operationproduction`
  MODIFY `NumOpProd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT pour la table `produitfini`
--
ALTER TABLE `produitfini`
  MODIFY `IdProd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `resultatcontrolemp`
--
ALTER TABLE `resultatcontrolemp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `resultatcontroleprod`
--
ALTER TABLE `resultatcontroleprod`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `IdUser` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affectationdepot`
--
ALTER TABLE `affectationdepot`
  ADD CONSTRAINT `affectationdepot_ibfk_1` FOREIGN KEY (`IdResDep`) REFERENCES `users` (`IdUser`),
  ADD CONSTRAINT `affectationdepot_ibfk_2` FOREIGN KEY (`NumDep`) REFERENCES `depot` (`NumDep`);

--
-- Contraintes pour la table `alerte`
--
ALTER TABLE `alerte`
  ADD CONSTRAINT `alerte_ibfk_1` FOREIGN KEY (`NumLotProd`) REFERENCES `lotproduitfini` (`NumLotProd`) ON DELETE SET NULL,
  ADD CONSTRAINT `alerte_ibfk_2` FOREIGN KEY (`NumLotMP`) REFERENCES `lotmatierepremiere` (`NumLotMP`) ON DELETE SET NULL,
  ADD CONSTRAINT `alerte_ibfk_3` FOREIGN KEY (`IdUser`) REFERENCES `users` (`IdUser`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bonlivraison`
--
ALTER TABLE `bonlivraison`
  ADD CONSTRAINT `bonlivraison_ibfk_1` FOREIGN KEY (`IdClient`) REFERENCES `users` (`IdUser`);

--
-- Contraintes pour la table `bonreception`
--
ALTER TABLE `bonreception`
  ADD CONSTRAINT `bonreception_ibfk_1` FOREIGN KEY (`IdFournisseur`) REFERENCES `users` (`IdUser`);

--
-- Contraintes pour la table `lignelivraison`
--
ALTER TABLE `lignelivraison`
  ADD CONSTRAINT `lignelivraison_ibfk_1` FOREIGN KEY (`NumBonLiv`) REFERENCES `bonlivraison` (`NumBonLiv`),
  ADD CONSTRAINT `lignelivraison_ibfk_2` FOREIGN KEY (`NumLotProd`) REFERENCES `lotproduitfini` (`NumLotProd`);

--
-- Contraintes pour la table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`IdUser`) REFERENCES `users` (`IdUser`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lotmatierepremiere`
--
ALTER TABLE `lotmatierepremiere`
  ADD CONSTRAINT `fk_lot_bonrec` FOREIGN KEY (`NumBonRec`) REFERENCES `bonreception` (`NumBonRec`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `lotmatierepremiere_ibfk_1` FOREIGN KEY (`IdMP`) REFERENCES `matierepremiere` (`IdMP`);

--
-- Contraintes pour la table `lotproduitfini`
--
ALTER TABLE `lotproduitfini`
  ADD CONSTRAINT `lotproduitfini_ibfk_1` FOREIGN KEY (`NumOpProd`) REFERENCES `operationproduction` (`NumOpProd`),
  ADD CONSTRAINT `lotproduitfini_ibfk_2` FOREIGN KEY (`IdProd`) REFERENCES `produitfini` (`IdProd`);

--
-- Contraintes pour la table `operationcontrole`
--
ALTER TABLE `operationcontrole`
  ADD CONSTRAINT `operationcontrole_ibfk_1` FOREIGN KEY (`IdResCtrl`) REFERENCES `users` (`IdUser`);

--
-- Contraintes pour la table `operationlocalisation`
--
ALTER TABLE `operationlocalisation`
  ADD CONSTRAINT `operationlocalisation_ibfk_1` FOREIGN KEY (`NumLotProd`) REFERENCES `lotproduitfini` (`NumLotProd`),
  ADD CONSTRAINT `operationlocalisation_ibfk_2` FOREIGN KEY (`NumDep`) REFERENCES `depot` (`NumDep`);

--
-- Contraintes pour la table `operationproduction`
--
ALTER TABLE `operationproduction`
  ADD CONSTRAINT `operationproduction_ibfk_1` FOREIGN KEY (`IdResFab`) REFERENCES `users` (`IdUser`);

--
-- Contraintes pour la table `qteutilise`
--
ALTER TABLE `qteutilise`
  ADD CONSTRAINT `qteutilise_ibfk_1` FOREIGN KEY (`NumLotMP`) REFERENCES `lotmatierepremiere` (`NumLotMP`),
  ADD CONSTRAINT `qteutilise_ibfk_2` FOREIGN KEY (`NumOpProd`) REFERENCES `operationproduction` (`NumOpProd`);

--
-- Contraintes pour la table `resultatcontrolemp`
--
ALTER TABLE `resultatcontrolemp`
  ADD CONSTRAINT `resultatcontrolemp_ibfk_1` FOREIGN KEY (`NumOpCtrl`) REFERENCES `operationcontrole` (`NumOpCtrl`),
  ADD CONSTRAINT `resultatcontrolemp_ibfk_2` FOREIGN KEY (`NumLotMP`) REFERENCES `lotmatierepremiere` (`NumLotMP`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
