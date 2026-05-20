<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductLocation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductLocationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_product_locations'); }
    public function view(AuthUser $authUser, ProductLocation $productLocation): bool { return $authUser->can('view_product_locations'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_product_locations'); }
    public function update(AuthUser $authUser, ProductLocation $productLocation): bool { return $authUser->can('edit_product_locations'); }
    public function delete(AuthUser $authUser, ProductLocation $productLocation): bool { return $authUser->can('delete_product_locations'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_product_locations'); }
    public function restore(AuthUser $authUser, ProductLocation $productLocation): bool { return false; }
    public function forceDelete(AuthUser $authUser, ProductLocation $productLocation): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, ProductLocation $productLocation): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
