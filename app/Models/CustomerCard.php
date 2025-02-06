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
 * Class CustomerCard
 * 
 * @property int $id
 * @property int|null $account_id
 * @property string $payment_token
 * @property string $card_brand
 * @property string $card_mask
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account|null $account
 *
 * @package App\Models
 */
class CustomerCard extends Model
{
	use HasFactory;
	protected $table = 'customer_cards';

	protected $casts = [
		'account_id' => 'int'
	];

	protected $hidden = [
		'payment_token'
	];

	protected $fillable = [
		'account_id',
		'payment_token',
		'card_brand',
		'card_mask'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}
}
