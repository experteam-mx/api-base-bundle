<?php

namespace Experteam\ApiBaseBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class UpgradeDoctrineMigrationsCommand extends Command
{
    private $entityManager;
    protected static $defaultName = 'experteam:doctrine:migrations:upgrade';
    protected static $defaultDescription = 'Upgrade doctrine migrations';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->getApplication()->find('doctrine:migrations:sync-metadata-storage')->run($input, $output);
            $connection = $this->entityManager->getConnection();
            $connection->executeQuery('INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) SELECT \'DoctrineMigrations\\Version\' + version, executed_at, 1 FROM migration_versions');
            $connection->executeQuery('DROP TABLE migration_versions');
        } catch (Throwable $t) {
            $io->error($t->getMessage());
        }

        $io->success('Successfully upgraded doctrine migrations.');
        return Command::SUCCESS;
    }
}
