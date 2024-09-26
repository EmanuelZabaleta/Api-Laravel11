<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Categorie extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'categories';
    protected $fillable = ['name', 'slug'];

    public function subcategories()
    {
        return $this->hasMany(Subcategories::class);
    }
}
