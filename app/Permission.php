<?php

namespace App;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;




class Permission extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_SEGURIDAD;

    protected $auditoriaCampos = [
        'name'        => 'Nombre',
        'slug'        => 'Slug',
        'description' => 'Descripción',
        'role_id'     => ['Rol', 'App\Role@name'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el permiso ' . $this->name
            . ($this->role ? ' del rol ' . $this->role->name : '');
    }

    protected $fillable = [
        'name', 'slug', 'description', 'role_id'
    ];


    // RELACIONES 
    public function role() 
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }


    public function store($request)
    {
        $slug = Str::slug($request->name, '-');
        Alert::success('Éxito', 'Permiso creado')->showConfirmButton();
        // Solo los campos del formulario; el slug siempre se calcula (antes uno enviado en el request lo pisaba)
        return self::create($request->only(['name', 'description', 'role_id']) + [
            'slug' => $slug,
        ]);
    }

    public function my_update($request)
    {
        $slug = Str::slug($request->name, '-');
        Alert::success('Éxito', 'Permiso actualizado')->showConfirmButton();
        return self::update($request->only(['name', 'description', 'role_id']) + [
            'slug' => $slug,
        ]);
        
    }

}
