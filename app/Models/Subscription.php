<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Subscription
 * 
 * @property int $id
 * @property int $plan_id
 * @property int $account_id
 * @property string|null $external_subscription_id
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account $account
 * @property Plan $plan
 * @property Collection|Payment[] $payments
 * @property Collection|WebhookRetransmissionUrl[] $webhook_retransmission_urls
 * @property Collection|Webhook[] $webhooks
 *
 * @package App\Models
 */
class Subscription extends Model
{
	use HasFactory;
	protected $table = 'subscriptions';

	protected $casts = [
		'plan_id' => 'int',
		'account_id' => 'int',
		'started_at' => 'datetime',
		'expires_at' => 'datetime',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'plan_id',
		'account_id',
		'external_subscription_id',
		'started_at',
		'expires_at',
		'is_active'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class);
	}

	public function webhook_retransmission_urls(): HasMany
	{
		return $this->hasMany(WebhookRetransmissionUrl::class);
	}

	public function webhooks(): HasMany
	{
		return $this->hasMany(Webhook::class);
	}
}
