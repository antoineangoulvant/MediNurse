-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Mer 30 Septembre 2015 à 14:19
-- Version du serveur: 5.5.44-0ubuntu0.14.04.1
-- Version de PHP: 5.5.9-1ubuntu4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `phpmylab`
--

-- --------------------------------------------------------

--
-- Structure de la table `T_CONFIGURATION`
--

CREATE TABLE IF NOT EXISTS `T_CONFIGURATION` (
  `VERSION_PHPMYLAB` varchar(10) NOT NULL,
  `VERSION_BASE` varchar(10) NOT NULL,
  PRIMARY KEY (`VERSION_PHPMYLAB`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_CONFIGURATION`
--

INSERT INTO `T_CONFIGURATION` (`VERSION_PHPMYLAB`, `VERSION_BASE`) VALUES
('2.2.0', '3.0');

-- --------------------------------------------------------

--
-- Structure de la table `T_CONGE`
--

CREATE TABLE IF NOT EXISTS `T_CONGE` (
  `ID_CONGE` int(11) NOT NULL,
  `UTILISATEUR` varchar(16) NOT NULL,
  `GROUPE` varchar(32) NOT NULL DEFAULT '0',
  `TYPE` smallint(6) NOT NULL,
  `DEBUT_DATE` date NOT NULL,
  `DEBUT_AM` tinyint(4) NOT NULL,
  `FIN_DATE` date NOT NULL,
  `FIN_PM` tinyint(4) NOT NULL,
  `NB_JOURS_OUVRES` float NOT NULL,
  `GENRE` smallint(6) NOT NULL DEFAULT '0',
  `COMMENTAIRE` varchar(256) NOT NULL,
  `INFORMER_GP` tinyint(4) NOT NULL DEFAULT '0',
  `VALIDE` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID_CONGE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `T_CONGE_SOLDE`
--

CREATE TABLE IF NOT EXISTS `T_CONGE_SOLDE` (
  `UTILISATEUR` varchar(16) NOT NULL,
  `SOLDE_CA` float NOT NULL DEFAULT '0',
  `SOLDE_CA_1` float NOT NULL DEFAULT '0',
  `SOLDE_RECUP` float NOT NULL DEFAULT '0',
  `SOLDE_CET` float NOT NULL DEFAULT '0',
  `QUOTA_JOURS` float NOT NULL DEFAULT '45',
  `QUOTITE` float NOT NULL DEFAULT '100',
  PRIMARY KEY (`UTILISATEUR`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_CONGE_SOLDE`
--

INSERT INTO `T_CONGE_SOLDE` (`UTILISATEUR`, `SOLDE_CA`, `SOLDE_CA_1`, `SOLDE_RECUP`, `SOLDE_CET`, `QUOTA_JOURS`, `QUOTITE`) VALUES
('logindeladmin', 31, 0, 0, 0, 32, 100),
('loginduchef', 32, 0, 0, 0, 32, 100),
('loginduchefadmin', 32, 10, 0, 0, 32, 100),
('logindudirecteur', 32, 0, 0, 0, 32, 100),
('logindutechnicie', 32, 0, 0, 0, 32, 100);

-- --------------------------------------------------------

--
-- Structure de la table `T_CORRESPONDANCE`
--

CREATE TABLE IF NOT EXISTS `T_CORRESPONDANCE` (
  `GROUPE` varchar(32) NOT NULL,
  `RESPONSABLE` varchar(16) NOT NULL,
  `RESPONSABLE2` varchar(16) DEFAULT NULL,
  `ADMINISTRATIF` varchar(16) NOT NULL,
  `ADMINISTRATIF2` varchar(16) NOT NULL,
  `VALID_MISSIONS` tinyint(4) NOT NULL DEFAULT '0',
  `VALID_CONGES` tinyint(4) NOT NULL DEFAULT '1',
  `ENTITE_DEPENSIERE` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`GROUPE`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_CORRESPONDANCE`
--

INSERT INTO `T_CORRESPONDANCE` (`GROUPE`, `RESPONSABLE`, `RESPONSABLE2`, `ADMINISTRATIF`, `ADMINISTRATIF2`, `VALID_MISSIONS`, `VALID_CONGES`, `ENTITE_DEPENSIERE`) VALUES
('ADMINISTRATION', 'loginduchefadmin', '', 'loginduchefadmin', '', 0, 1, 0),
('DIRECTION', 'logindudirecteur', '', 'loginduchefadmin', '', 0, 1, 0),
('EQUIPE1', 'loginduchef', '', 'logindeladmin', '', 0, 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `T_EXPEDITION`
--

CREATE TABLE IF NOT EXISTS `T_EXPEDITION` (
  `ID_EXPEDITION` int(11) NOT NULL,
  `UTILISATEUR` varchar(16) NOT NULL,
  `GROUPE_IMPUTE` varchar(32) NOT NULL,
  `LIEU_ENLEVEMENT` varchar(64) NOT NULL,
  `ADRESSE` varchar(64) NOT NULL,
  `CODE_POSTAL` varchar(5) NOT NULL,
  `VILLE` varchar(32) NOT NULL,
  `TELEPHONE` varchar(16) NOT NULL,
  `EMAIL` varchar(32) NOT NULL,
  `POIDS` float NOT NULL,
  `DIMENSIONS` varchar(16) NOT NULL,
  `VALEUR` float NOT NULL,
  `DESIGNATION` varchar(256) NOT NULL,
  `COMMENTAIRE` varchar(256) NOT NULL,
  `LIEN_TRACKER` varchar(256) NOT NULL,
  `NUMERO_TRACKING` varchar(64) NOT NULL,
  `PIECE_JOINTE` varchar(32) NOT NULL,
  `ETAT` tinyint(1) NOT NULL,
  PRIMARY KEY (`ID_EXPEDITION`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `T_INVENTAIRE`
--

CREATE TABLE IF NOT EXISTS `T_INVENTAIRE` (
  `ID_MATERIEL` int(11) NOT NULL,
  `LIBELLE` varchar(64) NOT NULL,
  `DESCRIPTION` varchar(256) NOT NULL,
  `GROUPE` varchar(32) NOT NULL,
  `NOM_CONTACT` varchar(32) NOT NULL,
  `TEL_CONTACT` varchar(16) NOT NULL,
  `EMAIL_CONTACT` varchar(32) NOT NULL,
  `DISPONIBILITE` tinyint(1) NOT NULL,
  `UTILISATION` varchar(32) NOT NULL,
  `PHOTO` varchar(32) NOT NULL,
  `fournisseur` varchar(64) DEFAULT NULL,
  `montant_HT` int(10) DEFAULT NULL,
  `date_MES` datetime DEFAULT NULL,
  `num_serie` int(20) DEFAULT NULL,
  `num_inventaire_labo` int(20) DEFAULT NULL,
  `num_fiche_immo` int(20) DEFAULT NULL,
  `partage` tinyint(1) DEFAULT NULL,
  `labo_origine` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`ID_MATERIEL`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_INVENTAIRE`
--

INSERT INTO `T_INVENTAIRE` (`ID_MATERIEL`, `LIBELLE`, `DESCRIPTION`, `GROUPE`, `NOM_CONTACT`, `TEL_CONTACT`, `EMAIL_CONTACT`, `DISPONIBILITE`, `UTILISATION`, `PHOTO`, `fournisseur`, `montant_HT`, `date_MES`, `num_serie`, `num_inventaire_labo`, `num_fiche_immo`, `partage`, `labo_origine`) VALUES
(2, 'climatiseur', 'Climatiseur mural', 'DIRECTION', 'Personne', '0473123456', 'clim@mail.fr', 1, 'Occasionnellement', '49DHh7qLJy1AXi1.jpg', '', 0, '1970-01-01 00:00:00', 0, 0, 0, 1, 'LIMOS');

-- --------------------------------------------------------

--
-- Structure de la table `T_MISSION`
--

CREATE TABLE IF NOT EXISTS `T_MISSION` (
  `ID_MISSION` int(11) NOT NULL,
  `UTILISATEUR` varchar(16) NOT NULL,
  `GROUPE` varchar(32) NOT NULL DEFAULT '0',
  `DEPART` varchar(16) NOT NULL,
  `DESTINATION` varchar(32) NOT NULL,
  `OBJET` varchar(64) NOT NULL,
  `TYPE` smallint(6) NOT NULL,
  `TRANSPORT` varchar(32) NOT NULL,
  `ALLER_DATE` date NOT NULL,
  `ALLER_H_DEPART` smallint(6) NOT NULL,
  `ALLER_H_ARRIVEE` smallint(6) NOT NULL,
  `RETOUR_DATE` date NOT NULL,
  `RETOUR_H_DEPART` smallint(6) NOT NULL,
  `RETOUR_H_ARRIVEE` smallint(6) NOT NULL,
  `COMMENTAIRE` varchar(256) NOT NULL,
  `VALIDE` smallint(6) NOT NULL DEFAULT '1',
  `ESTIMATION_COUT` float(8,2) DEFAULT NULL,
  PRIMARY KEY (`ID_MISSION`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `T_PUBLICATION`
--

CREATE TABLE IF NOT EXISTS `T_PUBLICATION` (
  `ID_PUBLICATION` int(11) NOT NULL,
  `UTILISATEUR` varchar(16) NOT NULL,
  `TITRE` varchar(256) NOT NULL,
  `FICHIER` varchar(256) NOT NULL,
  `CONTENU` text NOT NULL,
  `CATEGORIE` varchar(32) NOT NULL,
  `PLUS` text NOT NULL,
  `MOINS` text NOT NULL,
  `DATE_PUBLICATION` int(11) NOT NULL,
  PRIMARY KEY (`ID_PUBLICATION`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_PUBLICATION`
--

INSERT INTO `T_PUBLICATION` (`ID_PUBLICATION`, `UTILISATEUR`, `TITRE`, `FICHIER`, `CONTENU`, `CATEGORIE`, `PLUS`, `MOINS`, `DATE_PUBLICATION`) VALUES
(1, 'logindeladmin', 'CongÃ©s payÃ©s', 'https://www.youtube.com/watch?v=ssZe8eIeRBc', 'Les premiers congÃ©s payÃ©s en France.', 'Divers', '', '', 1443195174);

-- --------------------------------------------------------

--
-- Structure de la table `T_UTILISATEUR`
--

CREATE TABLE IF NOT EXISTS `T_UTILISATEUR` (
  `UTILISATEUR` varchar(16) NOT NULL,
  `NOM` varchar(32) NOT NULL,
  `PRENOM` varchar(32) NOT NULL,
  `MOTDEPASSE` varchar(32) NOT NULL,
  `SS` varchar(15) NOT NULL,
  `MEL` varchar(32) NOT NULL,
  `GROUPE` varchar(32) NOT NULL,
  `STATUS` smallint(6) NOT NULL DEFAULT '0',
  `ADMIN` tinyint(4) NOT NULL DEFAULT '0',
  `CONTRAT_TYPE` varchar(16) NOT NULL,
  `CONTRAT_DEBUT` date DEFAULT NULL,
  `CONTRAT_FIN` date DEFAULT NULL,
  `LOGIN_CAS` varchar(48) NOT NULL,
  `PHOTO` varchar(64) NOT NULL,
  `BUREAU` varchar(32) NOT NULL,
  `TELEPHONE` varchar(16) NOT NULL,
  `CONNEXION_COMMUNITY` int(11) NOT NULL,
  PRIMARY KEY (`UTILISATEUR`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Contenu de la table `T_UTILISATEUR`
--

INSERT INTO `T_UTILISATEUR` (`UTILISATEUR`, `NOM`, `PRENOM`, `MOTDEPASSE`, `SS`, `MEL`, `GROUPE`, `STATUS`, `ADMIN`, `CONTRAT_TYPE`, `CONTRAT_DEBUT`, `CONTRAT_FIN`, `LOGIN_CAS`, `PHOTO`, `BUREAU`, `TELEPHONE`, `CONNEXION_COMMUNITY`) VALUES
('logindeladmin', 'NOMADMIN', 'PRENOMADMIN', 'bWRwIQ==', '', '', 'ADMINISTRATION', 5, 1, 'Technicien', '2011-05-19', NULL, '', '', '', '', 1443195230),
('loginduchef', 'NOMDUCHEF', 'PRENOMDUCHEF', 'bWRw', '', '', 'EQUIPE1', 4, 0, 'Cadre', '2012-05-16', NULL, '', '', '', '', 1443603859),
('loginduchefadmin', 'NOMDUCHEFADMIN', 'PRENOMDUCHEFADMIN', 'bWRw', '', '', 'ADMINISTRATION', 6, 0, 'Cadre', '2012-05-16', NULL, '', '', '', '', 0),
('logindudirecteur', 'NOMDUDIRECTEUR', 'PRENOMDUDIRECTEUR', 'bWRw', '', '', 'DIRECTION', 6, 0, 'Cadre', '2000-08-01', NULL, '', '', '', '', 0),
('logindutechnicie', 'NOMDUTECHNICIEN', 'PRENOMDUTECHNICIEN', 'bWRw', '', '', 'EQUIPE1', 2, 0, 'Technicien', '2012-05-16', NULL, '', '', '', '', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
