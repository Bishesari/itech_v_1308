<?php

namespace App\Models;

use App\Enums\NationalityType;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected function casts(): array
    {
        return [
            'nationality_type' => NationalityType::class,
        ];
    }
}
