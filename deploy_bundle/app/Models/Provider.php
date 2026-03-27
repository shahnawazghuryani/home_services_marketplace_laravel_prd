<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'experience_years',
        'hourly_rate',
        'service_area',
        'availability',
        'approved_at',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'is_featured' => 'boolean',
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'provider_id', 'user_id');
    }
}
