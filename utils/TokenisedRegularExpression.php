<?php
/**
 * Created by Nivanka Fonseka (nivanka@silverstripers.com).
 * User: nivankafonseka
 * Date: 1/22/16
 * Time: 3:47 PM
 * To change this template use File | Settings | File Templates.
 */

class TokenisedRegularExpression
{

	protected $expression;

	public function __construct($expression)
	{
		$this->expression = $expression;
	}

	public function findAll($tokens)
	{
		$tokenTypes = array();
		foreach($tokens as $i => $token) {
			if(is_array($token)) {
				$tokenTypes[$i] = $token[0];
			} else {
				$tokenTypes[$i] = $token;
				// Pre-process string tokens for matchFrom()
				$tokens[$i] = array($token, $token);
			}
		}


		$startKeys = array_keys($tokenTypes, is_array($this->expression[0])
			? $this->expression[0][0] : $this->expression[0]);
		$allMatches = array();

		foreach($startKeys as $startKey) {
			$matches = array();
			if($this->matchFrom($startKey, 0, $tokens, $matches)) {
				$allMatches[] = $matches;
			}
		}
		return $allMatches;
	}



	public function matchFrom($tokenPos, $expressionPos, &$tokens, &$matches) {
		$expressionRule = $this->expression[$expressionPos];
		$expectation = is_array($expressionRule) ? $expressionRule[0] : $expressionRule;
		if(!is_array($expressionRule)) $expressionRule = array();

		if($expectation == $tokens[$tokenPos][0]) {
			if(isset($expressionRule['save_to'])) {
				// Append to an array
				if(substr($expressionRule['save_to'],-2) == '[]') {
					$matches[substr($expressionRule['save_to'],0,-2)][] = $tokens[$tokenPos][1];
				}
				// Regular variable setting
				else $matches[$expressionRule['save_to']] = $tokens[$tokenPos][1];
			}

			// End of the expression
			if(!isset($this->expression[$expressionPos+1])) {
				return true;

				// Process next step as normal
			} else if($this->matchFrom($tokenPos+1, $expressionPos+1, $tokens, $matches)) {
				return true;

				// This step is optional
			} else if(isset($expressionRule['optional'])
				&& $this->matchFrom($tokenPos, $expressionPos+1, $tokens, $matches)) {
				return true;

				// Process jumps
			} else if(isset($expressionRule['can_jump_to'])) {
				if(is_array($expressionRule['can_jump_to'])) foreach($expressionRule['can_jump_to'] as $canJumpTo) {
					// can_jump_to & optional both set
					if(isset($expressionRule['optional'])
						&& $this->matchFrom($tokenPos, $canJumpTo, $tokens, $matches)) {
						return true;
					}
					// can_jump_to set (optional may or may not be set)
					if($this->matchFrom($tokenPos+1, $canJumpTo, $tokens, $matches)) {
						return true;
					}

				} else {
					// can_jump_to & optional both set
					if(isset($expressionRule['optional'])
						&& $this->matchFrom($tokenPos, $expressionRule['can_jump_to'], $tokens, $matches)) {
						return true;
					}
					// can_jump_to set (optional may or may not be set)
					if($this->matchFrom($tokenPos+1, $expressionRule['can_jump_to'], $tokens, $matches)) {
						return true;
					}
				}
			}

		} else if(isset($expressionRule['optional'])) {
			if(isset($this->expression[$expressionPos+1])) {
				return $this->matchFrom($tokenPos, $expressionPos+1, $tokens, $matches);
			}
			else return true;
		}

		return false;

	}

} 