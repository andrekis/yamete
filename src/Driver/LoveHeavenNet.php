<?php

namespace Yamete\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use iterator;
use PHPHtmlParser\Dom\AbstractNode;
use Yamete\DriverAbstract;


class LoveHeavenNet extends DriverAbstract
{
    private $aMatches = [];
    const DOMAIN = 'loveheaven.net';

    public function canHandle(): bool
    {
        return (bool)preg_match(
            '~^https?://(' . strtr(self::DOMAIN, ['.' => '\.']) . ')/(?<album>[^.]+).html$~',
            $this->sUrl,
            $this->aMatches
        );
    }

    /**
     * Where to download
     * @return string
     */
    private function getFolder(): string
    {
        return implode(DIRECTORY_SEPARATOR, [self::DOMAIN, $this->aMatches['album']]);
    }

    /**
     * @return array|string[]
     * @throws GuzzleException
     */
    public function getDownloadables(): array
    {
        /**
         * @var iterator $oChapters
         * @var AbstractNode[] $aChapters
         * @var AbstractNode[] $oPages
         * @var AbstractNode $oImg
         */
        $sUrl = 'https://' . self::DOMAIN . '/' . $this->aMatches['album'] . '.html';
        $oResult = $this->getClient()->request('GET', $sUrl);
        $oChapters = $this->getDomParser()->load((string)$oResult->getBody())->find('a.chapter');
        $aChapters = iterator_to_array($oChapters);
        krsort($aChapters);
        $aReturn = [];
        $index = 0;
        foreach ($aChapters as $oChapter) {
            $sUrl = 'https://' . self::DOMAIN . '/' . $oChapter->getAttribute('href');
            $oResult = $this->getClient()->request('GET', $sUrl);
            $oPages = $this->getDomParser()->load((string)$oResult->getBody())->find('img.chapter-img');
            foreach ($oPages as $oPage) {
                $sFilename = base64_decode($oPage->getAttribute('data-src'));
                if (strpos($sFilename, 'http') === false) {
                    continue;
                }
                $sBasename = $this->getFolder() . DIRECTORY_SEPARATOR . str_pad($index++, 5, '0', STR_PAD_LEFT)
                    . '-' . basename($sFilename);
                $aReturn[$sBasename] = $sFilename;
            }
        }
        return $aReturn;
    }

    public function getClient(array $aOptions = []): Client
    {
        return parent::getClient(['headers' => ['Referer' => $this->sUrl]]);
    }
}
