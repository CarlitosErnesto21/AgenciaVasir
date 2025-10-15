<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WompiService
{
    protected $client;
    protected $publicKey;
    protected $privateKey;
    protected $baseUrl;
    protected $sandbox;

    protected $clientId;
    protected $clientSecret;
    protected $authUrl;
    protected $audience;
    protected $accessToken;

    public function __construct()
    {
        // Configuración de cliente HTTP con soporte para desarrollo local
        $clientConfig = [];

        // En desarrollo/sandbox, deshabilitar verificación SSL
        if (config('app.env') !== 'production') {
            $clientConfig['verify'] = false;
        }

        $this->client = new Client($clientConfig);

        // Configuración para Wompi El Salvador (OAuth 2.0)
        $this->clientId = config('services.wompi.client_id');
        $this->clientSecret = config('services.wompi.client_secret');
        $this->authUrl = config('services.wompi.auth_url');
        $this->audience = config('services.wompi.audience');
        $this->sandbox = env('WOMPI_SANDBOX', true);

                // Configuración tradicional (compatibilidad)
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
        $this->baseUrl = config('services.wompi.base_url');
        $this->sandbox = config('services.wompi.sandbox');
    }    /**
     * Crear token de fuente de pago (tarjeta de crédito)
     */
    public function createPaymentSource($cardData)
    {
        try {
            $requestConfig = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->publicKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'type' => 'CARD',
                    'token' => $cardData['token'], // Token generado por el widget de Wompi
                    'customer_email' => $cardData['customer_email'],
                    'acceptance_token' => $cardData['acceptance_token']
                ]
            ];

            // En desarrollo/sandbox, deshabilitar verificación SSL
            if (config('app.env') !== 'production' || config('services.wompi.sandbox')) {
                $requestConfig['verify'] = false;
            }

            $response = $this->client->post($this->baseUrl . '/payment_sources', $requestConfig);

            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('Wompi - Payment source created', $body);

            return [
                'success' => true,
                'data' => $body['data']
            ];

        } catch (RequestException $e) {
            Log::error('Wompi - Error creating payment source', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'success' => false,
                'error' => 'Error al crear fuente de pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar transacción
     */
    public function processTransaction($transactionData)
    {
        try {
            $requestConfig = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->privateKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'amount_in_cents' => $transactionData['amount_in_cents'],
                    'currency' => $transactionData['currency'] ?? 'COP',
                    'customer_email' => $transactionData['customer_email'],
                    'payment_method' => [
                        'type' => 'CARD',
                        'token' => $transactionData['payment_source_token']
                    ],
                    'reference' => $transactionData['reference'],
                    'payment_source_id' => $transactionData['payment_source_id'] ?? null,
                    'redirect_url' => $transactionData['redirect_url'] ?? null,
                    'shipping_address' => $transactionData['shipping_address'] ?? null,
                ]
            ];

            // En desarrollo/sandbox, deshabilitar verificación SSL
            if (config('app.env') !== 'production' || config('services.wompi.sandbox')) {
                $requestConfig['verify'] = false;
            }

            $response = $this->client->post($this->baseUrl . '/transactions', $requestConfig);

            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('Wompi - Transaction processed', $body);

            return [
                'success' => true,
                'data' => $body['data']
            ];

        } catch (RequestException $e) {
            Log::error('Wompi - Error processing transaction', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar transacción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consultar estado de transacción
     */
    public function getTransaction($transactionId)
    {
        try {
            $response = $this->client->get($this->baseUrl . '/transactions/' . $transactionId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->publicKey,
                    'Content-Type' => 'application/json',
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'data' => $body['data']
            ];

        } catch (RequestException $e) {
            Log::error('Wompi - Error getting transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error al consultar transacción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener access token usando OAuth 2.0 (Wompi El Salvador)
     */
    public function getAccessToken()
    {
        try {
            $requestConfig = [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'audience' => $this->audience
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ];

            // En desarrollo/sandbox, deshabilitar verificación SSL
            if (config('app.env') !== 'production' || $this->sandbox) {
                $requestConfig['verify'] = false;
            }

            $response = $this->client->post($this->authUrl, $requestConfig);
            $body = json_decode($response->getBody()->getContents(), true);

            $this->accessToken = $body['access_token'];

            Log::info('Wompi - Access token obtenido exitosamente');

            return [
                'success' => true,
                'access_token' => $body['access_token'],
                'expires_in' => $body['expires_in']
            ];

        } catch (RequestException $e) {
            Log::error('Wompi - Error obteniendo access token', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener access token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener token de aceptación (adaptado para El Salvador)
     */
    public function getAcceptanceToken()
    {
        // Para Wompi El Salvador, usamos access token en lugar de acceptance token
        $tokenResult = $this->getAccessToken();

        if ($tokenResult['success']) {
            return [
                'success' => true,
                'acceptance_token' => $tokenResult['access_token']
            ];
        }

        return $tokenResult;
    }

    /**
     * Crear enlace de pago (Wompi El Salvador)
     */
    public function createPaymentLink($paymentData)
    {
        // Obtener access token primero
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            // USAR LOS CAMPOS BÁSICOS + IMAGEN PÚBLICA
            $requestPayload = [
                'identificadorEnlaceComercio' => $paymentData['reference'],
                'monto' => (float) ($paymentData['amount_in_cents'] / 100),
                'nombreProducto' => $paymentData['product_name'] ?? $paymentData['description'] ?? 'Productos Vasir'
            ];

            // Solo agregar descripción si es corta (evitar caracteres especiales)
            if (!empty($paymentData['description']) && strlen($paymentData['description']) < 100) {
                $requestPayload['nombreProducto'] = $paymentData['description'];
            }

            // Usar imagen real del producto si está disponible
            if (!empty($paymentData['product_image'])) {
                $requestPayload['imagenProducto'] = $paymentData['product_image'];
                Log::info('🖼️ Usando imagen real del producto', [
                    'imagen_url' => $paymentData['product_image']
                ]);
            } else {
                // Fallback: imagen por defecto de VASIR más profesional
                $requestPayload['imagenProducto'] = 'https://via.placeholder.com/300x200/1F2937/FFFFFF?text=VASIR+Productos';
                Log::info('🖼️ Usando imagen placeholder por defecto');
            }

            // Log para debuggear qué estamos enviando
            Log::info('Wompi - Enviando payload para enlace de pago', [
                'payload' => $requestPayload,
                'productos_detalle' => $paymentData['productos_detalle'] ?? null
            ]);

            $requestConfig = [
                'json' => $requestPayload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokenResult['access_token'],
                    'Content-Type' => 'application/json'
                ]
            ];

            // En desarrollo/sandbox, deshabilitar verificación SSL
            if (config('app.env') !== 'production' || $this->sandbox) {
                $requestConfig['verify'] = false;
            }

            $response = $this->client->post($this->baseUrl . '/EnlacePago', $requestConfig);
            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('Wompi - Enlace de pago creado exitosamente', $body);

            return [
                'success' => true,
                'payment_link' => $body['urlEnlace'] ?? $body['enlace'] ?? $body['url'],
                'link_id' => $body['idEnlace'] ?? $body['id'],
                'reference' => $paymentData['reference']
            ];

        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

            Log::error('Wompi - Error creando enlace de pago', [
                'error' => $e->getMessage(),
                'response' => $errorResponse,
                'payload_sent' => $requestPayload ?? null
            ]);

            return [
                'success' => false,
                'error' => 'Error al crear enlace de pago: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar webhook signature
     */
    public function validateWebhookSignature($payload, $signature, $timestamp)
    {
        $concatenatedString = $payload . $timestamp . $timestamp;
        $hash = hash_hmac('sha256', $concatenatedString, $this->privateKey);

        return hash_equals($signature, $hash);
    }

    /**
     * Convertir pesos colombianos a centavos
     */
    public function convertTocents($amount)
    {
        return intval($amount * 100);
    }

    /**
     * Convertir centavos a pesos colombianos
     */
    public function convertFromCents($cents)
    {
        return $cents / 100;
    }

    /**
     * Generar referencia única para transacción
     */
    public function generateReference($prefix = 'VASIR')
    {
        return $prefix . '_' . time() . '_' . rand(1000, 9999);
    }
}
