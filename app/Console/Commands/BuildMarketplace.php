<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class BuildMarketplace extends Command
{
    protected $signature = 'marketplace:build';

    protected $description = 'Build and atomically publish a deterministic dumb-HTTP Git repository';

    public function handle(): int
    {
        $target = config('marketplace.repository');
        $parent = dirname($target);
        File::ensureDirectoryExists($parent);
        $lock = fopen($target.'.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('Could not open marketplace build lock');
        }
        flock($lock, LOCK_EX);
        $temp = $parent.'/.marketplace-build-'.Str::uuid();
        File::ensureDirectoryExists($temp.'/work');

        try {
            if ((new ExecutableFinder)->find('claude')) {
                (new Process(['claude', 'plugin', 'validate', config('marketplace.source')]))->mustRun();
            } else {
                $this->warn('Claude CLI unavailable; manifest validation skipped.');
            }

            File::copyDirectory(config('marketplace.source'), $temp.'/work');
            $env = [
                'GIT_AUTHOR_NAME' => 'Artisan Build', 'GIT_AUTHOR_EMAIL' => 'marketplace@example.invalid',
                'GIT_COMMITTER_NAME' => 'Artisan Build', 'GIT_COMMITTER_EMAIL' => 'marketplace@example.invalid',
                'GIT_AUTHOR_DATE' => config('marketplace.commit_date'),
                'GIT_COMMITTER_DATE' => config('marketplace.commit_date'),
                'GIT_CONFIG_NOSYSTEM' => '1', 'GIT_CONFIG_GLOBAL' => '/dev/null',
            ];
            $git = function (array $args, string $cwd) use ($env): string {
                return trim((new Process(['git', ...$args], $cwd, $env))->mustRun()->getOutput());
            };
            $git(['init', '--object-format=sha1', '-b', 'main'], $temp.'/work');
            $git(['-c', 'core.autocrlf=false', 'add', '.'], $temp.'/work');
            $git(['-c', 'commit.gpgsign=false', 'commit', '-m', 'Publish marketplace'], $temp.'/work');
            $sha = $git(['rev-parse', 'HEAD'], $temp.'/work');
            $git(['clone', '--bare', $temp.'/work', $temp.'/repo'], $temp);
            $git(['repack', '-a', '-d'], $temp.'/repo');
            $git(['update-server-info'], $temp.'/repo');
            File::put($temp.'/repo/marketplace.sha', $sha."\n");

            // Immutable release directories + rename of a symlink avoid a missing-repo window.
            $release = $target.'-'.$sha;
            if (! is_dir($release)) {
                File::moveDirectory($temp.'/repo', $release);
            }
            $link = $temp.'/current';
            symlink($release, $link);
            if (! rename($link, $target)) {
                throw new \RuntimeException('Could not atomically publish repository');
            }
            File::put($temp.'/sha', $sha."\n");
            rename($temp.'/sha', $parent.'/marketplace.sha');
            $this->info("Published marketplace {$sha}");

            return self::SUCCESS;
        } finally {
            File::deleteDirectory($temp);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
