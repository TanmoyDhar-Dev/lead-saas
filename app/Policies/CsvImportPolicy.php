<?php

namespace App\Policies;

use App\Models\CsvImport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CsvImportPolicy
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

    public function view(User $user, CsvImport $csvImport)
    {
        return $user->id === $csvImport->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, CsvImport $csvImport)
    {
        return $user->id === $csvImport->user_id;
    }

    public function delete(User $user, CsvImport $csvImport)
    {
        return $user->id === $csvImport->user_id;
    }
}
