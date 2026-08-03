$sidebarStyle = @"
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 60px); }
.admin-content { flex:1; padding:24px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }
</style>
"@

# Mapeo archivo => clave activa del sidebar
$files = @{
    "resources\views\admin\audit\index.blade.php"   = "audit"
    "resources\views\admin\reports\index.blade.php" = "reports"
    "resources\views\admin\sla\index.blade.php"     = "sla"
}

foreach ($filePath in $files.Keys) {
    $activeKey = $files[$filePath]
    $content   = Get-Content $filePath -Raw -Encoding UTF8

    # Solo modificar si no tiene sidebar ya
    if ($content -notmatch "admin_sidebar") {
        $sidebarInclude = "@include('layouts.admin_sidebar', ['active' => '$activeKey'])"
        $openWrapper    = "<div class=`"admin-layout`">`n$sidebarInclude`n<div class=`"admin-content`">`n"
        $closeWrapper   = "`n</div>{{-- /admin-content --}}`n</div>{{-- /admin-layout --}}"

        # Insertar style + wrapper después de @section('content')
        $content = $content -replace "@section\('content'\)", "@section('content')`n$sidebarStyle`n<div class=`"admin-layout`">`n$sidebarInclude`n<div class=`"admin-content`">"

        # Insertar cierre antes del primer @endsection
        $content = $content -replace "@endsection", "$closeWrapper`n@endsection"

        Set-Content $filePath $content -Encoding UTF8
        Write-Host "Updated: $filePath"
    } else {
        Write-Host "Already has sidebar: $filePath"
    }
}

Write-Host "Done"
