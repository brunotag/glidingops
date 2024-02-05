<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use DateTimeZone;
use DateTime;

use App\Models\Organisation;
use App\Models\Flight;
use App\Models\Member;
use DB;

class FlightsController extends Controller
{
    public function allFlightsReport(Request $request){
        if (isset($_GET['csv'])){
            return $this->getAllFlightsCSV($request);
        }
        return $this->getAllFlightsHTML($request);
    }

    private function getAllFlightsCSV(Request $request){
        set_time_limit(120);
        $user = Auth::user();

        $dateTimeZone = new DateTimeZone($_SESSION['timezone']);
        $dateTime = new DateTime('now', $dateTimeZone);
        $dateStr = $dateTime->format('Y-m-d');

        $strDateFrom  = $request->input("fromdate", $dateStr);
        $strDateTo    = $request->input("todate", $dateStr);

        $dateStart2 = substr($strDateFrom,0,4) . substr($strDateFrom,5,2) . substr($strDateFrom,8,2);
        $dateEnd2 = substr($strDateTo,0,4) . substr($strDateTo,5,2) . substr($strDateTo,8,2);

        $filterByMember = null;
        $memberId = null;
        if ($request->has('filterByMemberId')) {
            $memberId = $request->input('filterByMemberId');
            $filterByMember = Member::where('id', $memberId)->first();
        }

        $flights = $this->getAllFlights($dateStart2, $dateEnd2, $memberId)
            ->get();

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=export.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columns = array('DATE', 'SEQ', 'LOCATION', 'LAUNCH TYPE', 'GLIDER', 'TOWY', 'PIC', 'P2', 'TAKE OFF', 'LAND', 'DURATION', 'CHARGE', 'COMMENTS');

        $callback = function() use ($flights, $columns)
        {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach($flights as $f) {
                fputcsv($file, array($f->localdate, $f->seq, $f->location, $f->launchtype_name, $f->glider, $f->towpilot_displayname, $f->pic_displayname, $f->p2_displayname, $f->start, $f->land, $f->flightDuration, $f->billingoption_name, $f->comments));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getAllFlightsHTML(Request $request)
    {
        set_time_limit(120);
        $user = Auth::user();

        $dateTimeZone = new DateTimeZone($_SESSION['timezone']);
        $dateTime = new DateTime('now', $dateTimeZone);
        $dateStr = $dateTime->format('Y-m-d');

        $strDateFrom  = $request->input("fromdate", $dateStr);
        $strDateTo    = $request->input("todate", $dateStr);

        $dateStart2 = substr($strDateFrom,0,4) . substr($strDateFrom,5,2) . substr($strDateFrom,8,2);
        $dateEnd2 = substr($strDateTo,0,4) . substr($strDateTo,5,2) . substr($strDateTo,8,2);

        $filterByMember = null;
        $memberId = null;
        if ($request->has('filterByMemberId')) {
            $memberId = $request->input('filterByMemberId');
            $filterByMember = Member::where('id', $memberId)->first();
        }

        $flights = $this->getAllFlights($dateStart2, $dateEnd2, $memberId);
        $flights = $flights->paginate(100);
        $flights->appends($_GET)->links();

        $totalDuration = $this->getTotalDuration($dateStart2, $dateEnd2, $memberId);

        return response()->view('allFlightsReport', [
            'filterByMember' => $filterByMember,
            'flights' => $flights,
            'strDateFrom' => $strDateFrom,
            'strDateTo' => $strDateTo,
            'towChargeType' => $user->organisation->getTowChargeType(),
            'timezone' => $user->organisation->timezone,
            'totalDuration' => $totalDuration
        ]);
    }

    private function getAllFlights($dateStart2, $dateEnd2, $memberId){
        $flights = DB::table('flights')
            ->select(
                'flights.id',
                'flights.pic',
                'flights.p2',
                'flights.localdate',
                'flights.seq',
                'flights.glider',
                'flights.height',
                'flights.comments',
                'flights.type',
                'flights.location',
                'flights.start',
                'flights.land',
                'flights.towland',
                'flights.launchtype',
                'flights.finalised',
                DB::raw('flights.land-flights.start AS flightDuration'),
                DB::raw('IF(towland != 0, flights.towland-flights.start, 0) AS towDuration'),
                'towplanes.rego_short AS towplane_rego_short',
                'towpilots.displayname AS towpilot_displayname',
                'pics.displayname AS pic_displayname',
                'p2s.displayname AS p2_displayname',
                'launchtypes.name AS launchtype_name',
                'billingoptions.name AS billingoption_name')
            ->leftJoin('members AS towpilots', 'towpilots.id', '=', 'flights.towpilot')
            ->leftJoin('members AS pics', 'pics.id', '=', 'flights.pic')
            ->leftJoin('members AS p2s', 'p2s.id', '=', 'flights.p2')
            ->leftJoin('billingoptions AS billingoptions', 'billingoptions.id', '=', 'flights.billing_option')
            ->leftJoin('aircraft AS towplanes', 'towplanes.id', '=', 'flights.towplane')
            ->leftJoin('launchtypes AS launchtypes', 'launchtypes.id', '=', 'flights.launchtype')
            ->where('flights.org', $_SESSION['org'])
            ->where('flights.localdate', '>=', $dateStart2)
            ->where('flights.localdate', '<=', $dateEnd2)
            ->orderBy('flights.localdate')
            ->orderBy('flights.seq')
            ;

        if($memberId) {
            $flights = $flights->where(function($query) use($memberId){
                $query->where('flights.pic', $memberId)
                      ->orWhere('flights.p2', $memberId);
            });
        }

        return $flights;
    }

    private function getTotalDuration($dateStart2, $dateEnd2, $memberId){
        $totalDurationQuery = DB::table('flights')
            ->select(DB::raw('SUM(flights.land-flights.start) as totalDuration'))
            ->where('flights.org', $_SESSION['org'])
            ->where('flights.localdate', '>=', $dateStart2)
            ->where('flights.localdate', '<=', $dateEnd2);

        if($memberId) {
            $totalDurationQuery = $totalDurationQuery->where(function($query) use($memberId){
                $query->where('flights.pic', $memberId)
                    ->orWhere('flights.p2', $memberId);
            });
        }

        $totalDuration = $totalDurationQuery
            ->first()
            ->totalDuration
        ;

        return $totalDuration;
    }
}