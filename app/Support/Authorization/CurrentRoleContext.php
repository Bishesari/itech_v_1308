<?php

namespace App\Support\Authorization;

use App\Enums\RoleScope;
use App\Models\Branch;
use App\Models\Membership;
use App\Models\Role;
use App\Models\RoleAssignment;

final readonly class CurrentRoleContext
{
    public function __construct(
        public RoleAssignment $assignment,
        public Role $role,
        public ?Membership $membership = null,
        public ?Branch $branch = null,
    ) {
    }

    public function isSystem(): bool
    {
        return $this->role->scope === RoleScope::System;
    }

    public function isInstitute(): bool
    {
        return $this->role->scope === RoleScope::Institute;
    }

    public function isBranch(): bool
    {
        return $this->role->scope === RoleScope::Branch;
    }
}
