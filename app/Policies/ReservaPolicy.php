<?php

namespace App\Policies;

use App\Reserva;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any reservas.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the reserva.
     *
     * @param  \App\User  $user
     * @param  \App\Reserva  $reserva
     * @return mixed
     */
    public function view(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can create reservas.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->has_permission('create-reserva');
    }

    /**
     * Determine whether the user can update the reserva.
     *
     * @param  \App\User  $user
     * @param  \App\Reserva  $reserva
     * @return mixed
     */
    public function update(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can delete the reserva.
     *
     * @param  \App\User  $user
     * @param  \App\Reserva  $reserva
     * @return mixed
     */
    public function delete(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can restore the reserva.
     *
     * @param  \App\User  $user
     * @param  \App\Reserva  $reserva
     * @return mixed
     */
    public function restore(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the reserva.
     *
     * @param  \App\User  $user
     * @param  \App\Reserva  $reserva
     * @return mixed
     */
    public function forceDelete(User $user, Reserva $reserva)
    {
        //
    }
}
