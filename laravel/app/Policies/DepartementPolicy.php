<?php

namespace App\Policies;

use App\Models\Departement;
use App\Models\User;

class DepartementPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Departement $departement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Departement $departement): bool
    {
        return $user->isAgent();
    }

    public function delete(User $user, Departement $departement): bool
    {
        return $user->isAgent();
    }
}
