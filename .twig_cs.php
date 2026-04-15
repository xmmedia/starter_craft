<?php

declare(strict_types=1);

use FriendsOfTwig\Twigcs\Config\Config;
use FriendsOfTwig\Twigcs\Finder\TemplateFinder;
use FriendsOfTwig\Twigcs\RegEngine\RulesetBuilder;
use FriendsOfTwig\Twigcs\RegEngine\RulesetConfigurator;
use FriendsOfTwig\Twigcs\Rule;
use FriendsOfTwig\Twigcs\Ruleset\RulesetInterface;
use FriendsOfTwig\Twigcs\TemplateResolver\FileResolver;
use FriendsOfTwig\Twigcs\Validator\Violation;

/* Example of a ruleset that disables the RegEngine ruleset for a specific template
readonly class NoRegEngineRuleset implements RulesetInterface
{
    public function __construct(private int $twigMajorVersion) {}

    public function getRules(): array
    {
        return [
            new Rule\TrailingSpace(Violation::SEVERITY_ERROR),
            new Rule\UnusedMacro(Violation::SEVERITY_WARNING, new FileResolver(__DIR__ . '/templates')),
            new Rule\UnusedVariable(Violation::SEVERITY_WARNING, new FileResolver(__DIR__ . '/templates')),
        ];
    }
}*/

readonly class StarterCraftRuleset implements RulesetInterface
{
    public function __construct(private int $twigMajorVersion) {}

    public function getRules(): array
    {
        $configurator = new RulesetConfigurator();
        $configurator->setTwigMajorVersion($this->twigMajorVersion);
        $configurator->setPropertySpacingPattern('expr.expr | filter');
        $configurator->setHashSpacingPattern('{ key: expr, key: expr }');
        $builder = new RulesetBuilder($configurator);

        return [
            new Rule\RegEngineRule(Violation::SEVERITY_ERROR, $builder->build()),
            new Rule\TrailingSpace(Violation::SEVERITY_ERROR),
            new Rule\UnusedMacro(Violation::SEVERITY_WARNING, new FileResolver(__DIR__ . '/templates')),
            new Rule\UnusedVariable(Violation::SEVERITY_WARNING, new FileResolver(__DIR__ . '/templates')),
        ];
    }
}

$finder = TemplateFinder::create()->in(__DIR__ . '/templates');

return Config::create()
    ->setName('starter_craft')
    ->setSeverity('error')
    ->setRuleset(StarterCraftRuleset::class)
    // ->setSpecificRulesets([
    //     __DIR__ . '/templates/_layout.twig' => NoRegEngineRuleset::class,
    // ])
    ->addFinder($finder);
