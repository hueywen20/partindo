<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Purchase;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchasePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_purchases'); }
    public function view(AuthUser $authUser, Purchase $purchase): bool { return $authUser->can('view_purchases'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_purchases'); }
    public function update(AuthUser $authUser, Purchase $purchase): bool { return $authUser->can('edit_purchases'); }
    public function delete(AuthUser $authUser, Purchase $purchase): bool { return $authUser->can('delete_purchases'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_purchases'); }
    public function restore(AuthUser $authUser, Purchase $purchase): bool { return false; }
    public function forceDelete(AuthUser $authUser, Purchase $purchase): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Purchase $purchase): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
