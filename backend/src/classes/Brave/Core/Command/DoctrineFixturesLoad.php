<?php declare(strict_types=1);

namespace Brave\Core\Command;

use Brave\Core\Application;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineFixturesLoad extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('doctrine-fixtures-load')
            ->setDescription(
                'Load data fixtures to the database. ' .
                'Appends the data fixtures instead of deleting all data from the database first.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new Loader();
        $loader->loadFromDirectory(Application::ROOT_DIR . '/src/classes/Brave/Core/DataFixtures');

        $executor = new ORMExecutor($this->entityManager);
        $executor->setLogger(static function ($message) use ($output) : void {
            $output->writeln($message);
        });

        $executor->execute($loader->getFixtures(), true);

        return 0;
    }
}