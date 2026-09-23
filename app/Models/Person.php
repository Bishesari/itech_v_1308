<?php

namespace App\Models;

use App\Enums\NationalityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['nationality_type', 'identity', 'gender', 'first_name_fa', 'last_name_fa', 'nickname'])]

class Person extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'nationality_type' => NationalityType::class,
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function mobiles(): BelongsToMany
    {
        return $this->belongsToMany(Mobile::class)
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name_fa.' '.$this->last_name_fa);
    }

    public function initials(): string
    {
        return mb_substr($this->first_name_fa, 0, 1)
            .' '
            .mb_substr($this->last_name_fa, 0, 1);
    }
}
