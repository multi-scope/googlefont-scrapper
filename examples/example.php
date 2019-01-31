<?php

    namespace font_scrapper;

    require_once __DIR__ . '/../vendor/autoload.php';


    try {

        $config = Config::create(
            'https://fonts.googleapis.com/css?family=Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900',
            dirname(__DIR__, 1)
        );

        FontScrapper::create($config)->run();

    } catch (\Exception $e) {

        echo $e->getMessage();
    }



