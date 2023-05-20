<?php

namespace Osmianski\Code;

use Illuminate\Support\ServiceProvider;

class CodeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Code::class, function () {
            return new Code();
        });

    }
}
