SET @startDateInclusive = 20201201;
SET @endDateExclusive = 20210101;

/*** BEGIN							***/
/*** Utilisation + to solo quality 	***/
/*** BEGIN							***/

/*** Total flights ***/
SELECT 
	count(*) as Total_flights, 
    count(distinct localdate) as Flying_days
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1;


/*** Service levels ***/
SELECT 
    SUM(Raw_hours_of_service) as total_Raw_hours_of_service,
    /*TRUNCATE(SUM(Raw_hours_of_service),0) as total_hours_of_service,
    TRUNCATE(MOD(SUM(Raw_hours_of_service), 1) * 60, 0) as total_minutes_of_service,*/
    /*SUM(N_of_winch_launches_per_day) as total_winch_launches,*/
    AVG(N_of_winch_launches_per_day) as avg_winch_launches_per_day,
    SUM(N_of_winch_launches_per_day) / SUM(Raw_hours_of_service) as avg_winch_launches_per_hour_of_service
FROM(	
	SELECT 
		(MAX(start) - MIN(start))/3600000 as Raw_hours_of_service,
        SUM(CASE WHEN launchtype = 2 then 1 else 0 end) as N_of_winch_launches_per_day
	FROM gliding.flights
	WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
		  AND start > 0
	GROUP BY localdate
) as tmp;


/*** Soaring flights ***/
SELECT 
    SUM(CASE WHEN number_of_flights_exceeding_90_mins >= 2 then 1 else 0 end) as cat_1_soaring_days,
    SUM(CASE WHEN number_of_flights_exceeding_90_mins < 2 AND number_of_flights_between_30_and_90_minutes >= 4 then 1 else 0 end) as cat_2_soaring_days
FROM(	
	SELECT 
		SUM(CASE WHEN CAST((land - start) /60000 as unsigned) >= 90 then 1 else 0 end) as number_of_flights_exceeding_90_mins,
		SUM(CASE WHEN 
			CAST((land - start) /60000 as unsigned) < 90 
			AND
			CAST((land - start) /60000 as unsigned) >= 30
			then 1 else 0 end) as number_of_flights_between_30_and_90_minutes
	FROM gliding.flights
	WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
		  AND start > 0
	GROUP BY localdate
) as tmp;

/**number of launches per type **/

SELECT lt.name, lt.id, count(*) as count
FROM gliding.flights f JOIN gliding.launchtypes lt on f.launchtype = lt.id
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
GROUP BY lt.name, lt.id;


/**analytic query **/

SELECT 
	CAST(localdate as date) as date,
	count(*) as Flights_per_day, 
    /*SUM(land - start) div 3600000 as hours, 
    CAST((SUM(land - start) % 3600000)/60000 as unsigned) as minutes,*/
    (MAX(start) - MIN(start)) div 3600000 as hours_of_service,
    CAST(((MAX(start) - MIN(start)) % 3600000)/60000 as unsigned) as minutes_of_service,
    SUM(CASE WHEN launchtype = 2 then 1 else 0 end) as N_of_winch_launches,
    SUM(CASE WHEN launchtype = 2 then 1 else 0 end) / ((MAX(start) - MIN(start)) / 3600000) as N_of_winch_launches_per_hour_of_service
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND org = 1
	  AND start > 0
GROUP BY localdate;


/*** END							***/
/*** Utilisation + to solo quality 	***/
/*** END							***/

SET @club_k13 = 'GFY,GFN';
SET @club_DGs = 'GPJ,GGR';
SET @club_singles = 'GNB,GMB';

/*** BEGIN							***/
/*** Quality = pilots stay up	 	***/
/*** BEGIN							***/


