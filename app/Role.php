<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;



class Role extends Model
{
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
        return self::create($request->all() + [
            'slug' => $slug,
        ]);
    }

    public function my_update($request)
    {
        $slug = Str::slug($request->name, '-');
        Alert::success('Éxito', 'Rol actualizado')->showConfirmButton();
        return self::update($request->all() + [
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