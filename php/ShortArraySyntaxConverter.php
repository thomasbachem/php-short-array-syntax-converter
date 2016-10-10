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
 * PHP 5.4 Short Array Syntax Converter
 *
 * Command-line script to convert PHP's "array()" syntax to PHP 5.4's
 * short array syntax "[]" using PHP's built-in tokenizer.
 *
 * @link      https://github.com/thomasbachem/php-short-array-syntax-converter
 * @link      http://php.net/manual/en/language.types.array.php
 * @license   http://www.gnu.org/licenses/lgpl.html
 * @author    Thomas Bachem <mail@thomasbachem.com>
 */
class ShortArraySyntaxConverter
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

            // T_ARRAY could either mean the "array(...)" syntax we're looking for
            // or a type hinting statement ("function(array $foo) { ... }")
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_ARRAY) {
                // Look for a subsequent opening bracket ("(") to be sure we're actually
                // looking at an "array(...)" statement
                $isArraySyntax = false;
                $subOffset = $offset;
                for ($j = $i + 1; $j < count($tokens); ++$j) {
                    $subOffset += strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);

                    if (is_string($tokens[$j]) && $tokens[$j] == '(') {
                        $isArraySyntax = true;
                        break;
                    } elseif (!is_array($tokens[$j]) || $tokens[$j][0] !== T_WHITESPACE) {
                        $isArraySyntax = false;
                        break;
                    }
                }

                if ($isArraySyntax) {
                    // Replace "array" and the opening bracket (including preceeding whitespace) with "["
                    $replacements[] = array(
                        'start' => $offset - strlen($tokens[$i][1]),
                        'end' => $subOffset,
                        'string' => '[',
                    );

                    // Look for matching closing bracket (")")
                    $subOffset = $offset;
                    $openBracketsCount = 0;
                    for ($j = $i + 1; $j < count($tokens); ++$j) {
                        $subOffset += strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);

                        if (is_string($tokens[$j]) && $tokens[$j] == '(') {
                            ++$openBracketsCount;
                        } elseif (is_string($tokens[$j]) && $tokens[$j] == ')') {
                            --$openBracketsCount;

                            if ($openBracketsCount == 0) {
                                // Replace ")" with "]"
                                $replacements[] = array(
                                    'start' => $subOffset - 1,
                                    'end' => $subOffset,
                                    'string' => ']',
                                );
                                break;
                            }
                        }
                    }
                }
            }
        }


        // - - - - - UPDATE CODE - - - - -

        // Apply the replacements to the source code
        $offsetChange = 0;
        foreach ($replacements as $replacement) {
            $code = substr_replace(
                $code,
                $replacement['string'],
                $replacement['start'] + $offsetChange,
                $replacement['end'] - $replacement['start']
            );
            $offsetChange += strlen($replacement['string']) - ($replacement['end'] - $replacement['start']);
        }

        // reference
        $count = count($replacements);

        return $code;
    }
}
