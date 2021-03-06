<?php
/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Service\ConfigService;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\PlatformService;
use OCA\FullTextSearch\Service\ProviderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Check extends ExtendedBase {

	/** @var ConfigService */
	private $configService;

	/** @var PlatformService */
	private $platformService;

	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param ConfigService $configService
	 * @param PlatformService $platformService
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, PlatformService $platformService,
		ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();

		$this->configService = $configService;
		$this->platformService = $platformService;
		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:check')
			 ->addOption('json', 'j', InputOption::VALUE_NONE, 'return result as JSON')
			 ->setDescription('Check the installation');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		if ($input->getOption('json') === true) {
			$output->writeln(json_encode($this->displayAsJson(), JSON_PRETTY_PRINT));

			return;
		}

		$output->writeln(
			'Full text search ' . $this->configService->getAppValue('installed_version')
		);
		$output->writeln(' ');

		$this->displayPlatform($output);
		$this->displayProviders($output);
	}


	private function displayAsJson() {

		try {
			$platforms = $this->platformService->getPlatforms();
			$ak = array_keys($platforms);
			foreach ($ak as $k) {
				$platform = $platforms[$k];
				$platform->loadPlatform();
				$resultPlatform[$platform->getId()] = [
					'class'   => $k,
					'version' => $platform->getVersion(),
					'config'  => $platform->getConfiguration()
				];
			}

		} catch (Exception $e) {
			$resultPlatform = ['error' => $e->getMessage()];
		}

		$resultProviders = [];
		try {
			$providers = $this->providerService->getProviders();
			foreach ($providers as $provider) {
				$resultProviders[$provider->getId()] = [
					'version' => $provider->getVersion(),
					'config'  => $provider->getConfiguration()
				];
			}
		} catch (Exception $e) {
			$resultProviders[] = ['error' => $e->getMessage()];
		}

		return [
			'fulltextsearch' => [
				'version' => $this->configService->getAppValue('installed_version'),
				'config'  => $this->configService->getConfig()
			],

			'platform'  => $resultPlatform,
			'providers' => $resultProviders
		];

	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	private function displayPlatform(OutputInterface $output) {
		try {
			$platform = $this->platformService->getPlatform();
		} catch (Exception $e) {
			$output->writeln('No search platform available');

			return;
		}

		$output->writeln('- Search Platform:');

		$output->writeln($platform->getName() . ' ' . $platform->getVersion());
		echo json_encode($platform->getConfiguration(), JSON_PRETTY_PRINT);

		$output->writeln(' ');
		$output->writeln(' ');
	}


	/**
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	private function displayProviders(OutputInterface $output) {
		$providers = $this->providerService->getProviders();

		if (sizeof($providers) === 0) {
			$output->writeln('No Content Provider available');

			return;
		}

		$output->writeln('- Content Providers:');

		foreach ($providers as $provider) {
			$output->writeln($provider->getName() . ' ' . $provider->getVersion());
			echo json_encode($provider->getConfiguration(), JSON_PRETTY_PRINT);
			$output->writeln('');
		}


	}


}



