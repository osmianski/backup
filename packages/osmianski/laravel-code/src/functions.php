<?php

use Osmianski\Code\Code;

if (! function_exists('code')) {
    function code(): Code
    {
        return app(Code::class);
    }
}
