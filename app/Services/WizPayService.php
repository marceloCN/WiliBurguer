<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WizPayService
{
    private string $baseUrl;

    private string $key;

    public function __construct()
    {
        $this->baseUrl = config('services.wizpay.url', 'https://api-test.wizpay.app');
        $this->key = config('services.wizpay.key', '');

        if ($this->key === '') {
            throw new RuntimeException('WizPay: falta la clave x-wpay-key. Configura WPAY_KEY en .env.');
        }
    }

    /**
     * Crea una transacción y devuelve reference + fingerprint.
     *
     * @param  array{name: string, amount: float, id_currency: int, order_id?: string, description?: string, metadata?: array}  $data
     * @return array{reference: string, status: string, fingerprint: array{org_id: string, merchant_id: string}}
     */
    public function init(array $data): array
    {
        return $this->client()->post('/payment/init', $data)->json();
    }

    /**
     * Authentication Setup de 3D Secure: devuelve los tokens para la DDC.
     *
     * @param  array{reference: string, card: array{type: string, number: string, expiry: string, cvv: string}, fingerprint_session_id?: string}  $data
     * @return array{reference_id: string, access_token: string, device_data_collection_url: string}
     */
    public function authSetup(array $data): array
    {
        return $this->client()->post('/payment/auth-setup', $data)->json();
    }

    /**
     * Autoriza y captura el cobro. Devuelve APROBADO, RECHAZADO o PENDING_3DS.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function process(array $data): array
    {
        return $this->client()->post('/payment/process', $data)->json();
    }

    /**
     * Estado final de una transacción.
     *
     * @return array{reference: string, status: string, amount: float|int, order_id?: string, name?: string, payment_method?: string, created_at?: string}
     */
    public function status(string $reference): array
    {
        return $this->client()->get('/status/'.urlencode($reference))->json();
    }

    /**
     * Cancela una transacción pendiente.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $reference): array
    {
        return $this->client()->delete('/payment/pending/'.urlencode($reference))->json();
    }

    /**
     * Cobra con una tarjeta guardada (tokenizada).
     *
     * @return array<string, mixed>
     */
    public function chargeSaved(array $data): array
    {
        return $this->client()->post('/payment/charge-saved', $data)->json();
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'x-wpay-key' => $this->key,
            'Content-Type' => 'application/json',
        ])->timeout(30);
    }
}
