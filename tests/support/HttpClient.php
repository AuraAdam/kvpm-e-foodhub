<?php
declare(strict_types=1);

/**
 * Minimal browser-like HTTP client for the end-to-end tests:
 * keeps its own cookies (session), remembers the last CSRF token it saw,
 * and does NOT follow redirects so tests can assert on them.
 */
final class HttpClient
{
    private string $cookieFile;
    public string $lastBody = '';
    public int $lastStatus = 0;
    public string $lastLocation = '';
    public array $lastHeaders = [];
    private string $token = '';

    public function __construct(private string $base)
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'kvpm_cookie_');
    }

    public function __destruct()
    {
        @unlink($this->cookieFile);
    }

    public function get(string $path, array $headers = []): self
    {
        return $this->request('GET', $path, null, $headers);
    }

    /** POST a form. The CSRF token from the last page is added unless $withToken is false. */
    public function post(string $path, array $fields = [], bool $withToken = true, array $headers = []): self
    {
        if ($withToken && !array_key_exists('_token', $fields)) {
            if ($this->token === '') {
                $this->get('/login'); // any page gives a token
            }
            $fields['_token'] = $this->token;
        }
        return $this->request('POST', $path, $fields, $headers);
    }

    private function request(string $method, string $path, ?array $fields, array $headers): self
    {
        $ch = curl_init($this->base . $path);
        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseHeaders) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($k))] = trim($v);
                }
                return strlen($line);
            },
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $hasFile = false;
            foreach ($fields as $v) {
                if ($v instanceof CURLFile) {
                    $hasFile = true;
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $hasFile ? $fields : http_build_query($fields));
        }
        $this->lastBody = (string) curl_exec($ch);
        $this->lastStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->lastHeaders = $responseHeaders;
        $this->lastLocation = (string) ($responseHeaders['location'] ?? '');
        curl_close($ch);
        if (preg_match('/name="_token" value="([a-f0-9]{64})"/', $this->lastBody, $m)) {
            $this->token = $m[1];
        }
        return $this;
    }

    /** Follow one redirect (GET) and return the client. */
    public function follow(): self
    {
        if ($this->lastLocation === '') {
            return $this;
        }
        $path = parse_url($this->lastLocation, PHP_URL_PATH) . (parse_url($this->lastLocation, PHP_URL_QUERY) ? '?' . parse_url($this->lastLocation, PHP_URL_QUERY) : '');
        return $this->get($path);
    }

    public function login(string $email, string $password = 'Demo@1234'): self
    {
        $this->get('/login');
        return $this->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function sessionId(): string
    {
        foreach (file($this->cookieFile) ?: [] as $line) {
            if (str_contains($line, 'kvpm_session')) {
                return trim(substr($line, strrpos($line, "\t") + 1));
            }
        }
        return '';
    }

    public function contains(string $text): bool
    {
        return str_contains($this->lastBody, $text);
    }

    /** Flash/alert text after following a redirect. */
    public function flashText(): string
    {
        preg_match_all('#<div class="alert__body">(.*?)</div>#s', $this->lastBody, $m);
        return trim(strip_tags(implode(' | ', $m[1] ?? [])));
    }
}
