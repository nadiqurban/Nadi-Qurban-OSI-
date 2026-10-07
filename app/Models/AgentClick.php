<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $agent_id
 * @property Carbon $date
 * @property int $clicks
 */
class AgentClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['agent_id', 'date', 'clicks'];

    protected function casts(): array
    {
        return ['date' => 'date', 'clicks' => 'integer'];
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
