<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Tickets: cada departamento escucha solo los suyos; el administrador, todos
Broadcast::channel('tickets.rol.{rolId}', function ($user, $rolId) {
    return $user->rolesTickets()->contains('id', (int) $rolId);
});

Broadcast::channel('tickets.supervision', function ($user) {
    return $user->supervisaTickets();
});

// Broadcast::channel('canal-publico', function ($user) {
//     return (bool) $user;
// });
