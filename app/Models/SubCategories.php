<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class SubCategories extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'subcategories';
    protected $fillable = ['category_id', 'name', 'slug'];

    public function category()
    {
        return $this->belongsTo(Categorie::class);
    }
}
