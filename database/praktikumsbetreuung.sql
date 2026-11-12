-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Erstellungszeit: 12. Nov 2026 um 13:18
-- Server-Version: 10.1.9-MariaDB
-- PHP-Version: 7.0.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `praktikumsbetreuung`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `classes`
--

INSERT INTO `classes` (`id`, `code`, `name`, `department`) VALUES
(1, 'BEGS1.a', 'Berufseinstiegsschule Gesundheit und Soziales Klasse 1', 3),
(2, 'BEGS1.b', 'Berufseinstiegsschule Gesundheit und Soziales Klasse 1', 3),
(3, 'BEGS2', 'Berufseinstiegsschule Gesundheit und Soziales Klasse 2', 3),
(4, 'BESS1.a', 'Berufseinstiegsschule Sprache und Integration', 3),
(5, 'BESS1.b', 'Berufseinstiegsschule Sprache und Integration', 3),
(6, 'BEST1', 'Berufseinstiegsschule Technik Klasse 1', 3),
(7, 'BEST2', 'Berufseinstiegsschule Technik Klasse 2', 3),
(8, 'BETZ', 'Berufseinstiegsschule Sprache und Integration Teilzeit', 3),
(9, 'BFF', 'Berufsfachschule Farbtechnik und Raumgestaltung', 3),
(10, 'BG11GS', 'Berufliches Gymnasium Gesundheit und Soziales Schwerpunkt Sozialpädagogik', 4),
(11, 'BG12GS', 'Berufliches Gymnasium Gesundheit und Soziales Schwerpunkt Sozialpädagogik', 4),
(12, 'FOG11', 'Fachoberschule Gestaltung Klasse 11', 3),
(13, 'FOG12', 'Fachoberschule Gestaltung Klasse 12', 3),
(14, 'FP1.a', 'FS Sozialpädagogik', 2),
(15, 'FP1.b', 'FS Sozialpädagogik', 2),
(16, 'FP1T', 'FS Sozialpädagogik', 2),
(17, 'FP2', 'FS Sozialpädagogik', 2),
(18, 'FP3T', 'FS Sozialpädagogik', 2),
(19, 'H1', 'BFS Hauswirtschaft und Pflege', 2),
(20, 'H2', 'BFS Ernährung, Hauswirtschaft und Pflege', 2),
(21, 'HEP1', 'HEP', 2),
(22, 'HEP2', 'HEP', 2),
(23, 'HEP3', 'FS Heilerziehungspflege', 2),
(24, 'HHB', 'Höhere Handelsschule, SP Büromanagement', 1),
(25, 'KAI2', 'Kaufm. Assistenten, SP Informationsverarbeitung, Oberstufe', 1),
(26, 'P1.a', 'BFS Pflege', 2),
(27, 'P1.b', 'BFS Pflege', 2),
(28, 'P2.a', 'BFS Pflege', 2),
(29, 'P2.b', 'BFS Pflege', 2),
(30, 'P3', 'BFS Pflege', 2),
(31, 'PA1', 'PFS Pflegeassistenz', 2),
(32, 'PA2', 'BFS Pflegeassistenz', 2),
(33, 'SO1.a', 'Sozialpädagogische Assistenz, 1. Jahr', 2),
(34, 'SO1.b', 'Sozialpädagogische Assistenz, 1. Jahr', 2),
(35, 'SO2.a', 'Sozialpädagogische Assistenz,  2. Jahr', 2),
(36, 'SO2.b', 'Sozialpädagogische Assistenz, 2. Jahr', 2),
(37, 'SO2.c', 'Sozialpädagogische Assistenz, 2.', 2),
(38, 'SO2T', 'Sozialpädadgogische Assistenz, 2. Jahr', 2),
(39, 'SO3T', 'Sozialpädagogische Assistenz, 3. Jahr) - Teilzeit', 2),
(65, 'P1', 'Sammelklasse P1', 0),
(66, 'P2', 'Sammelklasse P2', 0),
(67, 'FPT', 'Sammelklasse FPT', 0),
(68, 'FP1', 'Sammelklasse FP1', 0),
(69, 'PR', 'Sammelklasse PR', 0),
(70, 'SO1', 'Sammelklasse SO1', 0),
(71, 'SO2', 'Sammelklasse SO2', 0),
(72, 'SOT', 'Sammelklasse SOT', 0),
(79, 'BESS1', 'Sammelklasse BESS1', 0),
(80, '11GS', 'Sammelklasse 11GS', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `class_rules`
--

CREATE TABLE `class_rules` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `supervision_factor` decimal(8,4) NOT NULL,
  `weeks_total` decimal(8,4) NOT NULL,
  `weeks_half_year_1` decimal(8,4) NOT NULL,
  `weeks_half_year_2` decimal(8,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `class_rules`
--

INSERT INTO `class_rules` (`id`, `school_year_id`, `class_id`, `supervision_factor`, `weeks_total`, `weeks_half_year_1`, `weeks_half_year_2`) VALUES
(3, 1, 1, '0.0000', '0.0000', '0.0000', '0.0000'),
(6, 1, 2, '0.0000', '0.0000', '0.0000', '0.0000'),
(9, 1, 3, '0.0000', '0.0000', '0.0000', '0.0000'),
(12, 1, 4, '0.0000', '0.0000', '0.0000', '0.0000'),
(15, 1, 5, '0.0000', '0.0000', '0.0000', '0.0000'),
(18, 1, 6, '0.0000', '0.0000', '0.0000', '0.0000'),
(21, 1, 7, '0.0000', '0.0000', '0.0000', '0.0000'),
(23, 1, 8, '0.0000', '0.0000', '0.0000', '0.0000'),
(26, 1, 9, '0.0000', '0.0000', '0.0000', '0.0000'),
(28, 1, 10, '0.0000', '0.0000', '0.0000', '0.0000'),
(30, 1, 11, '0.0000', '0.0000', '0.0000', '0.0000'),
(32, 1, 12, '0.0000', '0.0000', '0.0000', '0.0000'),
(34, 1, 13, '0.0000', '0.0000', '0.0000', '0.0000'),
(37, 1, 14, '0.0000', '0.0000', '0.0000', '0.0000'),
(40, 1, 15, '0.0000', '0.0000', '0.0000', '0.0000'),
(43, 1, 16, '0.0000', '0.0000', '0.0000', '0.0000'),
(46, 1, 17, '0.0000', '0.0000', '0.0000', '0.0000'),
(48, 1, 18, '0.0000', '0.0000', '0.0000', '0.0000'),
(51, 1, 19, '0.0000', '0.0000', '0.0000', '0.0000'),
(54, 1, 20, '0.0000', '0.0000', '0.0000', '0.0000'),
(57, 1, 21, '0.0000', '0.0000', '0.0000', '0.0000'),
(60, 1, 22, '0.0000', '0.0000', '0.0000', '0.0000'),
(63, 1, 23, '0.0000', '0.0000', '0.0000', '0.0000'),
(66, 1, 24, '0.0000', '0.0000', '0.0000', '0.0000'),
(69, 1, 25, '0.0000', '0.0000', '0.0000', '0.0000'),
(72, 1, 26, '0.0000', '0.0000', '0.0000', '0.0000'),
(75, 1, 27, '0.0000', '0.0000', '0.0000', '0.0000'),
(78, 1, 28, '0.0000', '0.0000', '0.0000', '0.0000'),
(81, 1, 29, '0.0000', '0.0000', '0.0000', '0.0000'),
(84, 1, 30, '0.0000', '0.0000', '0.0000', '0.0000'),
(87, 1, 31, '0.0000', '0.0000', '0.0000', '0.0000'),
(90, 1, 32, '0.0000', '0.0000', '0.0000', '0.0000'),
(93, 1, 33, '0.0000', '0.0000', '0.0000', '0.0000'),
(96, 1, 34, '0.0000', '0.0000', '0.0000', '0.0000'),
(99, 1, 35, '0.0000', '0.0000', '0.0000', '0.0000'),
(102, 1, 36, '0.0000', '0.0000', '0.0000', '0.0000'),
(105, 1, 37, '0.0000', '0.0000', '0.0000', '0.0000'),
(108, 1, 38, '0.0000', '0.0000', '0.0000', '0.0000'),
(110, 1, 39, '0.0000', '0.0000', '0.0000', '0.0000'),
(113, 1, 65, '0.0000', '0.0000', '0.0000', '0.0000'),
(116, 1, 66, '0.0000', '0.0000', '0.0000', '0.0000'),
(119, 1, 67, '0.0000', '0.0000', '0.0000', '0.0000'),
(122, 1, 68, '0.0000', '0.0000', '0.0000', '0.0000'),
(125, 1, 69, '0.0000', '0.0000', '0.0000', '0.0000'),
(128, 1, 70, '0.0000', '0.0000', '0.0000', '0.0000'),
(131, 1, 71, '0.0000', '0.0000', '0.0000', '0.0000'),
(134, 1, 72, '0.0000', '0.0000', '0.0000', '0.0000'),
(137, 1, 79, '0.0000', '0.0000', '0.0000', '0.0000'),
(140, 1, 80, '0.0000', '0.0000', '0.0000', '0.0000');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `import_batches`
--

CREATE TABLE `import_batches` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `imported_by` int(11) NOT NULL,
  `file_checksum` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_count` int(11) DEFAULT NULL,
  `error_count` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `school_years`
--

INSERT INTO `school_years` (`id`, `name`, `start_date`, `end_date`) VALUES
(1, '2026/2027', '2026-08-01', '2027-07-31');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `supervision_entries`
--

CREATE TABLE `supervision_entries` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `half_year` tinyint(4) NOT NULL,
  `class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `student_count` int(11) DEFAULT NULL,
  `factor_snapshot` decimal(8,4) NOT NULL,
  `weeks_snapshot` decimal(8,4) NOT NULL,
  `hours_per_week` decimal(10,4) NOT NULL,
  `hours_half_year` decimal(10,3) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `supervision_entries`
--

INSERT INTO `supervision_entries` (`id`, `school_year_id`, `half_year`, `class_id`, `teacher_id`, `student_count`, `factor_snapshot`, `weeks_snapshot`, `hours_per_week`, `hours_half_year`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(3, 1, 1, 65, 6, 1, '1.0000', '7.8225', '1.0000', '0.196', 1, NULL, '2026-11-05 13:59:14', NULL),
(4, 1, 1, 65, 43, 10, '1.0000', '7.8225', '10.0000', '1.956', 1, NULL, '2026-11-05 13:59:14', NULL),
(5, 1, 1, 65, 48, 7, '1.0000', '7.8225', '7.0000', '1.369', 1, NULL, '2026-11-05 13:59:14', NULL),
(6, 1, 1, 65, 86, 11, '1.0000', '7.8225', '11.0000', '2.151', 1, NULL, '2026-11-05 13:59:14', NULL),
(7, 1, 1, 65, 63, 4, '1.0000', '7.8225', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:14', NULL),
(8, 1, 1, 65, 94, 4, '1.0000', '7.8225', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:14', NULL),
(9, 1, 1, 66, 6, 2, '1.0000', '7.8225', '2.0000', '0.391', 1, NULL, '2026-11-05 13:59:14', NULL),
(10, 1, 1, 66, 86, 4, '1.0000', '7.8225', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:14', NULL),
(11, 1, 1, 66, 94, 7, '1.0000', '7.8225', '7.0000', '1.369', 1, NULL, '2026-11-05 13:59:14', NULL),
(12, 1, 1, 66, 48, 5, '1.0000', '7.8225', '5.0000', '0.978', 1, NULL, '2026-11-05 13:59:14', NULL),
(13, 1, 1, 66, 63, 3, '1.0000', '7.8225', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:14', NULL),
(14, 1, 1, 30, 2, 6, '1.0000', '7.8225', '6.0000', '1.173', 1, NULL, '2026-11-05 13:59:14', NULL),
(15, 1, 1, 30, 94, 6, '1.0000', '7.8225', '6.0000', '1.173', 1, NULL, '2026-11-05 13:59:14', NULL),
(16, 1, 1, 30, 86, 2, '1.0000', '7.8225', '2.0000', '0.391', 1, NULL, '2026-11-05 13:59:14', NULL),
(17, 1, 1, 30, 39, 6, '1.0000', '7.8225', '6.0000', '1.173', 1, NULL, '2026-11-05 13:59:14', NULL),
(18, 1, 1, 30, 48, 3, '1.0000', '7.8225', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:14', NULL),
(19, 1, 1, 9, 49, 2, '0.4000', '2.0000', '0.8000', '0.040', 1, NULL, '2026-11-05 13:59:14', NULL),
(20, 1, 1, 67, 38, 4, '0.5000', '2.5974', '2.0000', '0.130', 1, NULL, '2026-11-05 13:59:14', NULL),
(21, 1, 1, 67, 103, 4, '0.5000', '2.5974', '2.0000', '0.130', 1, NULL, '2026-11-05 13:59:14', NULL),
(22, 1, 1, 68, 24, 2, '0.5000', '5.1950', '1.0000', '0.130', 1, NULL, '2026-11-05 13:59:14', NULL),
(23, 1, 1, 68, 23, 3, '0.5000', '5.1950', '1.5000', '0.195', 1, NULL, '2026-11-05 13:59:14', NULL),
(24, 1, 1, 68, 89, 3, '0.5000', '5.1950', '1.5000', '0.195', 1, NULL, '2026-11-05 13:59:14', NULL),
(25, 1, 1, 68, 96, 3, '0.5000', '5.1950', '1.5000', '0.195', 1, NULL, '2026-11-05 13:59:14', NULL),
(26, 1, 1, 68, 68, 2, '0.5000', '5.1950', '1.0000', '0.130', 1, NULL, '2026-11-05 13:59:14', NULL),
(27, 1, 1, 21, 10, 4, '0.5000', '4.5000', '2.0000', '0.225', 1, NULL, '2026-11-05 13:59:14', NULL),
(28, 1, 1, 21, 45, 7, '0.5000', '4.5000', '3.5000', '0.394', 1, NULL, '2026-11-05 13:59:14', NULL),
(29, 1, 1, 21, 48, 7, '0.5000', '4.5000', '3.5000', '0.394', 1, NULL, '2026-11-05 13:59:14', NULL),
(30, 1, 1, 21, 24, 2, '0.5000', '4.5000', '1.0000', '0.113', 1, NULL, '2026-11-05 13:59:14', NULL),
(31, 1, 1, 22, 94, 4, '0.5000', '5.0000', '2.0000', '0.250', 1, NULL, '2026-11-05 13:59:14', NULL),
(32, 1, 1, 22, 48, 5, '0.5000', '5.0000', '2.5000', '0.313', 1, NULL, '2026-11-05 13:59:14', NULL),
(33, 1, 1, 22, 6, 5, '0.5000', '5.0000', '2.5000', '0.313', 1, NULL, '2026-11-05 13:59:14', NULL),
(34, 1, 1, 23, 6, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:14', NULL),
(35, 1, 1, 23, 45, 9, '0.5000', '9.0000', '4.5000', '1.013', 1, NULL, '2026-11-05 13:59:14', NULL),
(36, 1, 1, 23, 24, 3, '0.5000', '9.0000', '1.5000', '0.338', 1, NULL, '2026-11-05 13:59:14', NULL),
(37, 1, 1, 23, 25, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:14', NULL),
(38, 1, 1, 23, 48, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:14', NULL),
(39, 1, 1, 7, 1, 8, '0.2500', '2.0000', '2.0000', '0.100', 1, NULL, '2026-11-05 13:59:14', NULL),
(40, 1, 1, 7, 5, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:14', NULL),
(41, 1, 1, 7, 31, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:14', NULL),
(42, 1, 1, 7, 71, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:14', NULL),
(43, 1, 1, 7, 100, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:14', NULL),
(44, 1, 1, 7, 14, 6, '0.2500', '2.0000', '1.5000', '0.075', 1, NULL, '2026-11-05 13:59:14', NULL),
(45, 1, 1, 3, 20, 4, '0.2500', '2.0000', '1.0000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(46, 1, 1, 3, 53, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:14', NULL),
(47, 1, 1, 3, 26, 4, '0.2500', '2.0000', '1.0000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(48, 1, 1, 3, 80, 5, '0.2500', '2.0000', '1.2500', '0.063', 1, NULL, '2026-11-05 13:59:14', NULL),
(49, 1, 1, 3, 76, 5, '0.2500', '2.0000', '1.2500', '0.063', 1, NULL, '2026-11-05 13:59:14', NULL),
(50, 1, 1, 24, 98, 4, '0.4000', '4.0000', '1.6000', '0.160', 1, NULL, '2026-11-05 13:59:14', NULL),
(51, 1, 1, 24, 67, 9, '0.4000', '4.0000', '3.6000', '0.360', 1, NULL, '2026-11-05 13:59:14', NULL),
(52, 1, 1, 24, 108, 3, '0.4000', '4.0000', '1.2000', '0.120', 1, NULL, '2026-11-05 13:59:14', NULL),
(53, 1, 1, 24, 101, 2, '0.4000', '4.0000', '0.8000', '0.080', 1, NULL, '2026-11-05 13:59:14', NULL),
(54, 1, 1, 24, 99, 2, '0.4000', '4.0000', '0.8000', '0.080', 1, NULL, '2026-11-05 13:59:14', NULL),
(55, 1, 1, 24, 35, 2, '0.4000', '4.0000', '0.8000', '0.080', 1, NULL, '2026-11-05 13:59:14', NULL),
(56, 1, 1, 24, 29, 3, '0.4000', '4.0000', '1.2000', '0.120', 1, NULL, '2026-11-05 13:59:14', NULL),
(57, 1, 1, 24, 42, 1, '0.4000', '4.0000', '0.4000', '0.040', 1, NULL, '2026-11-05 13:59:14', NULL),
(58, 1, 1, 25, 36, 2, '0.2500', '4.0000', '0.5000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(59, 1, 1, 25, 58, 2, '0.2500', '4.0000', '0.5000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(60, 1, 1, 25, 73, 2, '0.2500', '4.0000', '0.5000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(61, 1, 1, 25, 79, 3, '0.2500', '4.0000', '0.7500', '0.075', 1, NULL, '2026-11-05 13:59:14', NULL),
(62, 1, 1, 25, 66, 2, '0.2500', '4.0000', '0.5000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(63, 1, 1, 25, 64, 2, '0.2500', '4.0000', '0.5000', '0.050', 1, NULL, '2026-11-05 13:59:14', NULL),
(64, 1, 1, 32, 39, 4, '0.5000', '6.0000', '2.0000', '0.300', 1, NULL, '2026-11-05 13:59:14', NULL),
(65, 1, 1, 32, 76, 4, '0.5000', '6.0000', '2.0000', '0.300', 1, NULL, '2026-11-05 13:59:14', NULL),
(66, 1, 1, 32, 43, 4, '0.5000', '6.0000', '2.0000', '0.300', 1, NULL, '2026-11-05 13:59:14', NULL),
(67, 1, 1, 32, 63, 2, '0.5000', '6.0000', '1.0000', '0.150', 1, NULL, '2026-11-05 13:59:14', NULL),
(68, 1, 1, 31, 39, 5, '0.5000', '6.0000', '2.5000', '0.375', 1, NULL, '2026-11-05 13:59:14', NULL),
(69, 1, 1, 31, 76, 3, '0.5000', '6.0000', '1.5000', '0.225', 1, NULL, '2026-11-05 13:59:14', NULL),
(70, 1, 1, 31, 43, 1, '0.5000', '6.0000', '0.5000', '0.075', 1, NULL, '2026-11-05 13:59:14', NULL),
(71, 1, 1, 31, 63, 1, '0.5000', '6.0000', '0.5000', '0.075', 1, NULL, '2026-11-05 13:59:14', NULL),
(72, 1, 1, 31, 80, 4, '0.5000', '6.0000', '2.0000', '0.300', 1, NULL, '2026-11-05 13:59:14', NULL),
(73, 1, 1, 70, 106, 6, '0.5000', '7.9000', '3.0000', '0.593', 1, NULL, '2026-11-05 13:59:14', NULL),
(74, 1, 1, 70, 107, 12, '0.5000', '7.9000', '6.0000', '1.185', 1, NULL, '2026-11-05 13:59:14', NULL),
(75, 1, 1, 70, 24, 3, '0.5000', '7.9000', '1.5000', '0.296', 1, NULL, '2026-11-05 13:59:14', NULL),
(76, 1, 1, 70, 68, 14, '0.5000', '7.9000', '7.0000', '1.383', 1, NULL, '2026-11-05 13:59:14', NULL),
(77, 1, 1, 70, 90, 12, '0.5000', '7.9000', '6.0000', '1.185', 1, NULL, '2026-11-05 13:59:14', NULL),
(78, 1, 1, 70, 103, 4, '0.5000', '7.9000', '2.0000', '0.395', 1, NULL, '2026-11-05 13:59:14', NULL),
(79, 1, 1, 70, 38, 9, '0.5000', '7.9000', '4.5000', '0.889', 1, NULL, '2026-11-05 13:59:14', NULL),
(80, 1, 1, 71, 90, 12, '0.5000', '6.9500', '6.0000', '1.043', 1, NULL, '2026-11-05 13:59:14', NULL),
(81, 1, 1, 71, 9, 8, '0.5000', '6.9500', '4.0000', '0.695', 1, NULL, '2026-11-05 13:59:14', NULL),
(82, 1, 1, 71, 107, 10, '0.5000', '6.9500', '5.0000', '0.869', 1, NULL, '2026-11-05 13:59:14', NULL),
(83, 1, 1, 71, 103, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:14', NULL),
(84, 1, 1, 71, 106, 11, '0.5000', '6.9500', '5.5000', '0.956', 1, NULL, '2026-11-05 13:59:14', NULL),
(85, 1, 1, 71, 22, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:14', NULL),
(86, 1, 1, 72, 106, 12, '0.5000', '3.4800', '6.0000', '0.522', 1, NULL, '2026-11-05 13:59:14', NULL),
(87, 1, 1, 72, 9, 10, '0.5000', '3.4800', '5.0000', '0.435', 1, NULL, '2026-11-05 13:59:14', NULL),
(88, 1, 1, 72, 103, 13, '0.5000', '3.4800', '6.5000', '0.566', 1, NULL, '2026-11-05 13:59:14', NULL),
(130, 1, 2, 65, 63, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(131, 1, 2, 65, 94, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(132, 1, 2, 65, 86, 14, '1.0000', '7.8200', '14.0000', '2.738', 1, NULL, '2026-11-05 13:59:36', NULL),
(133, 1, 2, 65, 48, 7, '1.0000', '7.8200', '7.0000', '1.369', 1, NULL, '2026-11-05 13:59:36', NULL),
(134, 1, 2, 65, 43, 8, '1.0000', '7.8200', '8.0000', '1.565', 1, NULL, '2026-11-05 13:59:36', NULL),
(135, 1, 2, 66, 2, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(136, 1, 2, 66, 63, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(137, 1, 2, 66, 43, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(138, 1, 2, 66, 48, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(139, 1, 2, 66, 86, 3, '1.0000', '7.8200', '3.0000', '0.587', 1, NULL, '2026-11-05 13:59:36', NULL),
(140, 1, 2, 66, 94, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(141, 1, 2, 30, 39, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(142, 1, 2, 30, 43, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(143, 1, 2, 30, 94, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(144, 1, 2, 30, 48, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(145, 1, 2, 30, 63, 2, '1.0000', '7.8200', '2.0000', '0.391', 1, NULL, '2026-11-05 13:59:36', NULL),
(146, 1, 2, 30, 86, 4, '1.0000', '7.8200', '4.0000', '0.782', 1, NULL, '2026-11-05 13:59:36', NULL),
(147, 1, 2, 79, 18, 13, '0.2500', '2.0000', '3.2500', '0.163', 1, NULL, '2026-11-05 13:59:36', NULL),
(148, 1, 2, 6, 5, 9, '0.2500', '2.0000', '2.2500', '0.113', 1, NULL, '2026-11-05 13:59:36', NULL),
(149, 1, 2, 6, 9, 1, '0.2500', '2.0000', '0.2500', '0.013', 1, NULL, '2026-11-05 13:59:36', NULL),
(150, 1, 2, 6, 20, 4, '0.2500', '2.0000', '1.0000', '0.050', 1, NULL, '2026-11-05 13:59:36', NULL),
(151, 1, 2, 6, 22, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:36', NULL),
(152, 1, 2, 6, 81, 6, '0.2500', '2.0000', '1.5000', '0.075', 1, NULL, '2026-11-05 13:59:36', NULL),
(153, 1, 2, 3, 1, 6, '0.2500', '2.0000', '1.6250', '0.081', 1, NULL, '2026-11-05 13:59:36', NULL),
(154, 1, 2, 3, 5, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:36', NULL),
(155, 1, 2, 3, 20, 2, '0.2500', '2.0000', '0.5000', '0.025', 1, NULL, '2026-11-05 13:59:36', NULL),
(156, 1, 2, 3, 31, 4, '0.2500', '2.0000', '1.0000', '0.050', 1, NULL, '2026-11-05 13:59:36', NULL),
(157, 1, 2, 3, 71, 4, '0.2500', '2.0000', '1.0000', '0.050', 1, NULL, '2026-11-05 13:59:36', NULL),
(158, 1, 2, 3, 100, 2, '0.2500', '2.0000', '0.6250', '0.031', 1, NULL, '2026-11-05 13:59:36', NULL),
(159, 1, 2, 3, 12, 1, '0.2500', '2.0000', '0.2500', '0.013', 1, NULL, '2026-11-05 13:59:36', NULL),
(160, 1, 2, 3, 14, 3, '0.2500', '2.0000', '0.7500', '0.038', 1, NULL, '2026-11-05 13:59:36', NULL),
(161, 1, 2, 3, 15, 1, '0.2500', '2.0000', '0.2500', '0.013', 1, NULL, '2026-11-05 13:59:36', NULL),
(162, 1, 2, 3, 26, 6, '0.2500', '2.0000', '1.5000', '0.075', 1, NULL, '2026-11-05 13:59:36', NULL),
(163, 1, 2, 3, 76, 5, '0.2500', '2.0000', '1.2500', '0.063', 1, NULL, '2026-11-05 13:59:36', NULL),
(164, 1, 2, 3, 80, 5, '0.2500', '2.0000', '1.2500', '0.063', 1, NULL, '2026-11-05 13:59:36', NULL),
(165, 1, 2, 9, 49, 3, '0.4000', '2.0000', '1.2000', '0.060', 1, NULL, '2026-11-05 13:59:36', NULL),
(166, 1, 2, 17, 96, 2, '0.5000', '10.3900', '1.0000', '0.260', 1, NULL, '2026-11-05 13:59:36', NULL),
(167, 1, 2, 17, 38, 4, '0.5000', '10.3900', '2.0000', '0.520', 1, NULL, '2026-11-05 13:59:36', NULL),
(168, 1, 2, 17, 89, 6, '0.5000', '10.3900', '3.0000', '0.779', 1, NULL, '2026-11-05 13:59:36', NULL),
(169, 1, 2, 17, 9, 2, '0.5000', '10.3900', '1.0000', '0.260', 1, NULL, '2026-11-05 13:59:36', NULL),
(170, 1, 2, 17, 23, 4, '0.5000', '10.3900', '2.0000', '0.520', 1, NULL, '2026-11-05 13:59:36', NULL),
(171, 1, 2, 17, 42, 9, '0.5000', '10.3900', '4.5000', '1.169', 1, NULL, '2026-11-05 13:59:36', NULL),
(172, 1, 2, 17, 103, 4, '0.5000', '10.3900', '2.0000', '0.520', 1, NULL, '2026-11-05 13:59:36', NULL),
(173, 1, 2, 67, 57, 5, '0.5000', '2.6000', '2.5000', '0.162', 1, NULL, '2026-11-05 13:59:36', NULL),
(174, 1, 2, 67, 103, 4, '0.5000', '2.6000', '2.0000', '0.130', 1, NULL, '2026-11-05 13:59:36', NULL),
(175, 1, 2, 67, 42, 5, '0.5000', '2.6000', '2.5000', '0.162', 1, NULL, '2026-11-05 13:59:36', NULL),
(176, 1, 2, 19, 12, 6, '0.4000', '4.0000', '2.4000', '0.240', 1, NULL, '2026-11-05 13:59:36', NULL),
(177, 1, 2, 19, 15, 6, '0.4000', '4.0000', '2.4000', '0.240', 1, NULL, '2026-11-05 13:59:36', NULL),
(178, 1, 2, 19, 92, 7, '0.4000', '4.0000', '2.8000', '0.280', 1, NULL, '2026-11-05 13:59:36', NULL),
(179, 1, 2, 19, 81, 5, '0.4000', '4.0000', '2.0000', '0.200', 1, NULL, '2026-11-05 13:59:36', NULL),
(180, 1, 2, 19, 80, 3, '0.4000', '4.0000', '1.2000', '0.120', 1, NULL, '2026-11-05 13:59:36', NULL),
(181, 1, 2, 21, 48, 6, '0.5000', '8.5000', '3.0000', '0.638', 1, NULL, '2026-11-05 13:59:36', NULL),
(182, 1, 2, 21, 10, 4, '0.5000', '8.5000', '2.0000', '0.425', 1, NULL, '2026-11-05 13:59:36', NULL),
(183, 1, 2, 21, 45, 6, '0.5000', '8.5000', '3.0000', '0.638', 1, NULL, '2026-11-05 13:59:36', NULL),
(184, 1, 2, 21, 24, 5, '0.5000', '8.5000', '2.5000', '0.531', 1, NULL, '2026-11-05 13:59:36', NULL),
(185, 1, 2, 22, 48, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:36', NULL),
(186, 1, 2, 22, 10, 5, '0.5000', '9.0000', '2.5000', '0.563', 1, NULL, '2026-11-05 13:59:36', NULL),
(187, 1, 2, 22, 22, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:36', NULL),
(188, 1, 2, 22, 45, 4, '0.5000', '9.0000', '2.0000', '0.450', 1, NULL, '2026-11-05 13:59:36', NULL),
(189, 1, 2, 22, 25, 2, '0.5000', '9.0000', '1.0000', '0.225', 1, NULL, '2026-11-05 13:59:36', NULL),
(190, 1, 2, 23, 25, 2, '0.5000', '4.0000', '1.0000', '0.100', 1, NULL, '2026-11-05 13:59:36', NULL),
(191, 1, 2, 23, 45, 10, '0.5000', '4.0000', '5.0000', '0.500', 1, NULL, '2026-11-05 13:59:36', NULL),
(192, 1, 2, 23, 48, 2, '0.5000', '4.0000', '1.0000', '0.100', 1, NULL, '2026-11-05 13:59:36', NULL),
(193, 1, 2, 23, 24, 3, '0.5000', '4.0000', '1.5000', '0.150', 1, NULL, '2026-11-05 13:59:36', NULL),
(194, 1, 2, 31, 39, 1, '0.5000', '6.0000', '0.5000', '0.075', 1, NULL, '2026-11-05 13:59:36', NULL),
(195, 1, 2, 31, 76, 4, '0.5000', '6.0000', '2.0000', '0.300', 1, NULL, '2026-11-05 13:59:36', NULL),
(196, 1, 2, 31, 80, 3, '0.5000', '6.0000', '1.5000', '0.225', 1, NULL, '2026-11-05 13:59:36', NULL),
(197, 1, 2, 32, 39, 13, '0.5000', '6.0000', '6.5000', '0.975', 1, NULL, '2026-11-05 13:59:36', NULL),
(198, 1, 2, 32, 63, 1, '0.5000', '6.0000', '0.5000', '0.075', 1, NULL, '2026-11-05 13:59:36', NULL),
(199, 1, 2, 71, 90, 12, '0.5000', '6.9500', '6.0000', '1.043', 1, NULL, '2026-11-05 13:59:36', NULL),
(200, 1, 2, 71, 9, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:36', NULL),
(201, 1, 2, 71, 107, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:36', NULL),
(202, 1, 2, 71, 103, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:36', NULL),
(203, 1, 2, 71, 106, 11, '0.5000', '6.9500', '5.5000', '0.956', 1, NULL, '2026-11-05 13:59:36', NULL),
(204, 1, 2, 71, 22, 7, '0.5000', '6.9500', '3.5000', '0.608', 1, NULL, '2026-11-05 13:59:36', NULL),
(205, 1, 2, 72, 106, 11, '0.5000', '3.4800', '5.5000', '0.479', 1, NULL, '2026-11-05 13:59:36', NULL),
(206, 1, 2, 72, 9, 8, '0.5000', '3.4800', '4.0000', '0.348', 1, NULL, '2026-11-05 13:59:36', NULL),
(207, 1, 2, 72, 103, 12, '0.5000', '3.4800', '6.0000', '0.522', 1, NULL, '2026-11-05 13:59:36', NULL),
(208, 1, 2, 80, 23, 5, '1.0340', '4.0000', '5.1700', '0.517', 1, NULL, '2026-11-05 13:59:36', NULL),
(209, 1, 2, 80, 9, 6, '1.0340', '4.0000', '6.2040', '0.620', 1, NULL, '2026-11-05 13:59:36', NULL),
(210, 1, 2, 80, 25, 2, '1.0340', '4.0000', '2.0680', '0.207', 1, NULL, '2026-11-05 13:59:36', NULL),
(211, 1, 2, 80, 32, 5, '1.0340', '4.0000', '5.1700', '0.517', 1, NULL, '2026-11-05 13:59:36', NULL),
(212, 1, 2, 80, 106, 7, '1.0340', '4.0000', '7.2380', '0.724', 1, NULL, '2026-11-05 13:59:36', NULL),
(213, 1, 2, 80, 89, 5, '1.0340', '4.0000', '5.1700', '0.517', 1, NULL, '2026-11-05 13:59:36', NULL),
(214, 1, 2, 80, 57, 2, '1.0340', '4.0000', '2.0680', '0.207', 1, NULL, '2026-11-05 13:59:36', NULL),
(215, 1, 2, 80, 42, 2, '1.0340', '4.0000', '2.0680', '0.207', 1, NULL, '2026-11-05 13:59:36', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `teachers`
--

INSERT INTO `teachers` (`id`, `code`, `surname`, `firstname`, `title`) VALUES
(1, 'BAL', 'Baltruschat', 'Kyra', ''),
(2, 'BAN', 'Bantelmann', 'Inge', ''),
(3, 'BAR', 'Barkminn', 'Alexander', ''),
(4, 'BAZ', 'Bazylak', 'Karolina', ''),
(5, 'BEI', 'Beinsen', 'Karin', ''),
(6, 'BEU', 'Beuleke-Schultze', 'Katharina', ''),
(7, 'BIL', 'Bilbeber', 'Carina', ''),
(8, 'BOE', 'Böttger', 'Ina', ''),
(9, 'BÖH', 'Böhm', 'Andrea', ''),
(10, 'BOR', 'Borchert', 'Nicole', ''),
(11, 'BOT', 'Both', 'Sandra', ''),
(12, 'BRI', 'Brinkmann-Freiherr', 'Kornelia', ''),
(13, 'BRN', 'Brand', 'Monique', ''),
(14, 'BRÖ', 'Bröhl', 'Tatjana', ''),
(15, 'BRU', 'Brunotte', 'Marie-Kristin', ''),
(16, 'BUE', 'Büning', 'Inga', ''),
(17, 'BÜK', 'Büker', 'Laura', 'Dr.'),
(18, 'BUR', 'Bauer', 'Alicia', ''),
(19, 'COL', 'Colditz', 'Frank', 'Dr.'),
(20, 'DIS', 'Disep', 'Michelle', ''),
(21, 'DOE', 'Döring', 'Renatus', ''),
(22, 'DOR', 'Dörger', 'Sarah', ''),
(23, 'DRE', 'Drewes', 'Laura', ''),
(24, 'DUE', 'Düvel', 'Kathrin', ''),
(25, 'EBE', 'Eberl', 'Sarah', ''),
(26, 'ECK', 'Eckhardt', 'Kathrin', ''),
(27, 'EDL', 'Edler-Mertens', 'Sabine', ''),
(28, 'EGL', 'Englert', 'Katrin', ''),
(29, 'ELL', 'Ellies', 'Cordula', ''),
(30, 'ENH', 'Engelhardt', 'Anna-Lena', ''),
(31, 'ESP', 'Espe', 'Thorsten', ''),
(32, 'FEH', 'Fehling', 'Ann-Kathrin', ''),
(33, 'FIL', 'Filmer', 'Melanie', ''),
(34, 'FIP', 'Fillips', 'Karoline', ''),
(35, 'GAR', 'Gareis-Senkevich', 'Stephanie', ''),
(36, 'GEI', 'Geisler', 'Saskia', ''),
(37, 'GIE', 'von Gierke', 'Leonie', ''),
(38, 'GÖK', 'Gökbulut', 'Melda', ''),
(39, 'GRA', 'Grastorf', 'Britta', ''),
(40, 'GRO', 'Groß-Scholz', 'Gabriele', ''),
(42, 'GRÜ', 'Grünewald', 'Jakob', ''),
(43, 'HAG', 'Hagemann', 'Marina', ''),
(44, 'HAS', 'Haß', 'Claudia', 'Dr.'),
(45, 'HEF', 'Heidemann', 'Frauke', ''),
(46, 'HEI', 'Heinemann-Boes', 'Marina', ''),
(47, 'HENK', 'Henkenius', 'Nick', ''),
(48, 'HER', 'Herbst', 'Svenja', ''),
(49, 'HOL', 'Holtmann', 'Carolin', ''),
(50, 'IZO', 'Izouli', 'Berivan', ''),
(51, 'JAB', 'Jablonski', 'Ralph', ''),
(52, 'JAI', 'Jain', 'Sonja', ''),
(53, 'JEL', 'Jelitto', 'Helena', ''),
(54, 'KIR', 'Kirst-Bode', 'Dörte', ''),
(55, 'KLO', 'Kloke', 'Meinhard', 'Dr.'),
(56, 'KNA', 'Knackstedt', 'Frank', ''),
(57, 'KNO', 'Knop', 'Rebecca', ''),
(58, 'KOP', 'Koopmann', 'Nina', ''),
(59, 'LAU', 'Launhardt', 'Marina', ''),
(60, 'LER', 'Lerche', 'Evelyn', ''),
(61, 'LOR', 'Lorenz', 'Max', ''),
(62, 'LUE', 'Lüdeke', 'Katrin', ''),
(63, 'MAP', 'Mannigel', 'Petra', ''),
(64, 'MEI', 'Meinecke', 'Niklas', ''),
(65, 'MENR', 'Meiners', 'Hanna', ''),
(66, 'MIC', 'Michaelis', 'Katrin', ''),
(67, 'NEN', 'Nennmann', 'Andrea', ''),
(68, 'NIE', 'Niesen', 'Rebecca', ''),
(69, 'NN', '', '', ''),
(70, 'OHE', 'von der Ohe', 'Katharina', ''),
(71, 'OHL', 'von Ohlen', 'Andreas', ''),
(72, 'PAPR', 'Pape', 'Lotta', ''),
(73, 'PEI', 'Peinemann', 'Ilka', ''),
(74, 'PIN', 'Pinne', 'Alina', ''),
(75, 'PLT', 'Plate', 'Mareike', ''),
(76, 'POS', 'Possner', 'Imke', ''),
(77, 'PRA', 'Pramann', 'Mareike', ''),
(78, 'PRI', 'Prien-Lehmkul', 'Sonja', ''),
(79, 'PST', 'Post', 'Anja', ''),
(80, 'RED', 'Redzig', 'Lara', ''),
(81, 'REI', 'Reitz', 'Wiebke', ''),
(82, 'RIE', 'Riedke', 'Susanne', ''),
(83, 'RUD', 'Rudolph', 'Mirjam', ''),
(84, 'SAN', 'Sandler', 'Christina', ''),
(85, 'SCA', 'Schadt', 'Boris', ''),
(86, 'SCH', 'Schilling', 'Sven', ''),
(87, 'SCL', 'Schleder', 'Theresa', ''),
(88, 'SCU', 'Schulz', 'Marc', ''),
(89, 'SCW', 'Schwichtenberg', 'Nicolai', ''),
(90, 'SCZ', 'Schulz', 'Ilona', ''),
(91, 'SEF', 'Seifert', 'Vanessa', ''),
(92, 'SEI', 'Seipelt', 'Petra', ''),
(93, 'SEV', 'Severit', 'Andreas', ''),
(94, 'SFT', 'Seifert', 'Nicole', ''),
(95, 'SIE', 'Siebrecht-Schöll', 'Daniel', 'Dr.'),
(96, 'SLD', 'Schleder', 'Sabine', ''),
(97, 'SMR', 'Schumacher', 'Julia', ''),
(98, 'SPI', 'Spintig', 'Céline', ''),
(99, 'SPN', 'Scupin', 'Ulf', ''),
(100, 'STB', 'Steblov', 'Arthur', ''),
(101, 'STE', 'Steckel', 'Jessica', ''),
(102, 'STR', 'Strohmeier', 'Nils', ''),
(103, 'SWA', 'Schwanke', 'Janine', ''),
(104, 'TIE', 'Tietze', 'Tanja', ''),
(105, 'TWL', 'Twele', 'Marina', ''),
(106, 'VOL', 'Vollbrecht', 'Daniel', ''),
(107, 'VSE', 'von Seelen', 'Hanna-Lena', ''),
(108, 'WEL', 'Welzel', 'Yana', ''),
(109, 'WEM', 'Wehmeyer', 'Melanie', ''),
(110, 'WOL', 'Wolfgang', 'Joana', ''),
(111, 'WSE', 'Wiese', 'Jobst', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `iserv_uuid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `iserv_uuid`, `email`, `display_name`, `role`, `status`, `last_login_at`) VALUES
(1, 'system-import', NULL, NULL, 'admin', 'active', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_class_assignments`
--

CREATE TABLE `user_class_assignments` (
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indizes für die Tabelle `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indizes für die Tabelle `class_rules`
--
ALTER TABLE `class_rules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_year_id` (`school_year_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indizes für die Tabelle `import_batches`
--
ALTER TABLE `import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `imported_by` (`imported_by`);

--
-- Indizes für die Tabelle `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `supervision_entries`
--
ALTER TABLE `supervision_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indizes für die Tabelle `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `iserv_uuid` (`iserv_uuid`);

--
-- Indizes für die Tabelle `user_class_assignments`
--
ALTER TABLE `user_class_assignments`
  ADD PRIMARY KEY (`user_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;
--
-- AUTO_INCREMENT für Tabelle `class_rules`
--
ALTER TABLE `class_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;
--
-- AUTO_INCREMENT für Tabelle `import_batches`
--
ALTER TABLE `import_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT für Tabelle `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT für Tabelle `supervision_entries`
--
ALTER TABLE `supervision_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=216;
--
-- AUTO_INCREMENT für Tabelle `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;
--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `class_rules`
--
ALTER TABLE `class_rules`
  ADD CONSTRAINT `class_rules_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`),
  ADD CONSTRAINT `class_rules_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Constraints der Tabelle `import_batches`
--
ALTER TABLE `import_batches`
  ADD CONSTRAINT `import_batches_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`),
  ADD CONSTRAINT `import_batches_ibfk_2` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `supervision_entries`
--
ALTER TABLE `supervision_entries`
  ADD CONSTRAINT `supervision_entries_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`),
  ADD CONSTRAINT `supervision_entries_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `supervision_entries_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `supervision_entries_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `supervision_entries_ibfk_5` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints der Tabelle `user_class_assignments`
--
ALTER TABLE `user_class_assignments`
  ADD CONSTRAINT `user_class_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_class_assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
