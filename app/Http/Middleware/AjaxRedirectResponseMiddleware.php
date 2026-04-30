<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

class AjaxRedirectResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (!$request->expectsJson() || !$response instanceof RedirectResponse) {
            return $response;
        }

        $message = $this->messageFromSession($request);
        $type = $this->typeFromSession($request);
        $status = $type === 'error' ? 422 : 200;

        return new JsonResponse([
            'ok' => $type !== 'error',
            'type' => $type,
            'message' => $message,
            'redirect_url' => $response->getTargetUrl(),
        ], $status);
    }

    private function typeFromSession(Request $request): string
    {
        if ($request->session()->has('error') || $request->session()->has('errors')) {
            return 'error';
        }

        if ($request->session()->has('status')) {
            return 'info';
        }

        return 'success';
    }

    private function messageFromSession(Request $request): string
    {
        foreach (['success', 'ok', 'status', 'message'] as $key) {
            $message = trim((string) $request->session()->get($key, ''));

            if ($message !== '') {
                return $message;
            }
        }

        $error = trim((string) $request->session()->get('error', ''));

        if ($error !== '') {
            return $error;
        }

        $errors = $request->session()->get('errors');

        if ($errors && method_exists($errors, 'getBag')) {
            $bag = $errors->getBag('default');

            if ($bag instanceof MessageBag && $bag->isNotEmpty()) {
                return (string) $bag->first();
            }
        }

        return $this->typeFromSession($request) === 'error'
            ? 'İşlem tamamlanamadı. Lütfen bilgileri kontrol edin.'
            : 'İşlem başarıyla tamamlandı.';
    }
}
