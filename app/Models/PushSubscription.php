<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PushSubscription
 * 
 * @property int $id
 * @property int|null $user_id
 * @property string $endpoint
 * @property string $p256dh
 * @property string $auth
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class PushSubscription extends Model
{
	use HasFactory;
	protected $table = 'push_subscriptions';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'user_id',
		'endpoint',
		'p256dh',
		'auth'
	];
}
