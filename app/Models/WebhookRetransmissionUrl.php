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
 * Class WebhookRetransmissionUrl
 * 
 * @property int $id
 * @property int $url_id
 * @property string $url
 * @property bool $is_online
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 *
 * @package App\Models
 */
class WebhookRetransmissionUrl extends Model
{
	use HasFactory;
	protected $table = 'webhook_retransmission_urls';

	protected $casts = [
		'url_id' => 'int',
		'is_online' => 'bool'
	];

	protected $fillable = [
		'url_id',
		'url',
		'is_online'
	];

	public function url(): BelongsTo
	{
		return $this->belongsTo(Url::class);
	}
}
