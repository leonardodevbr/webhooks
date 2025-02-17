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
 * Class PlanResource
 * 
 * @property int $id
 * @property int $plan_id
 * @property string $name
 * @property string|null $value
 * @property string|null $description
 * @property bool $available
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Plan $plan
 *
 * @package App\Models
 */
class PlanResource extends Model
{
	use HasFactory;
	protected $table = 'plan_resources';

	protected $casts = [
		'plan_id' => 'int',
		'available' => 'bool'
	];

	protected $fillable = [
		'plan_id',
		'name',
		'value',
		'description',
		'available'
	];

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}
}
