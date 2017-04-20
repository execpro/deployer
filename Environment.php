<?php
namespace WebTorque\Deployment;

class Environment
{

	private $file = null;

	/**
	 * @param $file string Contents of _ss_environment.php
	 */
	public function __construct($file) {
		$this->file = $file;
	}

	public static function inst($file) {
		$env = new Environment($file);
		return $env;
	}

	public function setFile($file) {
		$this->file = $file;
		return $this;
	}


	/**
	 * Sets up _ss_environment file
	 *
	 * @param $siteRoot string Full path to root of website
	 * @param $username string Database username
	 * @param $password string Database password
	 * @param $database string Database name
	 * @param $domain string Domain of website
	 *
	 * @return string
	 */
	public function setupEnvironmentFile($siteRoot, $username, $password, $database, $domain) {
		$file = $this->file;

		$file = preg_replace("/(define\\('SS_DATABASE_USERNAME'), '(.*?)'\\)/", "$1, '{$username}')", $file);
		$file = preg_replace("/(define\\('SS_DATABASE_PASSWORD'), '(.*?)'\\)/", "$1, '{$password}')", $file);
		$file = preg_replace("/(define\\('SS_DATABASE_NAME'), '(.*?)'\\)/", "$1, '{$database}')", $file);
		$file = preg_replace("/\\\$_FILE_TO_URL_MAPPING\\['.*'?\\] = '.*?'/", "\$_FILE_TO_URL_MAPPING['{$siteRoot}'] = 'http://{$domain}'", $file);

		return $file;
	}

	public function parseEnvironment() {
		$env = $this->file;
		$credentials = [];

		//get db user
		preg_match("/define\\('SS_DATABASE_USERNAME', '(.*?)'\\)/", $env, $matches);
		if ($matches) {
			$credentials['username'] = $matches[1];
		}
		unset($matches);

		preg_match("/define\\('SS_DATABASE_PASSWORD', '(.*?)'\\)/", $env, $matches);
		if ($matches) {
			$credentials['password'] = $matches[1];
		}
		unset($matches);

		preg_match("/define\\('SS_DATABASE_NAME', '(.*?)'\\)/", $env, $matches);
		if ($matches) {
			$credentials['database'] = $matches[1];
		}
		unset($matches);

		preg_match("/\\\$_FILE_TO_URL_MAPPING\\['(.*?)'\\] \\= 'http\\:\\/\\/(.*?)'/", $env, $matches);
		if ($matches) {
			$credentials['path'] = $matches[1];
			$credentials['domain'] = $matches[2];
		}

		return $credentials;
	}
}
