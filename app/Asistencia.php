<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        "user_id",
        "fecha",
        "observacion"
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
