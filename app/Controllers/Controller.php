<?php
namespace Core42\Controllers;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container;

abstract class Controller
{
    protected $container;

    /**
     * Controller, constructed by the container
     *
     * @param Container $container
     */
    public function __construct(Pimple $container)
    {
        $this->container = new Container($container);
    }
}