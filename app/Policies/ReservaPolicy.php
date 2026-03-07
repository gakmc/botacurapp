<?php
namespace App\Policies;

use App\Reserva;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        //
    }

    public function view(User $user, Reserva $reserva)
    {
        //return $user->has_permission('view-reserva');
    }

    public function create(User $user)
    {
        return $user->has_permission('create-reserva');
    }

    public function update(User $user, Reserva $reserva)
    {
        //return $user->has_permission('edit-reserva');
    }

    public function delete(User $user, Reserva $reserva)
    {

        //return $user->has_permission('delete-reserva');
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
