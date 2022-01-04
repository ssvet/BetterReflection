<?php

declare(strict_types=1);

namespace PHPStan\BetterReflection\SourceLocator\SourceStubber;

use Error;
use Generator;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use ParseError;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\BetterReflection\Reflection\Annotation\AnnotationHelper;
use PHPStan\BetterReflection\SourceLocator\FileChecker;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor;
use Traversable;

use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function explode;
use function file_get_contents;
use function in_array;
use function is_dir;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function usort;

use const PHP_VERSION_ID;

/**
 * @internal
 */
final class PhpStormStubsSourceStubber implements SourceStubber
{
    private const BUILDER_OPTIONS    = ['shortArraySyntax' => true];
    private const SEARCH_DIRECTORIES = [
        __DIR__ . '/../../../../../jetbrains/phpstorm-stubs',
        __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs',
    ];
    private const CORE_EXTENSIONS    =    [
        'apache',
        'bcmath',
        'bz2',
        'calendar',
        'Core',
        'ctype',
        'curl',
        'date',
        'dba',
        'dom',
        'enchant',
        'exif',
        'FFI',
        'fileinfo',
        'filter',
        'fpm',
        'ftp',
        'gd',
        'gettext',
        'gmp',
        'hash',
        'iconv',
        'imap',
        'interbase',
        'intl',
        'json',
        'ldap',
        'libxml',
        'mbstring',
        'mcrypt',
        'mssql',
        'mysql',
        'mysqli',
        'oci8',
        'odbc',
        'openssl',
        'pcntl',
        'pcre',
        'PDO',
        'pdo_ibm',
        'pdo_mysql',
        'pdo_pgsql',
        'pdo_sqlite',
        'pgsql',
        'Phar',
        'posix',
        'pspell',
        'readline',
        'recode',
        'Reflection',
        'regex',
        'session',
        'shmop',
        'SimpleXML',
        'snmp',
        'soap',
        'sockets',
        'sodium',
        'SPL',
        'sqlite3',
        'standard',
        'sybase',
        'sysvmsg',
        'sysvsem',
        'sysvshm',
        'tidy',
        'tokenizer',
        'wddx',
        'xml',
        'xmlreader',
        'xmlrpc',
        'xmlwriter',
        'xsl',
        'Zend OPcache',
        'zip',
        'zlib',
    ];

    /**
     * @var \PhpParser\BuilderFactory
     */
    private $builderFactory;

    /**
     * @var \PhpParser\PrettyPrinter\Standard
     */
    private $prettyPrinter;

    /**
     * @var \PhpParser\NodeTraverser
     */
    private $nodeTraverser;

    /**
     * @var string|null
     */
    private $stubsDirectory;

    /**
     * @var \PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor
     */
    private $cachingVisitor;

    /**
     * `null` means "class is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\ClassLike, 1: Node\Stmt\Namespace_|null}|null>
     */
    private $classNodes = [];

    /**
     * `null` means "function is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\Function_, 1: Node\Stmt\Namespace_|null}|null>
     */
    private $functionNodes = [];

    /**
     * `null` means "failed lookup" for constant that is not case insensitive or "constant is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\Const_|Node\Expr\FuncCall, 1: Node\Stmt\Namespace_|null}|null>
     */
    private $constantNodes = [];

    /**
     * @var bool
     */
    private static $mapsInitialized = false;

    /** @var array<lowercase-string, string> */
    private static $classMap;

    /** @var array<lowercase-string, string> */
    private static $functionMap;

    /** @var array<lowercase-string, string> */
    private static $constantMap;
    /**
     * @var \PhpParser\Parser
     */
    private $phpParser;
    /**
     * @var int
     */
    private $phpVersion = PHP_VERSION_ID;

    public function __construct(Parser $phpParser, int $phpVersion = PHP_VERSION_ID)
    {
        $this->phpParser = $phpParser;
        $this->phpVersion = $phpVersion;
        $this->builderFactory = new BuilderFactory();
        $this->prettyPrinter  = new Standard(self::BUILDER_OPTIONS);

        $this->cachingVisitor = new CachingVisitor($this->builderFactory);

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->cachingVisitor);

