<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Command;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use OCA\GovMailBranding\Service\TestEmailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * occ govmailbranding:email:test admin@example.gov.ng
 *
 * Runs in CLI context - the same context as cron and background jobs - so
 * it also catches the case where the branded class loads on the web but
 * not from cron (nextcloud/server#45392).
 */
class TestEmail extends Command {
	public function __construct(
		private TestEmailService $testEmail,
		private EmailTemplateGuard $guard,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('govmailbranding:email:test')
			->setDescription('Send a branded test email and report which template was used')
			->addArgument('to', InputArgument::REQUIRED, 'Recipient email address');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$status = $this->guard->status();
		$output->writeln('Node:            ' . $status['host']);
		$output->writeln('Override state:  ' . $status['state']);
		$output->writeln('Template in use: ' . $status['templateInUse']);
		if ($status['lastError'] !== '') {
			$output->writeln('<comment>Last error:      ' . $status['lastError'] . '</comment>');
		}

		$result = $this->testEmail->send((string)$input->getArgument('to'), 'occ');

		if (!$result['ok']) {
			$output->writeln('<error>Send failed: ' . $result['error'] . '</error>');
			return 1;
		}

		$output->writeln('<info>Sent to ' . $result['to'] . ' using ' . $result['templateClass'] . '</info>');
		if (!$result['branded']) {
			$output->writeln('<comment>Warning: this email used the stock template, so it is NOT branded.</comment>');
			return 2;
		}
		$output->writeln('Check the inbox and confirm the footer, signature and links look right.');
		return 0;
	}
}
