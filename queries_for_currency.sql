SET @startDateInclusive = 20210122;
SET @endDateExclusive = 20210422;

DROP TEMPORARY TABLE IF EXISTS raw_data;
CREATE TEMPORARY TABLE raw_data AS 
SELECT 
	m.displayname
    
    ,MAX(CASE WHEN f.p2 is null THEN localdate ELSE NULL END) as last_solo_flight 
    ,SUM(CASE WHEN f.p2 is null THEN 1 ELSE NULL END) as solo_flights 
    ,SUM(CASE WHEN f.p2 is null THEN ((land-start) / 3600000) ELSE NULL END ) as solo_hours
    
    ,MAX(CASE WHEN f.p2 = m.id THEN localdate ELSE NULL END) as last_flight_as_P2 
    ,SUM(CASE WHEN f.p2 = m.id THEN 1 ELSE NULL END) as flights_as_P2 
    ,SUM(CASE WHEN f.p2 = m.id THEN ((land-start) / 3600000) ELSE NULL END ) as hours_as_P2
    
    ,MAX(CASE WHEN f.pic = m.id and f.p2 is not null THEN localdate ELSE NULL END) as last_p1_with_p2_flight 
    ,SUM(CASE WHEN f.pic = m.id and f.p2 is not null THEN 1 ELSE NULL END) as p1_with_p2_flights 
    ,SUM(CASE WHEN f.pic = m.id and f.p2 is not null THEN ((land-start) / 3600000) ELSE NULL END ) as p1_with_p2_hours   
FROM gliding.flights f JOIN gliding.members m ON (f.pic = m.id OR f.p2 = m.id) 
WHERE 	
	localdate >= @startDateInclusive AND localdate < @endDateExclusive AND f.org = 1  AND status <= 4    
GROUP BY m.id
ORDER BY displayname;

SELECT
	displayname,
	COALESCE(DATE_FORMAT(last_solo_flight,"%Y-%m-%d"),'') as 'last solo flight',
    COALESCE(solo_flights,'') as '# solo flights',
    COALESCE(solo_hours,'') as '# solo hours',    
	COALESCE(DATE_FORMAT(last_p1_with_p2_flight,"%Y-%m-%d"),'') as 'last flight as P1 with a P2',
    COALESCE(p1_with_p2_flights,'') as '# flights as P1 with a P2',
    COALESCE(p1_with_p2_hours,'') as '# hours as P1 with a P2',            
    COALESCE(DATE_FORMAT(last_flight_as_P2,"%Y-%m-%d"),'') as 'last flight as P2',
    COALESCE(flights_as_P2,'') as '# flights as P2',
    COALESCE(hours_as_P2,'') as '# hours as P2'
from raw_data
order by displayname
;
