SET @startDateInclusive = 20200422;
SET @endDateExclusive = 20210422;

DROP TEMPORARY TABLE IF EXISTS solo_flights;
CREATE TEMPORARY TABLE solo_flights AS 
SELECT m.displayname as displayname1, MAX(localdate) as last_solo_flight, count(*) as solo_flights, m.id as id1,
    SUM((land-start) / 3600000) as solo_hours
FROM gliding.flights f JOIN gliding.members m ON f.pic = m.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND f.org = 1  and status <= 4
      and f.p2 is null 
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

DROP TEMPORARY TABLE IF EXISTS p1_with_p2_flights;
CREATE TEMPORARY TABLE p1_with_p2_flights AS 
SELECT m.displayname as displayname3, MAX(localdate) as last_p1_with_p2_flight, count(*) as p1_with_p2_flights, m.id as id3,
    SUM((land-start) / 3600000) as p1_with_p2_hours
FROM gliding.flights f JOIN gliding.members m ON f.pic = m.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND f.org = 1  and status <= 4
      and f.p2 is not null 
GROUP BY m.id
order by displayname3;

DROP TEMPORARY TABLE IF EXISTS full_join_part_1;
CREATE TEMPORARY TABLE full_join_part_1 AS 
SELECT *
FROM solo_flights 	
	LEFT JOIN flights_as_P2 		ON solo_flights.id1 = flights_as_P2.id2
	LEFT JOIN p1_with_p2_flights 	ON solo_flights.id1 = p1_with_p2_flights.id3
; 

DROP TEMPORARY TABLE IF EXISTS full_join_part_2;
CREATE TEMPORARY TABLE full_join_part_2 AS 
SELECT *
FROM flights_as_P2 
	LEFT JOIN solo_flights 			ON solo_flights.id1 = flights_as_P2.id2				
	LEFT JOIN p1_with_p2_flights 	ON flights_as_P2.id2 = p1_with_p2_flights.id3
WHERE solo_flights.id1 IS NULL
;

DROP TEMPORARY TABLE IF EXISTS full_join_part_3;
CREATE TEMPORARY TABLE full_join_part_3 AS 
SELECT *
FROM p1_with_p2_flights 
	LEFT JOIN solo_flights 			ON solo_flights.id1 = p1_with_p2_flights.id3
	LEFT JOIN flights_as_P2 		ON flights_as_P2.id2 = p1_with_p2_flights.id3
WHERE solo_flights.id1 IS NULL AND flights_as_P2.id2 is NULL
;


SELECT (CASE WHEN displayname1 IS NULL THEN 
			(CASE WHEN displayname2 IS NULL THEN displayname3 ELSE displayname2 END) ELSE displayname1 END) as displayname,
	COALESCE(DATE_FORMAT(last_p1_with_p2_flight,"%Y-%m-%d"),'') as 'last flight as P1 with a P2',
    COALESCE(p1_with_p2_flights,'') as '# flights as P1 with a P2',
    COALESCE(p1_with_p2_hours,'') as '# hours as P1 with a P2',            
	COALESCE(DATE_FORMAT(last_solo_flight,"%Y-%m-%d"),'') as 'last solo flight',
    COALESCE(solo_flights,'') as '# solo flights',
    COALESCE(solo_hours,'') as '# solo hours',
    COALESCE(DATE_FORMAT(last_flight_as_P2,"%Y-%m-%d"),'') as 'last flight as P2',
    COALESCE(flights_as_P2,'') as '# flights as P2',
    COALESCE(hours_as_P2,'') as '# hours as P2'
from
(
SELECT * FROM full_join_part_1
UNION ALL
SELECT * FROM full_join_part_2
UNION ALL
SELECT * FROM full_join_part_3
)a
order by displayname
;
