<?php

namespace Deployer;



/**
 * Sync from remote server to local
 */


task(
     'assets:pull',
     function() {
         askConfirmation('This will compress and download the remote assets folder, continue?');

         $remoteAssets = parse('{{deploy_path}}/shared');
         //  $filename = 'assets-' . date('Y-m-d-H-s') . '.zip';
         $filename = 'assets.zip';
         $tmpFile = '/tmp/' . $filename;

         writeln('<info>Zipping up remote assets</info>');
         run("cd {$remoteAssets} && zip -r {$tmpFile} assets -x **/*/_resampled/*");

         writeln('<info>Downloading assets file</info>');
         download($filename, $tmpFile);

         if(askConfirmation('Do you want to delete the remote ZIP file?', false)) {
             run("rm {$tmpFile}");
             writeln('<info>Remote assets file deleted.</info>');
         }

     }
)
->desc('Creates a ZIP of remote assets (all files) and downloads it.');


task(
     'assets:pull_images',
     function() {

         askConfirmation('This will compress and download the remote assets folder, continue?');

         $remoteAssets = parse('{{deploy_path}}/shared');
        //  $filename = 'assets-' . date('Y-m-d-H-s') . '.zip';
         $filename = 'assets.zip';
         $tmpFile = '/tmp/' . $filename;

         writeln('<info>Zipping up remote assets</info>');

         run("cd {$remoteAssets} && zip -r {$tmpFile} assets -x **/*/_resampled/* -x *.mov -x *.wmv -x *.wav -x *.mp4 -x *.mp3 -x *.mkv");

         writeln('<info>Downloading assets file</info>');

         download($filename, $tmpFile);

         if(askConfirmation('Do you want to delete the remote ZIP file?', false)) {
             run("rm {$tmpFile}");
             writeln('<info>Remote assets file deleted.</info>');
         }


     }
)
->desc('Creates a ZIP of remote assets (images only) and downloads it.');


task(
	'db:pull',
	function() {
        $credentials = get('silverstripe');

        if (!directoryExists('{{deploy_path}}/shared/db')) {
            writeln('<info>Creating shared/db folder</info>');
			run("mkdir {{deploy_path}}/shared/db");
		}

		writeln('<info>Dumping remote database</info>');

        $filename = "db-" . date('Y-m-d-H-s') . '.sql';
        $remoteFilename = parse('{{deploy_path}}/shared/db/') . $filename;

        $host = '';
        if(array_key_exists("db_host", $credentials) && $credentials["db_host"]) {
            $host = '-h ' .  $credentials["db_host"];
        }
		run("mysqldump --user='{$credentials['db_user']}' --password='{$credentials['db_password']}' {$host} {$credentials['db_name']} > {$remoteFilename}", true);

        writeln('<info>Downloading db file</info>');
		download($filename, $remoteFilename);

        if(askConfirmation('Do you want to delete the remote database file?', false)) {
		    run("rm {$remoteFilename}");
        }

		//store so db:import can pick it up
		set('db_file', $filename);
	}
)
	->desc('Downloads database between two servers');


task(
	'db:local_import',
	function() {

        if($dbFile = get('db_file')) {

            $localDBUser = ask("Enter local DB username (MAMP default = root)", "root");
            $localDBPass = ask("Enter local DB pass (MAMP default = root)", "root");
            $localDBName = ask("Enter local DB name");

            runLocally("mysql --user='{$localDBUser}' --password='{$localDBPass}' {$localDBName} < {$dbFile}");

        }
        else {
            writeln('<error>Local DB file not found</error>');
        }

	}
)
	->desc('Import dowmnloaded remote db file into MAMP');

task(
	'sync:cleanup',
	function() {
		if ($dbFile = get('db_file')) {
			runLocally("rm {$dbFile}");
		}

	}
)->desc('Cleanup import files');


task(
	'db:remote_to_local',
	[
		'db:pull',
		'db:local_import',
		'sync:cleanup'
	]
)->desc('Download and import database and assets');









// task(
// 	'sync:assets_download',
// 	function() {
// 		askConfirmation('This will compress the assets folder on the remote server, make sure there is enough disk space before continuing');
//
// 		$remoteAssets = parse('{{deploy_path}}/shared/' . get('web_dir'));
//         $localAssets = get('web_dir');
// 		$tmpFile = '/tmp/assets.tgz';
//
//         writeln('<info>Compressing assets</info>');
// 		runLocally("cd {$localAssets} && tar -czf {$tmpFile} assets");
//
//         writeln('<info>Uploading compressed assets</info>');
// 		upload($tmpFile, $tmpFile);
// 		runLocally("rm {$tmpFile}");
//
//         writeln('<info>Setting up remote assets</info>');
// 		run("rm -Rf {$remoteAssets}/assets");
// 		run("cd /tmp && tar -xzf {$tmpFile}");
// 		run("mv /tmp/assets {$remoteAssets}/assets");
// 		run("rm {$tmpFile}");
// 	}
// )->desc('Synching remote assets directory');
//
// task(
//     'sync:assets',
//     function() {
//         askConfirmation('This will upload the compreseed assets folder to the remote server, make sure there is enough disk space before continuing');
//
//         $remoteAssets = parse('{{deploy_path}}/shared/' . get('web_dir'));
//         $tmpFile = '/tmp/assets.tgz';
//
//         writeln('<info>Compressing assets</info>');
//         run("cd {$remoteAssets} && tar -czf {$tmpFile} assets");
//
//         writeln('<info>Downloading compressed assets</info>');
//         download($tmpFile, $tmpFile);
//         run("rm {$tmpFile}");
//
//         writeln('<info>Setting up local assets</info>');
//         runLocally('rm -Rf public_html/assets');
//         runLocally("cd /tmp && tar -xzf {$tmpFile}");
//         runLocally('mv /tmp/assets public_html/assets');
//         runLocally("rm {$tmpFile}");
//     }
// )->desc('Synching assets directory');


// Download remote db and Update local




// task(
//     'sync:uploaddb',
//     function(){
//         $file = get('db_file');
//
//         if (!$file) $file = ask('Enter the path to the file to import', '');
//
//         upload($file, $file);
//     }
// )->desc('Uploading database to remote server');


// task(
// 	'sync',
// 	[
// 		'sync:db_pull',
// 		'db:import',
// 		'sync:assets_get_remote',
// 		'sync:cleanup'
// 	]
// )->desc('Download and setup database and assets');
//
//
// /**
//  * Sync from local to remote server
//  */
// task(
//     'sync:local_to_remote',
//     [
//         'db:export',
//         'sync:uploaddb',
//         'db:import_remote',
//         'sync:assets_remote',
//         'sync:cleanup'
//     ]
// )->desc('Upload local db/assets to remote site');
