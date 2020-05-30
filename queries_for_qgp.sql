DROP TEMPORARY TABLE IF EXISTS tmp_table;
CREATE TEMPORARY TABLE tmp_table (
	displayname char(50),
    qgpdate date
);

INSERT INTO tmp_table (displayname, qgpdate)
VALUES 
	("Maria Cramp", "2019-02-08"),
    ("Sam Higgins", "2019-12-20"),
    ("Simon Lillico", "2017-05-27"),
    ("James Mitchell", "2019-01-16"),
    ("Ben Wilson", "2019-10-25"),
    ("Ian Johnson", "2020-03-12"),
    ("Kieran Cassidy", "2019-02-07"),
    ("Anja Runge", "2018-06-30");

DROP TEMPORARY TABLE IF EXISTS solo_flights_before_qgp;
CREATE TEMPORARY TABLE solo_flights_before_qgp AS
select tt.displayname
	,SUM(CASE WHEN DATEDIFF(qgpdate,localdate) >= 0 then 1 else 0 end) as Solo_Flights_Before_QGP
    ,(SUM((land - start) * (CASE WHEN DATEDIFF(qgpdate,localdate) >= 0 then 1 else 0 end)) / 3600000) as Solo_raw_hours_before_qgp
from gliding.flights f
join gliding.members m 
join tmp_table tt 
on tt.displayname = m.displayname and m.id = f.pic
group by displayname;

select * from solo_flights_before_qgp;

DROP TEMPORARY TABLE IF EXISTS p2_flights_before_qgp;
CREATE TEMPORARY TABLE p2_flights_before_qgp AS
select tt.displayname,
	(CAST(min(localdate) as date)) as first_flight_in_PW
	,SUM(CASE WHEN DATEDIFF(qgpdate,localdate) >= 0 then 1 else 0 end) as P2_Flights_Before_QGP
    ,(SUM((land - start) * (CASE WHEN DATEDIFF(qgpdate,localdate) >= 0 then 1 else 0 end)) / 3600000) as P2_raw_hours_before_qgp
    ,MAX(qgpdate) as qgpdate
from gliding.flights f
join gliding.members m 
join tmp_table tt 
on tt.displayname = m.displayname and m.id = f.p2
group by displayname;

select tp2.*, tp1.Solo_Flights_Before_QGP, tp1.Solo_raw_hours_before_qgp
from p2_flights_before_qgp tp2
join solo_flights_before_qgp tp1
on tp2.displayname = tp1.displayname