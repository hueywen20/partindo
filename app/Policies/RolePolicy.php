<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_roles_permissions'); }
    public function view(AuthUser $authUser, Role $role): bool { return $authUser->can('view_roles_permissions'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('manage_roles_permissions'); }
    public function update(AuthUser $authUser, Role $role): bool { return $authUser->can('manage_roles_permissions'); }
    public function delete(AuthUser $authUser, Role $role): bool { return $authUser->can('manage_roles_permissions'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('manage_roles_permissions'); }
    public function restore(AuthUser $authUser, Role $role): bool { return false; }
    public function forceDelete(AuthUser $authUser, Role $role): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Role $role): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
