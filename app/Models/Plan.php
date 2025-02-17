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
 * @property string|null $description
 * @property float $price
 * @property string $billing_cycle
 * @property bool $active
 * @property string|null $external_plan_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|PlanResource[] $plan_resources
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
		'active' => 'bool'
	];

	protected $fillable = [
		'name',
		'slug',
		'description',
		'price',
		'billing_cycle',
		'active',
		'external_plan_id'
	];

	public function plan_resources(): HasMany
	{
		return $this->hasMany(PlanResource::class);
	}

	public function subscriptions(): HasMany
	{
		return $this->hasMany(Subscription::class);
	}
}
