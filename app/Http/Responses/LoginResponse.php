<?php

namespace App\Http\Responses;

use App\Services\Authorization\CurrentRoleContextService;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private readonly CurrentRoleContextService $roleContextService,
    ) {
    }

    public function toResponse($request): RedirectResponse
    {
        $person = $request->user()->person;

        $contexts = $this->roleContextService->available($person);

        if ($contexts->count() === 0) {
            return redirect()->intended('/dashboard');
        }

        if ($contexts->count() === 1) {
            $this->roleContextService->select(
                $person,
                $contexts->first()->id,
            );

            return redirect()->intended('/dashboard');
        }

        return redirect()->route('role-context.select');
    }
}
