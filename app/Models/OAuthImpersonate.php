<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Models;

use App\Models\Base\Model;
use App\ModelTraits\MemorizeTrait;
use Illuminate\Support\Str;

/**
 * Class OAuthImpersonate
 * @package App\Models
 * @property int $user_id
 * @property int $via_user_id
 * @property string $impersonate_token
 * @property string $access_token_id
 * @property Admin $admin
 */
class OAuthImpersonate extends Model
{
    use MemorizeTrait;

    protected $table = 'oauth_impersonates';

    protected $fillable = [
        'user_id',
        'via_user_id',
        'impersonate_token',
        'access_token_id',
    ];

    protected $visible = [
        'user_id',
        'via_user_id',
        'impersonate_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->impersonate_token = Str::random(128);
        });
    }

    public function getAdminAttribute()
    {
        if (!$this->memorized('admin')) {
            $admin = $this->admin()->first();
            $admin->load('user');
            $this->memorize('admin', $admin);
        }
        return $this->remind('admin');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'via_user_id', 'user_id');
    }
}
