<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Company extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'companies';
    protected $fillable = ['name', 'address', 'phone_number', 'email', 'instagram', 'facebook', 'twitter', 'image_url'];

    public function users()
    {
        return $this->hasMany(User::class, 'user_id');
    }
}
