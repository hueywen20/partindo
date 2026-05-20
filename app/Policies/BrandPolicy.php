<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Brand;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_brands'); }
    public function view(AuthUser $authUser, Brand $brand): bool { return $authUser->can('view_brands'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_brands'); }
    public function update(AuthUser $authUser, Brand $brand): bool { return $authUser->can('edit_brands'); }
    public function delete(AuthUser $authUser, Brand $brand): bool { return $authUser->can('delete_brands'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_brands'); }
    public function restore(AuthUser $authUser, Brand $brand): bool { return false; }
    public function forceDelete(AuthUser $authUser, Brand $brand): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Brand $brand): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
