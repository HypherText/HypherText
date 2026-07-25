<?

use HypherText\Style\HypherTextPhpCsFixerRuleSet;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\RuleSet\RuleSets;

RuleSets::registerCustomRuleSet(new HypherTextPhpCsFixerRuleSet());

return (new Config())
    ->setRules(["@HypherText/Style" => true])
    ->setFinder((new Finder())->in(__DIR__))
    ->setCacheFile(__DIR__ . "/.php-cs-fixer.cache");
