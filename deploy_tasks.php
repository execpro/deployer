<?php

namespace Deployer;

task(
	'deploy:composer',
	function () {
		$webDir = get('web_dir', false);

		cd("{{release_path}}/{$webDir}");
		run("composer install --no-dev --verbose --prefer-dist --optimize-autoloader --no-progress --no-scripts");
	}
)->desc('Installing composer dependencies');


task(
  'deploy:installsake',
  function () {
    $webDir = get('web_dir', false);

    cd("{{release_path}}/{$webDir}");
    run("./framework/sake installsake");
  }
)->desc('Installing sake');



task(
	'deploy:environment',
	function () {
		$releasePath = parse('{{deploy_path}}/current');
		$webDir = get('web_dir', false);

		$tmp = '/tmp/env.php';
		$remoteEnv = parse("{{deploy_path}}/_ss_environment.php");

		if (!fileExists($remoteEnv)) {
			$env = new \WebTorque\Deployment\Environment(file_get_contents('_ss_environment.php'));

			$dbUser = ask('Please provide a username for the database', '');
			$dbPassword = ask('Please provide a password for the database', '');
			$domain = ask('Please provide a domain for this server', '');
            $database = ask('Please provide a database name for this server', '');
            $host = ask('Please provide a database host for this server', '');

			$file = $env->setupEnvironmentFile("{$releasePath}", $dbUser, $dbPassword, $database, $domain, $host);

			file_put_contents($tmp, $file);
			upload($tmp, $remoteEnv);
			unlink($tmp);
		}
	}
)->desc('Setting up environment file');


task(
	'deploy:ssbuild',
	function() {
		$webDir = get('web_dir');
        $releasePath = '{{deploy_path}}/current';
		run("cd {$releasePath} && sake dev/build flush=1");
	}
)->desc('Running dev/build on remote server');

task(
	'deploy:update',
	function() {
		$releasePath = '{{deploy_path}}/current';
		$webDir = get('web_dir');

        writeln('<info>Updating site</info>');
		if ($branch = get('branch')) {
			run("cd {$releasePath} && git pull --rebase origin {$branch} --depth 1 --force && php {$webDir}/framework/cli-script.php dev/build flush=1 && cd {$webDir} && composer install --no-dev");
		}
	}
)->desc('Updating site on remote server');

task(
    'deploy:submodules',
    function() {
        $releasePath = '{{deploy_path}}/current';
        run("cd {$release_path} && git submodule update --init --recursive --depth=1");
    }
)->desc('Update submodules');

task(
    'deploy:apache',
    [
        'apache:setup',
        'apache:ensite'
    ]
)->desc('Checking and setting up Apache');

task(
    'deploy:sync',
    [
        'sync:remote',
        'db:permissions'
    ]
)->desc('Uploading from local and setting up database permissions');


task(
    'deploy:fix_permissions',
    function() {
        $release_path = parse('{{deploy_path}}');
        run("cd {$release_path} && sudo chgrp -R developers releases");
    }
)->desc('Update group ownership of releases folder');
