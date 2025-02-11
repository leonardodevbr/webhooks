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
 * @property int $subscription_id
 * @property string $external_payment_id
 * @property string $status
 * @property float $amount
 * @property string|null $payment_method
 * @property array|null $gateway_response
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account $account
 * @property Subscription $subscription
 *
 * @package App\Models
 */
class Payment extends Model
{
	use HasFactory;
	protected $table = 'payments';

	protected $casts = [
		'account_id' => 'int',
		'subscription_id' => 'int',
		'amount' => 'float',
		'gateway_response' => 'json',
		'paid_at' => 'datetime'
	];

	protected $fillable = [
		'account_id',
		'subscription_id',
		'external_payment_id',
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

	public function subscription(): BelongsTo
	{
		return $this->belongsTo(Subscription::class);
	}
}
