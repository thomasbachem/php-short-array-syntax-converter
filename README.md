PHP 5.4 Short Array Syntax Converter
================================

Command-line script to convert PHP's `array()` syntax to PHP 5.4's short array syntax `[]` using PHP's built-in tokenizer.

By relying on the PHP tokenizer, nothing but the array syntax itself will be altered. The script was successfully tested against code bases with more than 5.000 PHP files.


Usage
================================

    Usage: ./convert.php [-w] <file>
    
Run the script with the path of the PHP file you wish to convert as argument. This will print the converted source code to STDOUT. 
    
You can add the `-w` switch if you want to override the original file with the converted code.
    
In case of any error, an error message is written to STDERR and the script exits with a return code of 1.

Use `find` to convert a whole directory recursively:

    find <directory> -name "*.php" -exec ./convert.php -w "{}" \;
    
In case you don't trust the script yet, you can even perform a syntax check after conversion:

    find <directory> -name "*.php" -exec php -l "{}" \; | grep "error:"

