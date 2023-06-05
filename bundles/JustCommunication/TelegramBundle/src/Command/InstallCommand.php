<?php

namespace JustCommunication\TelegramBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class InstallCommand extends Command
{
    protected static $defaultName = 'jc:telegram:install';

    /** @var Filesystem */
    private $filesystem;

    private $projectDir;

    //public function __construct(Filesystem $filesystem, string $projectDir)
    public function __construct(Filesystem $filesystem, KernelInterface $kernel)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->projectDir = $kernel->getProjectDir();

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Installing JustCommunication/Telegram...');

        $this->initConfig($output);

        // Прочие действия для инициализации
        // ...

        return 0;
    }

    private function initConfig(OutputInterface $output): void
    {
        // Create default config if not exists
        $bundleConfigFilename =
            $this->projectDir
                . DIRECTORY_SEPARATOR . 'config'
                    . DIRECTORY_SEPARATOR . 'packages'
                        . DIRECTORY_SEPARATOR . 'telegram.yaml'
        ;

        if ($this->filesystem->exists($bundleConfigFilename)) {
            $output->writeln('Config file already exists');

            return;
        }

        // Конечно лучше скопировать из готового файла
        $config = <<<YAML
telegram:
    my_param: "param-pam-pam"
YAML;
        $this->filesystem->appendToFile($bundleConfigFilename, $config);

        $output->writeln('Config created: "config/packages/telegram.yaml"');

    }
}