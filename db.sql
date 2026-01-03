-- Recommandé
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Localiser;
DROP TABLE IF EXISTS Implante;
DROP TABLE IF EXISTS Soutenir;
DROP TABLE IF EXISTS Assister;
DROP TABLE IF EXISTS Participer;
DROP TABLE IF EXISTS Region;
DROP TABLE IF EXISTS Partenaire;
DROP TABLE IF EXISTS Mission;
DROP TABLE IF EXISTS Presse;
DROP TABLE IF EXISTS Financement;
DROP TABLE IF EXISTS Evenement;
DROP TABLE IF EXISTS Benevole;

SET FOREIGN_KEY_CHECKS = 1;

-- 1) Benevole
CREATE TABLE Benevole (
                          IdBenevole INT NOT NULL AUTO_INCREMENT,
                          NomBenevole VARCHAR(50) NOT NULL,
                          PrenomBenevole VARCHAR(50) NOT NULL,
                          NumeroBenevole VARCHAR(50),
                          VilleBenevole VARCHAR(50),
                          CompetenceBenevole VARCHAR(50),
                          ProfessionBenevole VARCHAR(50),
                          RegimeAlimentaire VARCHAR(50),
                          DateNaissanceBenevole DATE,
                          OrigineGeographique VARCHAR(50),
                          DateInscriptionBenevole DATE,              -- (au lieu de VARCHAR)
                          DomaineIntervention VARCHAR(50),
                          Email VARCHAR(100) NOT NULL UNIQUE,
                          Password VARCHAR(255) NULL,
                          PRIMARY KEY (IdBenevole)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Evenement
CREATE TABLE Evenement (
                           IdEvenement INT NOT NULL AUTO_INCREMENT,
                           NomEvenement VARCHAR(50) NOT NULL,
                           TypeEvenement VARCHAR(50),
                           DateEvenement DATE,
                           HeureEvenement TIME,
                           LienMediaEvenement VARCHAR(255),            -- 50 est souvent trop court pour un lien
                           PRIMARY KEY (IdEvenement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Financement
CREATE TABLE Financement (
                             IdFinancement INT NOT NULL AUTO_INCREMENT,
                             MontantFinancement DECIMAL(10,2),           -- (au lieu de VARCHAR)
                             TypeFinancement VARCHAR(50),
                             AnneeFinancement YEAR,                      -- (au lieu de DATE)
                             UsagePrevu VARCHAR(50),
                             Financeur VARCHAR(50),
                             PRIMARY KEY (IdFinancement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Presse
CREATE TABLE Presse (
                        IdPresse INT NOT NULL AUTO_INCREMENT,
                        TitrePresse VARCHAR(50) NOT NULL,
                        ResumePresse VARCHAR(255),                  -- 50 très court pour un résumé
                        AuteurPresse VARCHAR(50),
                        DateHeurePublication DATETIME,
                        Statut VARCHAR(50),
                        LienSource VARCHAR(255),
                        Fichier VARCHAR(100),
                        IdEvenement INT,
                        PRIMARY KEY (IdPresse),
                        CONSTRAINT fk_presse_evenement
                            FOREIGN KEY (IdEvenement) REFERENCES Evenement(IdEvenement)
                                ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Mission
CREATE TABLE Mission (
                         IdMission INT NOT NULL AUTO_INCREMENT,
                         TitreMission VARCHAR(50) NOT NULL,
                         DescriptionMission VARCHAR(150),
                         CategorieMission VARCHAR(50),
                         LieuMission VARCHAR(50),
                         DateHeureDebut DATETIME,
                         DateHeureFin DATETIME,
                         NbBenevolesAttendus INT,
                         MaterielNecessaire VARCHAR(200),
                         IdPresse INT,
                         PRIMARY KEY (IdMission),
                         CONSTRAINT fk_mission_presse
                             FOREIGN KEY (IdPresse) REFERENCES Presse(IdPresse)
                                 ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Partenaire
CREATE TABLE Partenaire (
                            IdPartenaire INT NOT NULL AUTO_INCREMENT,
                            NomPartenaire VARCHAR(50) NOT NULL,
                            PrenomPartenaire VARCHAR(50),
                            TypePartenaire VARCHAR(50),
                            TypeSoutienPartenaire VARCHAR(50),          -- orthographe "Soutien"
                            ContactPrincipalPartenaire VARCHAR(50),
                            IdFinancement INT NOT NULL,
                            PRIMARY KEY (IdPartenaire),
                            CONSTRAINT fk_partenaire_financement
                                FOREIGN KEY (IdFinancement) REFERENCES Financement(IdFinancement)
                                    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) Region
-- NOTE: dans ton modèle original, Region dépend de Benevole/Evenement/Mission (3 FK obligatoires)
-- C'est inhabituel (une région "contient" plusieurs bénévoles/événements/missions), mais je garde ton choix.
CREATE TABLE Region (
                        IdRegion INT NOT NULL AUTO_INCREMENT,
                        NomRegion VARCHAR(50) NOT NULL,
                        TypeRegion VARCHAR(50),
                        AdressePostale VARCHAR(100),
                        IdBenevole INT NOT NULL,
                        IdEvenement INT NOT NULL,
                        IdMission INT NOT NULL,
                        PRIMARY KEY (IdRegion),
                        CONSTRAINT fk_region_benevole
                            FOREIGN KEY (IdBenevole) REFERENCES Benevole(IdBenevole)
                                ON DELETE RESTRICT ON UPDATE CASCADE,
                        CONSTRAINT fk_region_evenement
                            FOREIGN KEY (IdEvenement) REFERENCES Evenement(IdEvenement)
                                ON DELETE RESTRICT ON UPDATE CASCADE,
                        CONSTRAINT fk_region_mission
                            FOREIGN KEY (IdMission) REFERENCES Mission(IdMission)
                                ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) Participer (table de liaison Mission <-> Benevole)
CREATE TABLE Participer (
                            IdMission INT NOT NULL,
                            IdBenevole INT NOT NULL,
                            RoleBenevole VARCHAR(50),
                            Duree TIME,
                            Commentaire VARCHAR(255),
                            PRIMARY KEY (IdMission, IdBenevole),
                            CONSTRAINT fk_participer_mission
                                FOREIGN KEY (IdMission) REFERENCES Mission(IdMission)
                                    ON DELETE CASCADE ON UPDATE CASCADE,
                            CONSTRAINT fk_participer_benevole
                                FOREIGN KEY (IdBenevole) REFERENCES Benevole(IdBenevole)
                                    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9) Assister (Benevole <-> Evenement)
CREATE TABLE Assister (
                          IdBenevole INT NOT NULL,
                          IdEvenement INT NOT NULL,
                          Role VARCHAR(50),
                          EstPresent BOOLEAN NOT NULL DEFAULT 0,      -- (au lieu de LOGICAL)
                          PRIMARY KEY (IdBenevole, IdEvenement),
                          CONSTRAINT fk_assister_benevole
                              FOREIGN KEY (IdBenevole) REFERENCES Benevole(IdBenevole)
                                  ON DELETE CASCADE ON UPDATE CASCADE,
                          CONSTRAINT fk_assister_evenement
                              FOREIGN KEY (IdEvenement) REFERENCES Evenement(IdEvenement)
                                  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10) Soutenir (Mission <-> Partenaire)
CREATE TABLE Soutenir (
                          IdMission INT NOT NULL,
                          IdPartenaire INT NOT NULL,
                          TypeSoutien VARCHAR(50),
                          MontantSoutien DECIMAL(10,2),               -- (au lieu de VARCHAR)
                          PRIMARY KEY (IdMission, IdPartenaire),
                          CONSTRAINT fk_soutenir_mission
                              FOREIGN KEY (IdMission) REFERENCES Mission(IdMission)
                                  ON DELETE CASCADE ON UPDATE CASCADE,
                          CONSTRAINT fk_soutenir_partenaire
                              FOREIGN KEY (IdPartenaire) REFERENCES Partenaire(IdPartenaire)
                                  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11) Implante (Partenaire <-> Region)
CREATE TABLE Implante (
                          IdPartenaire INT NOT NULL,
                          IdRegion INT NOT NULL,
                          DateDebutImplante DATE,
                          DateFinImplante DATE,                       -- (au lieu de VARCHAR)
                          PRIMARY KEY (IdPartenaire, IdRegion),
                          CONSTRAINT fk_implante_partenaire
                              FOREIGN KEY (IdPartenaire) REFERENCES Partenaire(IdPartenaire)
                                  ON DELETE CASCADE ON UPDATE CASCADE,
                          CONSTRAINT fk_implante_region
                              FOREIGN KEY (IdRegion) REFERENCES Region(IdRegion)
                                  ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12) Localiser (Presse <-> Region)
CREATE TABLE Localiser (
                           IdPresse INT NOT NULL,
                           IdRegion INT NOT NULL,
                           PRIMARY KEY (IdPresse, IdRegion),
                           CONSTRAINT fk_localiser_presse
                               FOREIGN KEY (IdPresse) REFERENCES Presse(IdPresse)
                                   ON DELETE CASCADE ON UPDATE CASCADE,
                           CONSTRAINT fk_localiser_region
                               FOREIGN KEY (IdRegion) REFERENCES Region(IdRegion)
                                   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS CompteAuth_EGEE (
                                               IdCompte INT AUTO_INCREMENT PRIMARY KEY,
                                               Email VARCHAR(255) NOT NULL UNIQUE,
                                               MdpHash VARCHAR(255) NOT NULL,
                                               Role ENUM('USER','ADMIN') NOT NULL DEFAULT 'USER',
                                               IdBenevole INT NULL,
                                               CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                               CONSTRAINT fk_compte_benevole
                                                   FOREIGN KEY (IdBenevole) REFERENCES Benevole(IdBenevole)
                                                       ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE Benevole
    ADD Role ENUM('USER','ADMIN') NOT NULL DEFAULT 'USER';

/*UPDATE Benevole SET Role = 'USER' WHERE Role IS NULL;*/

UPDATE Benevole SET Role='ADMIN' WHERE Email='john@demo.com';

SELECT Email, Role FROM Benevole;



