<?php
/**
 * Created by PhpStorm.
 * User: jjsquady
 * Date: 12/21/18
 * Time: 11:48
 */

namespace ChatApiDriver\Providers;


use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Studio\Providers\StudioServiceProvider;
use ChatApiDriver\ChatApiDriver;
use ChatApiDriver\ChatApiFileDriver;
use Illuminate\Support\ServiceProvider;

class ChatApiDriverServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadDrivers();

        $this->publishes([
            __DIR__.'/../../stubs/chatapi.php' => config_path('botman/chatapi.php'),
        ]);

        $this->mergeConfigFrom(__DIR__.'/../../stubs/chatapi.php', 'botman.chatapi');
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(ChatApiDriver::class);
        DriverManager::loadDriver(ChatApiFileDriver::class);
    }
    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}