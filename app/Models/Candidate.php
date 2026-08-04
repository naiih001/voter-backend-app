<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = ['position_id', 'name', 'matric_number', 'manifesto'];

    /**
     * The position this candidate is contesting.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * The votes cast for this candidate.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
