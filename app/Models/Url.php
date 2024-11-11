<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Url
 * 
 * @property int $id
 * @property string $hash
 * @property string $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Webhook[] $webhooks
 *
 * @package App\Models
 */
class Url extends Model
{
	protected $table = 'urls';

	protected $fillable = [
		'hash',
		'ip_address'
	];

	public function webhooks()
	{
		return $this->hasMany(Webhook::class);
	}
}
