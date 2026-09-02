<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Command;

use OCA\TeamFolders\Service\Indexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RebuildCommand extends Command {
    public function __construct(private Indexer $indexer) { parent::__construct(); }
    protected function configure(): void { $this->setName('team-folders:rebuild')->setDescription('Queue or repair the Team Folders recursive exposure index'); }
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $result = $this->indexer->rebuild();
        $output->writeln(sprintf('<info>Indexed %d shares in generation %d.</info>', $result['shares'], $result['generation']));
        return self::SUCCESS;
    }
}
