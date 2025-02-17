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
 * Class WebhookRetransmissionUrl
 * 
 * @property int $id
 * @property int $url_id
 * @property int|null $subscription_id
 * @property Carbon|null $blocked_at
 * @property string $url
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Subscription|null $subscription
 * @property Collection|WebhookRetransmission[] $webhook_retransmissions
 *
 * @package App\Models
 */
class WebhookRetransmissionUrl extends Model
{
	use HasFactory;
	protected $table = 'webhook_retransmission_urls';

	protected $casts = [
		'url_id' => 'int',
		'subscription_id' => 'int',
		'blocked_at' => 'datetime',
		'is_public' => 'bool'
	];

	protected $fillable = [
		'url_id',
		'subscription_id',
		'blocked_at',
		'url',
		'is_public'
	];

	public function subscription(): BelongsTo
	{
		return $this->belongsTo(Subscription::class);
	}

	public function url(): BelongsTo
	{
		return $this->belongsTo(Url::class);
	}

	public function webhook_retransmissions(): HasMany
	{
		return $this->hasMany(WebhookRetransmission::class);
	}
}
