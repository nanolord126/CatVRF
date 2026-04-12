<?php
/**
 * Точный сканер: static-методы, использующие $this-> внутри тела
 * Парсит каждый метод отдельно через токены PHP
 */

$dirs = ['app/Domains', 'app/Services', 'app/Http', 'app/Jobs', 'app/Listeners'];
$issues = [];

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') continue;
        $tokens = token_get_all(file_get_contents($file->getPathname()));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            // Ищем: (public|protected|private) static function
            if (!is_array($tokens[$i])) continue;
            if ($tokens[$i][0] !== T_PUBLIC && $tokens[$i][0] !== T_PROTECTED && $tokens[$i][0] !== T_PRIVATE) continue;
            // Следующий значимый токен должен быть static
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STATIC) continue;
            // Следующий — function
            $k = $j + 1;
            while ($k < $count && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) $k++;
            if (!is_array($tokens[$k]) || $tokens[$k][0] !== T_FUNCTION) continue;
            // Имя метода
            $m = $k + 1;
            while ($m < $count && is_array($tokens[$m]) && $tokens[$m][0] === T_WHITESPACE) $m++;
            $methodName = is_array($tokens[$m]) ? $tokens[$m][1] : '?';
            // Ищем открывающую { тела метода
            $n = $m + 1;
            while ($n < $count && $tokens[$n] !== '{') $n++;
            if ($n >= $count) continue;
            // Читаем тело метода до закрывающей }
            $depth = 1;
            $n++;
            $body = '';
            while ($n < $count && $depth > 0) {
                $t = $tokens[$n];
                if ($t === '{') $depth++;
                elseif ($t === '}') { $depth--; if ($depth === 0) break; }
                $body .= is_array($t) ? $t[1] : $t;
                $n++;
            }
            // Проверяем наличие $this-> в теле
            if (strpos($body, '$this->') !== false) {
                $line = is_array($tokens[$i]) ? $tokens[$i][2] : 0;
                $issues[] = $file->getPathname() . " :: L{$line} static {$methodName}()";
            }
            $i = $n;
        }
    }
}

echo "=== Static methods using \$this-> (REAL bugs) ===\n";
echo "Found: " . count($issues) . "\n\n";
foreach ($issues as $issue) {
    echo $issue . "\n";
}
