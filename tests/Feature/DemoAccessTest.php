<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoAccessMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    public function test_demo_access_is_disabled_by_default(): void
    {
        $response = $this->handleRequest();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_demo_access_challenges_guests_and_rejects_wrong_credentials(): void
    {
        config()->set('demo.access', [
            'enabled' => true,
            'username' => 'demo',
            'password' => 'safe-password',
        ]);

        $guestResponse = $this->handleRequest();
        $wrongPasswordResponse = $this->handleRequest('demo', 'wrong-password');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $guestResponse->getStatusCode());
        $this->assertSame(
            'Basic realm="LETDS Demo", charset="UTF-8"',
            $guestResponse->headers->get('WWW-Authenticate'),
        );
        $this->assertSame('noindex, nofollow, noarchive', $guestResponse->headers->get('X-Robots-Tag'));
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $wrongPasswordResponse->getStatusCode());
    }

    public function test_demo_access_accepts_valid_credentials_and_disables_indexing(): void
    {
        config()->set('demo.access', [
            'enabled' => true,
            'username' => 'demo',
            'password' => 'safe-password',
        ]);

        $response = $this->handleRequest('demo', 'safe-password');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertSame('noindex, nofollow, noarchive', $response->headers->get('X-Robots-Tag'));
    }

    public function test_demo_access_fails_closed_without_a_password(): void
    {
        config()->set('demo.access', [
            'enabled' => true,
            'username' => 'demo',
            'password' => null,
        ]);

        $response = $this->handleRequest();

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    private function handleRequest(?string $username = null, ?string $password = null): Response
    {
        $server = [];

        if ($username !== null) {
            $server['PHP_AUTH_USER'] = $username;
            $server['PHP_AUTH_PW'] = $password;
        }

        $request = Request::create('/', 'GET', server: $server);

        return app(DemoAccessMiddleware::class)->handle(
            $request,
            fn () => response('ok'),
        );
    }
}
