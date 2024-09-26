<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customers extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'customers';
    protected $fillable = ['user_id', 'account_number', 'name', 'lastname', 'address', 'phone_number', 'birthdate', 'national_id', 'status', 'gender'];

    // Evento que se ejecuta antes de crear un nuevo cliente
    protected static function booted()
    {
        static::creating(function ($customer) {
            $customer->account_number = Customers::generateUniqueAccountNumber();
        });
    }

    // Método para generar un número de cuenta único
    public static function generateUniqueAccountNumber()
    {
        do {
            // Genera un número aleatorio de 10 dígitos
            $accountNumber = random_int(1000000000, 9999999999);
        } while (self::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }
}
