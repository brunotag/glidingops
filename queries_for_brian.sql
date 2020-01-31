SET @startDateInclusive = 20190701;
SET @endDateExclusive = 20190801;

/*** BEGIN							***/
/*** Utilisation + to solo quality 	***/
/*** BEGIN							***/
SELECT count(distinct localdate) as Flying_days
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1;

SELECT count(*) as Total_flights
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1;

SELECT 
	localdate, 
	count(*) as Flights_per_day, 
    /*SUM(land - start) div 3600000 as hours, 
    CAST((SUM(land - start) % 3600000)/60000 as unsigned) as minutes,*/
    (MAX(start) - MIN(start)) div 3600000 as hours_of_service,
    CAST(((MAX(start) - MIN(start)) % 3600000)/60000 as unsigned) as minutes_of_service,
    SUM(CASE WHEN launchtype = 2 then 1 else 0 end) as N_of_winch_launches,
    SUM(CASE WHEN launchtype = 2 then 1 else 0 end) / ((MAX(start) - MIN(start)) / 3600000) as N_of_winch_launches_per_hour_of_service
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
GROUP BY localdate;

SELECT lt.name, count(*) as count
FROM gliding.flights f JOIN gliding.launchtypes lt on f.launchtype = lt.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
GROUP BY lt.name;

/*** END							***/
/*** Utilisation + to solo quality 	***/
/*** END							***/

SET @club_k13 = 'GFY,GFN';
SET @club_DGs = 'GPJ,GGR';
SET @club_singles = 'GNB,GMB';

/*** BEGIN							***/
/*** Quality = pilots stay up	 	***/
/*** BEGIN							***/

SELECT 	(CASE 
			WHEN FIND_IN_SET(TRIM(glider),@club_k13) > 0  THEN 'club_k13_training' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option >= 3 AND billing_option <= 5))  THEN 'club_DGs_trial' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option < 3 OR billing_option > 5))  THEN 'club_DGs_training' 
			WHEN FIND_IN_SET(TRIM(glider),@club_singles) >0 THEN 'club_singles' 
			ELSE 'private_owners' 
		END) as category_of_customer,
	 count(*) as Winch_Launches,
	SUM(land - start) div 3600000 as hours, CAST((SUM(land - start) % 3600000)/60000 as unsigned) as minutes,
    (SUM(land - start) / 3600000) / SUM(CASE WHEN launchtype = 2 then 1 else 0 end)  as Hours_of_flight_per_launch
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = 2  AND org = 1
GROUP BY category_of_customer;
/*** END							***/
/*** Quality = pilots stay up	 	***/
/*** END							***/


SELECT *
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1;
