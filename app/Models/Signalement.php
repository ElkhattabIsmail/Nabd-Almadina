<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'latitude',
        'longitude',
        'photo',
        'category',
        'priority',
        'urgency',
        'summary',
        'status',
        'departement_id',
        'incident_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'urgency' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
