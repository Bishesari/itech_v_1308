<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['mobile'])]
class Mobile extends Model
{
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }
}
