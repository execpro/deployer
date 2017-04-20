<?php

require 'recipe/common.php';
require 'recipe/composer.php';
require 'vendor/eis/deployer/functions.php';
require 'vendor/eis/deployer/Environment.php';
require 'vendor/eis/deployer/apache_tasks.php';
require 'vendor/eis/deployer/db_tasks.php';
require 'vendor/eis/deployer/sync_tasks.php';
require 'vendor/eis/deployer/deploy_tasks.php';
require 'vendor/eis/deployer/teardown_tasks.php';


serverList('servers.yml');

$repo_url = exec("git config --get remote.origin.url");

if(!$repo_url) {
    echo("Please make sure you're running this in a project repository.");
}

set('repository', 'git@bitbucket.org:eisweb/mir.git');
set('keep_releases', 3);

set('shared_dirs', ['assets', 'silverstripe-cache']);
set('shared_files', ['_ss_environment.php']);
set('writable_dirs', ['assets', 'silverstripe-cache']);

task('show_keys', function () {
  writeln(run("sudo ssh-add -l"));
});

task('reload:php-fpm', function () {
    run('sudo /usr/sbin/service php5-fpm reload');
});

task('apache:restart', function () {
    run('sudo service httpd start');
});

set('web_dir', '');



//
// RSYNC Specifics
//

set('rsync_src', __DIR__ . "/assets");
set('rsync_dest','{{release_path}}/assets');


task(
  'deploy',
  [
    'deploy:prepare',
    'deploy:fix_permissions',
    'deploy:environment',
      'deploy:release',
    'deploy:update_code',
    'deploy:composer',
    'deploy:shared',
    // 'deploy:ssbuild',
    'deploy:symlink',
    'deploy:writable',
      // 'deploy:apacherestart',
    'cleanup',
    'success'
  ]
);

// Task to fix permissions on repo
after('deploy', 'apache:restart');


?>
