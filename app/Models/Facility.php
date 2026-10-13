<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Facility extends Model
{
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_DALAM_PERBAIKAN = 'dalam_perbaikan';

    /**
     * Nilai yang diizinkan kolom `status` (harus sinkron dengan enum di migrasi).
     */
    public const STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_DALAM_PERBAIKAN,
    ];

    protected $fillable = [
        'nama',
        'tipe',
        'lokasi',
        'kapasitas',
        'status',
        'foto',
        'deskripsi',
    ];

    /**
     * URL publik foto fasilitas (null bila belum ada foto).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

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