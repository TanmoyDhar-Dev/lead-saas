<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundMessage extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'imported_outreach_recipient_id',
        'graph_message_id',
        'from_email',
        'subject',
        'body_text',
        'body_html',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function importedOutreachRecipient(): BelongsTo
    {
        return $this->belongsTo(ImportedOutreachRecipient::class);
    }
}
