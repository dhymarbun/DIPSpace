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
}