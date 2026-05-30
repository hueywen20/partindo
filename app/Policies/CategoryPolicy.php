<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_categories');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('view_categories');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_categories');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('edit_categories');
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('delete_categories');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_categories');
    }

    public function restore(AuthUser $authUser, Category $category): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Category $category): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser, Category $category): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return false;
    }
}