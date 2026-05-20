<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Supplier;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_suppliers'); }
    public function view(AuthUser $authUser, Supplier $supplier): bool { return $authUser->can('view_suppliers'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_suppliers'); }
    public function update(AuthUser $authUser, Supplier $supplier): bool { return $authUser->can('edit_suppliers'); }
    public function delete(AuthUser $authUser, Supplier $supplier): bool { return $authUser->can('delete_suppliers'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_suppliers'); }
    public function restore(AuthUser $authUser, Supplier $supplier): bool { return false; }
    public function forceDelete(AuthUser $authUser, Supplier $supplier): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Supplier $supplier): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
