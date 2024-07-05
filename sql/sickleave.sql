INSERT INTO entitleddays (contract, employee, overtime, startdate, enddate, type, days)
SELECT 
    NULL AS contract,
    u.id AS employee,
    NULL AS overtime,
    CONCAT(YEAR(CURDATE()), '-01-01') AS startdate,
    CONCAT(YEAR(CURDATE()), '-12-31') AS enddate,
    2 AS type,
    14 AS days
FROM 
    users u
LEFT JOIN 
    entitleddays ed ON u.id = ed.employee 
    AND ed.type = 2
    AND ed.startdate = CONCAT(YEAR(CURDATE()), '-01-01')
    AND ed.enddate = CONCAT(YEAR(CURDATE()), '-12-31')
WHERE 
    TIMESTAMPDIFF(YEAR, u.datehired, CURDATE()) < 2
    AND u.active = 1
    AND ed.id IS NULL

UNION ALL

SELECT 
    NULL AS contract,
    u.id AS employee,
    NULL AS overtime,
    CONCAT(YEAR(CURDATE()), '-01-01') AS startdate,
    CONCAT(YEAR(CURDATE()), '-12-31') AS enddate,
    2 AS type,
    18 AS days
FROM 
    users u
LEFT JOIN 
    entitleddays ed ON u.id = ed.employee 
    AND ed.type = 2
    AND ed.startdate = CONCAT(YEAR(CURDATE()), '-01-01')
    AND ed.enddate = CONCAT(YEAR(CURDATE()), '-12-31')
WHERE 
    TIMESTAMPDIFF(YEAR, u.datehired, CURDATE()) >= 2
    AND TIMESTAMPDIFF(YEAR, u.datehired, CURDATE()) < 5
    AND u.active = 1
    AND ed.id IS NULL

UNION ALL

SELECT 
    NULL AS contract,
    u.id AS employee,
    NULL AS overtime,
    CONCAT(YEAR(CURDATE()), '-01-01') AS startdate,
    CONCAT(YEAR(CURDATE()), '-12-31') AS enddate,
    2 AS type,
    22 AS days
FROM 
    users u
LEFT JOIN 
    entitleddays ed ON u.id = ed.employee 
    AND ed.type = 2
    AND ed.startdate = CONCAT(YEAR(CURDATE()), '-01-01')
    AND ed.enddate = CONCAT(YEAR(CURDATE()), '-12-31')
WHERE 
    TIMESTAMPDIFF(YEAR, u.datehired, CURDATE()) >= 5
    AND u.active = 1
    AND ed.id IS NULL;
