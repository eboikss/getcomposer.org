<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!isset($env) || $env !== 'dev') {
    // force ssl
    $app->before(function (Request $request) {
        // skip SSL & non-GET/HEAD requests
        if (strtolower($request->server->get('HTTPS')) == 'on' || strtolower($request->headers->get('X_FORWARDED_PROTO')) == 'https' || !$request->isMethodSafe()) {
            return;
        }

        return new RedirectResponse('https://'.substr($request->getUri(), 7));
    });

    $app->after(function (Request $request, Response $response) {
        // Security headers
        if (!$response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31104000');
        }
        
        // Performance and caching headers
        if ($request->getPathInfo() === '/' || strpos($request->getPathInfo(), '/css/') === 0 || strpos($request->getPathInfo(), '/js/') === 0 || strpos($request->getPathInfo(), '/img/') === 0) {
            // Cache static assets for 1 year
            if (strpos($request->getPathInfo(), '/css/') === 0 || strpos($request->getPathInfo(), '/js/') === 0 || strpos($request->getPathInfo(), '/img/') === 0) {
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            } else {
                // Cache homepage for 1 hour
                $response->headers->set('Cache-Control', 'public, max-age=3600');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
            }
        }
        
        // Compression
        if (function_exists('gzencode') && 
            strpos($request->headers->get('Accept-Encoding'), 'gzip') !== false &&
            !$response->headers->has('Content-Encoding')) {
            
            $contentType = $response->headers->get('Content-Type', '');
            if (strpos($contentType, 'text/') === 0 || 
                strpos($contentType, 'application/json') === 0 || 
                strpos($contentType, 'application/javascript') === 0) {
                
                $content = $response->getContent();
                if (strlen($content) > 1024) { // Only compress if larger than 1KB
                    $response->setContent(gzencode($content, 6));
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Vary', 'Accept-Encoding');
                }
            }
        }
        
        // Additional security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // CSP for external scripts
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://www.google-analytics.com";
        $response->headers->set('Content-Security-Policy', $csp);
    });
}

// Enable OpCache optimizations if available
if (function_exists('opcache_get_status') && opcache_get_status() !== false) {
    ini_set('opcache.enable', 1);
    ini_set('opcache.enable_cli', 1);
    ini_set('opcache.memory_consumption', 128);
    ini_set('opcache.interned_strings_buffer', 8);
    ini_set('opcache.max_accelerated_files', 4000);
    ini_set('opcache.revalidate_freq', 60);
    ini_set('opcache.fast_shutdown', 1);
}