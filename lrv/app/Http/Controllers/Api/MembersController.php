<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Models\Organisation;
use App\Models\Member;
use App\Models\Role;

class MembersController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $orgInput = $request->input('org');
        $roleInput = $request->input('role');

        $org = Organisation::find($orgInput);
        $role = null;
        if($roleInput) {
            $role = Role::where('name', $roleInput)->first();
        }

        if($role) {
            $result = $role->members()->where(['members.org' => $org->id]);
        } else {
            $result = Member::where(['org' => $org->id]);
        }

        return response()->json([
          'data' => $result->get()
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
