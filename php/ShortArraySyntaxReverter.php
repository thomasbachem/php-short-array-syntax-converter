<?php

namespace Baguette\RefactorTool;

/*
 * This script is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License (LGPL) as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA.
 */

/**
 * PHP 5.4 Short Array Syntax Reverter
 *
 * Command-line script to convert PHP 5.4's short array "[]" syntax
 * to PHP 5.4's standard "array()" syntax using PHP's built-in tokenizer.
 *
 * @link      https://github.com/thomasbachem/php-short-array-syntax-converter
 * @link      http://php.net/manual/en/language.types.array.php
 * @license   http://www.gnu.org/licenses/lgpl.html
 * @author    Thomas Bachem <mail@thomasbachem.com>
 */
class ShortArraySyntaxReverter
{
    /**
     * @param  string $code
     * @param  int    &$count
     * @return string
     */
    public static function convert($code, &$count = null)
    {
        $tokens = token_get_all($code);

        $replacements = array();
        $offset = 0;
        for ($i = 0; $i < count($tokens); ++$i) {
            // Keep track of the current byte offset in the source code
            $offset += strlen(is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]);

            // "[" literal could either be an array index pointer
            // or an array definition
            if (is_string($tokens[$i]) && $tokens[$i] === '[') {
                // Assume we're looking at an array definition by default
                $isArraySyntax = true;
                $subOffset = $offset;
                for ($j = $i - 1; $j > 0; --$j) {
                    $subOffset -= strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);

                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        $subOffset += strlen($tokens[$j][1]);
                        continue;
                        // Look for a previous variable or function return
                        // to make sure we're not looking at an array pointer
                    } elseif (
                        (is_array($tokens[$j]) && ($tokens[$j][0] === T_VARIABLE || $tokens[$j][0] === T_STRING))
                        || in_array($tokens[$j], array(')', ']', '}'), true)
                    ) {
                        $isArraySyntax = false;
                        break;
                    } else {
                        break;
                    }
                }

                if ($isArraySyntax) {
                    // Replace "[" with "array("
                    $replacements[] = array(
                        'start'  => $offset - strlen($tokens[$i]),
                        'end'    => $offset,
                        'string' => 'array(',
                    );

                    // Look for matching closing bracket ("]")
                    $subOffset = $offset;
                    $openBracketsCount = 1;
                    for ($j = $i + 1; $j < count($tokens); ++$j) {
                        $subOffset += strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);

                        if (is_string($tokens[$j]) && $tokens[$j] == '[') {
                            ++$openBracketsCount;
                        } elseif (is_string($tokens[$j]) && $tokens[$j] == ']') {
                            --$openBracketsCount;
                            if($openBracketsCount == 0) {
                                // Replace "]" with ")"
                                $replacements[] = array(
                                    'start'  => $subOffset - 1,
                                    'end'    => $subOffset,
                                    'string' => ')',
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }


        // Apply the replacements to the source code
        $offsetChange = 0;
        foreach ($replacements as $replacement) {
            $code = substr_replace($code, $replacement['string'], $replacement['start'] + $offsetChange, $replacement['end'] - $replacement['start']);
            $offsetChange += strlen($replacement['string']) - ($replacement['end'] - $replacement['start']);
        }

        // reference
        $count = count($replacements);

        return $code;
    }
}
