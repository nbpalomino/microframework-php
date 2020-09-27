<?php
namespace Core42\Services;

use Pimple\ServiceProviderInterface;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container;

class Provider implements ServiceProviderInterface
{
    protected $providers = [
        'services.user' => UserService::class,
    ];

    public function register(Pimple $pimple)
    {
        foreach ($this->providers as $key => $value) {
            $pimple[$key] = function($pimple) use ($value) {
                return new $value($pimple);
            };
        }
    }
}