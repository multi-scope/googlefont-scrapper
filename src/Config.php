<?php
    /**
     * Created by PhpStorm.
     * User: multi-scope
     * email: multi-scope@yandex.ru
     * Date: 30.01.19
     * Time: 20:07
     */

    namespace font_scrapper;


    class Config
    {

        ##### base paths

        /** @var string|null Base dir for path calculation */
        public $baseDir;

        /** @var string base path to web directory */
        public $baseWebPath = "/";

        /** @var string|null google font lint to @font-face css file */
        public $fontLink = 'https://fonts.googleapis.com/css?family=Lora:400,400i,700,700i&subset=cyrillic,cyrillic-ext,latin-ext,vietnamese';

        ##### loaded CSS assets

        /**
         * A path relative to base dir for loaded css file with @font-face. Can use templates as:
         *  {fontFamily}  - family queryParam from link
         *  {webPath}     - path to base web dir
         *  {basePath}    - path to base dir
         *
         * @var string
         */
        public $relativeLoadCssDir = 'parsed/googlefont_scrapper/{fontFamily}';

        /**
         * filename for rename loaded @font-face css file can use templates as:
         *  {fontFamily}  - family queryParam from link
         *  {webPath}     - path to base web dir
         *  {basePath}    - path to base dir
         *
         * @var string
         */
        public $loadedCssName = "{fontFamily}--parsed.css";


        ##### loaded font files
        /**
         * An absolute path to dir where to load fonts. Can use templates
         *  {cssDirName} -path to directory where been loaded css file with @font-faces
         *  {shortName}  -short font name got from link to font file source
         *  {version}    -v-parameter got from link to font file source
         *  {fontName}   -full name, got from concrete @font-face
         *  {language}   -language for @font-face, got from concrete @font-face
         *  {extension}  -font file extension
         *
         *
         * @var string
         */
        public $relativeLoadFontDir = '{cssDirName}/{version}/{fontName}--{language}.{extension}'; /* "{shortName}" */


        /**
         * Template for generating web path in css file with @font-faces,
         * Required for replacing  from old google fonts web path  to new web path. Can use templates
         *  {cssDirName} -path to directory where been loaded css file with @font-faces
         *  {shortName}  -short font name got from link to font file source
         *  {version}    -v-parameter got from link to font file source
         *  {fontName}   -full name, got from concrete @font-face
         *  {language}   -language for @font-face, got from concrete @font-face
         *  {extension}  -font file extension
         *
         * @var string
         */
        public $fontWebPathTemplate = '/assets/css/fonts/{shortName}/{version}/{fontName}--{language}.{extension}';


        ##### substituted css with new @font-faces
        /**
         * Where to save css with new @font-face urls. Can use templates as
         *  {fontFamily}  - family queryParam from link
         *  {webPath}     - path to base web dir
         *  {basePath}    - path to base dir
         *
         * @var string
         */
        public $moveCssToPathTemplate = '{basePath}/parsed/googlefont_scrapper/{fontFamily}/{fontFamily}.css'; /* {webPath}{basePath} */

        ##### FLAGS

        public $isToReloadCss   = false;
        public $isToReloadFonts = false;

        ##### REGEXP

        public $fontFamilyRegexp = '/family=(.*)([:]|\z)/U';
        public $languageRegExp   = '/\/\* (.*) \*\//m';
        public $fontRegExp       = '/,.*local\(\'(.*)\'\),/m';
        public $urlRegexp        = '/.* url\((.*)\)\s/m';

        ##### headers
        public $cssFileHeaders = [
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/71.0.3578.98 Chrome/71.0.3578.98 Safari/537.36',
        ];

        /**
         * Config constructor.
         *
         * @param string|null $fontlink
         * @param string|null $baseDir
         * @param array       $config
         *
         * @throws \ReflectionException
         */
        public function __construct(?string $fontlink, ?string $baseDir, $config = [])
        {
            $this->setProperties($config);
            $this->fontLink = $fontlink ?? $this->fontLink;
            $this->baseDir  = $baseDir ?? $this->baseDir ?? dirname(__DIR__, 2);
        }


        /**
         * @param $config
         *
         * @throws \ReflectionException
         */
        private function setProperties($config)
        {
            foreach ($config as $key => $value) {
                $res = false;
                if (property_exists(self::class, $key)) {
                    $Reflection = new \ReflectionProperty(static::class, $key);
                    if ($Reflection->isPublic()) {
                        $this->$key = $value;
                        $res        = true;
                    }
                }
                if (!$res) {
                    throw new \InvalidArgumentException("Property " . $key . ' do not exists in ' . static::class);
                }
            }
        }

        /**
         * @param string|null $fontlink
         * @param string|null $baseDir
         *
         * @return Config
         * @throws \ReflectionException
         */
        public static function create(?string $fontlink = null, string $baseDir = null)
        {
            return new self($fontlink, $baseDir, []);
        }


    }