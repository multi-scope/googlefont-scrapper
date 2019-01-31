<?php
    /**
     * Created by PhpStorm.
     * User: multi-scope
     * email: multi-scope@yandex.ru
     * Date: 31.01.19
     * Time: 9:31
     */

    namespace font_scrapper;


    class FontPart
    {
        public  $name;
        public  $language;
        public  $link;
        private $linkInfo;
        private $shortName;
        private $fontVersion;
        private $srcPath;


        public function __construct($name, $language, $link)
        {
            $this->name     = $name;
            $this->language = $language;
            $this->link     = $link;
        }


        public function specifySrcPath($srcPath)
        {
            $this->srcPath = $srcPath;
        }

        public function getSrcPath()
        {
            if (!$this->srcPath) {
                throw new \LogicException("Path where to download font is not specified!");
            }
            return $this->srcPath;
        }


        public function getShortFontName()
        {

            if (!$this->shortName) {
                preg_match_all('/\/s\/(.+)[\/].?/U', $this->link, $output_array);
                $this->shortName = $output_array[1][0] ?? null;
            }
            return $this->shortName;


        }

        public function getExtension()
        {
            return $this->getLinkInfo()['extension'];
        }

        private function getLinkInfo()
        {
            if (!$this->linkInfo) {
                $this->linkInfo = pathinfo($this->link);
            }
            return $this->linkInfo;
        }

        public function getFontVersion()
        {
            if (!$this->fontVersion) {
                preg_match_all('/\/([v0-9]{1,10})\//U', $this->link, $versions);
                $this->fontVersion = $versions[1][0] ?? null;
            }
            return $this->fontVersion;
        }

    }