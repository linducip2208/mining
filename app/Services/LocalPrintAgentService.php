<?php

namespace App\Services;

use App\Models\PrintJob;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

final class LocalPrintAgentService
{
    public function package(PrintJob $job, string $endpoint = '/print'): array
    {
        $payload = [
            'job_uuid' => $job->uuid,
            'printer_name' => $job->printer?->device_identifier ?: $job->printer?->name,
            'printer_type' => $job->printer?->printer_type,
            'connection_type' => $job->printer?->connection_type,
            'copies' => $job->copies,
            'auto_cut' => (bool) Setting::get('printer.auto_cut', true),
            'format' => $job->payload['format'] ?? 'html',
            'content_base64' => $job->payload['content_base64'] ?? null,
            'callback_url' => route('print-jobs.agent-status', $job),
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $headers = $this->signedHeaders($body, 'POST');

        return ['url' => rtrim((string) Setting::get('printer.agent_url', 'http://127.0.0.1:17845'), '/').$endpoint, 'body' => $body, 'headers' => $headers];
    }

    public function health(): array
    {
        try {
            $response = Http::timeout(2)->get(rtrim((string) Setting::get('printer.agent_url', 'http://127.0.0.1:17845'), '/').'/health');

            return ['available' => $response->successful(), 'data' => $response->json()];
        } catch (\Throwable $exception) {
            return ['available' => false, 'error' => 'Local Print Agent belum terhubung.'];
        }
    }

    public function discoveryPackage(): array
    {
        // GET requests cannot carry a browser fetch body reliably; sign an empty body.
        $body = '';

        return ['url' => rtrim((string) Setting::get('printer.agent_url', 'http://127.0.0.1:17845'), '/').'/printers', 'body' => $body, 'headers' => $this->signedHeaders($body, 'GET')];
    }

    public function validSignature(string $body, string $method, ?string $timestamp, ?string $signature): bool
    {
        if (! $timestamp || ! $signature || abs(now()->timestamp - (int) $timestamp) > 90) {
            return false;
        }
        $token = (string) Setting::get('printer.pairing_token', '');
        if ($token === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.strtoupper($method).'.'.$body, $token);

        return hash_equals($expected, $signature);
    }

    public function signedHeaders(string $body, string $method): array
    {
        $timestamp = (string) now()->timestamp;
        $token = (string) Setting::get('printer.pairing_token', '');

        return [
            'X-Mining-Agent-Timestamp' => $timestamp,
            'X-Mining-Agent-Signature' => hash_hmac('sha256', $timestamp.'.'.strtoupper($method).'.'.$body, $token),
            'Content-Type' => 'application/json',
        ];
    }
}
