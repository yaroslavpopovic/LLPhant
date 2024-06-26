<?php

declare(strict_types=1);

use LLPhant\Embeddings\DocumentUtils;
use LLPhant\Embeddings\VectorStores\Milvus\MilvusClient;
use LLPhant\Embeddings\VectorStores\Milvus\MilvusVectorStore;

it('tests a full embedding flow with Milvus', function () {
    // Get the already embeded france.txt and paris.txt documents
    $path = __DIR__.'/../EmbeddedMock/francetxt_paristxt.json';
    $rawFileContent = file_get_contents($path);
    if (!$rawFileContent)
    {
        throw new Exception('File not found');
    }

    $rawDocuments = json_decode($rawFileContent, true);
    $embeddedDocuments = DocumentUtils::createDocumentsFromArray($rawDocuments);

    // Get the embedding of "France the country"
    $path = __DIR__.'/../EmbeddedMock/france_the_country_embedding.json';
    $rawFileContent = file_get_contents($path);
    if (!$rawFileContent)
    {
        throw new Exception('File not found');
    }
    /** @var float[] $embeddingQuery */
    $embeddingQuery = json_decode($rawFileContent, true);

    $client = new MilvusClient(getenv('MILVUS_HOST') ?? 'localhost', '19530', 'root', 'milvus');
    $vectorStore = new MilvusVectorStore($client);

    $vectorStore->addDocuments($embeddedDocuments);

    $searchResult1 = $vectorStore->similaritySearch($embeddingQuery, 2);
    expect(DocumentUtils::getFirstWordFromContent($searchResult1[0]))->toBe('France');

    $requestParam = [
        'filter' => 'sourceName == "paris.txt"',
    ];
    $searchResult2 = $vectorStore->similaritySearch($embeddingQuery, 2, $requestParam);
    expect(DocumentUtils::getFirstWordFromContent($searchResult2[0]))->toBe('Paris');
});
