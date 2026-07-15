<?php

declare(strict_types=1);

namespace Atom\Modules\ErrorPages;

use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\RequestIdOptions;
use Atom\Di\InjectionContext;
use Atom\Logging\LoggerInterface;
use Atom\Router\MatchedRoute;
use RuntimeException;
use Throwable;
use WeakMap;

final readonly class DefaultErrorPageHandler implements ErrorPageHandlerInterface
{
    /** @var WeakMap<Throwable, bool> */
    private WeakMap $logged;

    public function __construct(
        private ErrorPagesOptions $options,
        private ?LoggerInterface $logger = null,
        private ?InjectionContext $context = null,
        private ?RequestIdOptions $requestIds = null
    ) {
        $this->logged = new WeakMap();
    }

    public function forStatus(int $status, Request $request, array $headers = []): Response
    {
        [$title, $message] = $this->description($status);

        return $this->render(new ErrorPage(
            $status,
            $title,
            $message,
            null,
            $request,
            $headers
        ));
    }

    public function forException(Throwable $exception, Request $request): Response
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->status() : 500;
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $headers = $exception instanceof HttpExceptionInterface ? $exception->headers() : [];
        [$title, $message] = $this->description($status);
        if ($exception instanceof HttpExceptionInterface && trim($exception->getMessage()) !== "") {
            $message = $exception->getMessage();
        }

        $page = new ErrorPage(
            $status,
            $title,
            $message,
            $this->errorId(),
            $request,
            $headers,
            $exception
        );

        $this->log($page);

        return $this->render($page);
    }

    private function render(ErrorPage $page): Response
    {
        $response = (new Response())
            ->status($page->status)
            ->withHeaders($page->headers)
            ->header("Cache-Control", "no-store");

        if ($this->wantsJson($page->request)) {
            return $response->json($this->json($page));
        }

        return $response
            ->header("Content-Type", "text/html; charset=utf-8")
            ->content($this->html($page));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function description(int $status): array
    {
        return match ($status) {
            400 => ["Bad request", "The request could not be understood."],
            401 => ["Authentication required", "Please sign in to continue."],
            403 => ["Access denied", "You do not have permission to view this page."],
            404 => ["Page not found", "The page you were looking for could not be found."],
            405 => ["Method not allowed", "This request method is not available for this page."],
            422 => ["Unable to process request", "The submitted information could not be processed."],
            429 => ["Too many requests", "Please wait a moment and try again."],
            503 => ["Service unavailable", "The service is temporarily unavailable."],
            default => ["Something went wrong", "An unexpected error occurred. Please try again."],
        };
    }

    private function wantsJson(Request $request): bool
    {
        $accept = strtolower($request->headers()->get("Accept", "") ?? "");

        return str_contains($accept, "application/json") || str_contains($accept, "+json");
    }

    /**
     * @return array{error: array<string, mixed>}
     */
    private function json(ErrorPage $page): array
    {
        $error = [
            "status" => $page->status,
            "title" => $page->title,
            "message" => $page->message,
        ];

        if ($page->id !== null) {
            $error["id"] = $page->id;
        }

        if ($this->options->debug) {
            $error["debug"] = $this->diagnostics($page);
        }

        return ["error" => $error];
    }

    private function html(ErrorPage $page): string
    {
        return $this->renderPhp(__DIR__ . "/Views/error.php", [
            "page" => $page,
            "debug" => $this->options->debug,
            "diagnostics" => $this->options->debug ? $this->diagnostics($page) : [],
        ]);
    }

    /**
     * @return array<string, int|string>
     */
    private function diagnostics(ErrorPage $page): array
    {
        $diagnostics = [
            "method" => $page->request->getMethod(),
            "path" => $page->request->getPath(),
        ];

        $allow = $page->headers["Allow"] ?? $page->headers["allow"] ?? null;
        if ($allow !== null) {
            $diagnostics["allowed methods"] = $allow;
        }

        if ($page->exception !== null) {
            $diagnostics["exception"] = $page->exception::class;
            $diagnostics["exception message"] = $page->exception->getMessage();
            $diagnostics["file"] = $page->exception->getFile();
            $diagnostics["line"] = $page->exception->getLine();
            $diagnostics["trace"] = $page->exception->getTraceAsString();
        }

        return $diagnostics;
    }

    private function log(ErrorPage $page): void
    {
        if ($this->logger === null || $page->exception === null || isset($this->logged[$page->exception])) {
            return;
        }
        $this->logged[$page->exception] = true;

        try {
            $context = [
                "error_id" => $page->id,
                "method" => $page->request->getMethod(),
                "path" => $page->request->getPath(),
                "exception" => $page->exception,
            ];

            $requestIdHeader = $this->requestIds === null
                ? "X-Request-Id"
                : $this->requestIds->headerName;
            $requestId = $page->request->headers()->get($requestIdHeader, "") ?? "";
            if ($requestId !== "") {
                $context["request_id"] = $requestId;
            }
            if ($page->request->getClientIp() !== "") {
                $context["client_ip"] = $page->request->getClientIp();
            }
            if ($page->exception->getPrevious() !== null) {
                $context["previous_exception"] = $page->exception->getPrevious();
            }

            $route = $this->context?->get(MatchedRoute::class);
            if ($route instanceof MatchedRoute) {
                $entry = $route->getRouteEntry();
                $context["route_path"] = $entry->getFullPath();
                $context["route_methods"] = $entry->getMethodList();
                if ($entry->getName() !== null) {
                    $context["route_name"] = $entry->getName();
                }
                if ($entry->getController() !== null) {
                    $context["controller"] = $entry->getController();
                }
                if ($entry->getMethodName() !== null) {
                    $context["controller_method"] = $entry->getMethodName();
                }
            }

            $this->logger->error("Unhandled exception", $context);
        } catch (Throwable) {
        }
    }

    private function errorId(): string
    {
        try {
            return "err_" . bin2hex(random_bytes(6));
        } catch (Throwable) {
            return "err_" . str_replace(".", "", uniqid("", true));
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderPhp(string $path, array $variables): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("Error page template '{$path}' was not found.");
        }

        ob_start();

        try {
            (static function (string $__path, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                require $__path;
            })($path, $variables);

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
