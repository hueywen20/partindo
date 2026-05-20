<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PurchaseOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_purchase_orders'); }
    public function view(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return $authUser->can('view_purchase_orders'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_purchase_orders'); }
    public function update(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return $authUser->can('edit_purchase_orders'); }
    public function delete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return $authUser->can('delete_purchase_orders'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_purchase_orders'); }
    public function restore(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return false; }
    public function forceDelete(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, PurchaseOrder $purchaseOrder): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
