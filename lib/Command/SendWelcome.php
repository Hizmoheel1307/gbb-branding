<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Command;

use OCA\GovMailBranding\Service\WelcomeMailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * occ govmailbranding:welcome:send jane.doe@mda.gov.ng
 * occ govmailbranding:welcome:send jane.doe@mda.gov.ng --to=me@gbb.gov.ng   (test copy)
 * occ govmailbranding:welcome:send jane.doe@mda.gov.ng --force              (send again)
 */
class SendWelcome extends Command {
	public function __construct(
		private WelcomeMailService $welcome,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('govmailbranding:welcome:send')
			->setDescription('Send the branded welcome email to a user')
			->addArgument('user', InputArgument::REQUIRED, 'User ID (login name)')
			->addOption('to', null, InputOption::VALUE_REQUIRED, 'Send to this address instead (does not mark the user as welcomed)')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Send even if the user already received it');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$to = $input->getOption('to');
		$result = $this->welcome->send(
			(string)$input->getArgument('user'),
			is_string($to) ? $to : null,
			$to !== null || (bool)$input->getOption('force'),
			$to === null,
		);
		if ($result['ok']) {
			$output->writeln('<info>Welcome email sent to ' . $result['to'] . '</info>');
			return 0;
		}
		$output->writeln(($result['skipped'] ? '<comment>Skipped: ' : '<error>Failed: ') . $result['error'] . ($result['skipped'] ? '</comment>' : '</error>'));
		return $result['skipped'] ? 0 : 1;
	}
}
