<?php

namespace App\Services;


use App\Models\SystemSetting;



class SystemSettingService
{


    public function get()
    {

        return SystemSetting::first();

    }





    public function update(
        array $data
    ) {


        $setting =
            SystemSetting::first();



        if (!$setting) {


            return SystemSetting::create(
                $data
            );


        }



        $setting->update(
            $data
        );

        return $setting->refresh();


    }


}
