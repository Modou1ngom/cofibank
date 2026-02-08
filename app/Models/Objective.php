<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Objective extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'category',
        'territory',
        'zone',
        'agency_code',
        'agency_name',
        'value',
        'period',
        'year',
        'month',
        'quarter',
        'description',
        'status',
        'created_by',
        'validated_by',
        'validated_at',
        'rejection_reason'
    ];

    protected $casts = [
        'value' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
        'validated_at' => 'datetime'
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopePendingValidation($query)
    {
        return $query->where('status', 'pending_validation');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
