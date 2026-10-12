<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEditLog extends Model
{
    protected $table = 'order_edit_logs';

    protected $fillable = [
        'order_id',
        'edited_by',
        'editor_name',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
