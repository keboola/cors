<?php

declare(strict_types=1);

namespace Keboola\Cors\Tests;

use Keboola\Cors\ResponseHeadersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseHeadersListenerTest extends TestCase
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
        $response = new Response();
        $request->setMethod('OPTIONS');
        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
        $listener = new ResponseHeadersListener();
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(
            ['access-control-allow-headers', 'access-control-allow-methods', 'access-control-allow-origin',
                'access-control-max-age', 'content-type', 'date'],
            $headerNames,
        );
        self::assertEquals('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals(
            'GET, HEAD, POST, PUT, DELETE, OPTIONS, PATCH',
            $event->getResponse()->headers->get('Access-Control-Allow-Methods'),
        );
        self::assertEquals(
            'Content-Type, Accept',
            $event->getResponse()->headers->get('Access-Control-Allow-Headers'),
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
            $response,
        );
        $listener = new ResponseHeadersListener();
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(
            ['access-control-allow-origin', 'cache-control', 'date'],
            $headerNames,
        );
        self::assertEquals('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testListenerSubRequest(): void
    {
        $request = new Request();
        $response = new Response('some-text');
        $request->setMethod('GET');
        $event = new ResponseEvent(
            $this->getKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response,
        );
        $listener = new ResponseHeadersListener();
        $listener->onKernelResponse($event);
        self::assertNotNull($event->getResponse());
        $headerNames = $event->getResponse()->headers->keys();
        sort($headerNames);
        self::assertEquals(['cache-control', 'date'], $headerNames);
    }
}
