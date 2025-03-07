<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\NodeCompiler\Exception;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflectionTest\BetterReflectionSingleton;

use function sprintf;

/**
 * @covers \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode
 */
final class UnableToCompileNodeTest extends TestCase
{
    public function testDefaults(): void
    {
        $exception = new UnableToCompileNode();

        self::assertNull($exception->constantName());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfClassNotLoaded(CompilerContext $context, string $contextName): void
    {
        $className = 'SomeClassThatCannotBeFound';

        $exception = UnableToCompileNode::becauseOfClassCannotBeLoaded($context, new New_(new Name($className)), $className);

        self::assertStringContainsString(sprintf('Cound not load class "%s" while evaluating expression in %s in file', $className, $contextName), $exception->getMessage());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfNotFoundConstantReference(CompilerContext $context, string $contextName): void
    {
        $constantName = 'FOO';

        $exception = UnableToCompileNode::becauseOfNotFoundConstantReference($context, new ConstFetch(new Name($constantName)), $constantName);

        self::assertStringContainsString(sprintf('Could not locate constant "%s" while evaluating expression in %s in file', $constantName, $contextName), $exception->getMessage());

        self::assertSame($constantName, $exception->constantName());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfNotFoundClassConstantReference(CompilerContext $context, string $contextName): void
    {
        $targetClass = $this->createMock(ReflectionClass::class);

        $targetClass
            ->expects(self::any())
            ->method('getName')
            ->willReturn('An\\Example');

        self::assertStringContainsString(sprintf('Could not locate constant An\Example::SOME_CONSTANT while trying to evaluate constant expression in %s in file', $contextName), UnableToCompileNode::becauseOfNotFoundClassConstantReference($context, $targetClass, new ClassConstFetch(new Name\FullyQualified('A'), new Identifier('SOME_CONSTANT')))->getMessage());
    }

    /** @dataProvider supportedContextTypes */
    public function testForUnRecognizedExpressionInContext(CompilerContext $context, string $contextName): void
    {
        self::assertStringContainsString(sprintf('Unable to compile expression in %s: unrecognized node type %s in file', $contextName, Yield_::class), UnableToCompileNode::forUnRecognizedExpressionInContext(new Yield_(new String_('')), $context)->getMessage());
    }

    /** @dataProvider supportedContextTypes */
    public function testBecauseOfMissingFileName(CompilerContext $context, string $contextName): void
    {
        $exception = UnableToCompileNode::becauseOfMissingFileName($context, new File());

        self::assertSame(sprintf('No file name for %s (line -1)', $contextName), $exception->getMessage());
    }

    /** @return list<array{0: CompilerContext, 1: string}> */
    public function supportedContextTypes(): array
    {
        $php = <<<'PHP'
<?php

namespace Foo;

const SOME_CONSTANT = 'some_constant';

class SomeClass
{
    public function someMethod()
    {
    }
}

function someFunction()
{
}
PHP;

        $astLocator = BetterReflectionSingleton::instance()->astLocator();
        $reflector  = new DefaultReflector(new AggregateSourceLocator([
            new StringSourceLocator($php, $astLocator),
            new PhpInternalSourceLocator($astLocator, BetterReflectionSingleton::instance()->sourceStubber()),
        ]));

        $class          = $reflector->reflectClass('Foo\SomeClass');
        $method         = $class->getMethod('someMethod');
        $function       = $reflector->reflectFunction('Foo\someFunction');
        $constant       = $reflector->reflectConstant('Foo\SOME_CONSTANT');
        $globalConstant = $reflector->reflectConstant('PHP_VERSION_ID');

        return [
            [new CompilerContext($reflector, $globalConstant), 'global namespace'],
            [new CompilerContext($reflector, $constant), 'namespace Foo'],
            [new CompilerContext($reflector, $class), 'class Foo\SomeClass'],
            [new CompilerContext($reflector, $method), 'method Foo\SomeClass::someMethod()'],
            [new CompilerContext($reflector, $function), 'function Foo\someFunction()'],
        ];
    }
}
