<?php
namespace App\Command;


use App\Service\DataVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheClearCommand extends Command
{
    protected static $defaultName = 'frontend:cache:clear';
    private $dataVersion;

    public function __construct(DataVersion $dataVersion)
    {
        parent::__construct();
        $this->dataVersion = $dataVersion;
    }

    protected function configure()
    {
        $this->setName('frontend:cache:clear')->setDescription('Fetch new version of assets.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->dataVersion->getVersion(true);
        $io->success('Fetch new version of assets.');
        return 0;
    }
}