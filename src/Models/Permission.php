<?php

namespace Parth\LaravelRbacSuite\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Check if this permission is currently assigned to users or non-admin roles.
     */
    public function isAssignedToNonAdmin(): bool
    {
        if ($this->users()->exists()) {
            return true;
        }

        $adminName = strtolower(config('rbac-suite.roles.admin_name', 'Admin'));

        return $this->roles()
            ->whereRaw('LOWER(name) != ?', [$adminName])
            ->exists();
    }
}
