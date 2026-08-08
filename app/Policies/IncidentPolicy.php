<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Determine whether the user can view any incidents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the incident.
     */
    public function view(User $user, Incident $incident): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create incidents.
     */
    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    /**
     * Determine whether the user can update the incident.
     */
    public function update(User $user, Incident $incident): bool
    {
        return $user->isAgent();
    }

    /**
     * Determine whether the agent can group signalements into an incident.
     */
    public function regrouper(User $user): bool
    {
        return $user->isAgent();
    }

    /**
     * Determine whether the user can delete the incident.
     * Note: An incident cannot be deleted if it has attached signalements (referential integrity).
     */
    public function delete(User $user, Incident $incident): bool
    {
        return $user->isAgent();
    }
}
