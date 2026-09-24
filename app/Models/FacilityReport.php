<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'facility_id',
        'category',
        'report_date',
        'description',
        'photo_path',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    // Relasi: 1 laporan punya 1 user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: 1 laporan punya 1 fasilitas
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}