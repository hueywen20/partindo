<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Uom;
use Illuminate\Auth\Access\HandlesAuthorization;

class UomPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_uom'); }
    public function view(AuthUser $authUser, Uom $uom): bool { return $authUser->can('view_uom'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_uom'); }
    public function update(AuthUser $authUser, Uom $uom): bool { return $authUser->can('edit_uom'); }
    public function delete(AuthUser $authUser, Uom $uom): bool { return $authUser->can('delete_uom'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_uom'); }
    public function restore(AuthUser $authUser, Uom $uom): bool { return false; }
    public function forceDelete(AuthUser $authUser, Uom $uom): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Uom $uom): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
