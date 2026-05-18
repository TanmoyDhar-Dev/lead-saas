<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvImport extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'missing_required_columns',
        'validation_errors',
    ];

    protected $casts = [
        'missing_required_columns' => 'array',
        'validation_errors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }
}
