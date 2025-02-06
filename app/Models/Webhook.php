<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Webhook
 * 
 * @property int $id
 * @property string $hash
 * @property int $url_id
 * @property int|null $subscription_id
 * @property Carbon|null $blocked_at
 * @property Carbon|null $timestamp
 * @property string $method
 * @property array|null $headers
 * @property array|null $query_params
 * @property string|null $body
 * @property array|null $form_data
 * @property string $host
 * @property int|null $size
 * @property bool $retransmitted
 * @property bool $viewed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Subscription|null $subscription
 * @property Url $url
 *
 * @package App\Models
 */
class Webhook extends Model
{
	use HasFactory;
	protected $table = 'webhooks';

	protected $casts = [
		'url_id' => 'int',
		'subscription_id' => 'int',
		'blocked_at' => 'datetime',
		'timestamp' => 'datetime',
		'headers' => 'array',
		'query_params' => 'array',
		'form_data' => 'array',
		'size' => 'int',
		'retransmitted' => 'bool',
		'viewed' => 'bool'
	];

	protected $fillable = [
		'hash',
		'url_id',
		'subscription_id',
		'blocked_at',
		'timestamp',
		'method',
		'headers',
		'query_params',
		'body',
		'form_data',
		'host',
		'size',
		'retransmitted',
		'viewed'
	];

	public function subscription(): BelongsTo
	{
		return $this->belongsTo(Subscription::class);
	}

	public function url(): BelongsTo
	{
		return $this->belongsTo(Url::class);
	}
}
