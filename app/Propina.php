<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Propina extends Model
{
    protected $table = 'propinas';

    protected $fillable = [
        'fecha', 'cantidad', 'id_consumo'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'propina_user', 'id_propina', 'id_user')
                    ->withPivot('monto_asignado')
                    ->withTimestamps();
    }

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function propinable()
    {
        return $this->morphTo();
    }
}
