-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 29 sep. 2025 à 14:05
-- Version du serveur : 9.1.0
-- Version de PHP : 8.2.26

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
-- Structure de la table `collaboration_request`
--

DROP TABLE IF EXISTS `collaboration_request`;
CREATE TABLE IF NOT EXISTS `collaboration_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `invited_user_id` int NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `responded_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_invitation` (`project_id`,`invited_user_id`),
  KEY `IDX_6CCAF4EE166D1F9C` (`project_id`),
  KEY `IDX_6CCAF4EEF624B39D` (`sender_id`),
  KEY `IDX_6CCAF4EEC58DAD6E` (`invited_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `collaboration_request`
--

INSERT INTO `collaboration_request` (`id`, `project_id`, `sender_id`, `invited_user_id`, `status`, `message`, `response`, `created_at`, `updated_at`, `responded_at`) VALUES
(19, 12, 1, 7, 'accepted', NULL, 'Demande acceptée.', '2025-09-29 13:03:04', '2025-09-29 13:05:19', '2025-09-29 13:05:19');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250901124650', '2025-09-01 12:47:07', 430),
('DoctrineMigrations\\Version20250904075909', '2025-09-04 07:59:22', 29),
('DoctrineMigrations\\Version20250911131010', '2025-09-12 14:22:19', 10),
('DoctrineMigrations\\Version20250912160226', '2025-09-12 16:18:12', 76),
('DoctrineMigrations\\Version20250913093344', '2025-09-13 09:34:18', 59),
('DoctrineMigrations\\Version20250915143452', '2025-09-15 14:35:17', 520),
('DoctrineMigrations\\Version20250923095859', '2025-09-23 09:59:15', 46);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `messenger_messages`
--

