<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    protected $table = 'asignaciones';
    
    protected $fillable = [
        'fecha'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'asignacion_user', 'asignacion_id', 'user_id')->withTimestamps();
    }
}
