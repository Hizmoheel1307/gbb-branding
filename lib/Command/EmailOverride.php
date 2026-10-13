<?php

declare(strict_types=1);

namespace OCA\GovMailBranding\Command;

use OCA\GovMailBranding\Service\EmailTemplateGuard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * occ govmailbranding:email:override [on|off|status|reconcile]
 *
 * config.php is per node, so run this on each app server (deploy.sh's
 * repair step does `reconcile` automatically).
 */
class EmailOverride extends Command {
	public function __construct(
		private EmailTemplateGuard $guard,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('govmailbranding:email:override')
			->setDescription('Switch the branded email template on/off, or show its status on this node')
			->addArgument('action', InputArgument::OPTIONAL, 'on, off, status or reconcile', 'status');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$action = (string)$input->getArgument('action');
		$status = match ($action) {
			'on' => $this->guard->enable(),
			'off' => $this->guard->disable(),
			'reconcile' => $this->guard->reconcile(),
			'status' => $this->guard->status(),
			default => null,
		};

		if ($status === null) {
			$output->writeln('<error>Unknown action. Use on, off, status or reconcile.</error>');
			return 1;
		}

		$output->writeln('Node:            ' . $status['host']);
		$output->writeln('State:           ' . $status['state']);
		$output->writeln('Switched on:     ' . ($status['enabled'] ? 'yes' : 'no'));
		$output->writeln('config.php:      ' . ($status['systemValue'] === '' ? '(not set)' : $status['systemValue']));
		$output->writeln('Template in use: ' . $status['templateInUse']);
		if ($status['lastError'] !== '') {
			$output->writeln('<comment>Last error:      ' . $status['lastError'] . '</comment>');
		}

		return in_array($status['state'], ['active', 'disabled'], true) ? 0 : 2;
	}
}
