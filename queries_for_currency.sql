SET @startDateInclusive = 20200422;
SET @endDateExclusive = 20210422;

DROP TEMPORARY TABLE IF EXISTS flights_as_PIC;
CREATE TEMPORARY TABLE flights_as_PIC AS 
SELECT m.displayname as displayname1, MAX(localdate) as last_flight_as_PIC, count(*) as flights_as_PIC, m.id as id1,
    SUM((land-start) / 3600000) as hours_as_PIC
FROM gliding.flights f JOIN gliding.members m ON f.pic = m.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND f.org = 1  and status <= 4
GROUP BY m.id
order by displayname1;

DROP TEMPORARY TABLE IF EXISTS flights_as_P2;
CREATE TEMPORARY TABLE flights_as_P2 AS 
SELECT m.displayname as displayname2, MAX(localdate) as last_flight_as_P2, count(*) as flights_as_P2, m.id as id2,
    SUM((land-start) / 3600000) as hours_as_P2
FROM gliding.flights f JOIN gliding.members m ON f.p2 = m.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND f.org = 1  and status <= 4
GROUP BY m.id
order by displayname2;

DROP TEMPORARY TABLE IF EXISTS left_join;
CREATE TEMPORARY TABLE left_join AS 
SELECT * FROM flights_as_PIC
LEFT OUTER JOIN flights_as_P2
ON flights_as_PIC.id1 = flights_as_P2.id2;

DROP TEMPORARY TABLE IF EXISTS right_join;
CREATE TEMPORARY TABLE right_join AS 
SELECT * FROM flights_as_PIC
RIGHT OUTER JOIN flights_as_P2
ON flights_as_PIC.id1 = flights_as_P2.id2;

SELECT (CASE WHEN displayname1 IS NULL THEN displayname2 ELSE displayname1 END) as displayname,
	COALESCE(DATE_FORMAT(last_flight_as_PIC,"%Y-%m-%d"),'') as 'last flight as P1/PIC',
    COALESCE(flights_as_PIC,'') as '# flight as P1/PIC',
    COALESCE(hours_as_PIC,'') as '# hours as P1/PIC',
    COALESCE(DATE_FORMAT(last_flight_as_P2,"%Y-%m-%d"),'') as 'last flight as P2',
    COALESCE(flights_as_P2,'') as '# flights as P2',
    COALESCE(hours_as_P2,'') as '# hours as P2'
from
(
SELECT *
FROM left_join 
UNION ALL
SELECT *
FROM right_join
WHERE id1 IS NULL
)a
order by displayname;