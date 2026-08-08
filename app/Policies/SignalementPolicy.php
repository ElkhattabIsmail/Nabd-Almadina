<?php

namespace App\Policies;

use App\Models\Signalement;
use App\Models\User;

class SignalementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered in controller based on role
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Signalement $signalement): bool
    {
        if ($user->isAgent()) {
            return true;
        }

        return $user->id === $signalement->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Signalement $signalement): bool
    {
        if ($user->isAgent()) {
            return true;
        }

        return $user->id === $signalement->user_id && $signalement->status === 'nouveau';
    }

    /**
     * Determine whether an agent can change status.
     */
    public function updateStatus(User $user, Signalement $signalement): bool
    {
        return $user->isAgent();
    }

    /**
     * Determine whether an agent can assign a departement.
     */
    public function assignDepartement(User $user, Signalement $signalement): bool
    {
        return $user->isAgent();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Signalement $signalement): bool
    {
        if ($user->isAgent()) {
            return true;
        }

        return $user->id === $signalement->user_id && $signalement->status === 'nouveau';
    }
}
