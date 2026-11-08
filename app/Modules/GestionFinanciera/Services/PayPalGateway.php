<?php

namespace App\Modules\GestionFinanciera\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Process\Process;

class PayPalGateway
{
    private const BASE = 'https://api-m.sandbox.paypal.com';

    private ?string $token = null;

    private function transport(): PendingRequest
    {
        $options = ['verify' => true];
        if (PHP_OS_FAMILY === 'Windows' && ! ini_get('curl.cainfo')) {
            // Exportar únicamente raíces públicas de confianza, sin modificar Windows.
            $bundle = base_path('.runtime/php-conf/windows-trusted-roots.pem');
            if (! is_readable($bundle)) {
                $process = new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-File', base_path('scripts/prepare-certificates.ps1')]);
                $process->setTimeout(20)->run();
            }
            if (! is_readable($bundle)) {
                throw new PasarelaException('No se pudo preparar el almacén de certificados de confianza de Windows.');
            }
            $options['verify'] = $bundle;
        } elseif (PHP_OS_FAMILY === 'Linux' && ! ini_get('curl.cainfo')) {
            // Almacén de confianza estándar de Amazon Linux / Debian.
            foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt'] as $bundle) {
                if (is_readable($bundle)) {
                    $options['verify'] = $bundle;
                    break;
                }
            }
        }

        return Http::withOptions($options)->connectTimeout(5)->timeout(20);
    }

    public function configured(): bool
    {
        return (bool) config('services.paypal.enabled') && (bool) config('services.paypal.client_id') && (bool) config('services.paypal.client_secret') && (bool) config('services.paypal.merchant_id');
    }

    public function requestId(object $p, string $operation): string
    {
        $h = hash('sha256', $p->clave_pasarela.'|'.$operation);

        return substr($h, 0, 8).'-'.substr($h, 8, 4).'-'.substr($h, 12, 4).'-'.substr($h, 16, 4).'-'.substr($h, 20, 12);
    }

    private function request(string $method, string $path, array $data = [], ?string $key = null): array
    {
        if (! $this->configured()) {
            throw new PasarelaException('Falta configuración de PayPal Sandbox.');
        }
        try {
            if ($this->token === null) {
                $r = $this->transport()->asForm()->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.client_secret'))->post(self::BASE.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
                if (! $r->successful() || ! is_string($r->json('access_token')) || $r->json('access_token') === '') {
                    throw new PasarelaException('No se pudo autenticar con PayPal Sandbox (HTTP '.$r->status().'). Revisa las credenciales Sandbox del servidor.');
                }$this->token = $r->json('access_token');
            }
            $http = $this->transport()->withToken($this->token)->acceptJson()->asJson()->withHeaders(['Prefer' => 'return=representation']);
            if ($key) {
                $http = $http->withHeaders(['PayPal-Request-Id' => $key]);
            }
            $r = $method === 'GET' ? $http->get(self::BASE.$path) : $http->withBody($data === [] ? '{}' : json_encode($data, JSON_THROW_ON_ERROR), 'application/json')->send('POST', self::BASE.$path);
            if ($r->status() === 422 && str_ends_with($path, '/capture')) {
                $issues = array_column((array) $r->json('details', []), 'issue');
                if (array_intersect($issues, ['INSTRUMENT_DECLINED', 'PAYER_CANNOT_PAY', 'PAYMENT_DENIED', 'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED'])) {
                    throw new RechazoPayPalException('PayPal rechazó el instrumento de pago.');
                }
            }
            if (! $r->successful() || ! is_array($r->json())) {
                throw new PasarelaException('PayPal no devolvió un resultado verificable (HTTP '.$r->status().'). El pago requiere verificación.');
            }

            return $r->json();
        } catch (PasarelaException $e) {
            throw $e;
        } catch (\Throwable $error) {
            // Solo categorías: nunca copiar cuerpos HTTP, tokens o credenciales.
            $reason = 'Error de comunicación con PayPal Sandbox.';
            for ($cause = $error; $cause !== null; $cause = $cause->getPrevious()) {
                if (preg_match('/cURL error (\d+)/i', $cause->getMessage(), $match)) {
                    $reason = match ((int) $match[1]) {
                        60, 77 => 'El servidor no pudo validar el certificado HTTPS de PayPal Sandbox.',
                        6 => 'El servidor no pudo resolver el dominio de PayPal Sandbox.',
                        7 => 'El servidor no pudo conectar con PayPal Sandbox.',
                        28 => 'PayPal Sandbox no respondió dentro del tiempo permitido.',
                        default => $reason,
                    };
                    break;
                }
            }
            throw new PasarelaException($reason.' El resultado requiere verificación.');
        }
    }

    private function id(string $id): string
    {
        if (! preg_match('/^[A-Za-z0-9-]{1,100}$/D', $id)) {
            throw new PasarelaException('Identificador de pasarela inválido.');
        }

        return $id;
    }

    public function create(object $p): array
    {
        return $this->request('POST', '/v2/checkout/orders', ['intent' => 'CAPTURE', 'purchase_units' => [['reference_id' => (string) $p->id, 'custom_id' => (string) $p->id, 'invoice_id' => 'VS-SB-'.$p->clave_pasarela,
            'payee' => ['merchant_id' => $p->destinatario_paypal], 'amount' => ['currency_code' => 'USD', 'value' => $p->monto_pasarela]]],
            'payment_source' => ['paypal' => ['experience_context' => ['shipping_preference' => 'NO_SHIPPING', 'user_action' => 'PAY_NOW', 'return_url' => URL::temporarySignedRoute('finance.paypal.customer.return', now()->addDays(7), ['id' => $p->id]), 'cancel_url' => URL::temporarySignedRoute('finance.paypal.customer.cancel', now()->addDays(7), ['id' => $p->id])]]]], $this->requestId($p, 'Crear'));
    }

    public function order(string $id): array
    {
        return $this->request('GET', '/v2/checkout/orders/'.$this->id($id));
    }

    public function capture(object $p, string $id): array
    {
        return $this->request('POST', '/v2/checkout/orders/'.$this->id($id).'/capture', [], $this->requestId($p, 'Capturar'));
    }

    public function captureDetails(string $id): array
    {
        return $this->request('GET', '/v2/payments/captures/'.$this->id($id));
    }

    public function refund(object $p, string $capture): array
    {
        return $this->request('POST', '/v2/payments/captures/'.$this->id($capture).'/refund', [], $this->requestId($p, 'Reembolsar'));
    }

    public function refundDetails(string $id): array
    {
        return $this->request('GET', '/v2/payments/refunds/'.$this->id($id));
    }

    public function approval(array $order): ?string
    {
        foreach ($order['links'] ?? [] as $link) {
            if (in_array($link['rel'] ?? '', ['approve', 'payer-action'], true)) {
                $url = $link['href'] ?? '';
                $parts = parse_url($url);
                if (($parts['scheme'] ?? '') !== 'https' || ! in_array(strtolower($parts['host'] ?? ''), ['www.sandbox.paypal.com', 'sandbox.paypal.com'], true) || isset($parts['user']) || isset($parts['pass'])) {
                    throw new PasarelaException('Enlace de aprobación ajeno a Sandbox.');
                }

                return $url;
            }
        }

        return null;
    }
}
