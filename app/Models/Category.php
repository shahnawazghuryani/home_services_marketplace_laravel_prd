<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function servicesMany(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }
}
