<?php

namespace App\Policies;

use App\Models\LeadAutomationDetail;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadAutomationDetailPolicy
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

    public function view(User $user, LeadAutomationDetail $detail)
    {
        $lead = $detail->lead;
        $campaign = $detail->campaign;
        return ($lead && $lead->user_id === $user->id) || ($campaign && $campaign->user_id === $user->id);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, LeadAutomationDetail $detail)
    {
        return $this->view($user, $detail);
    }

    public function delete(User $user, LeadAutomationDetail $detail)
    {
        return $this->view($user, $detail);
    }
}
