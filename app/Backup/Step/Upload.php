<?php

namespace App\Backup\Step;

use App\Backup\Operation;
use Osmianski\Code\Attributes\Key;
use Osmianski\Exceptions\NotImplemented;

#[Key('upload')]
class Upload extends Operation
{
    public string $source;
    public string $target;


    public function run(): void
    {
        foreach ($this->find($this->source) as $file) {
            if (!file_exists($file->path)) {
                continue;
            }

            $this->upload($file->path, $this->interpolate($this->target, $file->variables));
        }
    }

    protected function upload(string $source, string $target): void
    {
        if (!str_ends_with($source, DIRECTORY_SEPARATOR)) {
            $source .= DIRECTORY_SEPARATOR;
        }

        $this->shell("rclone sync \"{$source}\" \"{$target}\" -v");
    }
}
