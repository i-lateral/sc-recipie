<?php
namespace Deployer;

use Deployer\Task\Context;

require 'recipe/silverstripe.php';

// Project name
set('application', '__ApplicationName__');

// Historic releases
set('keep_releases', 5);

// Project repository
set('repository', 'git@bitbucket.org:ilateral/{{application}}.git');
set('branch', 'dev');
set('default_stage', 'dev');

// Silverstripe shared dirs
set(
    'shared_dirs',
    [
        'assets',
        'silverstripe-cache'
    ]
);

// Which folders are writable
set(
    'writable_dirs',
    [
        'themes',
        'assets',
        'silverstripe-cache',
        'logs'
    ]
);

// Silverstripe shared files
set(
    'shared_files',
    [
        '.env',
        'logs/silverstripe.log',
        'logs/omnipay.log',
    ]
);

// Setup dev server deployment
host('__DevServer__')
    ->stage('dev')
    ->user('root')
    ->set('deploy_path', '/srv/{{application}}')
    ->set('http_user', 'apache')
    ->identityFile('~/.ssh/id_rsa');

// Setup live server deployment
host('__LiveServer__')
    ->port(2020)
    ->stage('live')
    ->set('branch', 'master')
    ->user('yous')
    ->set('deploy_path', '/home/monu/webroot')
    ->identityFile('~/.ssh/id_rsa');

// Disable composer --no-dev on dev
task(
    'composer:config',
    function () {
        $stage = Context::get()->getHost()->getConfig()->get('stage');

        if ($stage == "dev") {
            set(
                'composer_options',
                '{{composer_action}} --verbose --no-progress --no-interaction --optimize-autoloader'
            );
        }
    }
);

before('deploy:vendors', 'composer:config');

// Reload PHP-FPM
task(
    'reload:php-fpm',
    function () {
        run('sudo /bin/systemctl restart php-fpm.service');
    }
);

after('deploy', 'reload:php-fpm');

// Tasks
desc('Populate .env file');
task(
    'silverstripe:create_dotenv',
    function () {
        $envPath = "{{deploy_path}}/shared/.env";
        if (test("[ -f {$envPath} ]")) {
            return;
        }

        $dbServer = ask('Please enter the database server', 'localhost');
        $dbUser = ask('Please enter the database username');
        $dbPass = str_replace("'", "\\'", askHiddenResponse('Please enter the database password'));
        $dbName = ask('Please enter the database name', get('application'));
        $dbPrefix = Context::get()->getHost()->getConfig()->get('stage') === 'stage' ? '_stage_' : '';
        $baseURL = ask('Please enter the baseURL');
        $type = Context::get()->getHost()->getConfig()->get('stage');

        $contents = <<<ENV
SS_DATABASE_CLASS='MySQLPDODatabase'
SS_DATABASE_USERNAME='{$dbUser}'
SS_DATABASE_PASSWORD='{$dbPass}'
SS_DATABASE_SERVER='{$dbServer}'
SS_DATABASE_NAME='{$dbName}'
SS_DATABASE_PREFIX='{$dbPrefix}'
SS_BASE_URL='{$baseURL}'
SS_ENVIRONMENT_TYPE='{$type}'
ENV;

        $command = <<<BASH
cat >{$envPath} <<EOL
$contents
EOL
BASH;

        run("$command");
    }
)->setPrivate();

before('deploy:vendors', 'silverstripe:create_dotenv');
