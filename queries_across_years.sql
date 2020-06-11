DROP PROCEDURE IF EXISTS LoopDemo;

DELIMITER $$
CREATE PROCEDURE QueriesAcrossYears()
BEGIN	
	DROP TEMPORARY TABLE IF EXISTS flightsPerDay;
    CREATE TEMPORARY TABLE  flightsPerDay (
		Total_flights_per_day INT(10),
		Total_aerotow_flights_per_day INT(10),
        Total_winch_flights_per_day INT(10),
        Total_selflaunch_flights_per_day INT(10),
		localdate INT(10)
		);

    SET @startDate = "2016-05-01";
    SET @endDate = "2020-06-01";	
    
    SET @startDateInclusive = @startDate;
	SET @endDateExclusive = ADDDATE(@startDate, INTERVAL 1 MONTH); /*1 month ahead*/
        
	loop_label:  LOOP
		IF  DATEDIFF(@endDate,@startDateInclusive) < 0 THEN 
			LEAVE  loop_label;
		END  IF;
        
        INSERT INTO flightsPerDay
        SELECT 
			count(*) as Total_flights_per_day,
			sum(CASE WHEN launchtype = 1 then 1 else 0 END) as Total_aerotow_flights_per_day,
            sum(CASE WHEN launchtype = 2 then 1 else 0 END) as Total_winch_flights_per_day,
            sum(CASE WHEN launchtype = 3 then 1 else 0 END) as Total_selflaunch_flights_per_day,
			localdate 
		FROM gliding.flights
		WHERE localdate >= @startDateInclusive_work AND localdate < @endDateExclusive_work  AND org = 1
		GROUP BY localdate;
        
        SET @startDateInclusive_work = DAY(@startDateInclusive)+100*MONTH(@startDateInclusive)+10000*YEAR(@startDateInclusive);
        SET @endDateExclusive_work = DAY(@endDateExclusive)+100*MONTH(@endDateExclusive)+10000*YEAR(@endDateExclusive);
        
		SET @startDateInclusive = ADDDATE(@startDateInclusive, INTERVAL 1 MONTH);
		SET @endDateExclusive = ADDDATE(@startDateInclusive, INTERVAL 1 MONTH); 
	END LOOP;
    SELECT * FROM flightsPerDay
    ORDER BY localdate desc;
END$$

DELIMITER ;

CALL QueriesAcrossYears();



/*** BEGIN							***/
/*** Utilisation + to solo quality 	***/
/*** BEGIN							***/

/* ***** */
/* AEROTOW */
/* ***** */

/*
SET @LaunchType = 1;

SELECT 
	count(*) as Total_aerotow_flights_per_day, 
    localdate 
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = @LaunchType  AND org = 1
GROUP BY localdate;

SELECT 
	count(*) as Total_aerotow_flights, 
    count(distinct localdate) as Flying_days_with_aerotow
FROM gliding.flights
WHERE localdate >= @startDateInclusive AND localdate < @endDateExclusive AND launchtype = @LaunchType  AND org = 1;
*/
