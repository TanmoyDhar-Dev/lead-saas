<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadAutomationDetail extends Model
{
    protected $guarded = [];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
