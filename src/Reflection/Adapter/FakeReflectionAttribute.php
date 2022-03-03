<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\Reflection\Adapter;

use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;

final class FakeReflectionAttribute
{
    /**
     * @var BetterReflectionAttribute
     */
    private $betterReflectionAttribute;
    public function __construct(BetterReflectionAttribute $betterReflectionAttribute)
    {
        $this->betterReflectionAttribute = $betterReflectionAttribute;
    }

    public function getName(): string
    {
        return $this->betterReflectionAttribute->getName();
    }

    public function getTarget(): int
    {
        return $this->betterReflectionAttribute->getTarget();
    }

    public function isRepeated(): bool
    {
        return $this->betterReflectionAttribute->isRepeated();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getArguments(): array
    {
        return $this->betterReflectionAttribute->getArguments();
    }

    /**
     * @return object
     */
    public function newInstance()
    {
        $class = $this->getName();

        return new $class(...$this->getArguments());
    }

    public function __toString(): string
    {
        return $this->betterReflectionAttribute->__toString();
    }
}
