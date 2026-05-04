<?php

namespace App\Models\DataMaster;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
    ];

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'categories_id', 'id');
    }
}
