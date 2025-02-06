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
 * @property string|null $external_subscription_id
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Plan $plan
 * @property Collection|Account[] $accounts
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
		'started_at' => 'datetime',
		'expires_at' => 'datetime',
		'is_active' => 'bool'
	];

	protected $fillable = [
		'plan_id',
		'external_subscription_id',
		'started_at',
		'expires_at',
		'is_active'
	];

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}

	public function accounts(): HasMany
	{
		return $this->hasMany(Account::class);
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
