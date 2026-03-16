<?php
declare(strict_types=1);

$fixes = [
    'GardenProductResource' => [
        'CreateGardenProduct' => 'CreateGardenProduct',
        'EditGardenProduct' => 'EditGardenProduct',
        'ViewGardenProduct' => 'ViewGardenProduct',
    ],
    'TaxiServiceResource' => [
        'CreateTaxiService' => 'CreateTaxi',
        'EditTaxiService' => 'EditTaxi',
        'ViewTaxiService' => 'ViewTaxi',
        'ListTaxiServices' => 'ListTaxiServices',
    ],
    'EducationCourseResource' => [
        'CreateEducationCourse' => 'CreateEducation',
        'EditEducationCourse' => 'EditEducation',
        'ViewEducationCourse' => 'ViewEducation',
        'ListEducationCourses' => 'ListEducationCourses',
    ],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources';

foreach ($fixes as $resourceDir => $mapping) {
    $pagesDir = "$baseDir/{$resourceDir}/Pages";
    
    if (!is_dir($pagesDir)) {
        continue;
    }
    
    foreach ($mapping as $correctName => $currentName) {
        $filePath = "$pagesDir/{$currentName}.php";
        
        if (!file_exists($filePath)) {
            continue;
        }
        
        $content = file_get_contents($filePath);
        
        // Extract resource name and determine correct class names
        preg_match('/namespace App\\\\Filament\\\\Tenant\\\\Resources\\\\([^\\\\]+)\\\\Pages/', $content, $matches);
        
        if (empty($matches[1])) {
            continue;
        }
        
        $resourceName = $matches[1];
        
        // Replace class name in file content
        $content = str_replace(
            "final class {$currentName} extends",
            "final class {$correctName} extends",
            $content
        );
        
        // Rename file if needed
        $correctFilePath = "$pagesDir/{$correctName}.php";
        
        if ($filePath !== $correctFilePath && !file_exists($correctFilePath)) {
            rename($filePath, $correctFilePath);
            echo "Renamed: {$currentName}.php -> {$correctName}.php\n";
        } else {
            file_put_contents($filePath, $content);
            echo "Updated: {$correctName}.php\n";
        }
    }
}

echo "\n✅ Pages fixed!\n";
