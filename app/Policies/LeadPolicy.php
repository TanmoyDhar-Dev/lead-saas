<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Lead $lead)
    {
        return $lead->isAccessibleBy($user);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Lead $lead)
    {
        return $lead->isAccessibleBy($user);
    }

    public function delete(User $user, Lead $lead)
    {
        return false;
    }
}
