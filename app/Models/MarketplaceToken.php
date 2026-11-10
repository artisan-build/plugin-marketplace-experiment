<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property Carbon|null $revoked_at
 */
class MarketplaceToken extends Model
{
    protected $guarded = [];

    protected $hidden = ['token', 'token_hash'];

    protected function casts(): array
    {
        return ['token' => 'encrypted', 'revoked_at' => 'datetime'];
    }

    public static function forUser(User $user, bool $regenerate = false): self
    {
        return DB::transaction(function () use ($user, $regenerate) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $active = self::where('user_id', $user->id)->whereNull('revoked_at');

            if (! $regenerate && ($token = (clone $active)->first())) {
                return $token;
            }

            $active->update(['revoked_at' => now()]);
            $secret = bin2hex(random_bytes(32));

            return self::create([
                'user_id' => $user->id,
                'token' => $secret,
                'token_hash' => hash('sha256', $secret),
            ]);
        });
    }
}
