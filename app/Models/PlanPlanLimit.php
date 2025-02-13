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
 * Class PlanPlanLimit
 * 
 * @property int $id
 * @property int $plan_id
 * @property int $plan_limit_id
 * @property int $limit_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Plan $plan
 * @property PlanLimit $plan_limit
 *
 * @package App\Models
 */
class PlanPlanLimit extends Model
{
	use HasFactory;
	protected $table = 'plan_plan_limits';

	protected $casts = [
		'plan_id' => 'int',
		'plan_limit_id' => 'int',
		'limit_value' => 'int'
	];

	protected $fillable = [
		'plan_id',
		'plan_limit_id',
		'limit_value'
	];

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}

	public function plan_limit(): BelongsTo
	{
		return $this->belongsTo(PlanLimit::class);
	}
}
