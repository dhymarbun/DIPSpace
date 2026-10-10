<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facility_id',
        'start_time',
        'end_time',
        'tujuan',
        'status',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Relasi: 1 reservasi punya 1 user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: 1 reservasi punya 1 fasilitas
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Reservasi di fasilitas yang sama yang waktunya beririsan dengan [start, end).
     * Memakai perbandingan ketat, sehingga slot yang bersambung (mis. 08:00–09:00
     * lalu 09:00–10:00) TIDAK dianggap bentrok.
     */
    public function scopeOverlapping($query, $facilityId, $start, $end)
    {
        return $query->where('facility_id', $facilityId)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }
}