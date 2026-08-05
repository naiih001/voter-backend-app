<?php

namespace App\Models;

use App\Enums\ElectionStatus;
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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ElectionStatus::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
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
        return $query->where('status', ElectionStatus::OPEN);
    }

    public function scopeActive($query)
    {
        return $query->open()
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }
}
