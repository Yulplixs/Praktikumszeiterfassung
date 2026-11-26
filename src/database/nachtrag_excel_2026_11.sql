-- Nachtrag aus der Excel-Vorlage des Admins (Praktikumsbetreuung 1. und 2. Halbjahr), Schuljahr 1 (2026/2027).
-- Nach praktikumsbetreuung.sql und class_rules_4_1.sql einmal ausführen. Mehrfaches Ausführen ist unschädlich.
--
-- 1. Sechs Kürzel aus den Excel-Blättern fehlen in der Lehrkräfteliste. Ihre 17 Zeilen fehlten deshalb im Dump.
--    Die Lehrkräfte werden nur mit Kürzel angelegt (Namen unbekannt, auf der Seite Lehrkräfte nachtragen).
-- 2. Die 17 Zeilen werden mit den Klassenwerten aus class_rules berechnet (Kap. 4.2, 3 Nachkommastellen).
-- 3. weeks_snapshot einiger HJ2-Zeilen wurde aus der Excel-Anzeige übernommen (7,82 / 2,6 / 10,39).
--    Die Stunden wurden aber mit den exakten Wochen berechnet. Nur der Snapshot wird korrigiert.

START TRANSACTION;

INSERT INTO teachers (code, surname, firstname, title)
SELECT neu.code, '', '', ''
FROM (
              SELECT 'FRH' AS code
    UNION ALL SELECT 'SRK'
    UNION ALL SELECT 'GÖB'
    UNION ALL SELECT 'KAP'
    UNION ALL SELECT 'ASL'
    UNION ALL SELECT 'HEK'
) AS neu
WHERE NOT EXISTS (SELECT 1 FROM teachers WHERE teachers.code = neu.code);

INSERT INTO supervision_entries (school_year_id, half_year, class_id, teacher_id, student_count,
                                 factor_snapshot, weeks_snapshot, hours_per_week, hours_half_year,
                                 created_by, updated_by, created_at, updated_at)
SELECT 1,
       zeile.hj,
       classes.id,
       teachers.id,
       zeile.schueler,
       class_rules.supervision_factor,
       IF(zeile.hj = 1, class_rules.weeks_half_year_1, class_rules.weeks_half_year_2),
       class_rules.supervision_factor * zeile.schueler,
       ROUND(class_rules.supervision_factor * zeile.schueler
                 * IF(zeile.hj = 1, class_rules.weeks_half_year_1, class_rules.weeks_half_year_2) / 40, 3),
       1, NULL, NOW(), NULL
FROM (
              SELECT 1 AS hj, 'P1' AS klasse, 'FRH' AS lk, 4 AS schueler
    UNION ALL SELECT 1, 'FPT',  'SRK', 7
    UNION ALL SELECT 1, 'FP1',  'SRK', 3
    UNION ALL SELECT 1, 'HEP1', 'GÖB', 5
    UNION ALL SELECT 1, 'HEP2', 'FRH', 4
    UNION ALL SELECT 1, 'HEP3', 'GÖB', 3
    UNION ALL SELECT 1, 'HHB',  'KAP', 1
    UNION ALL SELECT 1, 'PA2',  'FRH', 3
    UNION ALL SELECT 1, 'PA1',  'FRH', 4
    UNION ALL SELECT 1, 'SOT',  'ASL', 4
    UNION ALL SELECT 2, 'P1',   'FRH', 3
    UNION ALL SELECT 2, 'FP2',  'GÖB', 5
    UNION ALL SELECT 2, 'FP2',  'SRK', 2
    UNION ALL SELECT 2, 'HEP2', 'GÖB', 2
    UNION ALL SELECT 2, 'HEP3', 'GÖB', 3
    UNION ALL SELECT 2, 'PA1',  'FRH', 4
    UNION ALL SELECT 2, '11GS', 'HEK', 2
) AS zeile
INNER JOIN classes ON classes.code = zeile.klasse
INNER JOIN teachers ON teachers.code = zeile.lk
INNER JOIN class_rules ON class_rules.class_id = classes.id AND class_rules.school_year_id = 1
WHERE NOT EXISTS (
    SELECT 1 FROM supervision_entries vorhanden
    WHERE vorhanden.school_year_id = 1
      AND vorhanden.half_year = zeile.hj
      AND vorhanden.class_id = classes.id
      AND vorhanden.teacher_id = teachers.id
);

UPDATE supervision_entries
INNER JOIN class_rules
    ON class_rules.class_id = supervision_entries.class_id
   AND class_rules.school_year_id = supervision_entries.school_year_id
SET supervision_entries.weeks_snapshot = IF(supervision_entries.half_year = 1,
                                            class_rules.weeks_half_year_1, class_rules.weeks_half_year_2)
WHERE supervision_entries.school_year_id = 1
  AND supervision_entries.weeks_snapshot IN (7.82, 2.6, 10.39)
  AND ROUND(IF(supervision_entries.half_year = 1,
               class_rules.weeks_half_year_1, class_rules.weeks_half_year_2), 2) = supervision_entries.weeks_snapshot;

COMMIT;
