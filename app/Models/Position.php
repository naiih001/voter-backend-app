<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['title', 'description'];

    /**
     * The candidates contesting this position.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * The votes cast for this position.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
