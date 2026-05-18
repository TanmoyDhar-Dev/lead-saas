<?php

namespace App\Policies;

use App\Models\LeadSearch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadSearchPolicy
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

    public function view(User $user, LeadSearch $leadSearch)
    {
        return $user->id === $leadSearch->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, LeadSearch $leadSearch)
    {
        return $user->id === $leadSearch->user_id;
    }

    public function delete(User $user, LeadSearch $leadSearch)
    {
        return false;
    }
}
