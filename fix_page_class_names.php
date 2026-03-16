<?php
declare(strict_types=1);

$fixes = [
    [
        'dir' => 'GardenProductResource/Pages',
        'file' => 'CreateGardenProduct.php',
        'oldClass' => 'CreateGarden',
        'newClass' => 'CreateGardenProduct',
    ],
    [
        'dir' => 'GardenProductResource/Pages',
        'file' => 'EditGardenProduct.php',
        'oldClass' => 'EditGarden',
        'newClass' => 'EditGardenProduct',
    ],
    [
        'dir' => 'GardenProductResource/Pages',
        'file' => 'ViewGardenProduct.php',
        'oldClass' => 'ViewGarden',
        'newClass' => 'ViewGardenProduct',
    ],
    [
        'dir' => 'TaxiServiceResource/Pages',
        'file' => 'CreateTaxiService.php',
        'oldClass' => 'CreateTaxi',
        'newClass' => 'CreateTaxiService',
    ],
    [
        'dir' => 'TaxiServiceResource/Pages',
        'file' => 'EditTaxiService.php',
        'oldClass' => 'EditTaxi',
        'newClass' => 'EditTaxiService',
    ],
    [
        'dir' => 'TaxiServiceResource/Pages',
        'file' => 'ViewTaxiService.php',
        'oldClass' => 'ViewTaxi',
        'newClass' => 'ViewTaxiService',
    ],
    [
        'dir' => 'EducationCourseResource/Pages',
        'file' => 'CreateEducationCourse.php',
        'oldClass' => 'CreateEducation',
        'newClass' => 'CreateEducationCourse',
    ],
    [
        'dir' => 'EducationCourseResource/Pages',
        'file' => 'EditEducationCourse.php',
        'oldClass' => 'EditEducation',
        'newClass' => 'EditEducationCourse',
    ],
    [
        'dir' => 'EducationCourseResource/Pages',
        'file' => 'ViewEducationCourse.php',
        'oldClass' => 'ViewEducation',
        'newClass' => 'ViewEducationCourse',
    ],
];

$baseDir = __DIR__ . '/app/Filament/Tenant/Resources/Marketplace';

foreach ($fixes as $fix) {
    $filePath = "$baseDir/{$fix['dir']}/{$fix['file']}";
    
    if (!file_exists($filePath)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    $content = str_replace(
        "final class {$fix['oldClass']} extends",
        "final class {$fix['newClass']} extends",
        $content
    );
    
    file_put_contents($filePath, $content);
    echo "✅ Fixed {$fix['file']}: {$fix['oldClass']} → {$fix['newClass']}\n";
}

echo "\n✅ All Page classes fixed!\n";
