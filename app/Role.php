<?php

namespace App;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;



class Role extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_SEGURIDAD;

    protected $auditoriaCampos = [
        'name'        => 'Nombre',
        'slug'        => 'Slug',
        'description' => 'Descripción',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el rol ' . $this->name;
    }

    protected $fillable = [
        'name', 'slug', 'description'
    ];


    // RELACIONES 
    public function permissions() 
    {
        return $this->hasMany('App\Permission');
    }
    
    public function users()
    {
        return $this->belongsToMany('App\User')->withTimestamps();
    }

    public function rangoSueldo()
    {
        return $this->hasMany(RangoSueldoRole::class, 'role_id');
    }

    // ALMACENAMIENTO 
    public function store($request)
    {
        $slug = Str::slug($request->name, '-');
        Alert::success('Éxito', 'Rol guardado')->showConfirmButton();
        // Solo los campos del formulario; el slug siempre se calcula (antes uno enviado en el request lo pisaba)
        return self::create($request->only(['name', 'description']) + [
            'slug' => $slug,
        ]);
    }

    public function my_update($request)
    {
        $slug = Str::slug($request->name, '-');
        Alert::success('Éxito', 'Rol actualizado')->showConfirmButton();
        return self::update($request->only(['name', 'description']) + [
            'slug' => $slug,
        ]);
        
    }


    // VALIDACION 


    // RECUPERACION DE INFORMACIÓN
    public function getUsersWithRoleAttribute()
    {
        $nombres = $this->users()->pluck('name')->implode(', ');
        return $nombres;
    }

    // OTRAS OPERACIONES



}