<?php

declare( strict_types=1 );

namespace NoOptionalParamPlugin;

use Phan\CodeBase;
use Phan\Language\Element\Func;
use Phan\Language\Element\Method;
use Phan\Language\Element\Parameter;
use Phan\PluginV3;
use Phan\PluginV3\AnalyzeFunctionCapability;
use Phan\PluginV3\AnalyzeMethodCapability;

final class NoOptionalParamPlugin extends PluginV3 implements
	AnalyzeFunctionCapability,
	AnalyzeMethodCapability
{
	private function parameterHasAnnotation( Parameter $parameter ): bool
	{
		$doc = $parameter->getDocComment();

		if ( $doc === null ) {
			return false;
		}

		return
			str_contains( $doc, '@phan-optional-param' ) ||
			str_contains( $doc, '@optional-param' );
	}

	private function checkParameters(
		CodeBase $code_base,
		Func|Method $element
	): void {

		foreach ( $element->getParameterList() as $parameter ) {

			// Skip if annotated explicitly
			if ( $this->parameterHasAnnotation( $parameter ) ) {
				continue;
			}

			if ( $parameter->isOptional() ) {
				$this->emitPluginIssue(
					$code_base,
					$element->getContext(),
					'PhanDisallowedOptionalParameter',
					'{FUNCTION} declares a disallowed optional parameter ${PARAMETER}. ' .
					'Optional parameters are prohibited unless explicitly annotated.',
					[
						$element->getName(),
						$parameter->getName()
					]
				);
			}
		}
	}

	public function analyzeFunction(
		CodeBase $code_base,
		Func $function
	): void {
		$this->checkParameters( $code_base, $function );
	}

	public function analyzeMethod(
		CodeBase $code_base,
		Method $method
	): void {

		// Skip inherited methods
		if ( $method->isOverride() ) {
			return;
		}

		$this->checkParameters( $code_base, $method );
	}

	public function getIssueSuppressionList(): array
	{
		return [
			'PhanDisallowedOptionalParameter',
		];
	}
}

return new NoOptionalParamPlugin();
