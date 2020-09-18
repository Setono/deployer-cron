<?php

declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once 'task/cron.php';

before('deploy:prepare', 'cron:prepare');

// apply the cron just before symlinking. This is where all the necessary parameters are available, i.e. 'release_path'
before('deploy:symlink', 'cron:apply');

// cleanup created files
after('cleanup', 'cron:cleanup');
after('deploy:failed', 'cron:cleanup');
