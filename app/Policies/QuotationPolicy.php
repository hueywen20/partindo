<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Quotation;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool { return $authUser->can('view_quotations'); }
    public function view(AuthUser $authUser, Quotation $quotation): bool { return $authUser->can('view_quotations'); }
    public function create(AuthUser $authUser): bool { return $authUser->can('create_quotations'); }
    public function update(AuthUser $authUser, Quotation $quotation): bool { return $authUser->can('edit_quotations'); }
    public function delete(AuthUser $authUser, Quotation $quotation): bool { return $authUser->can('delete_quotations'); }
    public function deleteAny(AuthUser $authUser): bool { return $authUser->can('delete_quotations'); }
    public function restore(AuthUser $authUser, Quotation $quotation): bool { return false; }
    public function forceDelete(AuthUser $authUser, Quotation $quotation): bool { return false; }
    public function forceDeleteAny(AuthUser $authUser): bool { return false; }
    public function restoreAny(AuthUser $authUser): bool { return false; }
    public function replicate(AuthUser $authUser, Quotation $quotation): bool { return false; }
    public function reorder(AuthUser $authUser): bool { return false; }
}
