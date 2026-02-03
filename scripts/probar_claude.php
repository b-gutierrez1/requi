<?php

$apiKey = getenv('ANTHROPIC_API_KEY');

if (!$apiKey) {
    fwrite(STDERR, "Error: no se encontró la variable de entorno ANTHROPIC_API_KEY.\n");
    fwrite(STDERR, "Exporta la clave antes de ejecutar este script.\n");
    exit(1);
}

$prompt = $argv[1] ?? 'Escribe un resumen breve de una requisición de ejemplo.';

$payload = [
    'model' => 'claude-3-5-sonnet-20241022',
    'max_tokens' => 256,
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt,
        ],
    ],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60,
]);

$response = curl_exec($ch);

if ($response === false) {
    fwrite(STDERR, "Error en la solicitud cURL: " . curl_error($ch) . "\n");
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$decoded = json_decode($response, true);

if ($httpCode >= 400) {
    fwrite(STDERR, "La API devolvió un error ({$httpCode}):\n");
    fwrite(STDERR, $response . "\n");
    exit(1);
}

$texto = $decoded['content'][0]['text'] ?? null;

if ($texto === null) {
    fwrite(STDOUT, "Respuesta completa de la API:\n");
    fwrite(STDOUT, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
    exit(0);
}

fwrite(STDOUT, "Respuesta de Claude:\n");
fwrite(STDOUT, $texto . "\n");

