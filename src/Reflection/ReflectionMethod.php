<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use ReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use Roave\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use Roave\BetterReflection\Reflection\StringCast\ReflectionMethodStringCast;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function assert;
use function class_exists;
use function sprintf;
use function strtolower;
use function trait_exists;

class ReflectionMethod
{
    use ReflectionFunctionAbstract;

    /**
     * @var MethodNode
     */
    private $methodNode;
    /**
     * @var \Roave\BetterReflection\Reflector\Reflector
     */
    private $reflector;
    /**
     * @var MethodNode|\PhpParser\Node\Expr\ArrowFunction|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\Function_
     */
    private $node;
    /**
     * @var \Roave\BetterReflection\SourceLocator\Located\LocatedSource
     */
    private $locatedSource;
    /**
     * @var NamespaceNode|null
     */
    private $declaringNamespace;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionClass
     */
    private $declaringClass;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionClass
     */
    private $implementingClass;
    /**
     * @var \Roave\BetterReflection\Reflection\ReflectionClass
     */
    private $currentClass;
    /**
     * @var string|null
     */
    private $aliasName;
    /**
     * @param MethodNode|\PhpParser\Node\Expr\ArrowFunction|\PhpParser\Node\Expr\Closure|\PhpParser\Node\Stmt\Function_ $node
     */
    private function __construct(Reflector $reflector, $node, LocatedSource $locatedSource, ?NamespaceNode $declaringNamespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName)
    {
        $this->reflector = $reflector;
        $this->node = $node;
        $this->locatedSource = $locatedSource;
        $this->declaringNamespace = $declaringNamespace;
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->currentClass = $currentClass;
        $this->aliasName = $aliasName;
        assert($node instanceof MethodNode);
        $this->methodNode = $node;
    }
    /**
     * @internal
     */
    public static function createFromNode(Reflector $reflector, MethodNode $node, LocatedSource $locatedSource, ?Namespace_ $namespace, ReflectionClass $declaringClass, ReflectionClass $implementingClass, ReflectionClass $currentClass, ?string $aliasName = null): self
    {
        return new self($reflector, $node, $locatedSource, $namespace, $declaringClass, $implementingClass, $currentClass, $aliasName);
    }

    /**
     * Create a reflection of a method by it's name using a named class
     *
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromName(string $className, string $methodName): self
    {
        return ReflectionClass::createFromName($className)->getMethod($methodName);
    }

    /**
     * Create a reflection of a method by it's name using an instance
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     * @param object $instance
     */
    public static function createFromInstance($instance, string $methodName): self
    {
        return ReflectionClass::createFromInstance($instance)->getMethod($methodName);
    }

    public function getAst(): MethodNode
    {
        return $this->methodNode;
    }

