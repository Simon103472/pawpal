-- ============================================================
-- PawPal - Pet Care Planner
-- Databasebestand: 6 tabellen + testgegevens
--
-- Gebruik: maak in phpMyAdmin eerst een lege database 'pawpal'
-- (collatie utf8mb4_unicode_ci), klik die aan en importeer dit bestand.
--
-- LET OP: dit bestand verwijdert eerst de bestaande PawPal-tabellen.
-- Opnieuw importeren zet de database dus terug naar de testgegevens.
-- ============================================================

SET NAMES utf8mb4;

-- Eerst de tabellen verwijderen die naar andere tabellen verwijzen,
-- anders houdt een foreign key het verwijderen tegen.
DROP TABLE IF EXISTS wijzigingslog;
DROP TABLE IF EXISTS reserveringen;
DROP TABLE IF EXISTS capaciteit;
DROP TABLE IF EXISTS verzorging;
DROP TABLE IF EXISTS dieren;
DROP TABLE IF EXISTS gebruikers;

-- ------------------------------------------------------------
-- 1. GEBRUIKERS: eigenaren en medewerkers (TE-03)
-- locatie is alleen gevuld bij medewerkers (nodig voor FE-07).
-- ------------------------------------------------------------
CREATE TABLE gebruikers (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam            VARCHAR(100) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    wachtwoord_hash VARCHAR(255) NOT NULL,
    rol             ENUM('eigenaar', 'medewerker') NOT NULL,
    locatie         ENUM('Noord', 'Zuid') NULL,
    aangemaakt_op   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniek_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. DIEREN: ieder dier hoort bij precies een eigenaar (FE-01)
-- ------------------------------------------------------------
CREATE TABLE dieren (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    eigenaar_id   INT UNSIGNED NOT NULL,
    naam          VARCHAR(100) NOT NULL,
    diersoort     ENUM('hond', 'kat', 'konijn') NOT NULL,
    ras           VARCHAR(100) NULL,
    geboortedatum DATE NULL,
    aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_dieren_eigenaar
        FOREIGN KEY (eigenaar_id) REFERENCES gebruikers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. VERZORGING: een rij per dier (1:1), daarom UNIQUE op dier_id (FE-02)
-- Wordt het dier verwijderd, dan verdwijnt de verzorging mee (CASCADE).
-- ------------------------------------------------------------
CREATE TABLE verzorging (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dier_id        INT UNSIGNED NOT NULL,
    voeding        TEXT NULL,
    contactpersoon VARCHAR(150) NULL,
    bijzonderheden TEXT NULL,
    bijgewerkt_op  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniek_dier (dier_id),
    CONSTRAINT fk_verzorging_dier
        FOREIGN KEY (dier_id) REFERENCES dieren (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. CAPACITEIT: aantal plekken per datum + dienst + locatie + diersoort (FE-04)
-- De UNIQUE-regel voorkomt dat dezelfde combinatie twee keer bestaat (TE-05).
-- ------------------------------------------------------------
CREATE TABLE capaciteit (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    datum        DATE NOT NULL,
    dienst       ENUM('ochtend', 'middag') NOT NULL,
    locatie      ENUM('Noord', 'Zuid') NOT NULL,
    diersoort    ENUM('hond', 'kat', 'konijn') NOT NULL,
    max_plaatsen SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniek_capaciteit (datum, dienst, locatie, diersoort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. RESERVERINGEN: koppelt een dier aan een capaciteitsrij (FE-03, FE-05)
-- UNIQUE (dier_id, capaciteit_id): een dier kan dezelfde opvangdienst
-- maar een keer reserveren.
-- ------------------------------------------------------------
CREATE TABLE reserveringen (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    dier_id       INT UNSIGNED NOT NULL,
    capaciteit_id INT UNSIGNED NOT NULL,
    status        ENUM('aangevraagd', 'goedgekeurd', 'afgewezen') NOT NULL DEFAULT 'aangevraagd',
    aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniek_dier_capaciteit (dier_id, capaciteit_id),
    CONSTRAINT fk_reserveringen_dier
        FOREIGN KEY (dier_id) REFERENCES dieren (id) ON DELETE CASCADE,
    CONSTRAINT fk_reserveringen_capaciteit
        FOREIGN KEY (capaciteit_id) REFERENCES capaciteit (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. WIJZIGINGSLOG: wie heeft wat wanneer gewijzigd (FE-08)
-- Wordt het dier verwijderd, dan wordt dier_id leeg (SET NULL),
-- maar dier_naam blijft staan zodat de logregel leesbaar blijft.
-- ------------------------------------------------------------
CREATE TABLE wijzigingslog (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    gebruiker_id  INT UNSIGNED NOT NULL,
    dier_id       INT UNSIGNED NULL,
    dier_naam     VARCHAR(100) NOT NULL,
    veld          VARCHAR(50) NOT NULL,
    oude_waarde   TEXT NULL,
    nieuwe_waarde TEXT NULL,
    aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_gebruiker
        FOREIGN KEY (gebruiker_id) REFERENCES gebruikers (id),
    CONSTRAINT fk_log_dier
        FOREIGN KEY (dier_id) REFERENCES dieren (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TESTGEGEVENS (geen echte personen)
-- Het wachtwoord van alle vier de accounts is: Test1234!
-- In de database staat alleen de hash, nooit het wachtwoord zelf.
-- ============================================================
INSERT INTO gebruikers (id, naam, email, wachtwoord_hash, rol, locatie) VALUES
(1, 'Medewerker Noord', 'noord@pawpal.test', '$2y$10$mTTHEeN1Se.00HpiwMpWGOsrJBOKUihPrzomFVY32bEaexF0gsyD.', 'medewerker', 'Noord'),
(2, 'Medewerker Zuid',  'zuid@pawpal.test',  '$2y$10$8qf4wQSgZELkIG42y8lUn.4oPQs2mKghxxx8oqwScT5kiaIl6s4jW', 'medewerker', 'Zuid'),
(3, 'Eva de Tester',    'eva@pawpal.test',   '$2y$10$UWUIjg3geMmsV2O5I1UjP.qYwgWrdsdTkJYI47VhpyZ0U0ZM78Xnm', 'eigenaar',   NULL),
(4, 'Tom de Tester',    'tom@pawpal.test',   '$2y$10$cK6IX5zz2zAzGIp2ZUmNh.Ijx2DNl1m9pTKB4J28Q2Cywhtw.9XH.', 'eigenaar',   NULL);

INSERT INTO dieren (id, eigenaar_id, naam, diersoort, ras, geboortedatum) VALUES
(1, 3, 'Max',     'hond',   'Labrador',          '2021-04-12'),
(2, 3, 'Luna',    'kat',    'Europese korthaar', '2019-09-03'),
(3, 4, 'Flappie', 'konijn', 'Hangoor',           '2023-01-20');

INSERT INTO verzorging (dier_id, voeding, contactpersoon, bijzonderheden) VALUES
(1, 'Twee keer per dag 150 gram brokken', 'Eva de Tester, 06-00000001', 'Is bang voor onweer'),
(2, 'Natvoer in de ochtend',              'Eva de Tester, 06-00000001', 'Krijgt dagelijks een pil'),
(3, 'Hooi en verse groente',              'Tom de Tester, 06-00000002', NULL);

-- De datums zijn berekend vanaf de dag van importeren (CURDATE() = vandaag),
-- zodat de testgegevens altijd in de toekomst liggen.
-- De laatste rij heeft maar 1 plek: daarmee test je "de opvang is vol".
INSERT INTO capaciteit (datum, dienst, locatie, diersoort, max_plaatsen) VALUES
(DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'ochtend', 'Noord', 'hond',   3),
(DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'middag',  'Noord', 'kat',    2),
(DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'ochtend', 'Zuid',  'konijn', 2),
(DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'ochtend', 'Zuid',  'hond',   1);
