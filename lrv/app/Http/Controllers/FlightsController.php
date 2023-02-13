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
    /**
     * Display a listing of all the flights.
     *
     * @return \Illuminate\Http\Response
     */
    public function allFlightsReport(Request $request)
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

        $totalDurationQuery = DB::table('flights')
            ->select(DB::raw('SUM(flights.land-flights.start) as totalDuration'))
            ->where('flights.org', $_SESSION['org'])
            ->where('flights.localdate', '>=', $dateStart2)
            ->where('flights.localdate', '<=', $dateEnd2);

        $filterByMember = null;
        if ($request->has('filterByMemberId')) {
            $memberId = $request->input('filterByMemberId');
            $filterByMember = Member::where('id', $memberId)->first();
        }
        if($filterByMember) {
            $flights = $flights->where(function($query) use($memberId){
                $query->where('flights.pic', $memberId)
                      ->orWhere('flights.p2', $memberId);
            });
            $totalDurationQuery = $totalDurationQuery->where(function($query) use($memberId){
                $query->where('flights.pic', $memberId)
                      ->orWhere('flights.p2', $memberId);
            });
        }

        $totalDuration = $totalDurationQuery
            ->first()
            ->totalDuration
        ;

        $flights = $flights->paginate(100);

        $flights->appends($_GET)->links();

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
}