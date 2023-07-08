<?php
/**
 * This file contains the User model
 */

namespace App\Models;

use Charm\Vivid\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class User
 *
 * User model
 *
 * @package App\Models
 */
class User extends Model
{
    /** @var string table name */
    protected $table = 'users';
    
    /** @var array fields for mass insert */
    protected $fillable = ['id'];

    use SoftDeletes;

    /**
     * The database table structure for up-migration
     *
     * @return \Closure
     */
    public static function getTableStructure(): \Closure
    {
        return function(Blueprint $table) {
            $table->increments('id');

            // Basic fields
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('secret', 128)->nullable(); // 2FA secret

            // User data
            $table->integer('gender')->nullable(); // 1 - male, 2 - female
            $table->string('name')->nullable();

            // API token
            $table->string('api_token')->nullable();

            $table->dateTime('last_login')->nullable();
            $table->boolean('enabled')->default(0);

            $table->timestamps();
            $table->softDeletes();
        };
    }

    /**
     * Get default (system) user
     *
     * @return self
     */
    public static function getDefaultUser()
    {
        return self::where('username', 'system')->first();
    }

    /**
     * Get the display name
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->username;
    }

    /**
     * Get language string of user
     *
     * This will be used on login via Guard and automatically set the
     * language based on the user's settings
     *
     * @return string
     */
    public function getLanguage()
    {
        return 'en';
    }
    
}