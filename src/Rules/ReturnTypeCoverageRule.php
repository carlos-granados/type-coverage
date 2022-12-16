<?php

declare(strict_types=1);

namespace TomasVotruba\TypeCoverage\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use TomasVotruba\TypeCoverage\Collectors\FunctionLike\ReturnTypeDeclarationCollector;
use TomasVotruba\TypeCoverage\Formatter\TypeCoverageFormatter;
use Symplify\RuleDocGenerator\Contract\DocumentedRuleInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \TomasVotruba\TypeCoverage\Tests\Rules\ReturnTypeDeclarationSeaLevelRule\ReturnTypeDeclarationSeaLevelRuleTest
 *
 * @implements Rule<CollectedDataNode>
 */
final class ReturnTypeCoverageRule implements Rule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Out of %d possible return types, only %d %% actually have it. Add more return types to get over %d %%';

    public function __construct(
        private TypeCoverageFormatter $seaLevelRuleErrorFormatter,
        private float $minimalLevel = 0.80,
        private bool $printSuggestions = true
    ) {
    }

    /**
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     * @return mixed[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $returnSeaLevelDataByFilePath = $node->get(ReturnTypeDeclarationCollector::class);

        $typedReturnCount = 0;
        $returnCount = 0;

        $printedClassMethods = [];

        foreach ($returnSeaLevelDataByFilePath as $returnSeaLevelData) {
            foreach ($returnSeaLevelData as $nestedReturnSeaLevelData) {
                $typedReturnCount += $nestedReturnSeaLevelData[0];
                $returnCount += $nestedReturnSeaLevelData[1];

                if (! $this->printSuggestions) {
                    continue;
                }

                /** @var string $printedClassMethod */
                $printedClassMethod = $nestedReturnSeaLevelData[2];
                if ($printedClassMethod !== '') {
                    $printedClassMethods[] = trim($printedClassMethod);
                }
            }
        }

        return $this->seaLevelRuleErrorFormatter->formatErrors(
            self::ERROR_MESSAGE,
            $this->minimalLevel,
            $returnCount,
            $typedReturnCount,
            $printedClassMethods
        );
    }
}