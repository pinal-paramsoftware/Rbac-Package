<?php

namespace Parth\LaravelRbacSuite\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Determine if this role is the protected Admin role.
     */
    public function isAdmin(): bool
    {
        $adminName = config('rbac-suite.roles.admin_name', 'Admin');

        return strcasecmp($this->name, $adminName) === 0;
    }
}
