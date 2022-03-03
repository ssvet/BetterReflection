<?php

declare(strict_types=1);

namespace Roave\BetterReflection;

use PhpParser\Lexer\Emulative;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator as AstLocator;
use Roave\BetterReflection\SourceLocator\Ast\Parser\MemoizingParser;
use Roave\BetterReflection\SourceLocator\SourceStubber\AggregateSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\Util\FindReflectionOnLine;

use const PHP_VERSION_ID;

final class BetterReflection
{
    /**
     * @var int
     */
    public static $phpVersion = PHP_VERSION_ID;

    /**
     * @var \Roave\BetterReflection\SourceLocator\Type\SourceLocator|null
     */
    private static $sharedSourceLocator;

    /**
     * @var \Roave\BetterReflection\SourceLocator\Type\SourceLocator|null
     */
    private $sourceLocator;

    /**
     * @var \Roave\BetterReflection\Reflector\Reflector|null
     */
    private static $sharedReflector;

    /**
     * @var \Roave\BetterReflection\Reflector\Reflector|null
     */
    private $reflector;

    /**
     * @var \PhpParser\Parser|null
     */
    private static $sharedPhpParser;

    /**
     * @var \PhpParser\Parser|null
     */
    private $phpParser;

    /**
     * @var AstLocator|null
     */
    private $astLocator;

    /**
     * @var \Roave\BetterReflection\Util\FindReflectionOnLine|null
     */
    private $findReflectionOnLine;

    /**
     * @var \Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber|null
     */
    private static $sharedSourceStubber;

    /**
     * @var \Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber|null
     */
    private $sourceStubber;

    public static function populate(int $phpVersion, SourceLocator $sourceLocator, Reflector $classReflector, Parser $phpParser, SourceStubber $sourceStubber): void
    {
        self::$phpVersion          = $phpVersion;
        self::$sharedSourceLocator = $sourceLocator;
        self::$sharedReflector     = $classReflector;
        self::$sharedPhpParser     = $phpParser;
        self::$sharedSourceStubber = $sourceStubber;
    }

    public function __construct()
    {
        $this->sourceLocator = self::$sharedSourceLocator;
        $this->reflector     = self::$sharedReflector;
        $this->phpParser     = self::$sharedPhpParser;
        $this->sourceStubber = self::$sharedSourceStubber;
    }

    public function sourceLocator(): SourceLocator
    {
        $astLocator    = $this->astLocator();
        $sourceStubber = $this->sourceStubber();

        return $this->sourceLocator
            ?? $this->sourceLocator = new MemoizingSourceLocator(new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator, $sourceStubber),
                new EvaledCodeSourceLocator($astLocator, $sourceStubber),
                new AutoloadSourceLocator($astLocator, $this->phpParser()),
            ]));
    }

    public function reflector(): Reflector
    {
        return $this->reflector
            ?? $this->reflector = new DefaultReflector($this->sourceLocator());
    }

    public function phpParser(): Parser
    {
        return $this->phpParser
            ?? $this->phpParser = new MemoizingParser((new ParserFactory())->create(ParserFactory::ONLY_PHP7, new Emulative([
                'usedAttributes' => ['comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'],
            ])));
    }

    public function astLocator(): AstLocator
    {
        return $this->astLocator
            ?? $this->astLocator = new AstLocator($this->phpParser());
    }

    public function findReflectionsOnLine(): FindReflectionOnLine
    {
        return $this->findReflectionOnLine
            ?? $this->findReflectionOnLine = new FindReflectionOnLine($this->sourceLocator(), $this->astLocator());
    }

    public function sourceStubber(): SourceStubber
    {
        return $this->sourceStubber
            ?? $this->sourceStubber = new AggregateSourceStubber(new PhpStormStubsSourceStubber($this->phpParser(), self::$phpVersion), new ReflectionSourceStubber());
    }
}
