<?php

declare(strict_types=1);

namespace Setono\Deployer\Cron;

use function Deployer\get;
use function Deployer\has;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;
use function Deployer\writeln;
use function file_put_contents;
use Setono\CronBuilder\CronBuilder;
use Setono\CronBuilder\VariableResolver\ReplacingVariableResolver;
use Setono\CronBuilder\VariableResolver\VariableResolverInterface;
use function sprintf;
use Symfony\Component\Console\Output\OutputInterface;
use function unlink;
use Webmozart\Assert\Assert;

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
set('cron_dry_run', false);

// If you're deploying as root you have the option to edit other users' crontabs
// So this parameter is the http_user if you're deploying as root else we don't set it
set('cron_user', static function (): string {
    if ('root' !== run('whoami')) {
        return '';
    }

    $user = get('http_user');
    Assert::string($user);

    return $user;
});

task('cron:prepare', static function (): void {
    if (get('cron_dry_run') === true) {
        writeln('The cron recipe is running in dry run mode, which means it won\'t apply the crontab on the remote server. You can see the resulting crontab by running deployment in verbose mode (-vvv)');
    }

    if (!has('stage')) {
        // if a stage isn't set then we presume the stage to be prod since you are only deploying to one place
        set('stage', 'prod');
    }
})->desc('Prepares parameters for the cron deployer lib');

task('cron:apply', static function (): void {
    $cronUser = get('cron_user');
    Assert::string($cronUser);

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

    $cronContext = get('cron_context');
    Assert::isArray($cronContext);

    $newCrontab = $cronBuilder->merge($existingCrontab, $cronBuilder->build($cronContext));

    if ('' === $newCrontab) {
        return;
    }

    writeln('New crontab', OutputInterface::VERBOSITY_VERBOSE);
    writeln($newCrontab, OutputInterface::VERBOSITY_VERBOSE);

    file_put_contents('new_crontab.txt', $newCrontab);
    upload('new_crontab.txt', '{{release_path}}/new_crontab.txt');

    if (get('cron_dry_run') === false) {
        run(sprintf('cat {{release_path}}/new_crontab.txt | crontab%s -', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
    }
})->desc('Builds and applies new crontab');

task('cron:cleanup', static function (): void {
    // delete local file
    if (file_exists('new_crontab.txt')) {
        @unlink('new_crontab.txt');
    }
})->desc('Removes any generated files');
