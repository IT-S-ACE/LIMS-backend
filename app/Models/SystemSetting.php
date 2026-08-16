<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class SystemSetting extends Model
{

    use HasUuids;


    protected $fillable = [

        'lab_name',

        'license_number',

        'address',

        'email_notifications'

    ];



    protected $casts = [

        'email_notifications'
        =>
            'boolean'

    ];

}