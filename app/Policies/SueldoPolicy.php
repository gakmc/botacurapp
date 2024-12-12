<?php

namespace App\Policies;

use App\Sueldo;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SueldoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any sueldos.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the sueldo.
     *
     * @param  \App\User  $user
     * @param  \App\Sueldo  $sueldo
     * @return mixed
     */
    public function view(User $user, Sueldo $sueldo)
    {
        return $user->id === $sueldo->id_user;
    }

    /**
     * Determine whether the user can create sueldos.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the sueldo.
     *
     * @param  \App\User  $user
     * @param  \App\Sueldo  $sueldo
     * @return mixed
     */
    public function update(User $user, Sueldo $sueldo)
    {
        //
    }

    /**
     * Determine whether the user can delete the sueldo.
     *
     * @param  \App\User  $user
     * @param  \App\Sueldo  $sueldo
     * @return mixed
     */
    public function delete(User $user, Sueldo $sueldo)
    {
        //
    }

    /**
     * Determine whether the user can restore the sueldo.
     *
     * @param  \App\User  $user
     * @param  \App\Sueldo  $sueldo
     * @return mixed
     */
    public function restore(User $user, Sueldo $sueldo)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the sueldo.
     *
     * @param  \App\User  $user
     * @param  \App\Sueldo  $sueldo
     * @return mixed
     */
    public function forceDelete(User $user, Sueldo $sueldo)
    {
        //
    }
}
