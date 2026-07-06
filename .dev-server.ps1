# Local preview server (NOT part of the deployable site; do not upload)
param([int]$Port = 8123, [string]$Root = "D:\Claude\greenarc")
Add-Type -AssemblyName System.Net.HttpListener -ErrorAction SilentlyContinue
$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$Port/")
$listener.Start()
Write-Host "Serving $Root on http://localhost:$Port/"
$mime = @{ ".html"="text/html"; ".css"="text/css"; ".js"="application/javascript"; ".svg"="image/svg+xml"; ".json"="application/json"; ".xml"="application/xml"; ".jpg"="image/jpeg"; ".png"="image/png"; ".ico"="image/x-icon"; ".webmanifest"="application/manifest+json" }
while ($listener.IsListening) {
  try {
    $ctx = $listener.GetContext()
    $path = [System.Uri]::UnescapeDataString($ctx.Request.Url.AbsolutePath)
    if ($path -eq "/" ) { $path = "/index.html" }
    $file = Join-Path $Root ($path.TrimStart("/"))
    if (Test-Path $file -PathType Leaf) {
      $ext = [System.IO.Path]::GetExtension($file).ToLower()
      $ctype = $mime[$ext]; if (-not $ctype) { $ctype = "application/octet-stream" }
      $bytes = [System.IO.File]::ReadAllBytes($file)
      $ctx.Response.ContentType = $ctype
      $ctx.Response.OutputStream.Write($bytes, 0, $bytes.Length)
    } else {
      $ctx.Response.StatusCode = 404
      $nf = Join-Path $Root "404.html"
      if (Test-Path $nf) { $b=[System.IO.File]::ReadAllBytes($nf); $ctx.Response.ContentType="text/html"; $ctx.Response.OutputStream.Write($b,0,$b.Length) }
    }
    $ctx.Response.OutputStream.Close()
  } catch { }
}
