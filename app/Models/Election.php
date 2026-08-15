<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Election extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'start_time',
        'end_time',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeActive($query)
    {
        return $query->open()
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }

    public function getStatusAttribute(): string
    {
        if (! $this->published_at) {
            return 'draft';
        }

        if (now()->lt($this->start_time)) {
            return 'scheduled';
        }

        return now()->lte($this->end_time) ? 'open' : 'closed';
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
