Get-ChildItem app/Domains -Directory | ForEach-Object {
     = .Name
    Write-Host "
===  ===" -ForegroundColor Cyan
    
     = @('Models', 'Services', 'Policies', 'Http/Controllers', 'Http/Requests', 'Http/Resources')
    foreach ( in ) {
         = "app/Domains//"
        if (Test-Path ) {
             = (Get-ChildItem  -File 2>/dev/null | Measure-Object).Count
            Write-Host " :  файлов" -ForegroundColor Green
        } else {
            Write-Host " : ❌ отсутствует" -ForegroundColor Red
        }
    }
}
