<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\Storage\StorageClient;
use GuzzleHttp\Client;

header('Content-Type: application/json');

try {
    if (!isset($_FILES['foto'])) {
        throw new Exception('Nenhuma foto enviada.');
    }

    $foto = $_FILES['foto'];

    if ($foto['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro ao receber a foto.');
    }

    $mimePermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($foto['type'], $mimePermitidos)) {
        throw new Exception('Formato de imagem inválido.');
    }

    $keyFilePath = __DIR__ . '/config/firebase-service-account.json';
    $cacertPath = __DIR__ . '/config/cacert.pem';

    $credenciais = json_decode(file_get_contents($keyFilePath), true);
    if (!$credenciais || empty($credenciais['project_id'])) {
        throw new Exception('Arquivo de credenciais Firebase inválido.');
    }

    $projectId = $credenciais['project_id'];
    // gs://transportes-thomaz.appspot.com
    $bucketName = 'transportes-thomaz.appspot.com';

    $guzzleOptions = [];
    if (file_exists($cacertPath)) {
        $guzzleOptions['verify'] = $cacertPath;
    }

    $httpHandler = HttpHandlerFactory::build(new Client($guzzleOptions));

    $storage = new StorageClient([
        'keyFilePath' => $keyFilePath,
        'projectId' => $projectId,
        'authHttpHandler' => $httpHandler,
        'httpHandler' => $httpHandler,
    ]);

    $bucket = $storage->bucket($bucketName);

    $extensao = match ($foto['type']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    $nomeArquivo = 'formularios/fotos/' . date('Y/m/d/') . uniqid('foto_', true) . '.' . $extensao;
    $downloadToken = bin2hex(random_bytes(16));

    $bucket->upload(
        fopen($foto['tmp_name'], 'r'),
        [
            'name' => $nomeArquivo,
            'metadata' => [
                'contentType' => $foto['type'],
                'metadata' => [
                    'firebaseStorageDownloadTokens' => $downloadToken,
                ],
            ],
        ]
    );

    $pathEncoded = rawurlencode($nomeArquivo);
    $url = "https://firebasestorage.googleapis.com/v0/b/{$bucketName}/o/{$pathEncoded}?alt=media&token={$downloadToken}";

    echo json_encode([
        'success' => true,
        'url' => $url,
        'path' => $nomeArquivo
    ]);

} catch (Exception $e) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}