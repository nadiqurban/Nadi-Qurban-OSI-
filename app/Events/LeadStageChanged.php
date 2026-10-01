<?php

namespace App\Events;

use App\Enums\LeadStage;
use App\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when a lead moves to another Kanban column. */
class LeadStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public LeadStage $from,
        public LeadStage $to,
    ) {}
}
