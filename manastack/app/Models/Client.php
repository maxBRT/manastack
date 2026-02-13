<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'player_id',
        'client_id',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
