<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideVideo extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'audience',
        'summary',
        'duration',
        'steps',
        'voiceover',
        'captions',
        'video_type',
        'video_url',
        'video_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'voiceover' => 'array',
            'captions' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
