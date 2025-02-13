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
 * Class PlanLimit
 * 
 * @property int $id
 * @property int $plan_id
 * @property string $resource
 * @property int $limit_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Plan $plan
 *
 * @package App\Models
 */
class PlanLimit extends Model
{
	use HasFactory;
	protected $table = 'plan_limits';

	protected $casts = [
		'plan_id' => 'int',
		'limit_value' => 'int'
	];

	protected $fillable = [
		'plan_id',
		'resource',
		'limit_value'
	];

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}
}
