<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuditoriaAutorizado extends Model
{
    // gestor: ve la auditoría y administra accesos (otorga lector/gestor, revoca a otros; nunca el propio).
    //         Lo otorga la consola u otro gestor.
    const NIVEL_GESTOR = 'gestor';
    // lector: otorgado desde la plataforma, solo ve la auditoría
    const NIVEL_LECTOR = 'lector';

    protected $table = 'auditoria_autorizados';

    protected $fillable = ['user_id', 'nivel', 'otorgado_por', 'otorgado_at'];

    protected $casts = ['otorgado_at' => 'datetime'];

    // RELACIONES

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function otorgante()
    {
        return $this->belongsTo(User::class, 'otorgado_por');
    }

    // VALIDACION

    public function es_gestor()
    {
        return $this->nivel === self::NIVEL_GESTOR;
    }
}
