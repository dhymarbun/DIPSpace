<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'tipe',
        'lokasi',
        'kapasitas',
        'status',
    ];

    // Relasi: 1 fasilitas bisa punya banyak reservasi
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Relasi: 1 fasilitas bisa punya banyak laporan
    public function facilityReports()
    {
        return $this->hasMany(FacilityReport::class);
    }
}