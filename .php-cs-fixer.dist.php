<? declare(strict_types=1);

use HypherText\Style\HypherTextPhpCsFixerRuleSet;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\RuleSet\RuleSets;

RuleSets::registerCustomRuleSet(new HypherTextPhpCsFixerRuleSet());

return new Config()
    ->setRiskyAllowed(true)
    ->setRules(["@HypherText/Style" => true, "phpdoc_to_comment" => false, "increment_style" => ["style" => "post"]])
    ->setFinder(new Finder()->in(__DIR__))
    ->setCacheFile(__DIR__."/.php-cs-fixer.cache")
;
