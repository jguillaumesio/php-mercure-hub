<?php

namespace Jguillaumesio\PhpMercureHub\Utils;

/**
 * Minimal Server-Sent Events writer compliant with the W3C HTML5 spec.
 * Used by HubController::subscription() to dispatch Mercure updates.
 */
class SSE
{
    /**
     * Emit a single SSE frame to stdout. Multiline data is split and each
     * line is prefixed with "data: " per the SSE spec.
     */
    public static function emit(string $data, ?string $id = null, ?string $event = null, ?int $retry = null): void
    {
        if ($id !== null) {
            echo "id: {$id}\n";
        }
        if ($event !== null) {
            echo "event: {$event}\n";
        }
        if ($data !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $data);
            foreach ($lines as $line) {
                echo "data: {$line}\n";
            }
        }
        if ($retry !== null) {
            echo "retry: {$retry}\n";
        }
        echo "\n";
        @ob_flush();
        @flush();
    }

    /**
     * Send SSE response headers and disable server-side buffering.
     * Safe to call after headers have already been sent (no-op).
     */
    public static function initHeaders(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            // Disable reverse-proxy buffering (nginx)
            header('X-Accel-Buffering: no');
        }
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @ob_implicit_flush(true);
    }
}
