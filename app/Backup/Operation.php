<?php

namespace App\Backup;

use App\Objects\FoundFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Osmianski\Code\Attributes\Get;
use Osmianski\Code\Attributes\Key;
use Osmianski\Traits\ConstructedFromArray;
use Osmianski\Traits\HasLazyProperties;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property-read OutputInterface $output
 */
abstract class Operation
{
    use ConstructedFromArray;
    use HasLazyProperties;

    #[Get(Key::class)]
    public string $key;

    protected ?string $workingDir = null;

    abstract public function run(): void;

    protected function get_output(): OutputInterface
    {
        return new BufferedOutput();
    }

    /**
     * @param string $pattern
     * @return array<int, FoundFile>
     */
    protected function find(string $pattern): array
    {
        $result = [new FoundFile(['path' => ''])];

        foreach(explode('/', $pattern) as $part) {
            $result = $this->findPart($result, $part);
        }

        return $result;
    }

    /**
     * @param array<int, FoundFile> $result
     * @param string $part
     * @return array<int, FoundFile>
     */
    protected function findPart(array $result, string $part): array
    {
        if ($part === '~') {
            return array_map(
                fn(FoundFile $file) => new FoundFile([
                    'path' => $this->getHomeDir(),
                    'variables' => $file->variables
                ]),
                $result,
            );
        }

        if (preg_match('/^{(?<variable>[^:}]+)(?::(?<valid_values>.+))?}$/', $part, $match)) {
            return Arr::flatten(array_map(
                fn(FoundFile $file) => $this->findInDir(
                    $file,
                    $match['variable'],
                    isset($match['valid_values']) ? explode('|', $match['valid_values']) : null,
                ),
                $result,
            ));
        }

        return array_map(
            function (FoundFile $file) use ($part) {
                $file->path .= DIRECTORY_SEPARATOR . $part;

                return $file;
            },
            $result,
        );
    }

    /**
     * @param FoundFile $file
     * @param string $variable
     * @return array<int, FoundFile>
     */
    protected function findInDir(FoundFile $file, string $variable, ?array $validValues): array
    {
        $result = [];

        if (!is_dir($file->path)) {
            return $result;
        }

        foreach (scandir($file->path) as $path) {
            if (in_array($path, ['.', '..'])) {
                continue;
            }

            if ($validValues && !in_array($path, $validValues)) {
                continue;
            }

            $result[] = new FoundFile([
                'path' => $file->path . DIRECTORY_SEPARATOR . $path,
                'variables' => array_merge($file->variables, [$variable => $path]),
            ]);
        }

        return $result;
    }

    protected function interpolate(string $pattern, array $variables): string
    {
        $pattern = str_replace('/', DIRECTORY_SEPARATOR, $pattern);

        if (str_starts_with($pattern, '~')) {
            $pattern = $this->getHomeDir() . substr($pattern, 1);
        }

        return preg_replace_callback(
            '/{(?<variable>.+)}/',
            function ($match) use ($variables) {
                return $variables[$match['variable']] ?? $match[0];
            },
            $pattern,
        );
    }

    protected function getHomeDir(): string
    {
        return ($home = $_SERVER['HOME'] ?? null)
            ? rtrim($home, '/')
            : rtrim("{$_SERVER['HOMEDRIVE']}{$_SERVER['HOMEPATH']}", '\\/');
    }

    protected function shell(string $command): void
    {
        $this->output->writeln("> {$command}");

        Process::forever()->tty()->run($command)->throw();
    }

    protected function mkdir(string $path): void
    {
        if (!is_dir($path)) {
            $this->shell("mkdir -p {$path}");
        }
    }

    protected function chdir(string $dirname): void
    {
        if ($this->workingDir === $dirname) {
            return;
        }

        $this->output->writeln("> cd {$dirname}");

        chdir($dirname);

        $this->workingDir = $dirname;
    }

    protected function sameFile(string $a, string $b): bool
    {
        // Check if filesize is different
        if(filesize($a) !== filesize($b))
            return false;

        // Check if content is different
        $ah = fopen($a, 'rb');
        $bh = fopen($b, 'rb');

        $result = true;
        while(!feof($ah))
        {
            if(fread($ah, 8192) != fread($bh, 8192)) {
                $result = false;
                break;
            }
        }

        fclose($ah);
        fclose($bh);

        return $result;
    }

    protected function move(string $source, string $target): void
    {
        if (is_file($target)) {
            $this->delete($target);
        }

        $this->output->writeln("> mv {$source} {$target}");

        rename($source, $target);
    }

    protected function delete(string $path): void
    {
        $this->output->writeln("> rm {$path}");

        unlink($path);
    }
}
