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
        'is_verified',
        'rejection_reason',
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
            'is_verified' => 'boolean',
        ];
    }

    // Akun pengguna yang ditolak admin (belum diverifikasi + ada alasan penolakan).
    public function isRejected(): bool
    {
        return $this->role === 'pengguna' && ! $this->is_verified && ! empty($this->rejection_reason);
    }

    // Akun pengguna yang masih menunggu keputusan admin.
    public function isPendingVerification(): bool
    {
        return $this->role === 'pengguna' && ! $this->is_verified && empty($this->rejection_reason);
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