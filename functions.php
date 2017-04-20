<?php

namespace Deployer;

use Deployer\Deployer;
use Deployer\Environment;
use Deployer\Server;
use Deployer\Stage;
use Deployer\Task;
use Deployer\Utils;

/**
 * Checks if given file path exists
 *
 * @param $path
 * @return bool
 */
function fileExists($path)
{
    return trim(run("if test -f \"{$path}\"; then echo 'true'; else echo 'false'; fi")) === 'true';
}

/**
 * Checks if given directory path exists
 *
 * @param $path
 * @return bool
 */
function directoryExists($path)
{
    return trim(run("if test -d \"{$path}\"; then echo 'true'; else echo 'false'; fi")) === 'true';
}


function databaseExixts($database)
{
    $password = askHiddenResponse('What is the root password for the database?');
    return run("if ! mysql -u root -p{$password} -e 'use {$database}'; then echo 'false'; else echo 'true'; fi")->toBool();
}

/**
 * Returns the version of Apache
 *
 * <code>
 * $version = array(
 *      'major' => 2,
 *      'minor' => 4
 *      'revision' => 9
 * );
 * </code>
 *
 * @return array|null
 */
function apacheVersion()
{
    $response = run("apachectl -v");

    preg_match('/Apache\\/(\d+)?\.(\d+)?\.(\*|\d+)/i', $response, $match);

    if (count($match) === 4) {
        $major = $match[1];
        $minor = $match[2];
        $revision = $match[3];

        $version = array(
            'major' => (int)$major,
            'minor' => (int)$minor,
            'revision' => (int)$revision
        );

        set('apache_version', $version);

        return $version;
    }

    return null;
}

/**
 * Checks if Apache config has already been setup for given domain
 *
 * @param $domain
 * @return bool
 */
function apacheSetup($domain)
{
    return fileExists('/etc/apache2/sites-enabled/' . getApacheConfigFile($domain));
}

/**
 * Restarts the Apache server, tests for a valid config first
 *
 * @throws Exception
 */
function restartApache()
{
    writeln('');
    writeln('<info>Testing Apache config</info>');
    $response = trim(run('apachectl configtest'));
    $test = strtolower($response) === 'syntax ok';

    if ($test) {
        writeln('<info>Gracefully restarting Apache</info>');
        run('service apache2 graceful');
    } else {
        throw new \Exception("There is an error in the Apache config\n\n{$response}");
    }

}

set('apache_version', '');
/**
 * Returns the name of the apache config file, {domain}.conf for 2.4, {domain} for 2.2
 * @param $domain
 * @return string
 */
function getApacheConfigFile($domain)
{
    $version = get('apache_version');

    if (!$version) {
        $version = apacheVersion();
    }

    return $version['major'] === 2 && $version['minor'] === 4
        ? "{$domain}.conf"
        : "{$domain}";
}

set('remote_env', '');
/**
 * Gets the contents of the remote _ss_environment.php file
 * @return string
 */
function getRemoteEnvironment()
{

    if ($env = set('remote_env')) {
        return $env;
    }

    $tmp = '/tmp/env.php';
    $remoteEnv = parse("{{deploy_path}}/shared/_ss_environment.php");

    writeln('');
    writeln("<info>Getting remote credentials from {$remoteEnv}</info>");
    download($tmp, $remoteEnv);
    writeln('<info>Remote environment downloaded</info>');
    $env = file_get_contents($tmp);
    unlink($tmp);

    set('remote_env', $env);

    return $env;
}

function runOn($command, $server, $raw = false)
{

    $server = Deployer::get()->getServer($server);
    $config = config();
    $workingPath = env()->getWorkingPath();
    if (!$raw) {
        $command = "cd {$workingPath} && $command";
    }
    if (output()->isDebug()) {
        writeln("[{$server->getConfiguration()->getHost()}] $command");
    }
    $output = $server->run($command);
    if (output()->isDebug()) {
        array_map(
            function ($output) use ($config) {
                write("[{$config->getHost()}] :: $output\n");
            },
            explode("\n", $output)
        );
    }

    return $output;
}
