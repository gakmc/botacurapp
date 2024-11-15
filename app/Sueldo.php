<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sueldo extends Model
{
    protected $table = 'sueldos';
    protected $fillable = [
        'dia_trabajado', 'valor_dia', 'sub_sueldo', 'total_pagar', 'id_user', 'id_propina_user'
    ];

        // Relación con User
        public function user()
        {
            return $this->belongsTo(User::class, 'id_user');
        }
    
        // Relación con Propina a través de la tabla pivote propina_user
        public function propina()
        {
            return $this->belongsToMany(Propina::class, 'propina_user', 'id', 'id_propina')
                        ->withPivot('monto_asignado')
                        ->withTimestamps();
        }
}
