<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'email',
        'password',
        'phone_number',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }
    public function livreur()
    {
        return $this->hasOne(Livreur::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin'; // Update according to your role system
    }
    public function isClient()
    {
        return $this->role === 'client'; // Update according to your role system
    }
    public function isLivreur()
    {
        return $this->role === 'livreur'; // Update according to your role system
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\UserResetPasswordNotification($token));
    }
}
