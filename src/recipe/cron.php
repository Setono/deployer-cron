<?php

declare(strict_types=1);

use function Deployer\after;
use function Deployer\before;

require_once 'task/cron.php';

// download and build are done before anything else because we can stop the deploy if
// any errors happen in either of these two tasks
before('deploy:prepare', 'cron:download');
after('cron:download', 'cron:build');

// and then we upload the generated crontab when we are pointing the symlink to the new directory
after('deploy:symlink', 'cron:upload');

// cleanup created files
after('cleanup', 'cron:cleanup');
