<?php

namespace App\Services\Authorization;

use App\Enums\RoleScope;
use App\Models\Person;
use App\Models\RoleAssignment;
use App\Support\Authorization\CurrentRoleContext;
use Illuminate\Database\Eloquent\Collection;

class CurrentRoleContextService
{
    private const SESSION_KEY = 'current_role_assignment_id';

    /**
     * نقش‌های فعال و معتبر شخص.
     */
    public function available(Person $person): Collection
    {
        return RoleAssignment::query()
            ->where('person_id', $person->id)
            ->active()
            ->with([
                'role',
                'membership.branch',
            ])
            ->get()
            ->filter(fn (RoleAssignment $assignment) => $this->isValid($assignment))
            ->values();
    }

    /**
     * نقش/شعبه فعلی شخص.
     */
    public function current(Person $person): ?CurrentRoleContext
    {
        $assignmentId = session(self::SESSION_KEY);

        if (!$assignmentId) {
            return null;
        }

        $assignment = RoleAssignment::query()
            ->whereKey($assignmentId)
            ->where('person_id', $person->id)
            ->active()
            ->with([
                'role',
                'membership.branch',
            ])
            ->first();

        if (!$assignment || !$this->isValid($assignment)) {
            $this->clear();

            return null;
        }

        return $this->makeContext($assignment);
    }

    /**
     * انتخاب نقش/شعبه فعلی.
     */
    public function select(
        Person $person,
        int $roleAssignmentId
    ): CurrentRoleContext {
        $assignment = RoleAssignment::query()
            ->whereKey($roleAssignmentId)
            ->where('person_id', $person->id)
            ->active()
            ->with([
                'role',
                'membership.branch',
            ])
            ->first();

        if (!$assignment || !$this->isValid($assignment)) {
            abort(403);
        }

        session([
            self::SESSION_KEY => $assignment->id,
        ]);

        return $this->makeContext($assignment);
    }

    /**
     * حذف نقش/شعبه فعلی.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * بررسی معتبر بودن RoleAssignment برای استفاده به عنوان Context.
     */
    private function isValid(RoleAssignment $assignment): bool
    {
        $role = $assignment->role;

        if (!$role || !$role->is_active) {
            return false;
        }

        return match ($role->scope) {
            RoleScope::System,
            RoleScope::Institute => $assignment->membership_id === null,

            RoleScope::Branch =>
                $assignment->membership !== null
                && $assignment->membership->is_active
                && $assignment->membership->branch !== null
                && $assignment->membership->branch->is_active,

            default => false,
        };
    }

    /**
     * ساخت Context فعلی از RoleAssignment.
     */
    private function makeContext(
        RoleAssignment $assignment
    ): CurrentRoleContext {
        return new CurrentRoleContext(
            assignment: $assignment,
            role: $assignment->role,
            membership: $assignment->membership,
            branch: $assignment->membership?->branch,
        );
    }
}
