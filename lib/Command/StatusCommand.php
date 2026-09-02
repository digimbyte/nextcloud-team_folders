<?php
declare(strict_types=1);

namespace OCA\TeamFolders\Command;

use OCA\TeamFolders\Service\IndexStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusCommand extends Command {
    public function __construct(private IndexStateService $state) { parent::__construct(); }
    protected function configure(): void { $this->setName('team-folders:status')->setDescription('Show Team Folders index health'); }
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $status = $this->state->status();
        foreach ($status as $key => $value) $output->writeln(sprintf('%s: %s', $key, $value ?? '-'));
        return $status['status'] === 'error' ? self::FAILURE : self::SUCCESS;
    }
}
