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
 * @property int|null $account_id
 * @property string|null $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account|null $account
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

	public function account()
	{
		return $this->belongsTo(Account::class);
	}

	public function webhooks()
	{
		return $this->hasMany(Webhook::class);
	}
}
