<?php


use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    // Check if user is admin
    return $user->isAdmin() === true;
});

Broadcast::channel('livreur.{id}', function ($user, $id) {
    // Check if user is the livreur or an admin
    return $user->livreur->id == $id || $user->is_admin === true;
});