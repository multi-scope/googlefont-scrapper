<?php
    /**
     * Created by PhpStorm.
     * User: multi-scope
     * email: multi-scope@yandex.ru
     * Date: 31.01.19
     * Time: 8:28
     */

    namespace font_scrapper;


    use GuzzleHttp\Client;
    use GuzzleHttp\Pool;
    use GuzzleHttp\Psr7\Request;
    use GuzzleHttp\Psr7\Response;

    class FontService
    {
        /** @var GoogleFontCssUrl */
        private $fontCssUrl;

        /** @var Config */
        private $config;

        private $content;

        /**
         * @var FontPart[]
         */
        private $partsStack = [];


        public function __construct(GoogleFontCssUrl $fontCssUrl, Config $config)
        {
            $this->fontCssUrl = $fontCssUrl;
            $this->config     = $config;
        }

        public static function create(Config $config, ?GoogleFontCssUrl $fontCssUrl = null): self
        {
            $fontCssUrl = $fontCssUrl ?? GoogleFontCssUrl::create($config->fontLink);
            return new static($fontCssUrl, $config);
        }

        /**
         * @return $this
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function run()
        {
            $this->downloadFonts()->replaceCssLinks();
            return $this;
        }

        public function replaceCssLinks()
        {
            $cssPath    = $this->loadedCssFilePath();
            $cssContent = file_get_contents($cssPath);

            $webPathTemplate = $this->config->fontWebPathTemplate;

            $subs = [];
            foreach ($this->partsStack as $fontPart) {
                $webPath                           = $this->replaceFontPartTemplate($fontPart, $webPathTemplate);
                $subs['#' . $fontPart->link . '#'] = $webPath;
            }
            $cssContent = preg_replace(array_keys($subs), array_values($subs), $cssContent);

            $newCssPath = $this->config->moveCssToPathTemplate;
            $newCssPath = $this->replaceCssTemplate($newCssPath);
            $this->saveFile($newCssPath, $cssContent);
            return $this;
        }

        /**
         * @return $this
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function downloadFonts()
        {
            if (empty($this->partsStack)) {
                $this->parseParts();
            }

            $client   = new Client();
            $requests = function () use ($client) {

                $length = count($this->partsStack);
                for ($i = 0; $i < $length; $i++) {
                    $fontPart = $this->partsStack[$i];

                    yield function ($poolOpts) use ($client, $fontPart) {

                        $this->generateFontSavingPath($fontPart);

                        $path = $fontPart->getSrcPath();

                        $this->createDirIfNotExists(dirname($path));

                        $reqOpts = [
                            'sink' => $path,
                        ];
                        if (is_array($poolOpts) && count($poolOpts) > 0) {
                            $reqOpts = array_merge($poolOpts, $reqOpts); // req > pool
                        }

                        return $client->getAsync($fontPart->link, $reqOpts);
                    };

                }
            };


            $pool    = new Pool($client, $requests(), [
                'concurrency' => 5,
                'fulfilled'   => function (Response $response, $index) {
                    echo "status: " . $response->getStatusCode() . ", " . $index . ", " . $this->partsStack[$index]->link . "\n\r";
                },
                'rejected'    => function ($reason, $index) {
                    echo $reason . ", " . $index . ", " . $this->partsStack[$index]->link . "\n\r";
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();
            return $this;
        }

        public function loadedCssFilePath()
        {
            $name = preg_replace(['#{fontFamily}#'], [$this->getFontFamily()], $this->config->loadedCssName);
            return $this->loadedCssFileDir() . '/' . $name;
        }

        private function replaceFontPartTemplate(FontPart $fontPart, $template): string
        {
            $sub = [
                '#{cssDirName}#' => $this->loadedCssFileDir(),
                '#{shortName}#'  => $fontPart->getShortFontName(),
                '#{version}#'    => $fontPart->getFontVersion(),
                '#{fontName}#'   => $fontPart->name,
                '#{language}#'   => $fontPart->language,
                '#{extension}#'  => $fontPart->getExtension(),
            ];
            return preg_replace(array_keys($sub), array_values($sub), $template);
        }

        private function replaceCssTemplate($template)
        {
            $subs = [
                "#{webPath}#"    => $this->config->baseWebPath,
                "#{basePath}#"   => $this->config->baseDir,
                "#{fontFamily}#" => $this->getFontFamily(),
            ];

            return preg_replace(array_keys($subs), array_values($subs), $template);
        }

        public function saveFile($path, $content): self
        {
            $this->createDirIfNotExists(dirname($path));
            file_put_contents($path, $content);
            return $this;
        }


        /**
         * @return FontService
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function parseParts(): self
        {
            if (!$this->content) {
                $this->loadCssFile();
            }

            $parts = explode('}', $this->content);
            foreach ($parts as $part) {

                if (empty(trim($part))) {
                    continue;
                }

                preg_match_all($this->config->languageRegExp, $part, $langs);
                preg_match_all($this->config->fontRegExp, $part, $fonts);
                preg_match_all($this->config->urlRegexp, $part, $urls);
                $language = $langs[1][0] ?? null;
                $font     = $fonts[1][0] ?? null;
                $url      = $urls[1][0] ?? null;

                $this->partsStack[] = new FontPart($font, $language, $url);
            }
            return $this;
        }

        private function generateFontSavingPath(FontPart $fontPart): void
        {
            $template = $this->config->relativeLoadFontDir;

            $srcPath = $this->replaceFontPartTemplate($fontPart, $template);
            $fontPart->specifySrcPath($srcPath);
        }

        private function createDirIfNotExists($dirName)
        {
            if (!is_dir($dirName)) {
                mkdir($dirName, 0777, true);
            }
        }

        public function getFontFamily()
        {
            preg_match_all($this->config->fontFamilyRegexp, $this->fontCssUrl->getUrl(), $matches);
            return $matches[1][0] ?? null;
        }

        public function loadedCssFileDir()
        {
            $relative = $this->replaceCssTemplate($this->config->relativeLoadCssDir);
            return $this->config->baseDir . '/' . $relative;
        }


        ##### PRIVATE INNER METHODS

        /**
         * @param null $isToReload
         *
         * @return FontService
         * @throws \GuzzleHttp\Exception\GuzzleException
         */
        public function loadCssFile($isToReload = null): self
        {
            $isToReload = $isToReload ?? $this->config->isToReloadCss;
            $path       = $this->loadedCssFilePath();

            /* if content exists  */
            if ($this->content && !$isToReload) {
                return $this;
            }

            /* if file exists */
            if (file_exists($path) && !$isToReload) {
                $this->content = file_get_contents($path);
                return $this;
            }


            /* css file loading */
            $request       = new Request("GET", $this->fontCssUrl->getUrl(), $this->config->cssFileHeaders);
            $client        = new Client();
            $response      = $client->send($request);
            $this->content = $response->getBody()->getContents();
            $this->saveFile($path, $this->content);
            return $this;
        }
    }