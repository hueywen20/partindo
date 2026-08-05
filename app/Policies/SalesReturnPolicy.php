<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SalesReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_sales_returns'); }
    public function view(AuthUser $authUser, SalesReturn $salesReturn): bool { return $authUser->can('view_sales_returns'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_sales_returns'); }
    public function update(AuthUser $authUser, SalesReturn $salesReturn): bool { return $authUser->can('edit_sales_returns'); }
    public function delete(AuthUser $authUser, SalesReturn $salesReturn): bool { return $authUser->can('delete_sales_returns'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_sales_returns'); }
    public function restore(AuthUser $authUser, SalesReturn $salesReturn): bool { return false; }
    public function forceDelete(AuthUser $authUser, SalesReturn $salesReturn): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, SalesReturn $salesReturn): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}