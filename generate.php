#!/usr/bin/env php
<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

require 'vendor/autoload.php';

$yamlParser = new \Symfony\Component\Yaml\Parser();

$config = $yamlParser->parse(file_get_contents('config.yml'));

foreach ($config as $fileName => $fileConfig) {

    $fileConfig += [
        'delimiter' => ';',
        'newline' => "\n",
        'charset' => 'UTF-8',
        'bom' => false,
        'excelheader' => false,
    ];

    $filePath = 'files/' . $fileName;
    $file = new SplFileObject($filePath, 'w');

    if (true === $fileConfig['bom']) {
        switch ($fileConfig['charset']) {
            case 'UTF-8':
                $file->fwrite(\League\Csv\Writer::BOM_UTF8);
                break;
            case 'UTF-16LE':
                $file->fwrite(\League\Csv\Writer::BOM_UTF16_LE);
                break;
            default:
                throw new RuntimeException(
                    'Unable to add BOM for charset ' . $fileConfig['charset']
                );
        }
    }

    unset($file);

    // See http://nyamsprod.com/blog/2014/stream-filtering-with-splfileobject/
    // http://technosophos.com/2012/02/28/php-stream-filters-compress-transform-and-transcode-fly.html
    // http://php.net/manual/en/filters.php
    if ($fileConfig['charset'] !== 'UTF-8') {
        $filePath = 'php://filter/write=convert.iconv.UTF-8.' . $fileConfig['charset'] . '/resource=' . $filePath;
    }

    $file = new SplFileObject($filePath, 'a');

    $csvWriter = new League\Csv\Writer($file);
    $csvWriter->setDelimiter($fileConfig['delimiter']);
    $csvWriter->setNewline($fileConfig['newline']);

    if (true === $fileConfig['excelheader']) {
        $file->fwrite("sep={$fileConfig['delimiter']}");
        $file->fwrite($fileConfig['newline']);
    }

    $data = [
        [
            "Café",
            "Tervuursesteenweg\r3000 Leuven\rBelgium",
            "Test 123"
        ],
        [
            "Immobiliën",
            "Parijsstraat\r3000 Leuven\rBelgië",
            "Test 123"
        ],
    ];

    /*
    if ($fileConfig['charset'] !== 'UTF-8') {
        foreach ($data as &$cells) {
            foreach ($cells as &$cell) {
                $cell = iconv(
                    'UTF-8',
                    $fileConfig['charset'] . '//IGNORE',
                    $cell
                );
            }
        }
    }*/

    foreach ($data as $row) {
        $csvWriter->insertOne($row);
    }

    unset($csvWriter);
    unset($file);
}

