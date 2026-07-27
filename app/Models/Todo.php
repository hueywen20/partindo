<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    //
    protected $fillable = [
        'description',
        'is_done',
        'created_by',
        'completed_by',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
