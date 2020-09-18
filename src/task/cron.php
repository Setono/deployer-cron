<?php

declare(strict_types=1);

namespace Setono\Deployer\Cron;

use function Deployer\get;
use function Deployer\has;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use function Deployer\writeln;
use function Safe\file_put_contents;
use function Safe\sprintf;
use function Safe\unlink;
use Setono\CronBuilder\CronBuilder;
use Setono\CronBuilder\VariableResolver\ReplacingVariableResolver;
use Setono\CronBuilder\VariableResolver\VariableResolverInterface;
use Symfony\Component\Console\Output\OutputInterface;

set('cron_source_dir', 'etc/cronjobs');
set('cron_delimiter', static function (): string {
    return parse('{{application}} ({{stage}})');
});
set('cron_variable_resolvers', []);
set('cron_context', static function (): array {
    return [
        'stage' => get('stage'),
    ];
});

// If you're deploying as root you have the option to edit other users' crontabs
// So this parameter is the http_user if you're deploying as root else we don't set it
set('cron_user', static function (): string {
    if ('root' !== run('whoami')) {
        return '';
    }

    return get('http_user');
});

task('cron:prepare', static function (): void {
    if (!has('stage')) {
        // if a stage isn't set then we presume the stage to be prod since you are only deploying to one place
        set('stage', 'prod');
    }
})->desc('Prepares parameters for the cron deployer lib');

task('cron:apply', static function (): void {
    $cronUser = get('cron_user');

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

    $existingCrontab = run(sprintf('crontab -l%s 2>/dev/null || true', $cronUser !== '' ? (' -u ' . $cronUser) : ''));

    $newCrontab = $cronBuilder->merge($existingCrontab, $cronBuilder->build(get('cron_context')));

    if ('' === $newCrontab) {
        return;
    }

    writeln('New crontab', OutputInterface::VERBOSITY_VERBOSE);
    writeln($newCrontab, OutputInterface::VERBOSITY_VERBOSE);

    file_put_contents('new_crontab.txt', $newCrontab);
    upload('new_crontab.txt', 'new_crontab.txt');
    run(sprintf('cat new_crontab.txt | crontab%s -', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
})->desc('Builds and applies new crontab');

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
