<?php


namespace Deployer;



/**
 * Silverstripe tasks
 */

task('silverstripe:build', function () {
    return run('sudo su - apache -c {{bin/php}} {{deploy_path}}/current/framework/cli-script.php /dev/build"');
})->desc('Run /dev/build');

task('silverstripe:buildflush', function () {
    return run('sudo su - apache -c "{{bin/php}} {{deploy_path}}/current/framework/cli-script.php /dev/build flush=all"');
})->desc('Run /dev/build?flush=all');

task('apache:restart', function () {
    run('sudo service httpd start');
});


//Create and _ss_environment.php values from the settings
task('silverstripe:env', function () {
    if(fileExists(parse('{{deploy_path}}') . '/releases/_ss_environment.php') || fileExists(parse('{{deploy_path}}') . '/shared/_ss_environment.php') || fileExists(parse('{{deploy_path}}') . '/_ss_environment.php')) {
        writeln('releases/_ss_environment.php present');
        return false;
    }
    if (askConfirmation('Do you want to generate and upload the _ss_environment.php file?')) {
        $basepath = dirname(__FILE__) . '/vendor/eis/deployer/templates';

        //Import secrets
        $secrets = get('silverstripe');

        //Prepare replacement variables
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($secrets)
        );

        $replacements = [];

        foreach ($iterator as $key => $value) {
            $keys = [];
            for ($i = $iterator->getDepth(); $i > 0; $i --) {
                $keys[] = $iterator->getSubIterator($i - 1)->key();
            }
            $keys[] = $key;

            $replacements['{{' . implode('.', $keys) . '}}'] = $value;
        }

        $credentials = $secrets;

        //Create settings from template
        $settings = file_get_contents($basepath . '/_ss_environment.php.tpl');


        // DB Name
        if($secrets["db_name"]) {
            $settings = strtr($settings, array(
                "//define('SS_DATABASE_NAME', 'db');" => "define('SS_DATABASE_NAME', '" . $secrets["db_name"] . "');"
            ));
        }

        // DB Prefix
        if($secrets["db_prefix"]) {
            $settings = strtr($settings, array(
                "//define('SS_DATABASE_PREFIX', 'db_prefix');" => "define('SS_DATABASE_PREFIX', '" . $secrets["db_prefix"] . "');"
            ));
        }

        // DB Suffix
        if($secrets["db_suffix"]) {
            $settings = strtr($settings, array(
                "//define('SS_DATABASE_SUFFIX', 'db_suffix');" => "define('SS_DATABASE_SUFFIX', '" . $secrets["db_suffix"] . "');"
            ));
        }


        // Enable $file_to_url_mapping
        if(!$secrets["disable_file_to_url_mapping"]) {
            $settings = strtr($settings, array(
                "//\$_FILE_TO_URL_MAPPING['/path/to/release']" => "\$_FILE_TO_URL_MAPPING[realpath('" . parse('{{deploy_path}}') ."/current')]",
                "//global \$_FILE_TO_URL_MAPPING;" => "global \$_FILE_TO_URL_MAPPING;"
            ));
        }

        $settings = strtr($settings, $replacements);

        // Enable admin settings
        if($secrets["admin_enable"]) {
            $settings = strtr($settings, array(
                "//define('SS_DEFAULT_ADMIN_USERNAME" => "define('SS_DEFAULT_ADMIN_USERNAME",
                "//define('SS_DEFAULT_ADMIN_PASSWORD" => "define('SS_DEFAULT_ADMIN_PASSWORD"
            ));
        }

        $tmpFilename = tempnam($basepath, '_ss_environment_tmp');
        file_put_contents($tmpFilename, $settings);

        writeln('_ss_environment.php created succesfuly');

        upload($tmpFilename, '{{deploy_path}}/releases/_ss_environment.php');
        unlink($tmpFilename);
    }
});
