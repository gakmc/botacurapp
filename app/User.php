<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use Notifiable;


     public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'dob', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['dob'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['salario'];

    //RELACIONES

    public function permissions()
    {
        return $this->belongsToMany('App\Permission')->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();

    }

    public function reservas()
    {
        return $this->hasMany('App\Reserva');
    }

    public function asignaciones()
    {
        return $this->belongsToMany(Asignacion::class, 'asignacion_user', 'user_id', 'asignacion_id')->withTimestamps();
    }

    public function propinas()
    {
        return $this->belongsToMany(Propina::class, 'propina_user', 'id_user', 'id_propina')
                    ->withPivot('monto_asignado')
                    ->withTimestamps();
    }

    public function sueldos()
    {
        return $this->hasMany(Sueldo::class, 'id_user');
    }

    public function ventas_directas()
    {
        return $this->hasMany(VentaDirecta::class, 'id_user');
    }

    public function masajes()
    {
        return $this->hasMany(Masaje::class, 'user_id');
    }

    public function estadosRecepcion()
    {
        return $this->hasMany(EstadoRecepcion::class, 'user_id');
    }
    
    public function anularSueldo()
    {
        return $this->hasOne(AnularSueldoUsuario::class, 'user_id');
    }
    
    public function asistencias()
    {
        return $this->belongsToMany(Asistencia::class);
    }

    public function sueldosPagados()
    {
        return $this->hasMany(sueldoPagado::class, 'user_id');
    }

        public function inventarioMovimientos()
    {
        return $this->hasMany(InventarioMovimiento::class, 'id_user');
    }

//ALMACENAMIENTO

    public function store($request)
    {
        $user = self::create($request->all());
        $user->update(['password' => Hash::make($request->password)]);
        $roles = [$request->role];
        $user->role_assignment(null, $roles);
        alert('Éxito', 'Usuario creado con éxito', 'success');
        return $user;
    }

    public function my_update($request)
    {
        self::update($request->all());
        alert('Éxito', 'Usuario actualizado', 'success');

    }

    public function role_assignment($request, array $roles = null)
    {
        $roles = (is_null($roles)) ? $request->roles : $roles;
        $this->permission_mass_assigment($roles);
        $this->roles()->sync($roles);
        $this->verify_permission_integrity($roles);
        alert('Éxito', 'Roles asignados', 'success');
    }

    //VALIDACION

    public function is_admin()
    {
        $admin = config('app.admin_role');
        if ($this->has_role($admin)) {
            return true;
        } else {
            return false;
        }
    }

    public function has_role($id)
    {
        foreach ($this->roles as $role) {
            if ($role->id == $id || $role->slug == $id) {
                return true;
            }

        }
        return false;
    }

    public function has_any_role(array $roles)
    {
        foreach ($roles as $role) {
            if ($this->has_role($role)) {
                return true;
            }

        }

        return false;
    }

    public function has_permission($id)
    {
        foreach ($this->permissions as $permission) {
            if ($permission->id == $id || $permission->slug == $id) {
                return true;
            }

        }
        return false;
    }

    public function age()
    {
        if (!is_null($this->dob)) {
            $age = $this->dob->age;
            $years = ($age == 1) ? ' año' : ' años';
            $msj = $age . '' . $years;
        } else {
            $msj = 'Indefinido';
        }
        return $msj;
    }

    // public function visible_users()
    // {
    //     if($this->has_role(config('app.admin_role'))){
    //         $users = self::all();
    //     }elseif($this->has_role(config('app.admin_role'))) {
    //         $users = self::whereHas('roles', function($q){
    //             $q->whereIn('slug', [
    //                 config('app.anfitriona_role'),
    //                 config('app.visit_role'),

    //             ]);
    //         })->get();

    //     }elseif($this->has_role(config('app.anfitriona_role'))) {
    //         $users = self::whereHas('roles', function($q){
    //             $q->whereIn('slug', [
    //                 config('app.visit_role'),
    //             ]);
    //         })->get();
    //     }
    //     return $users;
    // }

    public function visible_users()
    {
        // Si el usuario tiene el rol de administrador, muestra todos los usuarios
        if ($this->has_role(config('app.admin_role'))) {
            return self::all();
        }

        if ($this->has_role(config('app.jefe_local_role'))) {
            return self::whereHas('roles', function ($q) {
                $q->whereIn('slug', [
                    config('app.anfitriona_role'),
                    config('app.garzon_role'),
                    config('app.barman_role'),
                    config('app.cocina_role'),
                    config('app.masoterapeuta_role'),
                    config('app.mantencion_role'),
                ]);
            })->get();
        }
        // Si tiene el rol de anfitriona, muestra los usuarios que tienen los roles de 'anfitriona' o 'visitante'
        if ($this->has_role(config('app.anfitriona_role'))) {
            return self::whereHas('roles', function ($q) {
                $q->whereIn('slug', [
                    config('app.garzon_role'),
                    config('app.barman_role'),
                    config('app.cocina_role'),
                    config('app.masoterapeuta_role'),
                    config('app.mantencion_role'),
                ]);
            })->get();
        }

        // Si no cumple ninguna de las condiciones anteriores, devuelve una colección vacía
        return collect();
    }

    public function has_speciality($id)
    {
        foreach ($this->specialities as $speciality) {
            if ($speciality->id == $id) {
                return true;
            }

        }
        return false;
    }

    //OTRAS OPERACIONES

    public function verify_permission_integrity(array $roles)
    {
        $permissions = $this->permissions;
        foreach ($permissions as $permission) {
            if (!in_array($permission->role->id, $roles)) {
                $this->permissions()->detach($permission->id);
            }
        }
    }

    public function permission_mass_assigment(array $roles)
    {
        foreach ($roles as $role) {
            if (!$this->has_role($role)) {
                $role_obj = \App\Role::findOrFail($role);
                $permissions = $role_obj->permissions;
                $this->permissions()->syncWithoutDetaching($permissions);
            }
        }
    }

    public function list_roles()
    {
        $roles = $this->roles->pluck('name')->toArray();
        $string = implode(', ', $roles);
        return $string;
    }



    public function getSalarioAttribute()
    {
        if ($this->anularSueldo) {
            return $this->anularSueldo->salario;
        }

        // $hoy = now()->toDateString();
        $hoy = Carbon::now();

        $rango = $this->roles()
            ->with('rangoSueldo')
            ->get()
            ->pluck('rangoSueldo')
            ->flatten()
            ->where('vigente_desde', '<=', $hoy)
            ->filter(function ($r) use ($hoy) {
                return is_null($r->vigente_hasta) || $r->vigente_hasta >= $hoy;
            })
            ->sortByDesc('vigente_desde')
            ->first();

        return $rango ? $rango->sueldo_base : 0;
    }

}
