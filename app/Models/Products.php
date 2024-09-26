<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Products extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'price',
        'category_id',
        'subcategory_id',
        'fecha_creacion',
        'user_id',
        'company_id',
    ];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function assignedWarehouse()
    {
        return $this->hasOne(ProductStock::class, 'product_id', 'id')->with('warehouse');
    }

    public function category()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategories::class);
    }
}
