<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi: 1 user bisa punya banyak reservasi
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Relasi: 1 user bisa punya banyak laporan
    public function facilityReports()
    {
        return $this->hasMany(FacilityReport::class);
    }
}