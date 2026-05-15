<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = true;

    protected $casts = [
        'deleted' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo('App\Models\Member', 'member_id');
    }

    public function organisation()
    {
        return $this->belongsTo('App\Models\Organisation', 'org');
    }
}
