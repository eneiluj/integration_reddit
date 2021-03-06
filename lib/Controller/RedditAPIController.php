<?php
/**
 * Nextcloud - reddit
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Reddit\Controller;

use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\AppFramework\Http\DataDisplayResponse;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IL10N;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;

use OCP\AppFramework\Http\ContentSecurityPolicy;

use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Reddit\Service\RedditAPIService;
use OCA\Reddit\AppInfo\Application;

require_once __DIR__ . '/../constants.php';

class RedditAPIController extends Controller {


	private $userId;
	private $config;
	private $dbconnection;
	private $dbtype;

	public function __construct($AppName,
								IRequest $request,
								IServerContainer $serverContainer,
								IConfig $config,
								IL10N $l10n,
								IAppManager $appManager,
								IAppData $appData,
								LoggerInterface $logger,
								RedditAPIService $redditAPIService,
								$userId) {
		parent::__construct($AppName, $request);
		$this->userId = $userId;
		$this->l10n = $l10n;
		$this->appData = $appData;
		$this->serverContainer = $serverContainer;
		$this->config = $config;
		$this->logger = $logger;
		$this->redditAPIService = $redditAPIService;
		$this->accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token', '');
		$this->refreshToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'refresh_token', '');
		$this->clientID = $this->config->getAppValue(Application::APP_ID, 'client_id', DEFAULT_REDDIT_CLIENT_ID);
		$this->clientID = $this->clientID ? $this->clientID : DEFAULT_REDDIT_CLIENT_ID;
		$this->clientSecret = $this->config->getAppValue(Application::APP_ID, 'client_secret', '');
	}

	/**
	 * get notification list
	 * @NoAdminRequired
	 *
	 * @param ?string $after
	 * @return DataResponse
	 */
	public function getNotifications(?string $after = null): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse(null, 400);
		}
		$result = $this->redditAPIService->getNotifications(
			$this->accessToken, $this->refreshToken, $this->clientID, $this->clientSecret, $after
		);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

	/**
	 * get repository avatar
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param ?string $username
	 * @param ?string $subreddit
	 * @return DataDisplayResponse
	 */
	public function getAvatar(?string $username = null, string $subreddit = null): DataDisplayResponse {
		$response = new DataDisplayResponse(
			$this->redditAPIService->getAvatar(
				$this->accessToken, $this->clientID, $this->clientSecret, $this->refreshToken,
				$username, $subreddit
			)
		);
		$response->cacheFor(60*60*24);
		return $response;
	}
}
