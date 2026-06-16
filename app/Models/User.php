<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Rol;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Acceso al panel de administración Filament.
     *
     * Solo usuarios con un rol válido (admin o empleado). Lo que pueden hacer
     * dentro se controla además por rol (ver los recursos Filament y App\Enums\Rol).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->esAdmin() || $this->esEmpleado();
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    public function esEmpleado(): bool
    {
        return $this->rol === Rol::Empleado;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'rol',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }
}
