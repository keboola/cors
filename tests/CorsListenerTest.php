<?php

declare(strict_types=1);

namespace Keboola\Cors\Tests;

use Keboola\Cors\CorsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorsListenerTest extends TestCase
{
    private function getKernel(): HttpKernel
    {
        /** @var HttpKernel $kernel */
        $kernel = $this->getMockBuilder(HttpKernel::class)->disableOriginalConstructor()->getMock();
        return $kernel;
    }

    public function testListenerOptions(): void
    {
        $request = new Request();
        $request->setMethod('OPTIONS');
        $event = new RequestEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
        $listener = new CorsListener();
        $listener->onKernelRequest($event);
        self::assertNotNull($event->getResponse());
    }

    public function testListenerSubRequest(): void
    {
        $request = new Request();
        $request->setMethod('OPTIONS');
        $event = new RequestEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $listener = new CorsListener();
        $listener->onKernelRequest($event);
        self::assertNull($event->getResponse());
    }

    public function testListenerNoOptions(): void
    {
        $request = new Request();
        $request->setMethod('GET');
        $event = new RequestEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
        $listener = new CorsListener();
        $listener->onKernelRequest($event);
        self::assertNull($event->getResponse());
    }

    public function testListenerOptionsResponse(): void
    {
        $request = new Request();
        $response = new Response();
        $request->setMethod('OPTIONS');
        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
        $listener = new CorsListener(['X-StorageApi-Token']);
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(
            ['access-control-allow-headers', 'access-control-allow-methods', 'access-control-allow-origin',
                'access-control-max-age', 'cache-control', 'content-type', 'date'],
            $headerNames
        );
        self::assertEquals('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals(
            'GET, HEAD, POST, PUT, DELETE, OPTIONS, PATCH',
            $event->getResponse()->headers->get('Access-Control-Allow-Methods')
        );
        self::assertEquals(
            'Accept, Content-Type, X-StorageApi-Token',
            $event->getResponse()->headers->get('Access-Control-Allow-Headers')
        );
        self::assertEquals('86400', $event->getResponse()->headers->get('Access-Control-Max-Age'));
        self::assertEquals('text/html; charset=UTF-8', $event->getResponse()->headers->get('Content-Type'));
    }

    public function testListenerGet(): void
    {
        $request = new Request();
        $response = new Response('some-text');
        $request->setMethod('GET');
        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
        $listener = new CorsListener();
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(
            ['access-control-allow-origin', 'cache-control', 'date'],
            $headerNames
        );
        self::assertEquals('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testListenerSubRequestResponse(): void
    {
        $request = new Request();
        $response = new Response('some-text');
        $request->setMethod('GET');
        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );
        $listener = new CorsListener();
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(['cache-control', 'date'], $headerNames);
    }

    public function testCacheControlIsNotOverwritten(): void
    {
        $request = new Request();
        $response = new Response();
        $response->setCache([
            'max_age' => 60,
            'private' => true,
        ]);

        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new CorsListener(['X-StorageApi-Token']);
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());

        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(
            ['access-control-allow-origin', 'cache-control', 'date'],
            $headerNames
        );
        self::assertEquals('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('max-age=60, private', $event->getResponse()->headers->get('Cache-Control'));
    }
}
