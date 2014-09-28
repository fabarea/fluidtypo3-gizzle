<?php
namespace FluidTYPO3\FluidTYPO3Gizzle\GizzlePlugins;

use NamelessCoder\Gizzle\Payload;
use NamelessCoder\Gizzle\PluginInterface;
use NamelessCoder\GizzleGitPlugins\GizzlePlugins\PullPlugin;

/**
 * Class SiteDeployPlugin
 */
class SiteDeployPlugin implements PluginInterface {

	const OPTION_MONITORED = 'monitored';
	const OPTION_BRANCH = 'branch';
	const OPTION_DOCUMENTROOT = 'documentRoot';
	const OPTION_DIRECTORY = 'directory';
	const OPTION_POST_TRUNCATE = 'truncate';
	const OPTION_POST_DELETE = 'delete';

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Initialize the plugin with an array of settings.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function initialize(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Analyse $payload and return TRUE if this plugin should
	 * be triggered in processing the payload.
	 *
	 * @param Payload $payload
	 * @return boolean
	 */
	public function trigger(Payload $payload) {
		$monitoredRepositoryUrls = (array) $this->settings[self::OPTION_MONITORED];
		$isMonitored = TRUE === in_array($payload->getRepository()->getName(), $monitoredRepositoryUrls);
		$matchesHead = 'refs/heads/' . $this->settings[self::OPTION_BRANCH] === $payload->getRef();
		return ($matchesHead && $isMonitored);
	}

	/**
	 * Perform whichever task the Plugin should perform based
	 * on the payload's data.
	 *
	 * @param Payload $payload
	 * @return void
	 */
	public function process(Payload $payload) {
		$repositoryRelativePath = sprintf($this->settings[self::OPTION_DIRECTORY], $payload->getRepository()->getName());
		$repositoryBasePath = $this->getSettingValue(self::OPTION_DOCUMENTROOT, '/');
		$repositoryLocalPath = $repositoryBasePath . $repositoryRelativePath;
		$branchName = TRUE === isset($this->settings[self::OPTION_BRANCH]) ? $this->settings[self::OPTION_BRANCH] : $payload->getRepository()->getMasterBranch();
		// 1) use a Git PullPlugin to pull this repository
		$pull = new PullPlugin();
		$pull->initialize(array(
			PullPlugin::OPTION_BRANCH => $branchName,
			PullPlugin::OPTION_DIRECTORY => $repositoryLocalPath,
			PullPlugin::OPTION_DEPTH => 1
		));
		$pull->process($payload);
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	protected function getSettingValue($name, $defaultValue = NULL) {
		return TRUE === isset($this->settings[$name]) ? $this->settings[$name] : $defaultValue;
	}

}