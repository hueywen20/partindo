<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_users'); }
    public function view(AuthUser $authUser): bool { return $authUser->can('view_users'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_users'); }
    public function update(AuthUser $authUser): bool { return $authUser->can('edit_users'); }
    public function delete(AuthUser $authUser): bool { return $authUser->can('delete_users'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_users'); }
    public function restore(AuthUser $authUser): bool { return false; }
    public function forceDelete(AuthUser $authUser): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
