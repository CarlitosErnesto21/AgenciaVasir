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
        $clientConfig = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'VASIR-AgenciaViajes/1.0',
                'Accept' => 'application/json'
            ]
        ];

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
        $this->baseUrl = config('services.wompi.base_url');
        $this->sandbox = config('services.wompi.sandbox', true);

        // Configuración tradicional (compatibilidad)
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
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
            Log::info('🔐 Wompi - Intentando obtener access token', [
                'client_id' => $this->clientId,
                'auth_url' => $this->authUrl,
                'audience' => $this->audience,
                'sandbox' => $this->sandbox
            ]);

            // Usar autenticación Basic Auth en lugar de form params
            $requestConfig = [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'audience' => $this->audience
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                ]
            ];

            // En desarrollo/sandbox, deshabilitar verificación SSL
            if (config('app.env') !== 'production' || $this->sandbox) {
                $requestConfig['verify'] = false;
            }

            $response = $this->client->post($this->authUrl, $requestConfig);
            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('🔐 Wompi - Respuesta de autenticación', [
                'response_body' => $body,
                'status_code' => $response->getStatusCode()
            ]);

            $this->accessToken = $body['access_token'];

            Log::info('✅ Wompi - Access token obtenido exitosamente');

            return [
                'success' => true,
                'access_token' => $body['access_token'],
                'expires_in' => $body['expires_in'] ?? 3600
            ];

        } catch (RequestException $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'client_id' => $this->clientId,
                'auth_url' => $this->authUrl
            ];

            if ($e->hasResponse()) {
                $errorDetails['response'] = $e->getResponse()->getBody()->getContents();
                $errorDetails['response_status'] = $e->getResponse()->getStatusCode();
            }

            Log::error('❌ Wompi - Error obteniendo access token', $errorDetails);

            return [
                'success' => false,
                'error' => 'Error al obtener access token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Método de prueba para verificar la conexión con Wompi El Salvador
     */
    public function testConnection()
    {
        try {
            Log::info('🧪 WOMPI TEST - Iniciando prueba de conexión');

            // Verificar configuración
            $config = [
                'client_id' => $this->clientId,
                'client_secret' => !empty($this->clientSecret) ? 'SET' : 'NOT SET',
                'auth_url' => $this->authUrl,
                'base_url' => $this->baseUrl,
                'audience' => $this->audience,
                'sandbox' => $this->sandbox
            ];

            Log::info('🧪 WOMPI TEST - Configuración cargada', $config);

            // Probar autenticación
            $tokenResult = $this->getAccessToken();

            if (!$tokenResult['success']) {
                Log::error('🧪 WOMPI TEST - FALLO EN AUTENTICACIÓN', $tokenResult);
                return $tokenResult;
            }

            Log::info('🧪 WOMPI TEST - ✅ AUTENTICACIÓN EXITOSA');

            return [
                'success' => true,
                'message' => 'Conexión y autenticación exitosa con Wompi El Salvador',
                'token_obtained' => true
            ];

        } catch (\Exception $e) {
            Log::error('🧪 WOMPI TEST - ERROR GENERAL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Método de prueba para crear un enlace de pago de prueba
     */
    public function testPaymentLink()
    {
        try {
            Log::info('🧪 WOMPI PAYMENT TEST - Iniciando prueba de enlace de pago');

            // Datos de prueba simples
            $testData = [
                'reference' => 'TEST-' . time(),
                'amount_in_cents' => 500, // $5.00 USD
                'product_name' => 'Producto de Prueba VASIR'
            ];

            Log::info('🧪 WOMPI PAYMENT TEST - Datos de prueba', $testData);

            $result = $this->createPaymentLink($testData);

            Log::info('🧪 WOMPI PAYMENT TEST - Resultado', $result);

            return $result;

        } catch (\Exception $e) {
            Log::error('🧪 WOMPI PAYMENT TEST - ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Diagnóstico avanzado - Probar diferentes configuraciones
     */
    public function diagnosticPaymentLink()
    {
        $results = [];

        try {
            // Obtener token primero
            $tokenResult = $this->getAccessToken();
            if (!$tokenResult['success']) {
                return ['success' => false, 'error' => 'No se pudo obtener token'];
            }

            // Configuraciones a probar
            $testConfigs = [
                [
                    'name' => 'Configuración Mínima',
                    'payload' => [
                        'identificadorEnlaceComercio' => 'TEST-MIN-' . time(),
                        'monto' => 5.00,
                        'nombreProducto' => 'Test'
                    ]
                ],
                [
                    'name' => 'Con Moneda USD',
                    'payload' => [
                        'identificadorEnlaceComercio' => 'TEST-USD-' . time(),
                        'monto' => 5.00,
                        'nombreProducto' => 'Test USD',
                        'moneda' => 'USD'
                    ]
                ],
                [
                    'name' => 'Formato Original',
                    'payload' => [
                        'identificadorEnlaceComercio' => 'TEST-ORG-' . time(),
                        'monto' => 5,
                        'nombreProducto' => 'Test Original'
                    ]
                ]
            ];

            foreach ($testConfigs as $config) {
                Log::info('🧪 DIAGNOSTIC - Probando: ' . $config['name'], $config['payload']);

                try {
                    $requestConfig = [
                        'json' => $config['payload'],
                        'headers' => [
                            'Authorization' => 'Bearer ' . $tokenResult['access_token'],
                            'Content-Type' => 'application/json'
                        ],
                        'verify' => false
                    ];

                    $response = $this->client->post($this->baseUrl . '/EnlacePago', $requestConfig);
                    $body = json_decode($response->getBody()->getContents(), true);

                    $results[$config['name']] = [
                        'success' => true,
                        'status_code' => $response->getStatusCode(),
                        'response' => $body
                    ];

                } catch (RequestException $e) {
                    $errorBody = '';
                    if ($e->hasResponse()) {
                        $errorBody = $e->getResponse()->getBody()->getContents();
                    }

                    $results[$config['name']] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                        'response_body' => $errorBody
                    ];
                }
            }

            Log::info('🧪 DIAGNOSTIC - Resultados completos', $results);

            return [
                'success' => true,
                'message' => 'Diagnóstico completado',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('🧪 DIAGNOSTIC - Error general', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'partial_results' => $results
            ];
        }
    }

    /**
     * Obtener token de aceptación (adaptado para El Salvador)
     */
    public function getAcceptanceToken()
    {
        // Para Wompi El Salvador, el token de aceptación es el mismo access token
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
            // Payload para Wompi El Salvador - estructura mínima requerida
            $monto = ($paymentData['amount_in_cents'] / 100);
            $requestPayload = [
                'identificadorEnlaceComercio' => (string) $paymentData['reference'],
                'monto' => (float) $monto,
                'nombreProducto' => substr($paymentData['product_name'] ?? 'Productos VASIR', 0, 100),
                'moneda' => 'USD' // Wompi El Salvador trabaja en USD
            ];

            // Solo usar imagen si es una URL válida y accesible
            if (!empty($paymentData['product_image']) && filter_var($paymentData['product_image'], FILTER_VALIDATE_URL)) {
                $requestPayload['imagenProducto'] = $paymentData['product_image'];
                Log::info('🖼️ Usando imagen del producto', [
                    'imagen_url' => $paymentData['product_image']
                ]);
            }

            // Log para debuggear qué estamos enviando
            Log::info('🔗 Wompi - Enviando payload para enlace de pago', [
                'payload' => $requestPayload,
                'token_obtenido' => !empty($tokenResult['access_token']) ? 'SÍ' : 'NO',
                'base_url' => $this->baseUrl,
                'endpoint' => '/EnlacePago'
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

            Log::info('🚀 Wompi - Enviando petición de enlace de pago', [
                'url' => $this->baseUrl . '/EnlacePago',
                'headers' => [
                    'Authorization' => 'Bearer ***TOKEN***',
                    'Content-Type' => 'application/json'
                ],
                'payload' => $requestPayload
            ]);

            $response = $this->client->post($this->baseUrl . '/EnlacePago', $requestConfig);
            $responseBody = $response->getBody()->getContents();
            $body = json_decode($responseBody, true);

            Log::info('✅ Wompi - Enlace de pago creado exitosamente', [
                'response_status' => $response->getStatusCode(),
                'response_headers' => $response->getHeaders(),
                'response_body' => $body,
                'raw_response' => $responseBody
            ]);

            return [
                'success' => true,
                'payment_link' => $body['urlEnlace'] ?? $body['enlace'] ?? $body['url'] ?? $body['link'],
                'link_id' => $body['idEnlace'] ?? $body['id'] ?? $body['linkId'],
                'reference' => $paymentData['reference']
            ];

        } catch (RequestException $e) {
            $errorDetails = [
                'error_message' => $e->getMessage(),
                'status_code' => $e->getCode(),
                'payload_sent' => $requestPayload ?? null,
                'base_url' => $this->baseUrl,
                'endpoint' => '/EnlacePago',
                'full_url' => $this->baseUrl . '/EnlacePago'
            ];

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();

                $errorDetails['response_status'] = $response->getStatusCode();
                $errorDetails['response_headers'] = $response->getHeaders();
                $errorDetails['response_body_raw'] = $responseBody;

                // Intentar parsear como JSON
                $jsonResponse = json_decode($responseBody, true);
                if ($jsonResponse !== null) {
                    $errorDetails['response_body_parsed'] = $jsonResponse;
                }
            }

            Log::error('❌ WOMPI PAYMENT LINK ERROR - Detalles completos', $errorDetails);

            // Retornar información más detallada
            $errorMessage = $e->getMessage();
            if (isset($errorDetails['response_body_parsed']['message'])) {
                $errorMessage .= ' - Detalle: ' . $errorDetails['response_body_parsed']['message'];
            } elseif (isset($errorDetails['response_body_raw'])) {
                $errorMessage .= ' - Respuesta: ' . substr($errorDetails['response_body_raw'], 0, 200);
            }

            return [
                'success' => false,
                'error' => 'Error al crear enlace de pago: ' . $errorMessage,
                'debug_info' => $errorDetails
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
