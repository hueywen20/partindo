<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Models\PurchaseItem;
use App\Models\SaleItem;

class Product extends Model implements Auditable
{
    //
    use AuditableTrait;
    
    protected $fillable = [
        'location',
        'name',
        // 'part_no',
        'stock',
        'unit',
        'uom',
        'brand',
        'category',
        'code',
        'track_low_stock',
        'min_stock_threshold',
        'created_by',
        'updated_by',
        'is_composite',

        // add all fields you allow
    ];

    protected static function booted(): void
    {
        static::creating(function ($product) {
            $product->created_by = Auth::user()?->name;
            $product->updated_by = Auth::user()?->name;
        });

        static::updating(function ($product) {
            $product->updated_by = Auth::user()?->name;
        });
    }

    public static function getCategoryOptions(): array
    {
        return \App\Models\Category::query()
            ->where('status', 1)  // only active categories
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getUomOptions(): array
    {

        return \App\Models\Uom::query()
            ->where('status', 1)  // only active uoms
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getBrandOptions(): array
    {
        return \App\Models\Brand::query()
            ->where('status', 1)  // only active brands
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getLocationOptions(): array
    {
        return \App\Models\ProductLocation::query()
            ->where('status', 1)  // only active locations
            ->pluck('name', 'id')
            ->toArray();
    }

    public function brandModel()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand');
    }

    public function categoryModel()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category');
    }

    public function uomModel()
    {
        return $this->belongsTo(\App\Models\Uom::class, 'uom');
    }

    public function locationModel()
    {
        return $this->belongsTo(\App\Models\ProductLocation::class, 'location');
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_low_stock', true)
                    ->whereColumn('stock', '<', 'min_stock_threshold');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    // public function codes()
    // {
    //     return $this->hasMany(ProductCode::class);
    // }

    // Add relationships:
    public function recipeSlots()
    {
        return $this->hasMany(ProductRecipeSlot::class, 'composite_product_id');
    }

    // Computed: how many of this composite can be built right now
    public function getAvailableBuildsAttribute(): int
    {
        if (! $this->is_composite) return $this->stock ?? 0;

        return $this->recipeSlots
            ->map(function ($slot) {
                $default = $slot->defaultSubstitute?->product;
                if (! $default || $slot->quantity <= 0) return 0;
                return (int) floor($default->stock / $slot->quantity);
            })
            ->min() ?? 0;
    }


}
