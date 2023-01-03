<?php
declare(strict_types=1);

use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\BooleanNot\ReplaceMultipleBooleanNotRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\InlineArrayReturnAssignRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\Do_\DoWhileBreakFalseToIfElseRector;
use Rector\CodeQuality\Rector\For_\{ForRepeatedCountToOwnVariableRector, ForToForeachRector};
use Rector\CodeQuality\Rector\Foreach_\
{ForeachItemsAssignToEmptyArrayToAssignRector,
  ForeachToInArrayRector,
  SimplifyForeachToArrayFilterRector,
  SimplifyForeachToCoalescingRector,
  UnusedForeachValueToArrayKeysRector};
use Rector\CodeQuality\Rector\FuncCall\
{AddPregQuoteDelimiterRector,
  ArrayKeysAndInArrayToArrayKeyExistsRector,
  ArrayMergeOfNonArraysToSimpleArrayRector,
  ChangeArrayPushToArrayAssignRector,
  CompactToVariablesRector,
  IntvalToTypeCastRector,
  RemoveSoleValueSprintfRector,
  SetTypeToCastRector,
  SimplifyFuncGetArgsCountRector,
  SimplifyInArrayValuesRector,
  SimplifyRegexPatternRector,
  SingleInArrayToCompareRector};
use Rector\CodeQuality\Rector\Identical\
{BooleanNotIdenticalToNotIdenticalRector,
  GetClassToInstanceOfRector,
  SimplifyArraySearchRector,
  SimplifyBoolIdenticalTrueRector,
  SimplifyConditionsRector,
  StrlenZeroToIdenticalEmptyStringRector};
use Rector\CodeQuality\Rector\If_\
{CombineIfRector,
  ConsecutiveNullCompareReturnsToNullCoalesceQueueRector,
  ExplicitBoolCompareRector,
  ShortenElseIfRector,
  SimplifyIfElseToTernaryRector,
  SimplifyIfNotNullReturnRector,
  SimplifyIfReturnBoolRector};
