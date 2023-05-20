<?php

namespace Osmianski\Code;

use Illuminate\Support\Collection;
use Osmianski\Code\Attributes\Get;
use Osmianski\Code\Attributes\Key;
use Osmianski\Code\Exceptions\ExpectedDescendentClass;
use Osmianski\Traits\ConstructedFromArray;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

class Code
{
    protected array $paths;

    protected array $classes;

    protected array $traits = [];

    protected array $descendants = [];

    protected array $singletons = [];

    public function __construct()
    {
        $this->paths = [
            'App' => base_path('app'),
        ];
    }

    public function registerCodePath(string $namespace, string $path): static
    {
        $this->paths[$namespace] = $path;

        return $this;
    }

    protected function getClasses(): array
    {
        if (!isset($this->classes)) {
            $this->classes = [];

            foreach ($this->paths as $namespace => $path) {
                $this->classes = array_merge($this->classes, $this->getClassesFromPath($namespace, $path));
            }
        }

        return $this->classes;
    }

    protected function getClassesFromPath(string $namespace, string $path): array
    {
        $classes = [];

        foreach (scandir($path) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $fullPath = $path.'/'.$file;

            if (is_dir($fullPath)) {
                $classes = array_merge($classes, $this->getClassesFromPath($namespace.'\\'.$file, $fullPath));
            }
            else {
                $classes[] = $namespace.'\\'.pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $classes;
    }

    /**
     * @return Collection<string, string>
     */
    public function descendantsOf(string $baseClass): Collection
    {
        if (! isset($this->descendants[$baseClass])) {
            $this->descendants[$baseClass] = $this->collect($baseClass);
        }

        return $this->descendants[$baseClass];
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $baseClass
     * @return Collection<string, T>
     */
    public function singletonsOf(string $baseClass): Collection
    {
        if (! isset($this->singletons[$baseClass])) {
            $this->singletons[$baseClass] = $this->instantiate($baseClass);
        }

        return $this->singletons[$baseClass];
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $baseClass
     * @return ?T
     */
    public function instanceOf(string $baseClass, string $key, array $parameters = []): mixed
    {
        if (! ($class = $this->descendantsOf($baseClass)->get($key))) {
            throw new ExpectedDescendentClass(sprintf(
                'Define a class extending "%s" and having "Key" attribute equal to "%s"',
                $baseClass,
                $key,
            ));
        }

            return $this->make($class, $parameters);
    }

    protected function collect(string $baseClass): Collection
    {
        $classes = [];

        foreach ($this->getClasses() as $class) {
            if (! is_subclass_of($class, $baseClass)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (! ($attribute = $reflection->getAttributes(Key::class)[0] ?? null)) {
                continue;
            }

            $classes[$attribute->newInstance()->value] = $class;
        }

        return collect($classes);
    }

    protected function instantiate(string $baseClass): Collection
    {
        $singletons = [];

        foreach ($this->descendantsOf($baseClass) as $key => $class) {
            $singletons[$key] = $this->make($class);
        }

        return collect($singletons);
    }

    protected function make(string $class, array $parameters = []): object
    {
        $reflection = new ReflectionClass($class);

        $instance = $this->isConstructedFromArray($class)
            ? new $class($parameters)
            : app($reflection->getName(), $parameters);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->isInitialized($instance)) {
                continue;
            }

            if (! ($attribute = $property->getAttributes(Get::class)[0] ?? null)) {
                continue;
            }

            /* @var Get $get */
            $get = $attribute->newInstance();

            if ($get->value) {
                $property->setValue($instance, $this->value($reflection, $get->value));
            }
            elseif ($get->__) {
                $property->setValue($instance, $this->__($reflection, $get->__));
            }
            else {
                throw new RuntimeException(sprintf(
                    'Provide a value to the `%s` attribute of the `%s::%s` property',
                    Get::class,
                    $reflection->getName(),
                    $property->getName(),
                ));
            }
        }

        return $instance;
    }

    public function value(ReflectionClass $reflection, string $attribute): mixed
    {
        if (! ($attribute = $reflection->getAttributes($attribute)[0] ?? null)) {
            throw new RuntimeException(sprintf(
                'The `%s` attribute is required for the `%s` class',
                $attribute,
                $reflection->getName(),
            ));
        }

        return $attribute->newInstance()->value;
    }

    public function __(ReflectionClass $reflection, string $attribute): string
    {
        return __($this->value($reflection, $attribute));
    }

    protected function isConstructedFromArray(string $class): bool
    {
        return in_array(ConstructedFromArray::class, $this->getTraits($class));
    }

    protected function getTraits(string $class): array
    {
        if (! isset($this->traits[$class])) {
            $this->traits[$class] = class_uses_recursive($class);
        }

        return $this->traits[$class];
    }
}
