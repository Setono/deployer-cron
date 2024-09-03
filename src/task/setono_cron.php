<?php

declare(strict_types=1);

namespace Setono\Deployer\Cron;

use Deployer\Deployer;
use function Deployer\get;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use Deployer\Task\Context;
use function Deployer\upload;
use Setono\CronBuilder\CronBuilder;
use Symfony\Component\Finder\Finder;
use Webmozart\Assert\Assert;

set('cron_config_dir', 'etc/cronjobs');
set('cron_delimiter', static function (): string {
    return parse('{{application}} ({{stage}})');
});

// If you're deploying as root you have the option to edit other users' crontabs
// So this parameter is the http_user if you're deploying as root else we don't set it
set('cron_user', static function (): string {
    if ('root' !== run('whoami')) {
        return '';
    }

    $user = get('remote_user');
    Assert::string($user);

    return $user;
});

task('cron:prepare', [
    'cron:validate',
    'cron:backup',
    'cron:build',
]);

task('cron:validate', static function (): void {
    /** @var mixed $dir */
    $dir = get('cron_config_dir');
    Assert::directory($dir);
});

task('cron:backup', static function (): void {
    $cronUser = getCronUser();

    $crontab = run(sprintf('crontab -l%s 2>/dev/null || true', $cronUser !== '' ? (' -u ' . $cronUser) : ''));

    if ('' === $crontab) {
        return;
    }

    file_put_contents('crontab.backup.txt', $crontab); // todo allow to set the backup file name
})->desc('Backups the old crontab and stores it locally');

task('cron:build', static function (): void {
    $cronUser = getCronUser();

    $cronBuilder = (new CronBuilder())
        ->setDelimiter(get('cron_delimiter'))
        ->addFiles(
            (new Finder())
                ->files()
                ->in(get('cron_config_dir'))
                ->name('*.php'),
        )
    ;

    $config = Deployer::get()->config->ownValues();
    if (Context::has()) {
        $context = Context::get();
        if (false !== $context) {
            $config = array_merge($config, $context->getConfig()->ownValues());
        }
    }

    foreach ($config as $key => $value) {
        $cronBuilder->addContext($key, $value);
    }

    $existingCrontab = run(sprintf('crontab -l%s 2>/dev/null || true', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
    $newCrontab = CronBuilder::merge($existingCrontab, $cronBuilder);

    file_put_contents('crontab.txt', $newCrontab);
    upload('crontab.txt', '{{release_path}}/crontab.txt');
});

task('cron:apply', static function (): void {
    $cronUser = getCronUser();

    run(sprintf('cat {{release_path}}/crontab.txt | crontab%s -', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
});

task('cron:cleanup', static function (): void {
    // delete local file
    if (file_exists('crontab.txt')) {
        @unlink('crontab.txt');
    }
});

function getCronUser(): string
{
    $cronUser = get('cron_user');
    Assert::string($cronUser);

    return $cronUser;
}
