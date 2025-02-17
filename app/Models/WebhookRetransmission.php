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
 * Class WebhookRetransmission
 * 
 * @property int $id
 * @property int $webhook_request_id
 * @property int $webhook_retransmission_url_id
 * @property int $attempts
 * @property Carbon|null $last_attempt_at
 * @property string $status
 * @property int|null $response_status
 * @property string|null $response_body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property WebhookRequest $webhook_request
 * @property WebhookRetransmissionUrl $webhook_retransmission_url
 *
 * @package App\Models
 */
class WebhookRetransmission extends Model
{
	use HasFactory;
	protected $table = 'webhook_retransmissions';

	protected $casts = [
		'webhook_request_id' => 'int',
		'webhook_retransmission_url_id' => 'int',
		'attempts' => 'int',
		'last_attempt_at' => 'datetime',
		'response_status' => 'int'
	];

	protected $fillable = [
		'webhook_request_id',
		'webhook_retransmission_url_id',
		'attempts',
		'last_attempt_at',
		'status',
		'response_status',
		'response_body'
	];

	public function webhook_request(): BelongsTo
	{
		return $this->belongsTo(WebhookRequest::class);
	}

	public function webhook_retransmission_url(): BelongsTo
	{
		return $this->belongsTo(WebhookRetransmissionUrl::class);
	}
}
