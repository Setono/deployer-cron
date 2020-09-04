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
use function Safe\unlink;
use Setono\CronBuilder\CronBuilder;

set('cron_source_dir', 'etc/cronjobs');
set('cron_delimiter', '{{application}} ({{stage}})');

task('cron:download', static function (): void {
    run('crontab -l 2>/dev/null > existing_crontab.txt');
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

    $newCrontab = $cronBuilder->merge($existingCrontab, $cronBuilder->build());

    if ('' !== $newCrontab) {
        file_put_contents('new_crontab.txt', $newCrontab);
    }
})->desc('Builds a new crontab and saves it to new_crontab.txt');

task('cron:upload', static function (): void {
    if (!file_exists('new_crontab.txt')) {
        return;
    }

    upload('new_crontab.txt', 'new_crontab.txt');
    run('cat new_crontab.txt | crontab -');
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
