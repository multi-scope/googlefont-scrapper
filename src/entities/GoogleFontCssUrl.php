<?php
    /**
     * Created by PhpStorm.
     * User: multi-scope
     * email: multi-scope@yandex.ru
     * Date: 31.01.19
     * Time: 14:04
     */

    namespace font_scrapper\entities;


    class GoogleFontCssUrl
    {
        public $correctHost = 'fonts.googleapis.com';

        /**
         * @var string
         */
        protected $fontUrl;
        protected $config;

        ##### CONSTRUCTORS

        public function __construct(string $fontUrl)
        {
            $this->fontUrl = $fontUrl;
            $this->checkIsValid();
        }

        public function checkIsValid()
        {
            $info   = $this->urlInfo();
            $errors = [];
            if ($info['host'] !== $this->correctHost) {
                $errors[] = "Wrong domain host. Needs: " . $this->correctHost;
            }

            if (!preg_match('/family/', $this->fontUrl)) {
                $errors[] = "Font family unspecified";
            }

            if (!empty($errors)) {
                throw new \InvalidArgumentException(join('\n\r', $errors));
            }
            return true;
        }

        ##### MAIN METHODS

        private function urlInfo()
        {
            return parse_url($this->fontUrl);
        }

        public static function create(string $fontUrl)
        {
            return new static($fontUrl);
        }

        ##### INTERNAL METHODS

        public function getUrl()
        {
            return $this->fontUrl;
        }
    }