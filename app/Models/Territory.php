<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Territory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'responsible_user_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relation avec le responsable de zone
    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // Relation avec les agences du territoire
    public function agencies()
    {
        return $this->hasMany(Agency::class);
    }

    // Relation avec les utilisateurs du territoire
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
