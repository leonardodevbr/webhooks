<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;

/**
 * Class Account
 * 
 * @property int $id
 * @property string $hash
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string $password
 * @property bool $is_admin
 * @property int|null $subscription_id
 * @property string|null $cpf
 * @property string|null $phone
 * @property Carbon|null $birth_date
 * @property string|null $street
 * @property string|null $number
 * @property string|null $neighborhood
 * @property string|null $zipcode
 * @property string|null $city
 * @property string|null $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Subscription|null $subscription
 * @property Collection|CustomerCard[] $customer_cards
 * @property Collection|Payment[] $payments
 * @property Collection|Url[] $urls
 *
 * @package App\Models
 */
class Account extends User
{
	use HasFactory;
	protected $table = 'accounts';

	protected $casts = [
		'is_admin' => 'bool',
		'subscription_id' => 'int',
		'birth_date' => 'datetime'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'hash',
		'name',
		'slug',
		'email',
		'password',
		'is_admin',
		'subscription_id',
		'cpf',
		'phone',
		'birth_date',
		'street',
		'number',
		'neighborhood',
		'zipcode',
		'city',
		'state'
	];

	public function subscription(): BelongsTo
	{
		return $this->belongsTo(Subscription::class);
	}

	public function customer_cards(): HasMany
	{
		return $this->hasMany(CustomerCard::class);
	}

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class);
	}

	public function urls(): HasMany
	{
		return $this->hasMany(Url::class);
	}
}
