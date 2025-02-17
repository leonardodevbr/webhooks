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
 * Class Url
 * 
 * @property int $id
 * @property string $ip_address
 * @property int|null $account_id
 * @property string $slug
 * @property bool $notifications_enabled
 * @property int $request_count
 * @property Carbon|null $blocked_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account|null $account
 * @property Collection|WebhookRequest[] $webhook_requests
 * @property Collection|WebhookRetransmissionUrl[] $webhook_retransmission_urls
 *
 * @package App\Models
 */
class Url extends Model
{
	use HasFactory;
	protected $table = 'urls';

	protected $casts = [
		'account_id' => 'int',
		'notifications_enabled' => 'bool',
		'request_count' => 'int',
		'blocked_at' => 'datetime',
		'expires_at' => 'datetime'
	];

	protected $fillable = [
		'ip_address',
		'account_id',
		'slug',
		'notifications_enabled',
		'request_count',
		'blocked_at',
		'expires_at'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}

	public function webhook_requests(): HasMany
	{
		return $this->hasMany(WebhookRequest::class);
	}

	public function webhook_retransmission_urls(): HasMany
	{
		return $this->hasMany(WebhookRetransmissionUrl::class);
	}
}
