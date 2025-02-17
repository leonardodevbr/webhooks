<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class User
 * 
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool $is_owner
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Account $account
 * @property Collection|Role[] $roles
 *
 * @package App\Models
 */
class User extends \Illuminate\Foundation\Auth\User
{
	use HasFactory;
	protected $table = 'users';

	protected $casts = [
		'account_id' => 'int',
		'is_owner' => 'bool'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'account_id',
		'name',
		'email',
		'password',
		'is_owner',
		'remember_token'
	];

	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class);
	}

	public function roles(): BelongsToMany
	{
		return $this->belongsToMany(Role::class)
					->withPivot('id')
					->withTimestamps();
	}
}
