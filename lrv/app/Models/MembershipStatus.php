<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipStatus extends Model
{
    const STATUS_NAME_ACTIVE   = 'Active';
    const STATUS_NAME_PASSIVE  = 'Passive';
    const STATUS_NAME_RESIGNED = 'Resigned';
    const STATUS_NAME_DECEASED = 'Deceased';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'membership_status';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public static function activeStatus()
    {
        return MembershipStatus::where('status_name', self::STATUS_NAME_ACTIVE)->firstOrFail();
    }
}