        if (self::$mapsInitialized) {
            return;
        }

        /** @psalm-suppress PropertyTypeCoercion */
        self::$classMap = array_change_key_case(PhpStormStubsMap::CLASSES);
        /** @psalm-suppress PropertyTypeCoercion */
        self::$functionMap = array_change_key_case(PhpStormStubsMap::FUNCTIONS);
        /** @psalm-suppress PropertyTypeCoercion */
        self::$constantMap = array_merge(array_change_key_case(PhpStormStubsMap::CONSTANTS), [
            // Funny thing - The script used by Jetbrains to generate the map was originally developed in this repository - so the script throws away constants with resource value
            'stdin' => 'Core/Core_d.php',
            'stdout' => 'Core/Core_d.php',
            'stderr' => 'Core/Core_d.php',
        ]);

        self::$mapsInitialized = true;
    }

    public function hasClass(string $className): bool
    {
        $lowercaseClassName = strtolower($className);

        return array_key_exists($lowercaseClassName, self::$classMap);
    }

    public function isPresentClass(string $className): ?bool
    {
        $lowercaseClassName = strtolower($className);
        if (! array_key_exists($lowercaseClassName, self::$classMap)) {
            return null;
        }

        $filePath  = self::$classMap[$lowercaseClassName];
        $classNode = $this->findClassNode($filePath, $lowercaseClassName);

        return $classNode !== null;
    }

    /**
     * @return array{0: Node\Stmt\ClassLike, 1: Node\Stmt\Namespace_|null}|null
     */
    private function findClassNode(string $filePath, string $lowercaseName): ?array
    {
        if (! array_key_exists($lowercaseName, $this->classNodes)) {
            $this->parseFile($filePath);

            if (! array_key_exists($lowercaseName, $this->classNodes)) {
                $this->classNodes[$lowercaseName] = null;

                return null;
            }
        }

        return $this->classNodes[$lowercaseName];
    }

    public function isPresentFunction(string $functionName): ?bool
    {
        $lowercaseFunctionName = strtolower($functionName);
        if (! array_key_exists($lowercaseFunctionName, self::$functionMap)) {
            return null;
        }

        $filePath     = self::$functionMap[$lowercaseFunctionName];
        $functionNode = $this->findFunctionNode($filePath, $lowercaseFunctionName);

        return $functionNode !== null;
    }

    /**
     * @return array{0: Node\Stmt\Function_, 1: Node\Stmt\Namespace_|null}|null
     */
    private function findFunctionNode(string $filePath, string $lowercaseFunctionName): ?array
    {
        if (! array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
            $this->parseFile($filePath);

            if (! array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
                $this->functionNodes[$lowercaseFunctionName] = null;

                return null;
            }
        }

        return $this->functionNodes[$lowercaseFunctionName];
    }

    /**
     * @param class-string|trait-string $className
     */
    public function generateClassStub(string $className): ?StubData
    {
        $lowercaseClassName = strtolower($className);

        if (! array_key_exists($lowercaseClassName, self::$classMap)) {
            return null;
        }

        $filePath      = self::$classMap[$lowercaseClassName];
        $classNodeData = $this->findClassNode($filePath, $lowercaseClassName);
        if ($classNodeData === null) {
            return null;
        }

        [$classNode] = $classNodeData;
        if ($classNode instanceof Node\Stmt\Class_) {
            if (
                $classNode->name instanceof Node\Identifier
                && $classNode->name->toString() === ParseError::class
                && $this->phpVersion < 70300
            ) {
                $classNode->extends = new Node\Name\FullyQualified(Error::class);
            } elseif ($classNode->extends !== null) {
                $filteredExtends = $this->filterNames([$classNode->extends]);
                if ($filteredExtends === []) {
                    $classNode->extends = null;
                }
            }

            $classNode->implements = $this->filterNames($classNode->implements);
        } elseif ($classNode instanceof Node\Stmt\Interface_) {
            $classNode->extends = $this->filterNames($classNode->extends);
        }

        $extension = $this->getExtensionFromFilePath($filePath);
        $stub      = $this->createStub($classNodeData);

        if ($className === Traversable::class) {
            // See https://github.com/JetBrains/phpstorm-stubs/commit/0778a26992c47d7dbee4d0b0bfb7fad4344371b1#diff-575bacb45377d474336c71cbf53c1729
            $stub = str_replace(' extends \iterable', '', $stub);
        } elseif ($className === Generator::class) {
            $stub = str_replace('PS_UNRESERVE_PREFIX_throw', 'throw', $stub);
        }

        if ($className === 'PDOStatement' && $this->phpVersion < 80000) {
            $stub = str_replace('implements \IteratorAggregate', 'implements \Traversable', $stub);
        }

        if ($className === 'DatePeriod' && $this->phpVersion < 80000) {
            $stub = str_replace('implements \IteratorAggregate', 'implements \Traversable', $stub);
        }

        if ($className === 'SplFixedArray') {
            if ($this->phpVersion >= 80000) {
                $stub = str_replace('implements \Iterator, ', 'implements ', $stub);
            } else {
                $stub = str_replace(', \IteratorAggregate', '', $stub);
            }

            $stub = str_replace(', \JsonSerializable', '', $stub);
        }

        if ($className === 'SimpleXMLElement' && $this->phpVersion < 80000) {
            $stub = str_replace(', \RecursiveIterator', '', $stub);
        }

        return new StubData($stub, $extension, $this->getAbsoluteFilePath($filePath));
    }

    /**
     * @param Node\Name[] $names
     *
     * @return Node\Name[]
     */
    private function filterNames(array $names): array
    {
        $filtered = [];
        foreach ($names as $name) {
            $lowercaseName = $name->toLowerString();
            if (! array_key_exists($lowercaseName, self::$classMap)) {
                continue;
            }

            $filePath  = self::$classMap[$lowercaseName];
            $classNode = $this->findClassNode($filePath, $lowercaseName);
            if ($classNode === null) {
                continue;
            }

            $filtered[] = $name;
        }

        return $filtered;
    }

    public function generateFunctionStub(string $functionName): ?StubData
    {
        $lowercaseFunctionName = strtolower($functionName);

        if (! array_key_exists($lowercaseFunctionName, self::$functionMap)) {
            return null;
        }

        $filePath         = self::$functionMap[$lowercaseFunctionName];
        $functionNodeData = $this->findFunctionNode($filePath, $lowercaseFunctionName);
        if ($functionNodeData === null) {
            return null;
        }

        $extension = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($functionNodeData), $extension, $this->getAbsoluteFilePath($filePath));
    }

    public function generateConstantStub(string $constantName): ?StubData
    {
        $lowercaseConstantName = strtolower($constantName);

        if (! array_key_exists($lowercaseConstantName, self::$constantMap)) {
            return null;
        }

        if (
            array_key_exists($lowercaseConstantName, $this->constantNodes)
            && $this->constantNodes[$lowercaseConstantName] === null
        ) {
            return null;
        }

        $filePath         = self::$constantMap[$lowercaseConstantName];
        $constantNodeData = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

        if ($constantNodeData === null) {
            $this->parseFile($filePath);

            $constantNodeData = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

            if ($constantNodeData === null) {
                // Still `null` - the constant is not case-insensitive. Save `null` so we don't parse the file again for the same $constantName
                $this->constantNodes[$lowercaseConstantName] = null;

                return null;
            }
        }

        $extension = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($constantNodeData), $extension, $this->getAbsoluteFilePath($filePath));
    }

    private function parseFile(string $filePath): void
    {
        $absoluteFilePath = $this->getAbsoluteFilePath($filePath);
        FileChecker::assertReadableFile($absoluteFilePath);

        /** @var list<Node\Stmt> $ast */
        $ast = $this->phpParser->parse(file_get_contents($absoluteFilePath));

        // "@since" and "@removed" annotations in some cases do not contain a PHP version, but an extension version - e.g. "@since 1.3.0"
        // So we check PHP version only for stubs of core extensions
        $isCoreExtension = $this->isCoreExtension($this->getExtensionFromFilePath($filePath));

        $this->cachingVisitor->clearNodes();

        $this->nodeTraverser->traverse($ast);

        foreach ($this->cachingVisitor->getClassNodes() as $className => $classNodeData) {
            [$classNode] = $classNodeData;

            if ($isCoreExtension) {
                if ($className !== 'ReturnTypeWillChange' && ! $this->isSupportedInPhpVersion($classNode)) {
                    continue;
                }

                $classNode->stmts = $this->modifyStmtsByPhpVersion($classNode->stmts);
            }

            $this->classNodes[strtolower($className)] = $classNodeData;
        }

        foreach ($this->cachingVisitor->getFunctionNodes() as $functionName => $functionNodesData) {
            foreach ($functionNodesData as $functionNodeData) {
                [$functionNode] = $functionNodeData;

                if ($isCoreExtension) {
                    if (! $this->isSupportedInPhpVersion($functionNode)) {
                        continue;
                    }

                    $this->modifyFunctionReturnTypeByPhpVersion($functionNode);
                    $this->modifyFunctionParametersByPhpVersion($functionNode);
                }

                $lowercaseFunctionName = strtolower($functionName);

                if (array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
                    continue;
                }

                $this->functionNodes[$lowercaseFunctionName] = $functionNodeData;
            }
        }

        foreach ($this->cachingVisitor->getConstantNodes() as $constantName => $constantNodeData) {
            [$constantNode] = $constantNodeData;

            if ($isCoreExtension && ! $this->isSupportedInPhpVersion($constantNode)) {
                continue;
            }

            $this->constantNodes[$constantName] = $constantNodeData;
        }
    }

    /**
     * @param array{0: Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall, 1: Node\Stmt\Namespace_|null} $nodeData
     */
    private function createStub(array $nodeData): string
    {
        [$node, $namespaceNode] = $nodeData;

        if (! ($node instanceof Node\Expr\FuncCall)) {
            $this->addDeprecatedDocComment($node);

            $nodeWithNamespaceName = $node instanceof Node\Stmt\Const_ ? $node->consts[0] : $node;

            $namespaceBuilder = $this->builderFactory->namespace($nodeWithNamespaceName->namespacedName->slice(0, -1));

            if ($namespaceNode !== null) {
                foreach ($namespaceNode->stmts as $stmt) {
                    if (! ($stmt instanceof Node\Stmt\Use_) && ! ($stmt instanceof Node\Stmt\GroupUse)) {
                        continue;
                    }

                    $namespaceBuilder->addStmt($stmt);
                }
            }

            $namespaceBuilder->addStmt($node);

            $node = $namespaceBuilder->getNode();
        }

        return sprintf("<?php\n\n%s%s\n", $this->prettyPrinter->prettyPrint([$node]), $node instanceof Node\Expr\FuncCall ? ';' : '');
    }

    private function getExtensionFromFilePath(string $filePath): string
    {
        return explode('/', $filePath)[0];
    }

    private function getAbsoluteFilePath(string $filePath): string
    {
        return sprintf('%s/%s', $this->getStubsDirectory(), $filePath);
    }

    /**
     * @param list<Node\Stmt> $stmts
     *
     * @return list<Node\Stmt>
     */
    private function modifyStmtsByPhpVersion(array $stmts): array
    {
        $newStmts = [];
        foreach ($stmts as $stmt) {
            assert($stmt instanceof Node\Stmt\ClassConst || $stmt instanceof Node\Stmt\Property || $stmt instanceof Node\Stmt\ClassMethod);

            if (! $this->isSupportedInPhpVersion($stmt)) {
                continue;
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $this->modifyStmtTypeByPhpVersion($stmt);
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $this->modifyFunctionReturnTypeByPhpVersion($stmt);
                $this->modifyFunctionParametersByPhpVersion($stmt);
            }

            $this->addDeprecatedDocComment($stmt);

            $newStmts[] = $stmt;
        }

        return $newStmts;
    }

    /**
     * @param \PhpParser\Node\Param|\PhpParser\Node\Stmt\Property $stmt
     */
    private function modifyStmtTypeByPhpVersion($stmt): void
    {
        $type = $this->getStmtType($stmt);

        if ($type === null) {
            return;
        }

        $stmt->type = $type;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $function
     */
    private function modifyFunctionReturnTypeByPhpVersion($function): void
    {
        $isTentativeReturnType = $this->getNodeAttribute($function, 'JetBrains\PhpStorm\Internal\TentativeType') !== null;

        if ($isTentativeReturnType) {
            // Tentative types are the most correct in stubs
            // If the type is tentative in stubs, we should remove the type for PHP < 8.1

            if ($this->phpVersion >= 80100) {
                $this->addAnnotationToDocComment($function, AnnotationHelper::TENTATIVE_RETURN_TYPE_ANNOTATION);
            } else {
                $function->returnType = null;
            }

            return;
        }

        $type = $this->getStmtType($function);

        if ($type === null) {
            return;
        }

        $function->returnType = $type;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $function
     */
    private function modifyFunctionParametersByPhpVersion($function): void
    {
        $parameters = [];

        foreach ($function->getParams() as $parameterNode) {
            if (! $this->isSupportedInPhpVersion($parameterNode)) {
                continue;
            }

            $this->modifyStmtTypeByPhpVersion($parameterNode);

            $parameters[] = $parameterNode;
        }

        $function->params = $parameters;
    }

    /**
     * @param \PhpParser\Node\Param|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     * @return \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name|null
     */
    private function getStmtType($node)
    {
        $languageLevelTypeAwareAttribute = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Internal\LanguageLevelTypeAware');

        if ($languageLevelTypeAwareAttribute === null) {
            return null;
        }

        assert($languageLevelTypeAwareAttribute->args[0]->value instanceof Node\Expr\Array_);

        /** @var list<Node\Expr\ArrayItem> $types */
        $types = $languageLevelTypeAwareAttribute->args[0]->value->items;

        usort($types, static function (Node\Expr\ArrayItem $a, Node\Expr\ArrayItem $b) : int {
            return $b->key <=> $a->key;
        });

        foreach ($types as $type) {
            assert($type->key instanceof Node\Scalar\String_);
            assert($type->value instanceof Node\Scalar\String_);

            if ($this->parsePhpVersion($type->key->value) > $this->phpVersion) {
                continue;
            }

            return $this->normalizeType($type->value->value);
        }

        assert($languageLevelTypeAwareAttribute->args[1]->value instanceof Node\Scalar\String_);

        return $languageLevelTypeAwareAttribute->args[1]->value->value !== ''
            ? $this->normalizeType($languageLevelTypeAwareAttribute->args[1]->value->value)
            : null;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function addDeprecatedDocComment($node): void
    {
        if ($node instanceof Node\Stmt\Const_) {
            return;
        }

        if (! $this->isDeprecatedInPhpVersion($node)) {
            $this->removeAnnotationFromDocComment($node, 'deprecated');

            return;
        }

        $this->addAnnotationToDocComment($node, 'deprecated');
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function addAnnotationToDocComment($node, string $annotationName): void
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            $docCommentText = sprintf('/** @%s */', $annotationName);
        } else {
            $docCommentText = preg_replace('~(\r?\n\s*)\*/~', sprintf('\1* @%s\1*/', $annotationName), $docComment->getText());
        }
        $node->setDocComment(new Doc($docCommentText));
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function removeAnnotationFromDocComment($node, string $annotationName): void
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return;
        }
        $docCommentText = preg_replace('~@' . $annotationName . '.*$~m', '', $docComment->getText());
        $node->setDocComment(new Doc($docCommentText));
    }

    private function isCoreExtension(string $extension): bool
    {
        return in_array($extension, self::CORE_EXTENSIONS, true);
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function isDeprecatedInPhpVersion($node): bool
    {
        $deprecatedAttribute = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Deprecated');
        if ($deprecatedAttribute === null) {
            return false;
        }

        foreach ($deprecatedAttribute->args as $attributeArg) {
            if ($attributeArg->name !== null && $attributeArg->name->toString() === 'since') {
                assert($attributeArg->value instanceof Node\Scalar\String_);

                return $this->parsePhpVersion($attributeArg->value->value) <= $this->phpVersion;
            }
        }

        return true;
    }

    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Param|\PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function isSupportedInPhpVersion($node): bool
    {
        [$fromVersion, $toVersion] = $this->getSupportedPhpVersions($node);
        if ($fromVersion !== null && $fromVersion > $this->phpVersion) {
            return false;
        }
        return $toVersion === null || $toVersion >= $this->phpVersion;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Param|\PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function getSupportedPhpVersions($node): array
    {
        $fromVersion = null;
        $toVersion   = null;
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            if (preg_match('~@since\s+(?P<version>\d+\.\d+(?:\.\d+)?)\s+~', $docComment->getText(), $sinceMatches) === 1) {
                $fromVersion = $this->parsePhpVersion($sinceMatches['version']);
            }

            if (preg_match('~@removed\s+(?P<version>\d+\.\d+(?:\.\d+)?)\s+~', $docComment->getText(), $removedMatches) === 1) {
                $toVersion = $this->parsePhpVersion($removedMatches['version']) - 1;
            }
        }
        $elementsAvailable = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable');
        if ($elementsAvailable !== null) {
            foreach ($elementsAvailable->args as $i => $attributeArg) {
                $isFrom = false;
                if ($attributeArg->name !== null && $attributeArg->name->toString() === 'from') {
                    $isFrom = true;
                }

                if ($attributeArg->name === null && $i === 0) {
                    $isFrom = true;
                }

                if ($isFrom) {
                    assert($attributeArg->value instanceof Node\Scalar\String_);

                    $fromVersion = $this->parsePhpVersion($attributeArg->value->value);
                }

                $isTo = false;
                if ($attributeArg->name !== null && $attributeArg->name->toString() === 'to') {
                    $isTo = true;
                }

                if ($attributeArg->name === null && $i === 1) {
                    $isTo = true;
                }

                if (! $isTo) {
                    continue;
                }

                assert($attributeArg->value instanceof Node\Scalar\String_);

                $toVersion = $this->parsePhpVersion($attributeArg->value->value, 99);
            }
        }
        return [$fromVersion, $toVersion];
    }

    /**
     * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Param|\PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\ClassLike|\PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\Property $node
     */
    private function getNodeAttribute($node, string $attributeName): ?Node\Attribute
    {
        if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Stmt\Const_) {
            return null;
        }
        foreach ($node->attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                if ($attributeNode->name->toString() === $attributeName) {
                    return $attributeNode;
                }
            }
        }
        return null;
    }

    private function parsePhpVersion(string $version, int $defaultPatch = 0): int
    {
        $parts = array_map('intval', explode('.', $version));

        return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
    }

    /**
     * @return \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name|null
     */
    private function normalizeType(string $type)
    {
        // There are some invalid types in stubs, eg. `string[]|string|null`
        if (strpos($type, '[') !== false) {
            return null;
        }

        /** @psalm-suppress InternalClass, InternalMethod */
        return BuilderHelpers::normalizeType($type);
    }

    private function getStubsDirectory(): string
    {
        if ($this->stubsDirectory !== null) {
            return $this->stubsDirectory;
        }

        foreach (self::SEARCH_DIRECTORIES as $directory) {
            if (is_dir($directory)) {
                return $this->stubsDirectory = $directory;
            }
        }

        // @codeCoverageIgnoreStart
        // @infection-ignore-all
        // Untestable code
        throw CouldNotFindPhpStormStubs::create();
        // @codeCoverageIgnoreEnd
    }
}
