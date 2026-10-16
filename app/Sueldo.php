<?php

namespace App;

use Carbon\Carbon;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Sueldo extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'id_user'       => ['Trabajador', 'App\User@name'],
        'dia_trabajado' => ['Día trabajado', 'fecha'],
        'valor_dia'     => ['Valor día', 'monto'],
        'sub_sueldo'    => ['Subtotal', 'monto'],
        'total_pagar'   => ['Total a pagar', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $s = app(\App\Services\AuditoriaService::class);

        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' el sueldo de ' . optional($this->user)->name
            . ' del ' . $s->fecha($this->getAttributes()['dia_trabajado'] ?? null) . ' (' . $s->monto($this->total_pagar) . ')';
    }

    protected function auditoriaContexto()
    {
        return $this->user;
    }

    protected $table = 'sueldos';
    protected $casts = [
        'dia_trabajado' => 'date',
    ];
    protected $fillable = [
        'dia_trabajado', 'valor_dia', 'sub_sueldo', 'total_pagar', 'id_user'
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

        public function getDiaTrabajadoAttribute($value)
        {
            return Carbon::parse($value)->format('d-m-Y');
        }
}
