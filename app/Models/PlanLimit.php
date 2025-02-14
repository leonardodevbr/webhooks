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
 * @property string|null $limit_value
 * @property string|null $description
 * @property bool $available
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
		'available' => 'bool'
	];

	protected $fillable = [
		'plan_id',
		'resource',
		'limit_value',
		'description',
		'available'
	];

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}
}
