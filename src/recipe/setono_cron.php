<?php

declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once 'task/setono_cron.php';

before('deploy:prepare', 'cron:prepare');

// Apply the cron just before symlinking. This is where the release_path is available
before('deploy:symlink', 'cron:apply');

// Cleanup created files
after('deploy:cleanup', 'cron:cleanup');
after('deploy:failed', 'cron:cleanup');
