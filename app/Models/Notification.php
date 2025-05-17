<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'livreur_id',
        'message',
        'data',
        'read'
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean'
    ];

    public function livreur()
    {
        return $this->belongsTo(Livreur::class);
    }
}