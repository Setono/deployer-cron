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

[ico-version]: https://poser.pugx.org/setono/deployer-cron/v/stable
[ico-unstable-version]: https://poser.pugx.org/setono/deployer-cron/v/unstable
[ico-license]: https://poser.pugx.org/setono/deployer-cron/license
[ico-github-actions]: https://github.com/Setono/deployer-cron/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/deployer-cron
[link-github-actions]: https://github.com/Setono/deployer-cron/actions
