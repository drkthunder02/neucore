<?php

declare(strict_types=1);

namespace Tests\Functional;

use Neucore\Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tests\Helper;
use Tests\RequestFactory;

/**
 * Runs the application.
 */
class WebTestCase extends TestCase
{
    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param string|array|object|null $requestData the request data
     * @param array|null $headers
     * @param array $mocks key/value paris for the dependency injection container
     * @param string[] $envVars var=value
     * @return ResponseInterface|null
     */
    protected function runApp(
        $requestMethod,
        $requestUri,
        $requestData = null,
        array $headers = null,
        array $mocks = [],
        array $envVars = []
    ): ?ResponseInterface {
        // Set up a request object
        $request = RequestFactory::createRequest($requestMethod, $requestUri);

        // Add request data, if it exists
        if ($requestData !== null) {
            $contentType = $headers['Content-Type'] ?? null;
            if ($contentType === 'application/x-www-form-urlencoded' && ! is_string($requestData)) {
                if ($requestMethod === 'POST') {
                    $request = $request->withParsedBody($requestData);
                } else { // PUT
                    $body = $request->getBody();
                    $body->write(http_build_query($requestData));
                    $body->rewind();
                    $request = $request->withBody($body);
                }
            } elseif ($contentType === 'application/json') { // POST with Content-Type: application/json
                $body = $request->getBody();
                $body->write((string) \json_encode($requestData));
                $body->rewind();
                $request = $request->withBody($body);
            } elseif (is_string($requestData))  { // text/plain
                $body = $request->getBody();
                $body->write($requestData);
                $body->rewind();
                $request = $request->withBody($body);
            }
        }

        // add header
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        // create app with test settings
        $app = new Application();
        $app->loadSettings(true);

        foreach ($envVars as $envVar) {
            putenv($envVar);
        }

        // Add existing db connection
        $mocks = (new Helper)->addEm($mocks);

        // Process the application
        try {
            $response = $app->getApp($mocks)->handle($request);
        } catch (\Throwable $e) {
            echo $e->getMessage();
            return null;
        }

        // Return the response
        return $response;
    }

    /**
     * @return mixed
     */
    protected function parseJsonBody(?ResponseInterface $response, bool $assoc = true)
    {
        if (! $response) {
            return '';
        }

        $json = $response->getBody()->__toString();

        return json_decode($json, $assoc);
    }

    protected function loginUser($id)
    {
        $_SESSION['character_id'] = $id;
    }
}
