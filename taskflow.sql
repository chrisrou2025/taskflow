-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : ven. 12 sep. 2025 à 14:25
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `taskflow`
--

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250901124650', '2025-09-01 12:47:07', 430),
('DoctrineMigrations\\Version20250904075909', '2025-09-04 07:59:22', 29),
('DoctrineMigrations\\Version20250911131010', '2025-09-12 14:22:19', 10),
('DoctrineMigrations\\Version20250912132542', '2025-09-12 14:22:19', 58),
('DoctrineMigrations\\Version20250912140002', '2025-09-12 14:22:20', 37),
('DoctrineMigrations\\Version20250912140142', '2025-09-12 14:22:20', 5),
('DoctrineMigrations\\Version20250912140355', '2025-09-12 14:22:20', 5);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint NOT NULL,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `project`
--

CREATE TABLE `project` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `project`
--

INSERT INTO `project` (`id`, `owner_id`, `title`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Projet TaskFlow', 'Objectifs pédagogiques\r\n• Développer une application Symfony multi-utilisateurs\r\n• Gérer des relations entre entités (projets ↔ tâches ↔ utilisateurs)\r\n• Mettre en place un CRUD complet et une interface intuitive\r\n• Découvrir des notions d’organisation et de suivi (statuts, priorités', '2025-09-02 12:39:25', NULL),
(2, 1, 'Devenir Dev junior', 'Trouver une entreprise qui veut bien essayer un vieux', '2025-09-02 13:03:12', '2025-09-09 15:06:29');

-- --------------------------------------------------------

--
-- Structure de la table `task`
--

CREATE TABLE `task` (
  `id` int NOT NULL,
  `project_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `assignee_id` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `completed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `task`
--

INSERT INTO `task` (`id`, `project_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `assignee_id`, `updated_at`, `completed_at`) VALUES
(1, 1, 'Faire le Figma du projet', NULL, 'in_progress', 'high', '2025-09-30 10:24:00', '2025-09-02 12:41:55', NULL, NULL, NULL),
(2, 1, 'Faire le Readme', 'Présentation du projet pour Github', 'todo', 'medium', '2025-09-30 10:25:00', '2025-09-02 12:43:38', NULL, NULL, NULL),
(3, 2, 'Mettre à jour le CV', NULL, 'todo', 'medium', '2025-10-31 12:44:00', '2025-09-02 13:04:56', NULL, NULL, NULL),
(4, 2, 'Postuler', NULL, 'todo', 'medium', '2025-11-03 12:42:00', '2025-09-02 13:05:21', NULL, NULL, NULL),
(5, 1, 'Faire le code avec Symfony', NULL, 'in_progress', 'high', '2025-09-30 10:26:00', '2025-09-02 13:07:15', NULL, NULL, NULL),
(18, 1, 'Faire le MCD et le MLD', NULL, 'completed', 'medium', '2025-09-05 17:02:00', '2025-09-09 15:02:50', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_verified` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `first_name`, `last_name`, `created_at`, `reset_token`, `reset_token_expires_at`, `is_verified`) VALUES
(1, 'ch.roupioz@laposte.net', '[\"ROLE_USER\", \"ROLE_ADMIN\"]', '$2y$13$fWmbX7naUCebxkiTes3mkuGD1EKsadr5IZlET2cG6afJOIGuIhAeC', 'christian', 'ROUPIOZ', '2025-09-02 08:26:11', NULL, NULL, 1),
(3, 'jd@gmail.com', '[\"ROLE_USER\"]', '$2y$13$8x6VmGPYUls9JIZZtbaweONlbRuk/8fG5Oo18Z6MiD76ZuNN.f4Ui', 'John', 'Doe', '2025-09-09 15:16:22', NULL, NULL, 1),
(4, 'bl@gmail.com', '[\"ROLE_USER\"]', '$2y$13$FZ0f4on6kDGvgVvJJwk8Duh5QO8N3AflyH.Kj1MkkJbT59XhpXjP2', 'Bob', 'Leponge', '2025-09-09 15:18:01', NULL, NULL, 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  ADD KEY `IDX_75EA56E016BA31DB` (`delivered_at`);

--
-- Index pour la table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_2FB3D0EE7E3C61F9` (`owner_id`);

--
-- Index pour la table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_527EDB25166D1F9C` (`project_id`),
  ADD KEY `IDX_527EDB2559EC7D60` (`assignee_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `project`
--
ALTER TABLE `project`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `task`
--
ALTER TABLE `task`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `FK_2FB3D0EE7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `FK_527EDB25166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  ADD CONSTRAINT `FK_527EDB2559EC7D60` FOREIGN KEY (`assignee_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
