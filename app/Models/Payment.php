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
 * Class Payment
 * 
 * @property int $id
 * @property int $account_id
 * @property int $plan_id
 * @property string|null $gateway_reference
 * @property string $status
 * @property float $amount
 * @property string|null $payment_method
 * @property array|null $gateway_response
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account $account
 * @property Plan $plan
 *
 * @package App\Models
 */
class Payment extends Model
{
	use HasFactory;
	protected $table = 'payments';

	protected $casts = [
		'account_id' => 'int',
		'plan_id' => 'int',
		'amount' => 'float',
		'gateway_response' => 'json',
		'paid_at' => 'datetime'
	];

	protected $fillable = [
		'account_id',
		'plan_id',
		'gateway_reference',
		'status',
		'amount',
		'payment_method',
		'gateway_response',
		'paid_at'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}

	public function plan(): BelongsTo
	{
		return $this->belongsTo(Plan::class);
	}
}
