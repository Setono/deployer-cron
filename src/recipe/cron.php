<?php

declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once 'task/cron.php';

// download existing cron before anything else because we can stop the deploy if any errors happen
before('deploy:prepare', 'cron:download');

// build the cron just before symlinking. This is where all the necessary parameters are available, i.e. 'release_path'
before('deploy:symlink', 'cron:build');

// and then we upload the generated crontab when we are pointing the symlink to the new directory
after('deploy:symlink', 'cron:upload');

// cleanup created files
after('cleanup', 'cron:cleanup');
after('deploy:failed', 'cron:cleanup');
