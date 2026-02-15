<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationAnalysis extends Model
{
    use HasFactory;

    protected $table = 'reservation_analyses';

    protected $fillable = [
        'reservation_id',
        'analysis_id',
        'status'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function analyse()
    {
        return $this->belongsTo(Analyse::class, 'analysis_id');
    }
}