/* ***** */
/* WINCH */
/* ***** */
SET @LaunchType = 2;
SELECT 	(CASE 
			WHEN FIND_IN_SET(TRIM(glider),@club_k13) > 0  THEN 'club_k13_training' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option >= 3 AND billing_option <= 5))  THEN 'club_DGs_trial' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option < 3 OR billing_option > 5))  THEN 'club_DGs_training' 
			WHEN FIND_IN_SET(TRIM(glider),@club_singles) > 0 THEN 'club_singles' 
			ELSE 'private_owners' 
		END) as category_of_customer,
	 count(*) as Winch_Launches,
	ROUND(SUM(land - start) / 3600000) as hours, 
    (SUM(land - start) / 3600000) div SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end)  as Hours_of_flight_per_launch,
    TRUNCATE(MOD((SUM(land - start) / 3600000) / SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end), 1) * 60, 0) as Minutes_of_flight_per_launch
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = @LaunchType  AND org = 1
	  AND start > 0
GROUP BY category_of_customer;

/* ***** */
/* AEROTOW */
/* ***** */
SET @LaunchType = 1;
SELECT 	(CASE 
			WHEN FIND_IN_SET(TRIM(glider),@club_k13) > 0  THEN 'club_k13_training' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option >= 3 AND billing_option <= 5))  THEN 'club_DGs_trial' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option < 3 OR billing_option > 5))  THEN 'club_DGs_training' 
			WHEN FIND_IN_SET(TRIM(glider),@club_singles) > 0 THEN 'club_singles' 
			ELSE 'private_owners' 
		END) as category_of_customer,
	 count(*) as aerotows,
	ROUND(SUM(land - start) / 3600000) as hours,
    (SUM(land - start) / 3600000) div SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end)  as Hours_of_flight_per_launch,
    TRUNCATE(MOD((SUM(land - start) / 3600000) / SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end), 1) * 60, 0) as Minutes_of_flight_per_launch
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = @LaunchType  AND org = 1
	  AND start > 0
GROUP BY category_of_customer;


/* ***** */
/* SELF LAUNCH */
/* ***** */
SET @LaunchType = 3;
SELECT 	(CASE 
			WHEN FIND_IN_SET(TRIM(glider),@club_k13) > 0  THEN 'club_k13_training' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option >= 3 AND billing_option <= 5))  THEN 'club_DGs_trial' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option < 3 OR billing_option > 5))  THEN 'club_DGs_training' 
			WHEN FIND_IN_SET(TRIM(glider),@club_singles) > 0 THEN 'club_singles' 
			ELSE 'private_owners' 
		END) as category_of_customer,
	 count(*) as Self_Launches,
	ROUND(SUM(land - start) / 3600000) as hours,
    (SUM(land - start) / 3600000) div SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end)  as Hours_of_flight_per_launch,
    TRUNCATE(MOD((SUM(land - start) / 3600000) / SUM(CASE WHEN launchtype = @LaunchType then 1 else 0 end), 1) * 60, 0) as Minutes_of_flight_per_launch
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = @LaunchType  AND org = 1
	  AND start > 0
GROUP BY category_of_customer;


/* ***** */
/* ALL LAUNCHES */
/* ***** */
SELECT 	(CASE 
			WHEN FIND_IN_SET(TRIM(glider),@club_k13) > 0  THEN 'club_k13_training' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option >= 3 AND billing_option <= 5))  THEN 'club_DGs_trial' 
            WHEN ((FIND_IN_SET(TRIM(glider),@club_DGs)  > 0) AND (billing_option < 3 OR billing_option > 5))  THEN 'club_DGs_training' 
			WHEN FIND_IN_SET(TRIM(glider),@club_singles) > 0 THEN 'club_singles' 
			ELSE 'private_owners' 
		END) as category_of_customer,
	 count(*) as flights,
	ROUND(SUM(land - start) / 3600000) as hours,
    (SUM(land - start) / 3600000) div SUM(CASE WHEN true then 1 else 0 end)  as Hours_of_flight_per_launch,
    TRUNCATE(MOD((SUM(land - start) / 3600000) / SUM(CASE WHEN true then 1 else 0 end), 1) * 60, 0) as Minutes_of_flight_per_launch
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive  AND org = 1
	  AND start > 0
GROUP BY category_of_customer;




/*** END							***/
/*** Quality = pilots stay up	 	***/
/*** END							***/

/* YTD is from 1st of July */

/* for each period:
 the number of launches in the period, per service hour
 the number of service hours in the period
 */

