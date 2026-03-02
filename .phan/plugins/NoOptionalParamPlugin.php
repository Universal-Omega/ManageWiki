<?php

declare( strict_types=1 );

namespace NoOptionalParamPlugin;

use Phan\CodeBase;
use Phan\Language\Element\Func;
use Phan\Language\Element\Method;
use Phan\PluginV3;
use Phan\PluginV3\AnalyzeFunctionCapability;
use Phan\PluginV3\AnalyzeMethodCapability;

final class NoOptionalParamPlugin extends PluginV3 implements
	AnalyzeFunctionCapability,
	AnalyzeMethodCapability
{
	private function parameterHasSkipAnnotation(
		Func|Method $element,
		string $parameterName
	): bool {

		$doc = $element->getDocComment();

		if ( $doc === null ) {
			return false;
		}

		/*
		 * Find the @param line for this parameter.
		 * Then check if that line contains @phan-optional-param.
		 */
		if (
			preg_match_all(
				'/@param[^\n]*\$\s*' . preg_quote( $parameterName, '/' ) . '[^\n]*/m',
				$doc,
				$matches
			)
		) {

			foreach ( $matches[0] as $line ) {

				if (
					str_contains( $line, '@phan-optional-param' ) ||
					str_contains( $line, '@optional-param' )
				) {
					return true;
				}
			}
		}

		return false;
	}

	private function checkElement(
		CodeBase $code_base,
		Func|Method $element
	): void {

		// Skip inherited methods
		if ( $element instanceof Method && $element->isOverride() ) {
			return;
		}

		foreach ( $element->getParameterList() as $parameter ) {

			if ( ! $parameter->isOptional() ) {
				continue;
			}

			// Skip if annotation exists on the matching @param line
			if (
				$this->parameterHasSkipAnnotation(
					$element,
					$parameter->getName()
				)
			) {
				continue;
			}

			$this->emitPluginIssue(
				$code_base,
				$element->getContext(),
				'PhanDisallowedOptionalParameter',
				'{FUNCTION} declares a disallowed optional parameter ${PARAMETER}. ' .
				'Optional parameters are prohibited unless explicitly annotated with @phan-optional-param ' .
				'on the matching @param line.',
				[
					$element->getName(),
					$parameter->getName()
				]
			);
		}
	}

	public function analyzeFunction(
		CodeBase $code_base,
		Func $function
	): void {

		$this->checkElement( $code_base, $function );
	}

	public function analyzeMethod(
		CodeBase $code_base,
		Method $method
	): void {

		$this->checkElement( $code_base, $method );
	}

	public function getIssueSuppressionList(): array
	{
		return [
			'PhanDisallowedOptionalParameter',
		];
	}
}

return new NoOptionalParamPlugin();
