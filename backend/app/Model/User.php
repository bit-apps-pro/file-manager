<?php

namespace BitApps\FM\Model;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Vendor\BitApps\WPDatabase\Model;

/**
 * Model for log
 *
 * @property string $created_at
 * @property string $command
 * @property string $details
 * @property int    $user_id
 */
class User extends Model
{
    public $timestamps = false;

    protected $prefix = '';

    protected $fillable = [];

    public function log()
    {
        return $this->hasMany(Log::class, 'user_id', 'ID');
    }
}
