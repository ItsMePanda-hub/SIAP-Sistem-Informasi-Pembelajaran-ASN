<?php

namespace Tests\Feature;

use App\Http\Middleware\ForceHttps;
use Illuminate\Http\Request;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    public function test_redirects_http_to_https_in_production(): void
    {
        $this->app['env'] = 'production';
        $request = Request::create('http://example.com/dashboard', 'GET');
        $middleware = new ForceHttps();

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function test_does_not_redirect_when_already_secure_in_production(): void
    {
        $this->app['env'] = 'production';
        $request = Request::create('https://example.com/dashboard', 'GET');
        $middleware = new ForceHttps();

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertEquals(200, $response->getContent() === 'ok' ? 200 : $response->getStatusCode());
        $this->assertEquals('ok', $response->getContent());
    }

    public function test_does_not_redirect_in_non_production(): void
    {
        $this->app['env'] = 'testing';
        $request = Request::create('http://example.com/dashboard', 'GET');
        $middleware = new ForceHttps();

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertEquals('ok', $response->getContent());
    }
}
