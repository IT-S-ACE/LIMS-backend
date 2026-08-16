<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;

class Enum extends \BenSampo\Enum\Enum implements LocalizedEnum
{

    public static function asResponse(): array
    {
        $data = [];

        foreach (self::asArray() as $id) {

            $data[] = [
                'id' => $id,
                'name' => self::fromValue($id)->description

            ];
        }

        return $data;
    }

    public static function asSelectArray(): array
    {
        $data = [];

        foreach (self::asArray() as $array) {
            $data[] = $array;
        }

        return $data;
    }

    public function toObject(): array
    {
        return [
            'id' => $this->value,
            'name' => $this->description
        ];
    }
}
