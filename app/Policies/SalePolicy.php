<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Sale;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_sales'); }
    public function view(AuthUser $authUser, Sale $sale): bool { return $authUser->can('view_sales'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_sales'); }
    public function update(AuthUser $authUser, Sale $sale): bool { return $authUser->can('edit_sales'); }
    public function delete(AuthUser $authUser, Sale $sale): bool { return $authUser->can('delete_sales'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_sales'); }
    public function restore(AuthUser $authUser, Sale $sale): bool { return false; }
    public function forceDelete(AuthUser $authUser, Sale $sale): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Sale $sale): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
