<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;

class Programa extends Model
{
    protected $guarded = [];

    protected $casts = [
        'wc_main_image_ids' => 'array',
        'permite_giftcard'  => 'boolean',
        'solo_plataforma'   => 'boolean',
    ];

//RELACIONES
    // public function servicios()
    // {
    //     return $this->hasMany('App\Servicio');
    // }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'programa_servicio', 'id_programa', 'id_servicio')->withTimestamps();
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_programa');
    }

    public function cotizacionItems()
    {
        return $this->morphMany(CotizacionItem::class, 'itemable');
    }

    public function giftCards()
    {
        return $this->hasMany(GiftCard::class, 'id_programa');
    }

    public function woocommerceOrders()
    {
        return $this->hasMany(WoocommerceOrder::class, 'wc_product_id', 'wc_product_id');
    }

    //ALMACENAMIENTO

    public function store($request)
    {

        $slug = Str::slug($request->nombre_programa, '-');

        $programa = self::create([
            'nombre_programa'  => $request->input('nombre_programa'),
            'valor_programa'   => $request->input('valor_programa'),
            'descuento'        => $request->input('descuento'),
            'espacio_tipo'     => $request->input('espacio_tipo'),
            'min_personas'     => $request->input('min_personas', 1),
            'permite_giftcard' => $request->has('permite_giftcard') ? 1 : 0,
            'solo_plataforma'  => $request->has('solo_plataforma') ? 1 : 0,
            'slug'             => $slug,
        ]);

        if ($request->has('servicios')) {
            $programa->servicios()->sync($request->servicios);
        }

        Alert::success('Éxito', 'Programa guardado')->showConfirmButton();
        return $programa;
    }

    public function my_update($request)
    {
        $slug = Str::slug($request->nombre_programa, '-');

        $this->update($request->except(['servicios', 'imagenes', 'solo_plataforma', 'permite_giftcard']) + [
            'slug'             => $slug,
            'solo_plataforma'  => $request->has('solo_plataforma') ? 1 : 0,
            'permite_giftcard' => $request->has('permite_giftcard') ? 1 : 0,
        ]);

        if ($request->has('servicios')) {
            $this->servicios()->sync($request->servicios);
        } else {
            $this->servicios()->sync([]);
        }

        Alert::success('Éxito', 'Programa actualizado')->showConfirmButton();
        return $this;

    }

    //VALIDACION
    public function getIncluyeMasajesAttribute()
    {
        return $this->servicios->contains(function ($servicio) {
            return in_array($servicio->nombre_servicio, ['Masaje', 'masaje', 'Masajes', 'masajes']);
        });
    }

    public function getIncluyeAlmuerzosAttribute()
    {
        return $this->servicios->contains(function ($servicio) {
            return in_array(strtolower($servicio->nombre_servicio), ['almuerzo', 'almuerzos']);
        });
    }

    public function scopePermiteGc($q)
    {
        return $q->where('permite_giftcard', true);
    }

    //RECUPERACION DE INFORMACION

    //OTRAS OPERACIONES
    public function scopeActivos($q)
    {
        return $q->where('estado', 'activo')->orWhereNull('estado');
    }
    public function scopeInactivos($q)
    {
        return $q->where('estado', 'inactivo');
    }


}
