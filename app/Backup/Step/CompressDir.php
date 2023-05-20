<?php

namespace App\Backup\Step;

use App\Backup\Operation;
use Osmianski\Code\Attributes\Key;

#[Key('compress_dir')]
class CompressDir extends Operation
{
    public string $source;
    public string $target;

    public function run(): void
    {
        foreach ($this->find($this->source) as $file) {
            if (!is_dir($file->path)) {
                continue;
            }

            $this->compress($file->path, $this->interpolate($this->target, $file->variables));
        }
    }

    protected function compress(string $dir, string $tar): void
    {
        $this->mkdir(dirname($tar));

        $this->chdir(dirname($dir));

        $dir = basename($dir);

        if (file_exists($tar)) {
            $temp = tempnam(sys_get_temp_dir(), 'backup_');

            $this->shell("tar -czf $temp $dir");

            if ($this->sameFile($temp, $tar)) {
                $this->output->writeln("The file hasn't changed, skipping");
                unlink($temp);
            }
            else {
                $this->move($temp, $tar);
            }
        }
        else {
            $this->shell("tar -czf $tar $dir");
        }
    }
}
