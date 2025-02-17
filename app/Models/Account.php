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
 * Class Account
 * 
 * @property int $id
 * @property string $name
 * @property string|null $cpf
 * @property string|null $cnpj
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
 * @property Collection|CustomerCard[] $customer_cards
 * @property Collection|Payment[] $payments
 * @property Collection|Subscription[] $subscriptions
 * @property Collection|Url[] $urls
 * @property Collection|User[] $users
 *
 * @package App\Models
 */
class Account extends Model
{
	use HasFactory;
	protected $table = 'accounts';

	protected $casts = [
		'birth_date' => 'datetime'
	];

	protected $fillable = [
		'name',
		'cpf',
		'cnpj',
		'phone',
		'birth_date',
		'street',
		'number',
		'neighborhood',
		'zipcode',
		'city',
		'state'
	];

	public function customer_cards(): HasMany
	{
		return $this->hasMany(CustomerCard::class);
	}

	public function payments(): HasMany
	{
		return $this->hasMany(Payment::class);
	}

	public function subscriptions(): HasMany
	{
		return $this->hasMany(Subscription::class);
	}

	public function urls(): HasMany
	{
		return $this->hasMany(Url::class);
	}

	public function users(): HasMany
	{
		return $this->hasMany(User::class);
	}
}
