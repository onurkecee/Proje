<?php

namespace App\Models;

use App\Enums\BillType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property BillType $type
 * @property int $expected_amount_minor
 * @property Carbon $first_due_on
 * @property bool $repeat_monthly
 * @property Carbon|null $ends_on
 * @property bool $is_active
 * @property int $paid_occurrences_count
 * @property int $occurrences_count
 */
#[Fillable(['name', 'type', 'expected_amount_minor', 'first_due_on', 'repeat_monthly', 'ends_on', 'is_active'])]
class Bill extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BillOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(BillOccurrence::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BillType::class,
            'expected_amount_minor' => 'integer',
            'first_due_on' => 'date',
            'repeat_monthly' => 'boolean',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
