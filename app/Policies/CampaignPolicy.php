<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
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

    public function view(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function delete(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function confirm(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function process(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }

    public function cancel(User $user, Campaign $campaign)
    {
        return $user->id === $campaign->user_id;
    }
}
