@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($routeNames as $routeName)
    <url><loc>{{ rtrim((string) config('app.url'), '/').route($routeName, [], false) }}</loc></url>
@endforeach
</urlset>
