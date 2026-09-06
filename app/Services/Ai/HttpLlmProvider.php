<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Generic OpenAI-compatible chat provider (OpenAI, DeepSeek, GLM,
 * OpenRouter, Ollama, Gemini-openai-endpoint). Keys live in Settings
 * (ai.{provider}.base_url / ai.{provider}.api_key / ai.{provider}.model),
 * never in code. Strict read-only system prompt.
 */
class HttpLlmProvider implements AiProviderInterface
{
    public function __construct(protected string $provider = 'OPENAI')
    {
    }

    public function name(): string
    {
        return $this->provider;
    }

    public static function defaults(): array
    {
        return [
            'OPENAI' => ['base' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini'],
            'ANTHROPIC' => ['base' => 'https://api.anthropic.com/v1', 'model' => 'claude-3-5-haiku-latest'],
            'GEMINI' => ['base' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => 'gemini-2.0-flash'],
            'DEEPSEEK' => ['base' => 'https://api.deepseek.com/v1', 'model' => 'deepseek-chat'],
            'GLM' => ['base' => 'https://open.bigmodel.cn/api/paas/v4', 'model' => 'glm-4-flash'],
            'OPENROUTER' => ['base' => 'https://openrouter.ai/api/v1', 'model' => 'openrouter/auto'],
            'OLLAMA' => ['base' => 'http://localhost:11434/v1', 'model' => 'llama3.1'],
        ];
    }

    public static function supported(): array
    {
        return array_merge(['LOCAL'], array_keys(self::defaults()));
    }

    public function ask(string $question, string $context): array
    {
        $key = strtolower($this->provider);
        $def = self::defaults()[strtoupper($this->provider)] ?? null;
        if (!$def) {
            throw new \InvalidArgumentException('Provider AI tidak dikenal: ' . $this->provider);
        }
        $base = \App\Models\Setting::get("ai.{$key}.base_url", $def['base']);
        $model = \App\Models\Setting::get("ai.{$key}.model", $def['model']);
        $apiKey = \App\Models\Setting::get("ai.{$key}.api_key");
        if (!$apiKey && strtoupper($this->provider) !== 'OLLAMA') {
            throw new \DomainException('API key ' . $this->provider . ' belum dikonfigurasi (Settings → ai).');
        }
        $resp = Http::timeout(60)
            ->withToken($apiKey ?: 'ollama')
            ->post(rtrim($base, '/') . '/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu analis Mining ERP READ-ONLY berbahasa Indonesia. Jawab HANYA dari DATA berikut. Jangan membuat transaksi, jangan mengubah data, jangan menebak angka di luar data. Jika data tidak cukup, katakan terus terang.'],
                    ['role' => 'user', 'content' => "DATA:\n{$context}\n\nPERTANYAAN:\n{$question}"],
                ],
            ]);
        if ($resp->failed()) {
            throw new \DomainException('Provider AI merespons ' . $resp->status() . ': ' . substr($resp->body(), 0, 200));
        }
        return [
            'answer' => (string) data_get($resp->json(), 'choices.0.message.content', '(kosong)'),
            'model' => $model,
        ];
    }
}
