<?php

namespace App\Http\Requests\Setting;


use Illuminate\Foundation\Http\FormRequest;


class UpdateSystemSettingRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        return [

            'lab_name'
            =>
                'required|string|max:255',


            'license_number'
            =>
                'nullable|string|max:255',


            'address'
            =>
                'nullable|string',


            'email_notifications'
            =>
                'boolean'

        ];

    }

}