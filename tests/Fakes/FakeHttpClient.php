<?php

namespace Tests\Fakes;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * PSR-18 client that records the requests and answers them with queued
 * responses, so the Resend and Mailgun SDKs never reach the network.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    private array $requests = [];

    /** @var list<ResponseInterface> */
    private array $responses = [];

    public function respondWith(int $status = 200, array $json = []): self
    {
        $this->responses[] = new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($json, JSON_THROW_ON_ERROR)
        );

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request->getBody()->rewind();
        $this->requests[] = $request;

        return array_shift($this->responses)
            ?? throw new RuntimeException('No fake response queued for '.$request->getMethod().' '.$request->getUri());
    }

    /** @return list<RequestInterface> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function lastRequest(): ?RequestInterface
    {
        return $this->requests[array_key_last($this->requests) ?? 0] ?? null;
    }

    public function json(?RequestInterface $request = null): array
    {
        $request ??= $this->lastRequest();

        return json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Parses a multipart/form-data body into name => list of values,
     * with files as ['filename' => ..., 'content' => ...]
     *
     * @return array<string, list<string|array{filename: string, content: string}>>
     */
    public function multipart(?RequestInterface $request = null): array
    {
        $request ??= $this->lastRequest();

        preg_match('/boundary="?([^";]+)"?/', $request->getHeaderLine('Content-Type'), $matches);
        $parts = explode('--'.$matches[1], (string) $request->getBody());
        $fields = [];

        foreach ($parts as $part) {
            if (! str_contains($part, "\r\n\r\n")) {
                continue;
            }

            [$headers, $content] = explode("\r\n\r\n", $part, 2);
            $content = substr($content, 0, -2);

            if (! preg_match('/name="([^"]+)"/', $headers, $name)) {
                continue;
            }

            $fields[$name[1]][] = preg_match('/filename="([^"]*)"/', $headers, $filename)
                ? ['filename' => $filename[1], 'content' => $content]
                : $content;
        }

        return $fields;
    }
}
