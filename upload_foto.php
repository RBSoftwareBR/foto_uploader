<?php

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

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

    $bucketName = 'SEU_PROJECT_ID.firebasestorage.app';
    // Em buckets antigos pode ser: SEU_PROJECT_ID.appspot.com

    $storage = new StorageClient([
        'keyFilePath' => __DIR__ . '/config/firebase-service-account.json'
    ]);

    $bucket = $storage->bucket($bucketName);

    $extensao = match ($foto['type']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    $nomeArquivo = 'formularios/fotos/' . date('Y/m/d/') . uniqid('foto_', true) . '.' . $extensao;

    $bucket->upload(
        fopen($foto['tmp_name'], 'r'),
        [
            'name' => $nomeArquivo,
            'metadata' => [
                'contentType' => $foto['type']
            ]
        ]
    );

    // URL pública, caso o bucket permita leitura pública
    $url = "https://storage.googleapis.com/{$bucketName}/ERP_EKT_THOMAZ/{$nomeArquivo}";

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