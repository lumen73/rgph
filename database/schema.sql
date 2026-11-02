-- ===========================================================================
-- RGPH5 — Schéma de référence de la base de données
-- MySQL 8+/9+
--
-- Ce fichier documente la structure cible. Pour appliquer les évolutions sur
-- une base existante, utilisez plutôt : php database/migrate.php
-- ===========================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `departement` (
    `iddepartement` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`iddepartement`),
    KEY `idx_departement_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `commune` (
    `idcommune` INT NOT NULL AUTO_INCREMENT,
    `iddepartement` INT NOT NULL,
    `nom` VARCHAR(250) NOT NULL,
    PRIMARY KEY (`idcommune`),
    KEY `fk_iddepartement` (`iddepartement`),
    KEY `idx_commune_nom` (`nom`),
    CONSTRAINT `commune_departement_fk`
        FOREIGN KEY (`iddepartement`) REFERENCES `departement` (`iddepartement`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `arrondissement` (
    `idarrondissement` INT NOT NULL AUTO_INCREMENT,
    `idcommune` INT NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`idarrondissement`),
    KEY `idx_arrondissement_commune` (`idcommune`),
    KEY `idx_arrondissement_nom` (`nom`),
    CONSTRAINT `arrondissement_commune_fk`
        FOREIGN KEY (`idcommune`) REFERENCES `commune` (`idcommune`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `ville` (
    `idville` INT NOT NULL AUTO_INCREMENT,
    `idcommune` INT NOT NULL,
    `idarrondissement` INT NULL,
    `nom` VARCHAR(250) NOT NULL,
    `population` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`idville`),
    KEY `fk_idcommune` (`idcommune`),
    KEY `idx_ville_arrondissement` (`idarrondissement`),
    KEY `idx_ville_nom` (`nom`),
    KEY `idx_ville_population` (`population`),
    CONSTRAINT `ville_commune_fk`
        FOREIGN KEY (`idcommune`) REFERENCES `commune` (`idcommune`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `registre` (
    `idregistre` INT NOT NULL AUTO_INCREMENT,
    `idville` INT NOT NULL,
    `nom` VARCHAR(255) NOT NULL,
    `prenom` VARCHAR(255) NOT NULL,
    `date_ref` DATE NOT NULL,
    `genre` ENUM('M','F') NOT NULL,
    PRIMARY KEY (`idregistre`),
    KEY `idx_registre_ville` (`idville`),
    KEY `idx_registre_nom` (`nom`),
    KEY `idx_registre_prenom` (`prenom`),
    KEY `idx_registre_date_ref` (`date_ref`),
    KEY `idx_registre_genre` (`genre`),
    CONSTRAINT `registre_ville_fk`
        FOREIGN KEY (`idville`) REFERENCES `ville` (`idville`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `utilisateur` (
    `idutilisateur` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(250) NOT NULL,
    `email` VARCHAR(250) NOT NULL,
    `mdp` VARCHAR(250) NOT NULL,
    `role` ENUM('admin','agent','lecteur') NOT NULL DEFAULT 'agent',
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`idutilisateur`),
    UNIQUE KEY `uniq_utilisateur_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
