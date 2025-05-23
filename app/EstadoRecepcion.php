<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoRecepcion extends Model
{
    protected $table = 'estados_recepcion';

    protected $fillable = [
        'reserva_id',
        'user_id',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // public function scopeFilter($query, $filters)
    // {
    //     if ($filters['reserva_id'] ?? false) {
    //         $query->where('reserva_id', $filters['reserva_id']);
    //     }

    //     if ($filters['user_id'] ?? false) {
    //         $query->where('user_id', $filters['user_id']);
    //     }
    // }
    // public function scopeFilterByDate($query, $date)
    // {
    //     if ($date) {
    //         $query->whereDate('created_at', $date);
    //     }
    // }
    // public function scopeFilterByDateRange($query, $startDate, $endDate)
    // {
    //     if ($startDate && $endDate) {
    //         $query->whereBetween('created_at', [$startDate, $endDate]);
    //     }
    // }
    // public function scopeFilterByUser($query, $userId)
    // {
    //     if ($userId) {
    //         $query->where('user_id', $userId);
    //     }
    // }
    public function scopeFilterByReserva($query, $reservaId)
    {
        if ($reservaId) {
            $query->where('reserva_id', $reservaId);
        }
    }
    // public function scopeFilterByReservaAndUser($query, $reservaId, $userId)
    // {
    //     if ($reservaId && $userId) {
    //         $query->where('reserva_id', $reservaId)->where('user_id', $userId);
    //     }
    // }
}
