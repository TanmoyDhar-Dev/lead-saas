<?php

namespace App\Policies;

use App\Models\SenderIdentity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SenderIdentityPolicy
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

    public function view(User $user, SenderIdentity $senderIdentity)
    {
        return $user->id === $senderIdentity->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, SenderIdentity $senderIdentity)
    {
        return $user->id === $senderIdentity->user_id;
    }

    public function delete(User $user, SenderIdentity $senderIdentity)
    {
        return $user->id === $senderIdentity->user_id;
    }
}
