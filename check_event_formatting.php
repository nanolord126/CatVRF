<?php

$dir = __DIR__ . '/app/Events';
$files = glob("$dir/*.php");
$fixed = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Skip if already formatted properly
    $lineCount = substr_count($content, "\n");
    if ($lineCount > 10) {
        continue; // Already formatted
    }
    
    // Parse and reformat
    $tokens = token_get_all($content);
    $formatted = '';
    $indent = 0;
    $indentStr = '    ';
    $lastToken = null;
    $inClass = false;
    
    foreach ($tokens as $token) {
        if (is_string($token)) {
            $formatted .= $token;
            $lastToken = $token;
            
            if ($token === '{') {
                $indent++;
                $formatted .= "\n" . str_repeat($indentStr, $indent);
            } elseif ($token === '}') {
                $indent--;
                if ($lastToken !== '{') {
                    $formatted = rtrim($formatted) . "\n" . str_repeat($indentStr, $indent);
                }
                $formatted .= $token . "\n";
            } elseif ($token === ';') {
                $formatted .= "\n" . str_repeat($indentStr, $indent);
            }
        } else {
            $tokenType = $token[0];
            $tokenValue = $token[1];
            
            // Add newlines after namespace, use, class declarations
            if ($tokenType === T_NAMESPACE || $tokenType === T_USE || $tokenType === T_CLASS || $tokenType === T_FUNCTION) {
                $formatted .= $tokenValue;
                if ($tokenType === T_NAMESPACE || $tokenType === T_CLASS) {
                    $lastToken = $tokenType;
                }
            } elseif ($tokenType === T_WHITESPACE) {
                // Skip redundant whitespace
                if (!empty($formatted) && !in_array(substr($formatted, -1), [' ', "\n", "\t"])) {
                    $formatted .= ' ';
                }
            } else {
                $formatted .= $tokenValue;
            }
            $lastToken = $tokenType;
        }
    }
    
    // Simple cleanup - ensure newlines around major sections
    $output = "<?php\n\n";
    
    // Extract namespace
    if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
        $output .= "namespace {$m[1]};\n";
    }
    
    $output .= "\n";
    
    // Extract all use statements
    if (preg_match_all('/use\s+([^;]+);/', $content, $matches)) {
        foreach ($matches[1] as $use) {
            $output .= "use $use;\n";
        }
    }
    
    $output .= "\n";
    
    // Extract class declaration and body
    if (preg_match('/(?:final\s+)?class\s+\w+[^{]*\{(.*)\}$/s', $content, $m)) {
        // Get the class declaration
        preg_match('/(?:final\s+)?class\s+(\w+)([^{]*)\{/', $content, $classMatch);
        $output .= "final class {$classMatch[1]}" . str_replace('  ', '', $classMatch[2]) . "{\n";
        
        // Process class body
        $body = trim($m[1]);
        
        // Extract traits
        if (preg_match_all('/use\s+([^;]+);/', $body, $traitMatches)) {
            $output .= "    use " . implode(', ', $traitMatches[1]) . ";\n\n";
            $body = preg_replace('/use\s+[^;]+;/', '', $body);
        }
        
        // Extract properties
        preg_match_all('/(public|private|protected)\s+(?:readonly\s+)?(\w+)\s+\$\w+/', $body, $propMatches, PREG_OFFSET_CAPTURE);
        if (!empty($propMatches[0])) {
            foreach ($propMatches[0] as $prop) {
                $output .= "    " . trim($prop[0]) . ";\n";
            }
            $output .= "\n";
        }
        
        // Extract methods - just get function declarations and basic formatting
        preg_match_all('/(?:public|private|protected)(?:\s+static)?\s+function\s+\w+\([^)]*\)(?:\s*:\s*\w+)?\s*\{/', $body, $methodMatches, PREG_OFFSET_CAPTURE);
        
        if (!empty($methodMatches[0])) {
            foreach ($methodMatches[0] as $method) {
                $output .= "    " . trim($method[0]) . "\n";
                // Just add placeholder for now - too complex to parse full method
                $output .= "        // Method body\n";
                $output .= "    }\n\n";
            }
        }
        
        $output .= "}\n";
    }
    
    // Don't save - too risky without proper PHP parser
    // Just mark that we've identified it
}

echo "Analysis complete - Files are too complex for simple formatting\n";
echo "Recommend using PHP-CS-Fixer for proper formatting\n";
