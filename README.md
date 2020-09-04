# Cron functions for Deployer

[![Latest Version][ico-version]][link-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

Simple handling of cronjobs in your deployment process using the [Cron builder library](https://github.com/Setono/cron-builder).

## Installation

```bash
$ composer require setono/deployer-cron
```

## Usage
The easiest usage is to include the cron recipe which hooks into default Deployer events:

```php
<?php
// deploy.php

require_once 'recipe/cron.php';
```

## Deployer parameters

The following Deployer parameters are defined:

| Parameter               | Description                                                                                      | Default value                   |
|-------------------------|--------------------------------------------------------------------------------------------------|---------------------------------|
| cron_source_dir         | The directory to search for cronjob config files                                                 | `etc/cronjobs`                                |
| cron_delimiter          | The marker in the crontab file that delimits the generated cronjobs from manually added cronjobs | `{{application}} ({{stage}})`                 |
| cron_variable_resolvers | An array of variable resolvers to add to the cron builder                                        | `[]`                                          |
| cron_context            | The context to give as argument to the `CronBuilder::build` method                               | `[ 'stage' => get('stage') ]`                 |
| cron_user               | The user onto which the crontab should be added                                                  | `get('http_user')` if you are root, else `''` |

**NOTICE** that the default value of `cron_variable_resolvers` is an empty array, but this lib will always add a
`ReplacingVariableResolver` with the variables described in the section [below](#extra-variables-available).

## Build context

The default build context is defined in the Deployer parameter `cron_context`. It adds the stage as context which means
you can use the `condition` key in your cronjob config:

```yaml
# /etc/cronjobs/jobs.yaml

- schedule: "0 0 * * *"
  command: "%php_bin% %release_path%/bin/console my:dev:command"
  condition: "context.stage === 'dev'"
```

The above cronjob will only be added to the final cron file if the deployment stage equals `dev`.

## Extra variables available

This library also adds more variables you can use in your cronjob configs:

- `%application%`: Will output the application name
- `%stage%`: Will output the stage, i.e. `dev`, `staging`, or `prod`
- `%php_bin%`: Will output the path to the PHP binary
- `%release_path%`: Will output the release path on the server

With these variables you can define a cronjob like:

```yaml
# /etc/cronjobs/jobs.yaml

- schedule: "0 0 * * *"
  command: "%php_bin% %release_path%/bin/console my:command"
```

And that will translate into the following line in your crontab:

```text
0 0 * * * /usr/bin/php /var/www/your_application/releases/23/bin/console my:command
```

[ico-version]: https://poser.pugx.org/setono/deployer-cron/v/stable
[ico-unstable-version]: https://poser.pugx.org/setono/deployer-cron/v/unstable
[ico-license]: https://poser.pugx.org/setono/deployer-cron/license
[ico-github-actions]: https://github.com/Setono/deployer-cron/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/deployer-cron
[link-github-actions]: https://github.com/Setono/deployer-cron/actions
