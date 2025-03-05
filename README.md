# Cron functions for Deployer

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

Simple handling of cronjobs in your deployment process using the [Cron builder library](https://github.com/Setono/cron-builder).

## Installation

```bash
composer require setono/deployer-cron
```

## Usage
The easiest usage is to include the cron recipe which hooks into the default Deployer lifecycle:

```php
<?php
// deploy.php

require_once 'recipe/cron.php';
```

## Deployer parameters

The following Deployer parameters are defined:

| Parameter               | Description                                                                                      | Default value                                 |
|-------------------------|--------------------------------------------------------------------------------------------------|-----------------------------------------------|
| cron_config_dir         | The directory to search for cronjob config files                                                 | `etc/cronjobs`                                |
| cron_delimiter          | The marker in the crontab file that delimits the generated cronjobs from manually added cronjobs | The stage. If not set, the default is `prod`. |
| cron_user               | The user onto which the crontab should be added (default is `remote_user`)                       | `get('http_user')` if you are root, else `''` |

## Cron builder context

The cron builder context is set to the Deployer configuration parameters. This means you can use variables in your
cronjob config files. For example:

```php
<?php
# etc/cronjobs/jobs.php

declare(strict_types=1);

use Setono\CronBuilder\Context;
use Setono\CronBuilder\CronJob;

return static function (Context $context): iterable {
    yield new CronJob('0 0 * * *', '/usr/bin/php {{ release_path }}/send-report.php', 'Run every day at midnight');

    if ($context->get('stage') === 'prod') {
        yield new CronJob('0 0 * * *', '/usr/bin/php {{ release_path }}/process.php');
    }
};
```

Notice the usage of `release_path` and `stage` in the cronjob config file. This is possible because the Deployer
configuration is added as context on the cron builder. An important note about this feature is that Deployer configuration
having `/` in their key will be escaped with `_`. An example: `bin/console` is available as `bin_console`.

[ico-version]: https://poser.pugx.org/setono/deployer-cron/v/stable
[ico-license]: https://poser.pugx.org/setono/deployer-cron/license
[ico-github-actions]: https://github.com/Setono/deployer-cron/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/deployer-cron
[link-github-actions]: https://github.com/Setono/deployer-cron/actions
