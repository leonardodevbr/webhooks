<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Plan
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property string $billing_cycle
 * @property bool $active
 * @property int $max_urls
 * @property int $max_webhooks_per_url
 * @property int $max_retransmission_urls
 * @property string|null $external_plan_id
 * @property bool $supports_custom_slugs
 * @property bool $real_time_notifications
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Payment[] $payments
 * @property Collection|Subscription[] $subscriptions
 *
 * @package App\Models
 */
class Plan extends Model
{
	use HasFactory;
	protected $table = 'plans';

	protected $casts = [
		'price' => 'float',
		'active' => 'bool',
		'max_urls' => 'int',
		'max_webhooks_per_url' => 'int',
		'max_retransmission_urls' => 'int',
		'supports_custom_slugs' => 'bool',
		'real_time_notifications' => 'bool'
	];

	protected $fillable = [
		'name',
		'slug',
		'price',
		'billing_cycle',
		'active',
		'max_urls',
		'max_webhooks_per_url',
		'max_retransmission_urls',
		'external_plan_id',
		'supports_custom_slugs',
		'real_time_notifications'
	];

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class);
	}

	public function subscriptions(): HasMany
	{
		return $this->hasMany(Subscription::class);
	}
}
