<?php


use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    return $user->isAdmin(); // Ensure you have this method in your User model
});

Broadcast::channel('livreur.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});