<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Masaje extends Model
{
    protected $table = 'masajes';

    protected $fillable = [
        'persona', 'id_visita', 'user_id',
    ];

    public function visita()
    {
        return $this->belongsTo(Visita::class, 'id_visita');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
