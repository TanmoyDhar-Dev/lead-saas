<?php

namespace App\Policies;

use App\Models\ImportedLead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImportedLeadPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ImportedLead $importedLead): bool
    {
        return $user->id === $importedLead->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ImportedLead $importedLead): bool
    {
        return $user->id === $importedLead->user_id;
    }

    public function delete(User $user, ImportedLead $importedLead): bool
    {
        return $user->id === $importedLead->user_id;
    }
}