    public function getShortName(): string
    {
        if ($this->aliasName !== null) {
            return $this->aliasName;
        }

        return $this->methodNode->name->name;
    }

    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }

    /**
     * Find the prototype for this method, if it exists. If it does not exist
     * it will throw a MethodPrototypeNotFound exception.
     *
     * @throws Exception\MethodPrototypeNotFound
     */
    public function getPrototype(): self
    {
        $currentClass = $this->getImplementingClass();

        while ($currentClass) {
            foreach ($currentClass->getImmediateInterfaces() as $interface) {
                if ($interface->hasMethod($this->getName())) {
                    return $interface->getMethod($this->getName());
                }
            }

            $currentClass = $currentClass->getParentClass();

            if ($currentClass === null || ! $currentClass->hasMethod($this->getName())) {
                break;
            }

            $prototype = $currentClass->getMethod($this->getName())->findPrototype();

            if ($prototype !== null) {
                if ($this->isConstructor() && ! $prototype->isAbstract()) {
                    break;
                }

                return $prototype;
            }
        }

        throw new Exception\MethodPrototypeNotFound(sprintf('Method %s::%s does not have a prototype', $this->getDeclaringClass()->getName(), $this->getName()));
    }

    private function findPrototype(): ?self
    {
        if ($this->isAbstract()) {
            return $this;
        }

        if ($this->isPrivate()) {
            return null;
        }

        try {
            return $this->getPrototype();
        } catch (Exception\MethodPrototypeNotFound $exception) {
            return $this;
        }
    }

    /**
     * Get the core-reflection-compatible modifier values.
     */
    public function getModifiers(): int
    {
        $val  = $this->isStatic() ? CoreReflectionMethod::IS_STATIC : 0;
        $val += $this->isPublic() ? CoreReflectionMethod::IS_PUBLIC : 0;
        $val += $this->isProtected() ? CoreReflectionMethod::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? CoreReflectionMethod::IS_PRIVATE : 0;
        $val += $this->isAbstract() ? CoreReflectionMethod::IS_ABSTRACT : 0;
        $val += $this->isFinal() ? CoreReflectionMethod::IS_FINAL : 0;

        return $val;
    }

    public function __toString(): string
    {
        return ReflectionMethodStringCast::toString($this);
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function getNamespaceName(): string
    {
        return '';
    }

    public function isClosure(): bool
    {
        return false;
    }

    /**
     * Is the method abstract.
     */
    public function isAbstract(): bool
    {
        return $this->methodNode->isAbstract() || $this->declaringClass->isInterface();
    }

    /**
     * Is the method final.
     */
    public function isFinal(): bool
    {
        return $this->methodNode->isFinal();
    }

    /**
     * Is the method private visibility.
     */
    public function isPrivate(): bool
    {
        return $this->methodNode->isPrivate();
    }

    /**
     * Is the method protected visibility.
     */
    public function isProtected(): bool
    {
        return $this->methodNode->isProtected();
    }

    /**
     * Is the method public visibility.
     */
    public function isPublic(): bool
    {
        return $this->methodNode->isPublic();
    }

    /**
     * Is the method static.
     */
    public function isStatic(): bool
    {
        return $this->methodNode->isStatic();
    }

    /**
     * Is the method a constructor.
     */
    public function isConstructor(): bool
    {
        if (strtolower($this->getName()) === '__construct') {
            return true;
        }

        $declaringClass = $this->getDeclaringClass();
        if ($declaringClass->inNamespace()) {
            return false;
        }

        return strtolower($this->getName()) === strtolower($declaringClass->getShortName());
    }

    /**
     * Is the method a destructor.
     */
    public function isDestructor(): bool
    {
        return strtolower($this->getName()) === '__destruct';
    }

    /**
     * Get the class that declares this method.
     */
    public function getDeclaringClass(): ReflectionClass
    {
        return $this->declaringClass;
    }

    /**
     * Get the class that implemented the method based on trait use.
     */
    public function getImplementingClass(): ReflectionClass
    {
        return $this->implementingClass;
    }

    /**
     * Get the current reflected class.
     */
    public function getCurrentClass(): ReflectionClass
    {
        return $this->currentClass;
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @param object|null $object
     */
    public function getClosure($object = null): Closure
    {
        $declaringClassName = $this->getDeclaringClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($declaringClassName);

            return function (...$args) {
                return $this->callStaticMethod($args);
            };
        }

        $instance = $this->assertObject($object);

        return function (...$args) use ($instance) {
            return $this->callObjectMethod($instance, $args);
        };
    }

    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @param mixed ...$args
     * @return mixed
     * @param object|null $object
     */
    public function invoke($object = null, ...$args)
    {
        return $this->invokeArgs($object, $args);
    }

    /**
     * @param list<mixed> $args
     *
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @return mixed
     * @param object|null $object
     */
    public function invokeArgs($object = null, array $args = [])
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);

            return $this->callStaticMethod($args);
        }

        return $this->callObjectMethod($this->assertObject($object), $args);
    }

    /**
     * @param list<mixed> $args
     * @return mixed
     */
    private function callStaticMethod(array $args)
    {
        $implementingClassName = $this->getImplementingClass()->getName();

        /** @psalm-suppress InvalidStringClass */
        $closure = Closure::bind(function (string $implementingClassName, string $methodName, array $methodArgs) {
            return $implementingClassName::{$methodName}(...$methodArgs);
        }, null, $implementingClassName);

        assert($closure instanceof Closure);

        return $closure->__invoke($implementingClassName, $this->getName(), $args);
    }

    /**
     * @param list<mixed> $args
     * @return mixed
     * @param object $object
     */
    private function callObjectMethod($object, array $args)
    {
        $closure = Closure::bind(function ($object, string $methodName, array $methodArgs) {
            return $object->{$methodName}(...$methodArgs);
        }, $object, $this->getImplementingClass()->getName());

        assert($closure instanceof Closure);

        return $closure->__invoke($object, $this->getName(), $args);
    }

    /**
     * @throws ClassDoesNotExist
     */
    private function assertClassExist(string $className): void
    {
        if (! class_exists($className, false) && ! trait_exists($className, false)) {
            throw new ClassDoesNotExist(sprintf('Method of class %s cannot be used as the class is not loaded', $className));
        }
    }

    /**
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @param object|null $object
     * @return object
     */
    private function assertObject($object)
    {
        if ($object === null) {
            throw NoObjectProvided::create();
        }

        $implementingClassName = $this->getImplementingClass()->getName();

        if (get_class($object) !== $implementingClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($implementingClassName);
        }

        return $object;
    }
}
