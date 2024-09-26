<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'initial_balance',
        'final_balance',
        'difference',
        'observations',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
