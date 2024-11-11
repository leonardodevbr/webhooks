<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Webhook
 *
 * @property string $id
 * @property int $url_id
 * @property Carbon|null $timestamp
 * @property string $method
 * @property string|null $headers
 * @property string|null $query_params
 * @property string|null $body
 * @property string|null $form_data
 * @property string $host
 * @property int|null $size
 * @property bool $retransmitted
 * @property bool $viewed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Url $url
 *
 * @package App\Models
 */
class Webhook extends Model
{
	protected $table = 'webhooks';
	public $incrementing = false;

	protected $casts = [
		'url_id' => 'int',
		'timestamp' => 'datetime',
		'size' => 'int',
		'retransmitted' => 'bool',
		'viewed' => 'bool'
	];

	protected $fillable = [
		'id',
		'url_id',
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

	public function url()
	{
		return $this->belongsTo(Url::class);
	}
}
