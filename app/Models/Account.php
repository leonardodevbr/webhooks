<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Account
 * 
 * @property int $id
 * @property string $hash
 * @property string $name
 * @property string $slug
 * @property string $email
 * @property string $password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Url[] $urls
 *
 * @package App\Models
 */
class Account extends Model
{
	protected $table = 'accounts';

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'hash',
		'name',
		'slug',
		'email',
		'password'
	];

	public function urls(): HasMany
	{
		return $this->hasMany(Url::class);
	}
}
