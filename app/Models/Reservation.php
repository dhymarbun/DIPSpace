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

    /**
     * Apakah reservasi sudah mulai (waktu mulai sudah lewat/sama dengan sekarang).
     */
    public function hasStarted(): bool
    {
        return $this->start_time !== null && $this->start_time <= now();
    }

    /**
     * Reservasi berstatus approved yang sedang berlangsung (sudah mulai, belum selesai).
     */
    public function isOngoing(): bool
    {
        return $this->status === 'approved'
            && $this->hasStarted()
            && $this->end_time !== null
            && $this->end_time > now();
    }

    /**
     * Reservasi berstatus approved yang sudah selesai (waktu selesai sudah lewat).
     * "Selesai" adalah status turunan dari waktu, bukan kolom tersendiri.
     */
    public function isFinished(): bool
    {
        return $this->status === 'approved'
            && $this->end_time !== null
            && $this->end_time <= now();
    }

    /**
     * Status tampilan yang memperhitungkan waktu: approved yang sudah lewat
     * menjadi "finished", yang sedang berjalan menjadi "ongoing".
     */
    public function displayStatus(): string
    {
        if ($this->status === 'approved') {
            if ($this->isFinished()) {
                return 'finished';
            }

            if ($this->isOngoing()) {
                return 'ongoing';
            }
        }

        return $this->status;
    }

    /**
     * Label untuk badge status reservasi.
     */
    public function displayStatusLabel(): string
    {
        return match ($this->displayStatus()) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'ongoing' => 'Berlangsung',
            'finished' => 'Selesai',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }
}