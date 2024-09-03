<?php

declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once 'task/setono_cron.php';

before('deploy:prepare', 'cron:prepare');

// apply the cron just before symlinking. This is where the release_path is available
before('deploy:symlink', 'cron:build');

// cleanup created files
after('cleanup', 'cron:cleanup');
after('deploy:failed', 'cron:cleanup');
