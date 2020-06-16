DROP TEMPORARY TABLE IF EXISTS tmp_table;
CREATE TEMPORARY TABLE tmp_table AS 
select 
    solo.displayname, 
    solo.phone_home, solo.phone_mobile, solo.email,
	not_solo.first_flight_as_p2_at_WGC, 
	solo.first_solo_at_WGC, 
    (DATEDIFF(solo.first_solo_at_WGC, not_solo.first_flight_as_p2_at_WGC)) / 30  as months_to_solo, 
    DATEDIFF(solo.first_solo_at_WGC, not_solo.first_flight_as_p2_at_WGC) as calendar_days_to_solo,
    solo.id
from (
	select CAST(min(localdate) as date) as first_solo_at_WGC, m.displayname, m.phone_home, m.phone_mobile, m.email, m.id
	from gliding.flights f
	join gliding.members m on f.pic = m.id
	where f.org = 1
	group by m.displayname, m.phone_home, m.phone_mobile, m.email, m.id
) solo
join
(
	select CAST(min(localdate) as date) as first_flight_as_p2_at_WGC, m.displayname, m.id
	from gliding.flights f
	join gliding.members m on f.p2 = m.id
	where f.org = 1
	group by m.displayname, m.id
) not_solo
on solo.id = not_solo.id
where solo.displayname in 
(
"Steve Davies Howard",
"Charlie Kern-Smith",
"Tim Tarbotton",
"Sandy Dawson",
"Dan Corneanu",
"Anja Runge",
"Ryan Goldsworthy",
"James Mitchell",
"Kieran Cassidy",
"James Goldsworthy",
"Sam Higgins",
"Victor Lenting",
"Ben Wilson",
"Mark Wilson2",
"Nick Lewis",
"Rowan Higgins",
"Fergus Pitney",
"Amy Smith",
"Mark Shillingford",
"William Keedwell",
"Nick Johns",
"Joshua Wiegman",
"Bruno Tagliapietra"
)
order by displayname;

select * from
tmp_table;

select 	displayname
		,SUM(CASE WHEN DATEDIFF(first_solo_at_WGC,localdate) >= 0 then 1 else 0 end) as Flights_Before_Solo
        ,(SUM((land - start) * (CASE WHEN DATEDIFF(first_solo_at_WGC,localdate) >= 0 then 1 else 0 end)) / 3600000) as raw_hours_before_solo
        #,(SUM((land - start) * (CASE WHEN DATEDIFF(first_solo_at_WGC,localdate) >= 0 then 1 else 0 end)) div 3600000) as Hours_before_solo
        #,TRUNCATE(MOD((SUM((land - start) * (CASE WHEN DATEDIFF(first_solo_at_WGC,localdate) >= 0 then 1 else 0 end)) / 3600000), 1) * 60, 0) as Minutes_before_solo		
        ,COUNT(DISTINCT CASE WHEN DATEDIFF(first_solo_at_WGC,localdate) >= 0 then f.pic else 0 end) - 1 as Instructors_Before_Solo
from tmp_table t
join flights f on t.id = f.p2
group by displayname
;

#select * from flights;

DROP TEMPORARY TABLE IF EXISTS tmp_table;


/*
Not solo YET

Awhina Southey
Sam Taylor
Fritz Cooks
*/

/*
Steve Davies Howard
Charlie Kern-Smith
Tim Tarbotton
Sandy Dawson
Dan Corneanu
Anja Runge
Ryan Goldsworthy
James Mitchell
Kieran Cassidy
James Goldsworthy
Sam Higgins
Victor Lenting
Ben Wilson
Mark Wilson2
Nick Lewis
Rowan Higgins
Fergus Pitney
Amy Smith
Mark Shillingford
William Keedwell
Nick Johns
Joshua Wiegman
*/