<?php

namespace Experteam\ApiBaseBundle\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadFixturesCommand extends Command
{

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('experteam:fixtures:load')
            ->setDescription('Load data fixtures to your database')
            ->addOption('release', null, InputOption::VALUE_REQUIRED, 'The realese version to execute the fixtures')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ui = new SymfonyStyle($input, $output);

        $version = $input->getOption('release') ?? $this->parameterBag->get('experteam_api_base.fixtures');
        if (empty($version)) {
            $ui->text("<info>> DataFixtures: nothing to execute, empty version.</info>");
            return Command::SUCCESS;
        }

        if (!preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $version)) {
            $ui->error("DataFixtures: invalid version code: {$version}");
            return Command::FAILURE;
        }

        $folder = sprintf('%s/src/DataFixtures/v%s/',
            $this->parameterBag->get('kernel.project_dir'),
            str_replace('.', '_', $version)
        );

        if (file_exists($folder)) {
            $_input = new ArrayInput([
                '--append' => true,
                '--group' => [$version]
            ]);
            return $this->getApplication()->find('doctrine:fixtures:load')->run($_input, $output);
        } else {
            $ui->text("<info>> DataFixtures: nothing to execute for version {$version}.</info>");
        }

        return Command::SUCCESS;
    }



}