<?php

namespace App\Policies;

use App\Models\EmailSignatureTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailSignatureTemplatePolicy
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

    public function view(User $user, EmailSignatureTemplate $emailSignatureTemplate)
    {
        return $user->id === $emailSignatureTemplate->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, EmailSignatureTemplate $emailSignatureTemplate)
    {
        return $user->id === $emailSignatureTemplate->user_id;
    }

    public function delete(User $user, EmailSignatureTemplate $emailSignatureTemplate)
    {
        return $user->id === $emailSignatureTemplate->user_id;
    }
}
