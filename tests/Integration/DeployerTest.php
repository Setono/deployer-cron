<?php
declare(strict_types=1);

namespace Setono\Deployer\Cron\Integration;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\TestCase;

final class DeployerTest extends TestCase
{
    #[\Override]
    protected function assertPreConditions(): void
    {
        self::assertFileExists(__DIR__ . '/deploy.php');
    }

    /**
     * @test
     */
    public function it_deploys(): void
    {
        exec(sprintf('cd %s && php %s deploy -n', __DIR__, '../../vendor/bin/dep'), $output, $return);

        $this->assertSame(0, $return);

        $key = PublicKeyLoader::load(file_get_contents(__DIR__ . '/../docker/ssh_key'));

        $ssh = new SSH2('127.0.0.1', 2222);
        if (!$ssh->login('root', $key)) {
            throw new \RuntimeException('Could not connection to the server');
        }

        $release = basename(trim($ssh->exec('realpath ~/deployer/current')));
        self::assertIsNumeric($release);

        $release = (int) $release;

        $output = $ssh->exec('crontab -l -u root');

        self::assertStringContainsString(<<<CRON
###> prod ###
0 0 * * * /usr/bin/php ~/deployer/releases/$release/send-report.php # Run every day at midnight
###< prod ###
CRON
, $output);
    }
}
