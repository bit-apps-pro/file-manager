<?php

namespace BitApps\FM\Model;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Config;
use BitApps\FM\Vendor\BitApps\WPDatabase\Model;

/**
 * Model for log
 *
 * @property string $created_at
 * @property string $command
 * @property string $details
 * @property int    $user_id
 */
class Log extends Model
{
    public $timestamps = false;

    public $casts = ['details' => 'object'];

    protected $prefix = Config::VAR_PREFIX;

    protected $fillable = [
        'user_id',
        'command',
        'details',
        'created_at',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'ID', 'user_id');
    }
}
