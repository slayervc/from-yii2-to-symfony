<?php

declare(strict_types=1);

namespace src\Common\Infrastructure\PortAdapter\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'update-index-php')]
class UpdateIndexPhp extends Command
{
    private const DEFAULT_APPS = [
        'api',
        //...
    ];

    public function __construct(
        private readonly string $projectRoot,
    ) {
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setDescription('Заменяет файлы index.php запускавшие Yii2 на файлы, запускающие Symfony')
            ->addArgument('apps', InputArgument::OPTIONAL, 'Список приложений для замены файлов через запятую')
            ->addArgument(
                'env',
                InputArgument::OPTIONAL,
                'Окружение, для которого необходимо выполнить замену файлов',
                'dev'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $appsList = $input->getArgument('apps');
        $apps = null === $appsList ? self::DEFAULT_APPS : explode(',', $appsList);
        $env = $input->getArgument('env');
        $counter = 0;
        foreach ($apps as $app) {
            $sourceFile = $this->projectRoot . '/environments/' . $env . '/' . $app . '/web/index.php';
            $destFile = $this->projectRoot . '/' . $app . '/web/index.php';
            if (!file_exists($sourceFile)) {
                throw new InvalidArgumentException('File not found: ' . $sourceFile);
            }

            if (!file_exists($destFile)) {
                throw new InvalidArgumentException('File not found: ' . $destFile);
            }

            file_put_contents($destFile, file_get_contents($sourceFile));
            $counter++;
        }

        $io = new SymfonyStyle($input, $output);
        $io->success($counter . ' file(s) replaced successfully');

        return Command::SUCCESS;
    }
}