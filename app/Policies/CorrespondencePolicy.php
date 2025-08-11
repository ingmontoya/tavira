<?php

namespace App\Policies;

use App\Models\Correspondence;
use App\Models\User;

class CorrespondencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view correspondence list (filtered by role in controller)
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Correspondence $correspondence): bool
    {
        // Admins and security guards can view all correspondence
        if ($user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria'])) {
            return true;
        }

        // Residents can only view correspondence for their apartment
        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            return $user->apartment && $user->apartment->id === $correspondence->apartment_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Correspondence $correspondence): bool
    {
        // Only admins and security guards can update correspondence
        return $user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Correspondence $correspondence): bool
    {
        // Only admins can delete correspondence
        return $user->hasAnyRole(['admin_conjunto', 'superadmin']);
    }

    /**
     * Determine whether the user can deliver correspondence.
     */
    public function deliver(User $user, Correspondence $correspondence): bool
    {
        // Only admins and security guards can mark as delivered
        return $user->hasAnyRole(['admin_conjunto', 'superadmin', 'porteria'])
            && $correspondence->status === 'received';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Correspondence $correspondence): bool
    {
        return $user->hasAnyRole(['admin_conjunto', 'superadmin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Correspondence $correspondence): bool
    {
        return $user->hasRole('superadmin');
    }
}
