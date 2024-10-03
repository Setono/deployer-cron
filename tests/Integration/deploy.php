<?php
namespace Deployer;

require_once '../../vendor/autoload.php';

require_once 'recipe/common.php';
require_once 'recipe/setono_cron.php';

// Config
set('repository', 'https://github.com/Setono/deployer-cron.git');
set('cron_config_dir', 'jobs');

// Hosts
host('127.0.0.1')
    ->setPort(2222)
    ->setRemoteUser('root')
    ->setIdentityFile(__DIR__ . '/../docker/ssh_key')
    ->setSshArguments(['-o UserKnownHostsFile=/dev/null', '-o StrictHostKeyChecking=no'])
    ->set('deploy_path', '~/deployer');

// Hooks
after('deploy:failed', 'deploy:unlock');
