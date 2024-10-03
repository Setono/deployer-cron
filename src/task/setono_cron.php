<?php

declare(strict_types=1);

namespace Setono\Deployer\Cron;

use Deployer\Deployer;
use function Deployer\get;
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
    $labels = get('labels');
    if (!is_array($labels)) {
        return 'prod';
    }

    if (!isset($labels['stage'])) {
        return 'prod';
    }

    $stage = $labels['stage'];
    Assert::stringNotEmpty($stage);

    return $stage;
});
set('crontab_filename', 'crontab.txt');
set('crontab_backup_filename', 'crontab.backup.txt');

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

    file_put_contents(get('crontab_backup_filename'), $crontab);
})->desc('Backups the old crontab and stores it locally');

task('cron:apply', static function (): void {
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

    $config = [];
    foreach (Deployer::get()->config->ownValues() as $key => $value) {
        if (is_callable($value)) {
            continue;
        }

        $config[$key] = get($key);
    }

    if (Context::has()) {
        $context = Context::get();
        if (false !== $context) {
            foreach ($context->getConfig()->ownValues() as $key => $value) {
                if (is_callable($value)) {
                    continue;
                }

                $config[$key] = get($key);
            }
        }
    }

    $cronBuilder->setContext($config);

    file_put_contents(get('crontab_filename'), CronBuilder::merge(
        file_get_contents(get('crontab_backup_filename')),
        $cronBuilder,
    ));

    upload(get('crontab_filename'), '{{release_path}}/{{crontab_filename}}');
    run(sprintf('cat {{release_path}}/{{crontab_filename}} | crontab%s -', $cronUser !== '' ? (' -u ' . $cronUser) : ''));
});

task('cron:cleanup', static function (): void {
    if (file_exists(get('crontab_filename'))) {
        @unlink(get('crontab_filename'));
    }

    if (file_exists(get('crontab_backup_filename'))) {
        @unlink(get('crontab_backup_filename'));
    }
});

function getCronUser(): string
{
    $cronUser = get('cron_user');
    Assert::string($cronUser);

    return $cronUser;
}