INSERT INTO `messenger_messages` (`id`, `body`, `headers`, `queue_name`, `created_at`, `available_at`, `delivered_at`) VALUES
(5, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:39:\\\"Symfony\\\\Bridge\\\\Twig\\\\Mime\\\\TemplatedEmail\\\":5:{i:0;s:33:\\\"auth/confirmation_email.html.twig\\\";i:1;N;i:2;a:3:{s:9:\\\"signedUrl\\\";s:163:\\\"https://127.0.0.1:8000/verify/email?expires=1758829211&signature=0zCbEVr46lLYE73SQFGsk2c0FjyQy3hmsXW2eek8mAY&token=YRr4bamSBCVVS%2F5tYDHSt27AvPyhoQHEj2MwKHNFi7U%3D\\\";s:19:\\\"expiresAtMessageKey\\\";s:26:\\\"%count% hour|%count% hours\\\";s:20:\\\"expiresAtMessageData\\\";a:1:{s:7:\\\"%count%\\\";i:1;}}i:3;a:6:{i:0;N;i:1;N;i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:20:\\\"noreply@taskflow.app\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:8:\\\"TaskFlow\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:17:\\\"chris@laposte.net\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:45:\\\"Vérification de votre adresse email TaskFlow\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}i:4;N;}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2025-09-25 18:40:11', '2025-09-25 18:40:11', NULL),
(6, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:39:\\\"Symfony\\\\Bridge\\\\Twig\\\\Mime\\\\TemplatedEmail\\\":5:{i:0;s:33:\\\"auth/confirmation_email.html.twig\\\";i:1;N;i:2;a:3:{s:9:\\\"signedUrl\\\";s:165:\\\"https://127.0.0.1:8000/verify/email?expires=1758918836&signature=UeitOHWKfN8qWYk3ww9yhnz-mEltobMbFuLiewYyzsQ&token=jtxm8%2BLfPkmi%2FRlWlv2zsreNRenzJvXphXm3Ubzqvac%3D\\\";s:19:\\\"expiresAtMessageKey\\\";s:26:\\\"%count% hour|%count% hours\\\";s:20:\\\"expiresAtMessageData\\\";a:1:{s:7:\\\"%count%\\\";i:1;}}i:3;a:6:{i:0;N;i:1;N;i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:20:\\\"noreply@taskflow.app\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:8:\\\"TaskFlow\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:16:\\\"dede@laposte.net\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:45:\\\"Vérification de votre adresse email TaskFlow\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}i:4;N;}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2025-09-26 19:33:56', '2025-09-26 19:33:56', NULL),
(7, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:39:\\\"Symfony\\\\Bridge\\\\Twig\\\\Mime\\\\TemplatedEmail\\\":5:{i:0;s:33:\\\"auth/confirmation_email.html.twig\\\";i:1;N;i:2;a:3:{s:9:\\\"signedUrl\\\";s:163:\\\"https://127.0.0.1:8000/verify/email?expires=1759083127&signature=b7YOBVtL7nV_ffJ6M83zEtc2WyhaIBwav-kfHtjWEgc&token=YpXQjvHAWJ%2FBfM36qokqiSwYjp3IpHqVK56sQumcOU0%3D\\\";s:19:\\\"expiresAtMessageKey\\\";s:26:\\\"%count% hour|%count% hours\\\";s:20:\\\"expiresAtMessageData\\\";a:1:{s:7:\\\"%count%\\\";i:1;}}i:3;a:6:{i:0;N;i:1;N;i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:20:\\\"noreply@taskflow.app\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:8:\\\"TaskFlow\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:17:\\\"chris@laposte.net\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:45:\\\"Vérification de votre adresse email TaskFlow\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}i:4;N;}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2025-09-28 17:12:07', '2025-09-28 17:12:07', NULL),
(8, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:39:\\\"Symfony\\\\Bridge\\\\Twig\\\\Mime\\\\TemplatedEmail\\\":5:{i:0;s:33:\\\"auth/confirmation_email.html.twig\\\";i:1;N;i:2;a:3:{s:9:\\\"signedUrl\\\";s:167:\\\"https://127.0.0.1:8000/verify/email?expires=1759085531&signature=_VL_T-KCAh1gnaaBzF6PdN1l7QoxaJnFsux9Vp81-nA&token=aMMncjmHxPpIPn%2BPl%2BP%2Bp3qZZilFJMsU4bjCKsAYJoA%3D\\\";s:19:\\\"expiresAtMessageKey\\\";s:26:\\\"%count% hour|%count% hours\\\";s:20:\\\"expiresAtMessageData\\\";a:1:{s:7:\\\"%count%\\\";i:1;}}i:3;a:6:{i:0;N;i:1;N;i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:20:\\\"noreply@taskflow.app\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:8:\\\"TaskFlow\\\";}}}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:17:\\\"chris@laposte.net\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:45:\\\"Vérification de votre adresse email TaskFlow\\\";}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}i:4;N;}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2025-09-28 17:52:11', '2025-09-28 17:52:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

DROP TABLE IF EXISTS `notification`;
CREATE TABLE IF NOT EXISTS `notification` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipient_id` int NOT NULL,
  `sender_id` int DEFAULT NULL,
  `project_id` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `read_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `action_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CAE92F8F78` (`recipient_id`),
  KEY `IDX_BF5476CAF624B39D` (`sender_id`),
  KEY `IDX_BF5476CA166D1F9C` (`project_id`),
  KEY `IDX_BF5476CA8DB60186` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`id`, `recipient_id`, `sender_id`, `project_id`, `task_id`, `type`, `title`, `message`, `status`, `created_at`, `read_at`, `action_url`, `data`) VALUES
(34, 7, 1, 12, NULL, 'collaboration_request', 'Nouvelle demande de collaboration', 'christian ROUPIOZ vous invite à collaborer sur le projet \"Formation équipe développement\"', 'read', '2025-09-29 13:03:05', '2025-09-29 13:05:01', '/collaboration/requests', '{\"project_id\": 12, \"collaboration_request_id\": 19}'),
(35, 1, 7, 12, NULL, 'collaboration_accepted', 'Collaboration acceptée', 'GPT IA a accepté votre invitation à collaborer sur le projet \"Formation équipe développement\"', 'unread', '2025-09-29 13:05:19', NULL, '/projects/12', '{\"response\": \"Demande acceptée.\", \"collaboration_request_id\": 19}');

-- --------------------------------------------------------

--
-- Structure de la table `project`
--

DROP TABLE IF EXISTS `project`;
CREATE TABLE IF NOT EXISTS `project` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_2FB3D0EE7E3C61F9` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `project`
--

INSERT INTO `project` (`id`, `owner_id`, `title`, `description`, `created_at`, `updated_at`) VALUES
(10, 1, 'Refonte site e-commerce', 'Migration du site e-commerce vers une nouvelle plateforme avec amélioration de l\'expérience utilisateur et optimisation des performances. Objectif : augmenter le taux de conversion de 20%.', '2025-09-15 09:30:00', '2025-09-20 14:15:00'),
(11, 1, 'Organisation événement tech', 'Organisation d\'une conférence sur les nouvelles technologies pour 200 participants. Budget : 15 000 euros. Date prévue : 15 novembre 2025.', '2025-09-18 11:20:00', '2025-09-22 16:45:00'),
(12, 1, 'Formation équipe développement', 'Mise en place d\'un programme de formation continue pour l\'équipe de développement. Focus sur les nouvelles pratiques DevOps et la sécurité applicative.', '2025-09-20 08:00:00', '2025-09-29 13:05:19'),
(13, 3, 'Développement application mobile fitness', 'Création d\'une application mobile de suivi d\'activité physique avec fonctionnalités de coaching personnalisé et suivi nutritionnel. Technologies : React Native, Node.js, MongoDB.', '2025-09-10 10:00:00', '2025-09-22 09:15:00'),
(14, 3, 'Optimisation base de données clients', 'Refonte complète de la structure de la base de données clients pour améliorer les performances et faciliter l\'analyse des données. Migration progressive sans interruption de service.', '2025-09-14 14:30:00', NULL),
(15, 4, 'Mise en place système CI/CD', 'Automatisation complète du processus de déploiement avec pipeline CI/CD. Intégration GitLab CI, tests automatisés, déploiement sur environnements staging et production.', '2025-09-12 09:00:00', '2025-09-24 15:30:00'),
(16, 4, 'Création plateforme e-learning interne', 'Développement d\'une plateforme de formation en ligne pour les employés. Gestion des cours, suivi de progression, certificats et gamification.', '2025-09-16 13:00:00', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `project_user`
--

DROP TABLE IF EXISTS `project_user`;
CREATE TABLE IF NOT EXISTS `project_user` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`project_id`,`user_id`),
  KEY `IDX_B4021E51166D1F9C` (`project_id`),
  KEY `IDX_B4021E51A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `project_user`
--

INSERT INTO `project_user` (`project_id`, `user_id`) VALUES
(10, 3),
(10, 4),
(10, 5),
(10, 6),
(10, 7),
(11, 3),
(11, 4),
(11, 5),
(11, 6),
(11, 7),
(12, 3),
(12, 4),
(12, 5),
(12, 6),
(12, 7),
(13, 4),
(13, 5),
(13, 6),
(13, 7),
(14, 4),
(14, 5),
(14, 6),
(15, 3),
(15, 5),
(15, 6),
(15, 7),
(16, 3),
(16, 5),
(16, 6),
(16, 7);

-- --------------------------------------------------------

--
-- Structure de la table `task`
--

DROP TABLE IF EXISTS `task`;
CREATE TABLE IF NOT EXISTS `task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `assignee_id` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `completed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_527EDB25166D1F9C` (`project_id`),
  KEY `IDX_527EDB2559EC7D60` (`assignee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `task`
--

INSERT INTO `task` (`id`, `project_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `assignee_id`, `updated_at`, `completed_at`) VALUES
(27, 10, 'Audit de l\'existant', 'Analyser l\'architecture actuelle du site, identifier les points faibles et les opportunités d\'amélioration. Documenter les fonctionnalités critiques à conserver.', 'completed', 'high', '2025-09-25 17:00:00', '2025-09-15 09:35:00', 3, '2025-09-24 16:30:00', '2025-09-24 16:30:00'),
(28, 10, 'Choix de la nouvelle plateforme', 'Comparer les solutions disponibles (Shopify Plus, Magento, WooCommerce). Présenter un rapport avec recommandations.', 'completed', 'high', '2025-09-28 17:00:00', '2025-09-15 09:40:00', 5, '2025-09-27 11:20:00', '2025-09-27 11:20:00'),
(29, 10, 'Design des maquettes UI/UX', 'Créer les maquettes pour les pages principales : accueil, catégories, fiche produit, panier, tunnel de commande. Version desktop et mobile.', 'in_progress', 'high', '2025-10-05 17:00:00', '2025-09-15 09:45:00', 4, '2025-09-28 10:15:00', NULL),
(30, 10, 'Développement frontend', 'Intégration des maquettes et développement des composants réutilisables. Assurer la compatibilité cross-browser.', 'todo', 'high', '2025-10-20 17:00:00', '2025-09-15 09:50:00', 6, NULL, NULL),
(31, 10, 'Tests utilisateurs et ajustements', 'Organiser des sessions de tests avec des utilisateurs réels. Recueillir les retours et effectuer les ajustements nécessaires avant le lancement.', 'todo', 'medium', '2025-10-30 17:00:00', '2025-09-15 09:55:00', 1, '2025-09-29 12:53:31', NULL),
(32, 11, 'Réservation de la salle', 'Trouver et réserver une salle pouvant accueillir 200 personnes avec équipement audiovisuel complet. Budget max : 3000 euros.', 'completed', 'high', '2025-09-30 12:00:00', '2025-09-18 11:25:00', 3, '2025-09-28 15:45:00', '2025-09-28 15:45:00'),
(33, 11, 'Sélection des intervenants', 'Contacter et confirmer 5 intervenants experts dans leurs domaines. Préparer les contrats et gérer la logistique (déplacements, hébergement).', 'in_progress', 'high', '2025-10-10 17:00:00', '2025-09-18 11:30:00', 1, '2025-09-29 12:54:20', NULL),
(34, 11, 'Campagne de communication', 'Créer le site web de l\'événement, lancer la campagne sur les réseaux sociaux et contacter la presse spécialisée.', 'in_progress', 'medium', '2025-10-15 17:00:00', '2025-09-18 11:35:00', 4, '2025-09-26 14:20:00', NULL),
(35, 11, 'Gestion des inscriptions', 'Mettre en place le système de billetterie en ligne, gérer les inscriptions et envoyer les confirmations aux participants.', 'todo', 'medium', '2025-11-01 17:00:00', '2025-09-18 11:40:00', 6, NULL, NULL),
(36, 11, 'Organisation logistique jour J', 'Coordonner l\'accueil, la restauration (pause café, déjeuner), l\'installation technique et le déroulement de l\'événement.', 'todo', 'high', '2025-11-14 17:00:00', '2025-09-18 11:45:00', 7, NULL, NULL),
(37, 12, 'Analyse des besoins formation', 'Interroger l\'équipe pour identifier les compétences à développer en priorité. Établir un plan de formation adapté aux besoins et au budget.', 'in_progress', 'high', '2025-10-05 17:00:00', '2025-09-20 08:10:00', 1, '2025-09-27 10:00:00', NULL),
(38, 12, 'Sélection des organismes formateurs', 'Rechercher et comparer les organismes de formation spécialisés. Demander des devis et vérifier les certifications.', 'todo', 'high', '2025-10-12 17:00:00', '2025-09-20 08:15:00', 3, NULL, NULL),
(39, 12, 'Formation DevOps niveau 1', 'Session de formation de 3 jours sur les pratiques DevOps : CI/CD, conteneurisation (Docker), orchestration (Kubernetes).', 'todo', 'medium', '2025-10-25 17:00:00', '2025-09-20 08:20:00', 5, NULL, NULL),
(40, 12, 'Formation sécurité applicative', 'Atelier de 2 jours sur la sécurité : OWASP Top 10, tests de pénétration, bonnes pratiques de code sécurisé.', 'todo', 'medium', '2025-11-08 17:00:00', '2025-09-20 08:25:00', 4, NULL, NULL),
(41, 12, 'Évaluation et suivi post-formation', 'Mettre en place un système d\'évaluation des acquis et organiser des points de suivi réguliers pour mesurer l\'impact des formations.', 'todo', 'low', '2025-11-30 17:00:00', '2025-09-20 08:30:00', 6, NULL, NULL),
(42, 13, 'Rédaction cahier des charges', 'Définir précisément les fonctionnalités de l\'application, les personas utilisateurs et les parcours utilisateur. Inclure les contraintes techniques et budgétaires.', 'completed', 'high', '2025-09-20 17:00:00', '2025-09-10 10:10:00', 3, '2025-09-19 16:45:00', '2025-09-19 16:45:00'),
(43, 13, 'Création wireframes et prototypes', 'Designer les écrans principaux de l\'application avec un outil de prototypage (Figma). Valider l\'ergonomie avec des utilisateurs tests.', 'completed', 'high', '2025-09-28 17:00:00', '2025-09-10 10:15:00', 4, '2025-09-27 14:20:00', '2025-09-27 14:20:00'),
(44, 13, 'Développement fonctionnalités core', 'Développer les fonctionnalités principales : authentification, suivi activités, tableau de bord, synchronisation cloud.', 'in_progress', 'high', '2025-10-15 17:00:00', '2025-09-10 10:20:00', 6, '2025-09-28 11:30:00', NULL),
(45, 13, 'Intégration API nutrition', 'Intégrer une API tierce pour récupérer les informations nutritionnelles des aliments. Développer l\'interface de saisie des repas.', 'todo', 'medium', '2025-10-25 17:00:00', '2025-09-10 10:25:00', 5, NULL, NULL),
(46, 13, 'Tests et déploiement stores', 'Effectuer les tests complets (unitaires, intégration, utilisateurs). Préparer et soumettre l\'application sur App Store et Google Play.', 'todo', 'medium', '2025-11-10 17:00:00', '2025-09-10 10:30:00', 7, NULL, NULL),
(47, 14, 'Audit structure actuelle BDD', 'Analyser la structure existante, identifier les tables mal indexées, les requêtes lentes et les opportunités de normalisation.', 'in_progress', 'high', '2025-10-01 17:00:00', '2025-09-14 14:35:00', 3, '2025-09-26 10:00:00', NULL),
(48, 14, 'Conception nouvelle architecture', 'Proposer une nouvelle architecture optimisée avec schéma relationnel amélioré, stratégie d\'indexation et plan de partitionnement.', 'todo', 'high', '2025-10-08 17:00:00', '2025-09-14 14:40:00', 6, NULL, NULL),
(49, 14, 'Script de migration données', 'Développer les scripts de migration sécurisés avec rollback possible. Tester sur environnement de staging avec données réelles anonymisées.', 'todo', 'high', '2025-10-18 17:00:00', '2025-09-14 14:45:00', 5, NULL, NULL),
(50, 14, 'Migration environnement production', 'Planifier et exécuter la migration en production pendant une fenêtre de maintenance. Monitoring intensif post-migration.', 'todo', 'high', '2025-10-28 17:00:00', '2025-09-14 14:50:00', 3, NULL, NULL),
(51, 14, 'Documentation et formation équipe', 'Rédiger la documentation technique complète de la nouvelle architecture et former l\'équipe aux nouvelles pratiques d\'accès aux données.', 'todo', 'low', '2025-11-05 17:00:00', '2025-09-14 14:55:00', 4, NULL, NULL),
(52, 15, 'Configuration GitLab Runner', 'Installer et configurer les runners GitLab sur les serveurs dédiés. Définir les tags et les ressources allouées.', 'completed', 'high', '2025-09-20 17:00:00', '2025-09-12 09:10:00', 4, '2025-09-19 11:30:00', '2025-09-19 11:30:00'),
(53, 15, 'Création pipeline de tests', 'Développer le pipeline de tests automatisés : tests unitaires, tests d\'intégration, analyse de code (SonarQube), tests de sécurité.', 'completed', 'high', '2025-09-27 17:00:00', '2025-09-12 09:15:00', 6, '2025-09-26 16:00:00', '2025-09-26 16:00:00'),
(54, 15, 'Configuration déploiement staging', 'Automatiser le déploiement sur l\'environnement de staging avec validation manuelle. Inclure les tests de smoke et notifications Slack.', 'in_progress', 'high', '2025-10-05 17:00:00', '2025-09-12 09:20:00', 5, '2025-09-28 09:45:00', NULL),
(55, 15, 'Pipeline production avec blue-green', 'Mettre en place le déploiement blue-green pour la production avec rollback automatique en cas d\'échec des health checks.', 'todo', 'high', '2025-10-15 17:00:00', '2025-09-12 09:25:00', 4, NULL, NULL),
(56, 15, 'Documentation procédures DevOps', 'Rédiger la documentation complète des pipelines, des procédures de rollback et des bonnes pratiques pour l\'équipe.', 'todo', 'medium', '2025-10-22 17:00:00', '2025-09-12 09:30:00', 7, NULL, NULL),
(57, 16, 'Benchmark plateformes existantes', 'Analyser les solutions du marché (Moodle, Canvas, développement custom). Établir une matrice de décision avec critères techniques et budgétaires.', 'in_progress', 'high', '2025-10-02 17:00:00', '2025-09-16 13:10:00', 4, '2025-09-25 14:00:00', NULL),
(58, 16, 'Design interface utilisateur', 'Créer les maquettes UI/UX pour les profils apprenants et formateurs. Focus sur la simplicité d\'utilisation et l\'engagement.', 'todo', 'high', '2025-10-12 17:00:00', '2025-09-16 13:15:00', 3, NULL, NULL),
(59, 16, 'Développement module cours', 'Développer le système de gestion des cours : création, édition, publication, gestion des chapitres et des ressources pédagogiques.', 'todo', 'high', '2025-10-28 17:00:00', '2025-09-16 13:20:00', 6, NULL, NULL),
(60, 16, 'Système de suivi progression', 'Implémenter le tracking de progression des apprenants, les tableaux de bord et les statistiques pour les managers.', 'todo', 'medium', '2025-11-10 17:00:00', '2025-09-16 13:25:00', 5, NULL, NULL),
(61, 16, 'Module gamification et badges', 'Créer le système de points, badges et classements pour stimuler l\'engagement des apprenants. Intégrer les certificats de réussite.', 'todo', 'low', '2025-11-20 17:00:00', '2025-09-16 13:30:00', 7, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_verified` tinyint(1) NOT NULL,
  `token_registration` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_registration_life_time` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `first_name`, `last_name`, `created_at`, `reset_token`, `reset_token_expires_at`, `is_verified`, `token_registration`, `token_registration_life_time`) VALUES
(1, 'ch.roupioz@laposte.net', '[\"ROLE_USER\", \"ROLE_ADMIN\"]', '$2y$13$fWmbX7naUCebxkiTes3mkuGD1EKsadr5IZlET2cG6afJOIGuIhAeC', 'christian', 'ROUPIOZ', '2025-09-02 08:26:11', '5e920dcf-da15-40c2-833e-460d0d9e508b', '2025-09-23 16:46:25', 1, NULL, NULL),
(3, 'jd@gmail.com', '[\"ROLE_USER\"]', '$2y$13$Zfu7MCc7CHhnNUOqJ5PgXOzF0M6dq7YN94xCl4R2MVWJP3uKwAgG6', 'John', 'Doe', '2025-09-09 15:16:22', NULL, NULL, 1, NULL, NULL),
(4, 'bl@gmail.com', '[\"ROLE_USER\"]', '$2y$13$FZ0f4on6kDGvgVvJJwk8Duh5QO8N3AflyH.Kj1MkkJbT59XhpXjP2', 'Bob', 'Leponge', '2025-09-09 15:18:01', NULL, NULL, 1, NULL, NULL),
(5, 'geminia@gmail.com', '[\"ROLE_USER\"]', '$2y$13$fkR5I/RuyGi9aWj8nPxkc.xT3K.OpnfpwTdM1i9nbjXZQa4F8jSlO', 'Gemini', 'IA', '2025-09-12 15:08:44', NULL, NULL, 1, NULL, NULL),
(6, 'claudeia@gmail.com', '[\"ROLE_USER\"]', '$2y$13$7UG8g79rg68cHCIQen/OpeIEjOGzamxlj8oGB4zVdBKRe0090yH9W', 'claude', 'IA', '2025-09-12 15:09:27', NULL, NULL, 1, NULL, NULL),
(7, 'gptia@gmail.com', '[\"ROLE_USER\"]', '$2y$13$INsSUHzho11E5tDZuNkZfOJJYCaYwXAjXTGANlcmInUyvm473cMai', 'GPT', 'IA', '2025-09-12 15:10:07', NULL, NULL, 1, NULL, NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `collaboration_request`
--
ALTER TABLE `collaboration_request`
  ADD CONSTRAINT `FK_6CCAF4EE166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_6CCAF4EEC58DAD6E` FOREIGN KEY (`invited_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_6CCAF4EEF624B39D` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_BF5476CA166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  ADD CONSTRAINT `FK_BF5476CA8DB60186` FOREIGN KEY (`task_id`) REFERENCES `task` (`id`),
  ADD CONSTRAINT `FK_BF5476CAE92F8F78` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_BF5476CAF624B39D` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `FK_2FB3D0EE7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `project_user`
--
ALTER TABLE `project_user`
  ADD CONSTRAINT `FK_B4021E51166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_B4021E51A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `FK_527EDB25166D1F9C` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`),
  ADD CONSTRAINT `FK_527EDB2559EC7D60` FOREIGN KEY (`assignee_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
