<?php

namespace Osmianski\Code\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Get
{
    public function __construct(
        public ?string $value = null,
        public ?string $__ = null,
    ) {
    }
}
