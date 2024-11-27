<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Url
 * 
 * @property int $id
 * @property string $hash
 * @property string $ip_address
 * @property int|null $account_id
 * @property string|null $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account|null $account
 * @property Collection|WebhookRetransmissionUrl[] $webhook_retransmission_urls
 * @property Collection|Webhook[] $webhooks
 *
 * @package App\Models
 */
class Url extends Model
{
	protected $table = 'urls';

	protected $casts = [
		'account_id' => 'int'
	];

	protected $fillable = [
		'hash',
		'ip_address',
		'account_id',
		'slug'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
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
