<?php

namespace App\Objects;

use Osmianski\Traits\ConstructedFromArray;

class FoundFile
{
    use ConstructedFromArray;

    public string $path;
    public array $variables = [];
}
