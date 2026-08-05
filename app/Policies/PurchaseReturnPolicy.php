<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchaseReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_purchase_returns'); }
    public function view(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return $authUser->can('view_purchase_returns'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_purchase_returns'); }
    public function update(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return $authUser->can('edit_purchase_returns'); }
    public function delete(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return $authUser->can('delete_purchase_returns'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_purchase_returns'); }
    public function restore(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return false; }
    public function forceDelete(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, PurchaseReturn $purchaseReturn): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}