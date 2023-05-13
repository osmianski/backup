<?php

namespace App;

use Osmianski\Exceptions\NotImplemented;

class Backup
{
    public function run(): void
    {
        $this->compressDirectories(
            config('backup.compress.sources'),
            config('backup.compress.target'),
        );
        $this->backupDatabases(
            config('backup.database.sources'),
            config('backup.database.target'),
        );
        $this->uploadDirectoriesToTheCloud(
            config('backup.upload.sources'),
            config('backup.upload.disk'),
            config('backup.upload.target')
        );
    }

    protected function compressDirectories(array $sources, string $target): void
    {
        throw new NotImplemented();
    }

    protected function backupDatabases(array $sources, string $target): void
    {
        throw new NotImplemented();
    }

    protected function uploadDirectoriesToTheCloud(array $sources, string $disk, string $target): void
    {
        throw new NotImplemented();
    }
}
