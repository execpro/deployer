<?php

namespace Deployer;

/**
 * Sync from remote server to local
 */

task(
	'sync:db',
	function() {
		$filename = 'db/' . date('Y-m-d-H-s') . '.sql';
		$credentials = \WebTorque\Deployment\Environment::inst(getRemoteEnvironment())->parseEnvironment();

		if (!directoryExists('db')) {
			run("mkdir db");
		}

		writeln('<info>Dumping remote database</info>');
		$remoteFilename = getWorkingPath() . DIRECTORY_SEPARATOR . $filename;
		run("mysqldump -u {$credentials['username']} -p{$credentials['password']} -B {$credentials['database']} > {$remoteFilename}");

		writeln('<info>Downloading database file</info>');
		download($filename, $remoteFilename);

		run("rm {$remoteFilename}");

		//store so db:import can pick it up
		set('db_file', $filename);
	}
)
	->desc('Copies database between two servers');


task(
	'sync:assets_remote',
	function() {
		askConfirmation('This will compress the assets folder on the remote server, make sure there is enough disk space before continuing');

		$remoteAssets = parse('{{deploy_path}}/shared/' . get('web_dir'));
        $localAssets = get('web_dir');
		$tmpFile = '/tmp/assets.tgz';

        writeln('<info>Compressing assets</info>');
		runLocally("cd {$localAssets} && tar -czf {$tmpFile} assets");

        writeln('<info>Uploading compressed assets</info>');
		upload($tmpFile, $tmpFile);
		runLocally("rm {$tmpFile}");

        writeln('<info>Setting up remote assets</info>');
		run("rm -Rf {$remoteAssets}/assets");
		run("cd /tmp && tar -xzf {$tmpFile}");
		run("mv /tmp/assets {$remoteAssets}/assets");
		run("rm {$tmpFile}");
	}
)->desc('Synching remote assets directory');

task(
    'sync:assets',
    function() {
        askConfirmation('This will upload the compreseed assets folder to the remote server, make sure there is enough disk space before continuing');

        $remoteAssets = parse('{{deploy_path}}/shared/' . get('web_dir'));
        $tmpFile = '/tmp/assets.tgz';

        writeln('<info>Compressing assets</info>');
        run("cd {$remoteAssets} && tar -czf {$tmpFile} assets");

        writeln('<info>Downloading compressed assets</info>');
        download($tmpFile, $tmpFile);
        run("rm {$tmpFile}");

        writeln('<info>Setting up local assets</info>');
        runLocally('rm -Rf public_html/assets');
        runLocally("cd /tmp && tar -xzf {$tmpFile}");
        runLocally('mv /tmp/assets public_html/assets');
        runLocally("rm {$tmpFile}");
    }
)->desc('Synching assets directory');


task(
    'sync:uploaddb',
    function(){
        $file = get('db_file');

        if (!$file) $file = ask('Enter the path to the file to import', '');

        upload($file, $file);
    }
)->desc('Uploading database to remote server');

task(
	'sync:cleanup',
	function() {
		if ($dbFile = get('db_file')) {
			runLocally("rm {$dbFile}");
		}

	}
)->desc('Cleanup import files');


task(
	'sync',
	[
		'sync:db',
		'db:import',
		'sync:assets',
		'sync:cleanup'
	]
)->desc('Download and setup database and assets');


/**
 * Sync from local to remote server
 */
task(
    'sync:remote',
    [
        'db:export',
        'sync:uploaddb',
        'db:import_remote',
        'sync:assets_remote',
        'sync:cleanup'
    ]
)->desc('Upload local db/assets to remote site');
