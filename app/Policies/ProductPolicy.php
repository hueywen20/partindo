<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_products'); }
    public function view(AuthUser $authUser, Product $product): bool { return $authUser->can('view_products'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_products'); }
    public function update(AuthUser $authUser, Product $product): bool { return $authUser->can('edit_products'); }
    public function delete(AuthUser $authUser, Product $product): bool { return $authUser->can('delete_products'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_products'); }
    public function restore(AuthUser $authUser, Product $product): bool { return false; }
    public function forceDelete(AuthUser $authUser, Product $product): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Product $product): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
