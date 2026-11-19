-- Klassenwerte aus Spezifikation Kapitel 4.1 für Schuljahr 1 (2026/2027).
-- Ein Gruppenwert gilt für die Klasse mit genau diesem Code und für alle Einzelklassen
-- "<Gruppe>.<Buchstabe>" (z. B. BEGS1 -> BEGS1.a, BEGS1.b; P1 -> P1, P1.a, P1.b).
-- Nicht abgedeckt, bleiben 0: BETZ, BG11GS, BG12GS, FOG11, FOG12, FP1T, FP3T, SO2T, SO3T.
-- Nach dem Import von praktikumsbetreuung.sql einmal ausführen. Mehrfaches Ausführen ist unschädlich.

UPDATE class_rules
INNER JOIN classes ON classes.id = class_rules.class_id
INNER JOIN (
              SELECT '11GS' AS grp, 1.034 AS faktor, 4 AS gesamt, 0 AS hj1, 4 AS hj2
    UNION ALL SELECT 'BEGS1', 0.25,  3,       0,      2
    UNION ALL SELECT 'BEGS2', 0.25,  4,       2,      2
    UNION ALL SELECT 'BESS1', 0.25,  2,       0,      2
    UNION ALL SELECT 'BEST1', 0.25,  3,       0,      2
    UNION ALL SELECT 'BEST2', 0.25,  4,       2,      2
    UNION ALL SELECT 'BFF',   0.4,   4,       2,      2
    UNION ALL SELECT 'FP1',   0.5,   5.195,   5.195,  0
    UNION ALL SELECT 'FP2',   0.5,   10.389,  0,      10.389
    UNION ALL SELECT 'FPT',   0.5,   5.1948,  2.5974, 2.5974
    UNION ALL SELECT 'H1',    0.4,   4,       0,      4
    UNION ALL SELECT 'H2',    0,     0,       0,      0
    UNION ALL SELECT 'HEP1',  0.5,   12.987,  4.5,    8.5
    UNION ALL SELECT 'HEP2',  0.5,   12.987,  5,      9
    UNION ALL SELECT 'HEP3',  0.5,   12.987,  9,      4
    UNION ALL SELECT 'HHB',   0.4,   4,       4,      0
    UNION ALL SELECT 'KAI2',  0.25,  4,       4,      0
    UNION ALL SELECT 'P1',    1,     15.645,  7.8225, 7.8225
    UNION ALL SELECT 'P2',    1,     15.645,  7.8225, 7.8225
    UNION ALL SELECT 'P3',    1,     15.645,  7.8225, 7.8225
    UNION ALL SELECT 'PA1',   0.5,   12,      6,      6
    UNION ALL SELECT 'PA2',   0.5,   12,      6,      6
    UNION ALL SELECT 'PR',    0.4,   4,       0,      4
    UNION ALL SELECT 'SO1',   0.5,   7.9,     7.9,    0
    UNION ALL SELECT 'SO2',   0.5,   13.9,    6.95,   6.95
    UNION ALL SELECT 'SOT',   0.5,   6.95,    3.48,   3.48
) AS werte
    ON classes.code = werte.grp
    OR classes.code LIKE CONCAT(werte.grp, '.%')
SET class_rules.supervision_factor = werte.faktor,
    class_rules.weeks_total        = werte.gesamt,
    class_rules.weeks_half_year_1  = werte.hj1,
    class_rules.weeks_half_year_2  = werte.hj2
WHERE class_rules.school_year_id = 1;
