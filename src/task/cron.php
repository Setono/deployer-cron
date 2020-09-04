<?php

declare(strict_types=1);

namespace Setono\Deployer\Cron;

use function Deployer\download;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\sprintf;
use function Safe\unlink;
use Setono\CronBuilder\CronBuilder;
use Setono\CronBuilder\VariableResolver\ReplacingVariableResolver;
use Setono\CronBuilder\VariableResolver\VariableResolverInterface;

set('cron_source_dir', 'etc/cronjobs');
set('cron_delimiter', static function (): string {
    return sprintf('%s (%s)', get('application'), get('stage'));
});
set('cron_variable_resolvers', []);
set('cron_context', static function (): array {
    return [
        'stage' => get('stage'),
    ];
});

// If you're deploying as root you have the option to edit other users' crontabs
// So this parameters the user to the http_user if you're deploying as root else we don't set it
set('cron_user', static function (): string {
    if ('root' !== run('whoami')) {
        return '';
    }

    return get('http_user');
});

task('cron:download', static function (): void {
    $cronUser = get('cron_user');

    run(sprintf('crontab -l%s 2>/dev/null > existing_crontab.txt', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
    download('existing_crontab.txt', 'existing_crontab.txt');
})->desc('Downloads existing crontab to existing_crontab.txt file');

task('cron:build', static function (): void {
    $existingCrontab = '';
    if (file_exists('existing_crontab.txt')) {
        $existingCrontab = file_get_contents('existing_crontab.txt');
    }

    $cronBuilder = new CronBuilder([
        'source' => get('cron_source_dir'),
        'delimiter' => get('cron_delimiter'),
    ]);

    /** @var VariableResolverInterface[] $variableResolvers */
    $variableResolvers = get('cron_variable_resolvers');
    foreach ($variableResolvers as $variableResolver) {
        $cronBuilder->addVariableResolver($variableResolver);
    }

    $cronBuilder->addVariableResolver(new ReplacingVariableResolver([
        'application' => get('application'),
        'stage' => get('stage'),
        'php_bin' => get('bin/php'),
        'release_path' => get('release_path'),
    ]));

    $newCrontab = $cronBuilder->merge($existingCrontab, $cronBuilder->build(get('cron_context')));

    if ('' !== $newCrontab) {
        file_put_contents('new_crontab.txt', $newCrontab);
    }
})->desc('Builds a new crontab and saves it to new_crontab.txt');

task('cron:upload', static function (): void {
    if (!file_exists('new_crontab.txt')) {
        return;
    }

    $cronUser = get('cron_user');

    upload('new_crontab.txt', 'new_crontab.txt');
    run(sprintf('cat new_crontab.txt | crontab%s -', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
})->desc('Uploads and applies the new crontab');

task('cron:cleanup', static function (): void {
    if (file_exists('existing_crontab.txt')) {
        @unlink('existing_crontab.txt');
    }

    if (file_exists('new_crontab.txt')) {
        @unlink('new_crontab.txt');
    }

    if (test('[ -f existing_crontab.txt ]')) {
        run('rm existing_crontab.txt');
    }

    if (test('[ -f new_crontab.txt ]')) {
        run('rm new_crontab.txt');
    }
})->desc('Removes any generated files');
