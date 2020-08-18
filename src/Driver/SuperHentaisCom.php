<?php

namespace Yamete\Driver;

class SuperHentaisCom extends \Yamete\DriverAbstract
{
    private $aMatches = [];
    const DOMAIN = 'superhentais.com';

    public function canHandle(): bool
    {
        $sMatch = '~^https?://www\.(' . strtr(self::DOMAIN, ['.' => '\.'])
            . ')/(?<category>[^/]+)/(?<album>[^/]+)/(?<albumId>[0-9]+)$~';
        return (bool)preg_match($sMatch, $this->sUrl, $this->aMatches);
    }

    /**
     * @return array|string[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDownloadables(): array
    {
        $oRes = $this->getClient()->request('GET', $this->sUrl);
        $aReturn = [];
        $index = 0;
        foreach ($this->getDomParser()->load((string)$oRes->getBody())->find('.capituloView img') as $oImg) {
            /**
             * @var \PHPHtmlParser\Dom\AbstractNode $oImg
             */
            $sFilename = $oImg->getAttribute('src');
            $sBasename = $this->getFolder() . DIRECTORY_SEPARATOR . str_pad($index++, 5, '0', STR_PAD_LEFT)
                . '-' . basename($sFilename);
            $aReturn[$sBasename] = $sFilename;
        }
        return $aReturn;
    }

    private function getFolder(): string
    {
        return implode(DIRECTORY_SEPARATOR, [self::DOMAIN, $this->aMatches['album']]);
    }

    /**
     * @param array $aOptions
     * @return \GuzzleHttp\Client
     */
    public function getClient(array $aOptions = []): \GuzzleHttp\Client
    {
        return parent::getClient(['headers' => ['Accept-Language' => 'en'], 'http_errors' => false]);
    }
}
