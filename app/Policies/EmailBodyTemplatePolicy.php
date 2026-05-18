<?php

namespace App\Policies;

use App\Models\EmailBodyTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailBodyTemplatePolicy
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

    public function view(User $user, EmailBodyTemplate $emailBodyTemplate)
    {
        return $user->id === $emailBodyTemplate->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, EmailBodyTemplate $emailBodyTemplate)
    {
        return $user->id === $emailBodyTemplate->user_id;
    }

    public function delete(User $user, EmailBodyTemplate $emailBodyTemplate)
    {
        return $user->id === $emailBodyTemplate->user_id;
    }
}
