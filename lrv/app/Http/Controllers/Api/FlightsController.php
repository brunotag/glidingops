<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use DateTimeZone;
use DateTime;

use App\Models\Organisation;
use App\Models\Flight;

class FlightsController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $orgInput       = $request->input('org');
        $localdateInput = $request->input('localdate');

        $org = Organisation::find($orgInput);
        if(! $localdateInput) {
            $dateTimeZone = new DateTimeZone($org->timezone);
            $dateTime     = new DateTime('now', $dateTimeZone);
            $localdate    = $dateTime->format('Ymd');
        } else {
            $localdate = $localdateInput;
        }

        return response()->json([
          'data' => Flight::where(['org' => $org->id, 'localdate' => $localdate])->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
