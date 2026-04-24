<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserImpuesto extends Model
{
    protected $table = 'user_impuesto';

    protected $fillable = ['user_id', 'retiene_impuestos', 'retencion_desde'];

    protected $casts = ['retiene_impuestos' => 'boolean', 'retencion_desde' => 'datetime'];

    public function user() {
        return $this->belongsTo(User::class);
    }

}