use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\Strict\Rector\Ternary\BooleanInTernaryOperatorRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\
{AddVoidReturnTypeWhereNoReturnRector, ReturnTypeFromReturnNewRector};
use Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;
use Rector\Visibility\Rector\ClassMethod\ExplicitPublicClassMethodRector;
use Rector\CodeQuality\Rector\FunctionLike\
{RemoveAlwaysTrueConditionSetInConstructorRector, SimplifyUselessVariableRector};
use Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector;
use Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector;
use Rector\CodeQuality\Rector\PropertyFetch\ExplicitMethodCallOverMagicGetSetRector;
use Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector;
use Rector\CodeQuality\Rector\Ternary\
{
  ArrayKeyExistsTernaryThenValueToCoalescingRector,
  SimplifyTautologyTernaryRector,
  SwitchNegatedTernaryRector,
  UnnecessaryTernaryExpressionRector
};
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
      __DIR__ . '/src'
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Code quality rules
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#absolutizerequireandincludepathrector
    $rectorConfig->rule(AbsolutizeRequireAndIncludePathRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addpregquotedelimiterrector
    $rectorConfig->rule(AddPregQuoteDelimiterRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraykeyexiststernarythenvaluetocoalescingrector
    $rectorConfig->rule(ArrayKeyExistsTernaryThenValueToCoalescingRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraykeysandinarraytoarraykeyexistsrector
    $rectorConfig->rule(ArrayKeysAndInArrayToArrayKeyExistsRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#arraymergeofnonarraystosimplearrayrector
    $rectorConfig->rule(ArrayMergeOfNonArraysToSimpleArrayRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleannotidenticaltonotidenticalrector
    $rectorConfig->rule(BooleanNotIdenticalToNotIdenticalRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#changearraypushtoarrayassignrector
    $rectorConfig->rule(ChangeArrayPushToArrayAssignRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#combineifrector
    $rectorConfig->rule(CombineIfRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#combinedassignrector
    $rectorConfig->rule(CombinedAssignRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#commonnotequalrector
    $rectorConfig->rule(CommonNotEqualRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#compacttovariablesrector
    $rectorConfig->rule(CompactToVariablesRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#consecutivenullcomparereturnstonullcoalescequeuerector
    $rectorConfig->rule(ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#dowhilebreakfalsetoifelserector
    $rectorConfig->rule(DoWhileBreakFalseToIfElseRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#explicitboolcomparerector
    $rectorConfig->rule(ExplicitBoolCompareRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#explicitmethodcallovermagicgetsetrector
    $rectorConfig->rule(ExplicitMethodCallOverMagicGetSetRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#forrepeatedcounttoownvariablerector
    $rectorConfig->rule(ForRepeatedCountToOwnVariableRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#fortoforeachrector
    $rectorConfig->rule(ForToForeachRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#foreachitemsassigntoemptyarraytoassignrector
    $rectorConfig->rule(ForeachItemsAssignToEmptyArrayToAssignRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#foreachtoinarrayrector
    $rectorConfig->rule(ForeachToInArrayRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#getclasstoinstanceofrector
    $rectorConfig->rule(GetClassToInstanceOfRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlinearrayreturnassignrector
    $rectorConfig->rule(InlineArrayReturnAssignRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#inlineconstructordefaulttopropertyrector
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#intvaltotypecastrector
    $rectorConfig->rule(IntvalToTypeCastRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#optionalparametersafterrequiredrector
    $rectorConfig->rule(OptionalParametersAfterRequiredRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removealwaystrueconditionsetinconstructorrector
    $rectorConfig->rule(RemoveAlwaysTrueConditionSetInConstructorRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#removesolevaluesprintfrector
    $rectorConfig->rule(RemoveSoleValueSprintfRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#replacemultiplebooleannotrector
    $rectorConfig->rule(ReplaceMultipleBooleanNotRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#settypetocastrector
    $rectorConfig->rule(SetTypeToCastRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#shortenelseifrector
    $rectorConfig->rule(ShortenElseIfRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyarraysearchrector
    $rectorConfig->rule(SimplifyArraySearchRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyboolidenticaltruerector
    $rectorConfig->rule(SimplifyBoolIdenticalTrueRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyconditionsrector
    $rectorConfig->rule(SimplifyConditionsRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifydemorganbinaryrector
    $rectorConfig->rule(SimplifyDeMorganBinaryRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyemptyarraycheckrector
    $rectorConfig->rule(SimplifyEmptyArrayCheckRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyforeachtoarrayfilterrector
    $rectorConfig->rule(SimplifyForeachToArrayFilterRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyforeachtocoalescingrector
    $rectorConfig->rule(SimplifyForeachToCoalescingRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyfuncgetargscountrector
    $rectorConfig->rule(SimplifyFuncGetArgsCountRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifelsetoternaryrector
    $rectorConfig->rule(SimplifyIfElseToTernaryRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifnotnullreturnrector
    $rectorConfig->rule(SimplifyIfNotNullReturnRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyifreturnboolrector
    $rectorConfig->rule(SimplifyIfReturnBoolRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyinarrayvaluesrector
    $rectorConfig->rule(SimplifyInArrayValuesRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyregexpatternrector
    $rectorConfig->rule(SimplifyRegexPatternRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifytautologyternaryrector
    $rectorConfig->rule(SimplifyTautologyTernaryRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#simplifyuselessvariablerector
    $rectorConfig->rule(SimplifyUselessVariableRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#singleinarraytocomparerector
    $rectorConfig->rule(SingleInArrayToCompareRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#singularswitchtoifrector
    $rectorConfig->rule(SingularSwitchToIfRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#strlenzerotoidenticalemptystringrector
    $rectorConfig->rule(StrlenZeroToIdenticalEmptyStringRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#switchnegatedternaryrector
    $rectorConfig->rule(SwitchNegatedTernaryRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#throwwithpreviousexceptionrector
    $rectorConfig->rule(ThrowWithPreviousExceptionRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unnecessaryternaryexpressionrector
    $rectorConfig->rule(UnnecessaryTernaryExpressionRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#unusedforeachvaluetoarraykeysrector
    $rectorConfig->rule(UnusedForeachValueToArrayKeysRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#useidenticaloverequalwithsametyperector
    $rectorConfig->rule(UseIdenticalOverEqualWithSameTypeRector::class);

    // strict rules
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#booleaninternaryoperatorrulefixerrector
    $rectorConfig->rule(BooleanInTernaryOperatorRuleFixerRector::class);

    // type rules
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addclosurereturntyperector
    $rectorConfig->rule(AddClosureReturnTypeRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#addvoidreturntypewherenoreturnrector
    $rectorConfig->rule(AddVoidReturnTypeWhereNoReturnRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#returntypefromreturnnewrector
    $rectorConfig->rule(ReturnTypeFromReturnNewRector::class);

    // Visibility
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#explicitpublicclassmethodrector
    $rectorConfig->rule(ExplicitPublicClassMethodRector::class);
    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md#typedpropertyfromstrictsetuprector
    $rectorConfig->rule(TypedPropertyFromStrictSetUpRector::class);


    // define sets of rules
    $rectorConfig->sets([
      LevelSetList::UP_TO_PHP_82,
//      SetList::CODE_QUALITY
    ]);

    // https://github.com/rectorphp/rector/blob/main/docs/how_to_ignore_rule_or_paths.md
    $rectorConfig->skip(
      [
        __DIR__ . '/cache',
        __DIR__ . '/logs',
        __DIR__ . '/node_modules',
        __DIR__ . '/OtraStandard',
        __DIR__ . '/phpdoc',
        __DIR__ . '/reports',
        __DIR__ . '/sassdoc',
        __DIR__ . '/tmp',
        __DIR__ . '/vendor',

        // Those rules on `null` values consequences are complex, can be false positives
        AddDefaultValueForUndefinedVariableRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,

        NullToStrictStringFuncCallArgRector::class,

        // Rector does not understand the way my controllers are made
        RemoveParentCallWithoutParentRector::class,
        UnionTypesRector::class
      ]
    );
};
