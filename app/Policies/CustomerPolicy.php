<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Customer;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_customers'); }
    public function view(AuthUser $authUser, Customer $customer): bool { return $authUser->can('view_customers'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_customers'); }
    public function update(AuthUser $authUser, Customer $customer): bool { return $authUser->can('edit_customers'); }
    public function delete(AuthUser $authUser, Customer $customer): bool { return $authUser->can('delete_customers'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_customers'); }
    public function restore(AuthUser $authUser, Customer $customer): bool { return false; }
    public function forceDelete(AuthUser $authUser, Customer $customer): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Customer $customer): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
