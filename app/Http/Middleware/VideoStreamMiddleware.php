<?php


namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoStreamMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (str_contains($request->path(), 'storage/stories/')) {
            if ($response instanceof BinaryFileResponse) {
                $response->headers->set('Accept-Ranges', 'bytes');
                $response->headers->set('Content-Type', 'video/mp4');
                $response->headers->set('Content-Disposition', 'inline');
            }
        }

        return $response;
    }
}