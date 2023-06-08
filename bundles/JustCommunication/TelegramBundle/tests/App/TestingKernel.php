<?php
namespace JustCommunication\TelegramBundle\Tests\App;

use JustCommunication\TelegramBundle\TelegramBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

class TestingKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment='', bool $debug=false)
    {
        parent::__construct('test', false);

    }

    public function registerBundles(): iterable
    {
        // TODO: Implement registerBundles() method.
        return [
            new TelegramBundle(),
            new FrameworkBundle()
        ];
    }

    protected function configureRoutes($routes)
    {
        $routes->import(__DIR__.'/../../config/routes.yaml');
    }

}