<?php

namespace Deployer;
use \WebTorque\Deployment\Environment;

task(
	'db:import',
	function() {
		$file = get('db_file');

		if (!$file) $file = ask('Enter the path to the file to import', '');

		writeln('<info>Importing database</info>');

		$credentials = Environment::inst(file_get_contents('_ss_environment.php'))->parseEnvironment();

		runLocally("mysql -u {$credentials['username']} -p{$credentials['password']} < {$file}");
	}
)->desc('Importing database');
	//->option('file', 'f', 'File to import', null);

task(
    'db:import_remote',
    function() {
        $file = get('db_file');

        if (!$file) $file = ask('Enter the path to the file to import', '');

        writeln('<info>Importing database</info>');

        $env = Environment::inst(getRemoteEnvironment())->parseEnvironment();

        $dbuser = get('dbuser');
        $dbpassword = get('dbpassword');

        if (!databaseExixts($env['database']) || askConfirmation('Are you sure you want to overwrite the database')) {
            run("mysql -u {$dbuser} -p{$dbpassword} {$env['database']} < {$file}");
        }
    }
)->desc('Importing database on remote server');

task(
    'db:permissions',
    function() {
        $env = Environment::inst(getRemoteEnvironment())->parseEnvironment();

        $dbuser = get('dbuser');
        $dbpassword = get('dbpassword');

        $sql = "GRANT ALL ON {$env['database']}.* TO '{$env['username']}'@'localhost' IDENTIFIED BY '{$env['password']}'";

        run("mysql -u {$dbuser} -p{$dbpassword} -e \"{$sql}\"");
    }
)->desc('Setting permissions on database');

task(
	'db:export',
	function() {
		$filename = 'db/' . date('Y-m-d-H-s') . '.sql';

		$credentials = Environment::inst(file_get_contents('_ss_environment.php'))->parseEnvironment();

		runLocally("mysqldump -u {$credentials['username']} -p{$credentials['password']} -B {$credentials['database']} > {$filename}");

		//set filename so other task can use it
		set('db_file', $filename);
	}
);

task('db:exists',
    function() {
        $db = ask('What database would you like to check?', '');

        if (databaseExixts($db)) {
            writeln('<info>This database already exists</info>');
        } else {
            writeln('<info>This database doesn\'t exist</info>');
        }
    }
);