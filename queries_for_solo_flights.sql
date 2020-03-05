select 
	solo.first_solo_at_WGC, 
    not_solo.first_flight_as_p2_at_WGC, 
    DATEDIFF(solo.first_solo_at_WGC, not_solo.first_flight_as_p2_at_WGC) as calendar_days,  
    solo.displayname, 
    solo.dt
from (
	select CAST(min(localdate) as date) as first_solo_at_WGC, m.displayname, min(localdate) as dt
	from gliding.flights f
	join gliding.members m on f.pic = m.id
	where f.org = 1
	group by m.displayname
	order by dt desc
) solo
join
(
	select CAST(min(localdate) as date) as first_flight_as_p2_at_WGC, m.displayname, min(localdate) as dt
	from gliding.flights f
	join gliding.members m on f.p2 = m.id
	where f.org = 1
	group by m.displayname
	order by dt desc
) not_solo
on solo.displayname = not_solo.displayname
where solo.first_solo_at_WGC > 2015-12-31 and DATEDIFF(solo.first_solo_at_WGC, not_solo.first_flight_as_p2_at_WGC) > 7

/*
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