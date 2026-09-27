<?php

namespace App\Services\Auth;

use App\Contracts\SmsGateway;
use App\Models\Mobile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PasswordResetService
{
    public function __construct(
        protected SmsGateway $smsGateway,
    ) {
    }

    public function findByIdentity(string $identity): ?User
    {
        return User::query()
            ->whereHas('person', function ($query) use ($identity) {
                $query->where('identity', $identity);
            })
            ->with('person.mobiles')
            ->first();
    }

    public function mobiles(User $user): Collection
    {
        return $user->person->mobiles;
    }

    public function reset(User $user, Mobile $mobile): void
    {
        $this->ensureMobileBelongsToUser($user, $mobile);

        $password = $this->generatePassword();

        $this->smsGateway->sendPassword(
            $mobile->mobile,
            $password,
        );

        $user->update([
            'password' => $password,
        ]);
    }

    protected function generatePassword(int $length = 6): string
    {
        $digits = '123456789';
        $letters = 'abcdefghijkmnpqrstuvwxyz';

        $password = '';

        for ($i = 0; $i < 2; $i++) {
            $password .= $digits[random_int(0, strlen($digits) - 1)];
        }

        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $letters[random_int(0, strlen($letters) - 1)];
        }

        for ($i = 0; $i < 2; $i++) {
            $password .= $digits[random_int(0, strlen($digits) - 1)];
        }

        return $password;
    }

    protected function ensureMobileBelongsToUser(User $user, Mobile $mobile): void
    {
        $exists = $user->person
            ->mobiles()
            ->whereKey($mobile->getKey())
            ->exists();

        if (! $exists) {
            throw new \InvalidArgumentException(
                'شماره موبایل انتخاب‌شده متعلق به این کاربر نیست.'
            );
        }
    }
}
