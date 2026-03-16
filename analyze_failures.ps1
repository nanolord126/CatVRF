# Analyze Audit Failures by Category
# Processes audit_results.txt and categorizes incomplete files

$resultsFile = "audit_results.txt"
$categorizedFile = "FAILURES_BY_CATEGORY.txt"

if (-not (Test-Path $resultsFile)) {
    Write-Host "Error: $resultsFile not found" -ForegroundColor Red
    exit 1
}

$content = Get-Content $resultsFile
$categories = @{
    "Models" = @()
    "Policies" = @()
    "Controllers" = @()
    "Resources/Pages" = @()
    "Jobs" = @()
    "Services" = @()
    "Seeders" = @()
    "Migrations" = @()
    "Vue Components" = @()
    "Observers" = @()
    "Middleware" = @()
    "Other" = @()
}

# Parse each line
foreach ($line in $content) {
    if ($line -match '(\w+\.php|[\w\-]+\.vue)\s+-\s+(\d+)/60\s+lines') {
        $filename = $matches[1]
        $linecount = [int]$matches[2]
        
        # Skip warning and summary lines
        if ($line -match 'WARNING|files are incomplete|must be completed') {
            continue
        }
        
        # Categorize
        if ($filename -match '.*Policy\.php$') {
            $categories["Policies"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Controller\.php$') {
            $categories["Controllers"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match '.*Resource.*Pages.*\.php$' -or $filename -match '.*Pages.*\.php$') {
            $categories["Resources/Pages"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Job\.php$') {
            $categories["Jobs"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Service\.php$') {
            $categories["Services"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Seeder\.php$') {
            $categories["Seeders"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'migration' -or $filename -match '\d{4}_\d{2}_\d{2}') {
            $categories["Migrations"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match '\.vue$') {
            $categories["Vue Components"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Observer\.php$') {
            $categories["Observers"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Middleware\.php$') {
            $categories["Middleware"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        elseif ($filename -match 'Models.*\.php$') {
            $categories["Models"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
        else {
            $categories["Other"] += @{File=$filename; Lines=$linecount; Path=$line}
        }
    }
}

# Generate report
$report = @"
# FAILURES BY CATEGORY ANALYSIS
# Generated: $(Get-Date)
# Source: audit_results.txt

"@

$totalFails = 0
foreach ($cat in $categories.GetEnumerator() | Sort-Object -Property Name) {
    $count = $cat.Value.Count
    if ($count -gt 0) {
        $totalFails += $count
        $avgLines = ($cat.Value | Measure-Object -Property Lines -Average).Average
        
        $report += @"
## $($cat.Key) - $count files (avg: $([math]::Round($avgLines, 1)) lines)

"@
        foreach ($item in $cat.Value | Sort-Object -Property Lines) {
            $report += "- {0} ({1}/60 lines)`n" -f $item.File, $item.Lines
        }
        $report += "`n"
    }
}

$report += @"
---
## SUMMARY

Total Files with Failures: $totalFails

| Category | Count |
|---|---|
"@

foreach ($cat in $categories.GetEnumerator() | Sort-Object -Property {$_.Value.Count} -Descending) {
    if ($cat.Value.Count -gt 0) {
        $report += "| $($cat.Key) | $($cat.Value.Count) |`n"
    }
}

# Save report
$report | Out-File -FilePath $categorizedFile -Encoding UTF8
Write-Host "✅ Categorized analysis saved to: $categorizedFile" -ForegroundColor Green
Write-Host "Total incomplete files: $totalFails" -ForegroundColor Yellow
