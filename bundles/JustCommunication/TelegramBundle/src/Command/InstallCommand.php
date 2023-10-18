<?php

namespace JustCommunication\TelegramBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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

        $this->io = new SymfonyStyle($input, $output);

        $this->io->block('Installing JustCommunication/Telegram...');

        //$output->writeln('Installing JustCommunication/Telegram...');

        $this->initConfig($output);

        // Прочие действия для инициализации
        // ...

        return 0;
    }

    private function initConfig(OutputInterface $output): void
    {
        // Create default config if not exists
        $bundleConfigFilenamePath =
            DIRECTORY_SEPARATOR . 'config'
                . DIRECTORY_SEPARATOR . 'packages'
                    . DIRECTORY_SEPARATOR . 'telegram.yaml'
        ;
        $bundleRoutesFilenamePath =
            DIRECTORY_SEPARATOR . 'config'
                . DIRECTORY_SEPARATOR . 'routes'
                    . DIRECTORY_SEPARATOR . 'telegram.yaml'
        ;



        $usefull = false;

        if ($this->filesystem->exists($this->projectDir.$bundleConfigFilenamePath)) {
            $this->io->warning('Bundle config file already exists');
        }else{
            $usefull=true;
            // Конечно лучше скопировать из готового файла
            $config_content= file_get_contents(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.$bundleConfigFilenamePath);
            $content_lines = explode("\n", $config_content);
            foreach ($content_lines as $i=>$line){
                if (str_starts_with($line, "#")){
                    unset($content_lines[$i]);
                }
            }
            $config_content = implode("\n", $content_lines);
            $this->filesystem->appendToFile($this->projectDir.$bundleConfigFilenamePath, $config_content);
            $this->io->success('Bundle config created: "'.$bundleConfigFilenamePath.'"');
        }
        //$output->writeln('');
        //$this->io->block('');

        if ($this->filesystem->exists($this->projectDir.$bundleRoutesFilenamePath)) {
            $this->io->warning('Bundle routes file already exists');
        }else{
            $usefull=true;
            // Конечно лучше скопировать из готового файла
            $routes_content= file_get_contents(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.$bundleRoutesFilenamePath);
            $this->filesystem->appendToFile($this->projectDir.$bundleRoutesFilenamePath, $routes_content);
            $this->io->success('Bundle config created: "'.$bundleRoutesFilenamePath.'"');
        }
        //$this->io->block('');


//        $config = <<<YAML
//'.$config_content.'
//YAML;

        if ($usefull) {
            $exec_command = 'php bin/console cache:clear';
            $this->io->block('Running "' . $exec_command.'"');
            $_exec_out = '';

            exec($exec_command, $_exec_out);
            $output->writeln($_exec_out);
        }else{
            $output->writeln(['finished','']);
        }

    }
}