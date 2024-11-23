<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class WebhookRetransmissionUrl
 * 
 * @property int $id
 * @property int $url_id
 * @property string $url
 * @property bool $process_immediately
 * @property bool $is_online
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class WebhookRetransmissionUrl extends Model
{
	protected $table = 'webhook_retransmission_urls';

	protected $casts = [
		'url_id' => 'int',
		'process_immediately' => 'bool',
		'is_online' => 'bool'
	];

	protected $fillable = [
		'url_id',
		'url',
		'process_immediately',
		'is_online'
	];

	public function url(): BelongsTo
	{
		return $this->belongsTo(Url::class);
	}
}
