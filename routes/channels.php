<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Canal privado de turnos: cualquier usuario autenticado puede suscribirse.
| El acceso a los datos sigue siendo controlado por los middlewares de rol
| en los endpoints HTTP de la API.
|
*/

// Proteger /broadcasting/auth con JWT en vez de sesión
Broadcast::routes(['middleware' => ['auth:api']]);

Broadcast::channel('appointments', function ($user) {
    return $user !== null;
});
