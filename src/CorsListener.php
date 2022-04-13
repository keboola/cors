<?php

declare(strict_types=1);

namespace Keboola\Cors;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsListener
{
    /** @var array<string> */
    private array $allowedHeaders;

    /** @param array<string> $allowedHeaders */
    public function __construct(array $allowedHeaders = [])
    {
        $this->allowedHeaders = $allowedHeaders;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMainRequest()) {
            return;
        }
        if ($event->getRequest()->getRealMethod() === 'OPTIONS') {
            /* on a CORS request immediately set the response, this completely bypasses the controller stuff, see
            https://www.upbeatproductions.com/blog/cors-pre-flight-requests-and-headers-symfony-httpkernel-component */
            $event->setResponse(new Response());
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMainRequest()) {
            return;
        }
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');

        if ($event->getRequest()->getRealMethod() === 'OPTIONS') {
            $response->headers->set('Access-Control-Max-Age', '86400');
            /* CORS has no content-type, but symfony sets it automatically
            https://github.com/symfony/http-foundation/blob/5.1/Response.php#L290
            to make the response deterministic, set the content type explicitly */
            $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
            /* no content -> no cache control needed, but it's set automatically by symfony in
            https://github.com/symfony/http-foundation/blob/5.1/ResponseHeaderBag.php#L133) */
            $response->headers->remove('Cache-Control');
            $headers = implode(', ', array_merge(['Accept', 'Content-Type'], $this->allowedHeaders));
            $response->headers->set('Access-Control-Allow-Headers', $headers);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, POST, PUT, DELETE, OPTIONS, PATCH');
        } else {
            // all other responses are not cached
            $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
        }
    }
}
