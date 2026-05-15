<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    //

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
        'created_by',
        'updated_by',

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
        return [
            'oring' => 'Oring',
            'plate_thrust' => 'Plate Thrust',
            'packing_rod' => 'Packing Rod',
            'seal_dust' => 'Seal Dust',
            'ring_back_up' => 'Ring Back Up',
            'seal_piston' => 'Seal Piston',
            'ring_wear' => 'Ring Wear',
            'ring_teplon' => 'Ring Teplon',
            'buffer_seal' => 'Buffer Seal',
            'radiator' => 'Radiator',
            'engine_piston' => 'Engine Piston',
            'water_pump' => 'Water Pump',
            'valve_intake' => 'Valve Intake',
            'valve_exhaust' => 'Valve Exhaust',
            'gasket_head' => 'Gasket Head',
            'bolt_cylinder_head' => 'Bolt Cylinder Head',
            'connecting_rod' => 'Connecting Rod',
            'metal_main_std' => 'Metal Main STD',
            'metal_conn_rod_std' => 'Metal Conn Rod STD',
            'metal_thrust_std' => 'Metal Thrust STD',
            'ring_piston' => 'Ring Piston',
            'kit_gasket_overhaul' => 'Kit Gasket Overhaul',
            'turbo' => 'Turbo',
            'starting_motor' => 'Starting Motor',
            'alternator' => 'Alternator',
            'seal_group' => 'Seal Group',
            'seal_oil' => 'Seal Oil',
            'gear_pump' => 'Gear Pump',
            'rotor' => 'Rotor',
            'piston_shoe' => 'Piston Shoe',
            'retainer_shoe' => 'Retainer Shoe',
            'plate_shoe' => 'Plate Shoe',
            'ball_guide' => 'Ball Guide',
            'valve_plate' => 'Valve Plate',
            'swash_plate' => 'Swash Plate',
            'piston_servo' => 'Piston Servo',
            'others' => 'Others',
        ];
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

    public function uomModel()
    {
        return $this->belongsTo(\App\Models\Uom::class, 'uom');
    }

    public function locationModel()
    {
        return $this->belongsTo(\App\Models\ProductLocation::class, 'location');
    }

    // public function codes()
    // {
    //     return $this->hasMany(ProductCode::class);
    // }

}
