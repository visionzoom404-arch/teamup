<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach($urls as $u)
  <url><loc>{{ $u['loc'] }}</loc>@if($u['mod'])<lastmod>{{ $u['mod'] }}</lastmod>@endif<changefreq>{{ $u['freq'] }}</changefreq><priority>{{ $u['pri'] }}</priority>@foreach($u['imgs'] as $im)<image:image><image:loc>{{ $im['loc'] }}</image:loc><image:title>{{ $im['title'] }}</image:title></image:image>@endforeach</url>
@endforeach
</urlset>
