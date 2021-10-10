<?php

namespace Yamete\Driver;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Traversable;
use Yamete\DriverAbstract;

class ReadFreeComicsCom extends DriverAbstract
{
    private const DOMAIN = 'readfreecomics.com';
    protected array $aMatches = [];

    public function canHandle(): bool
    {
        $sReg = '~^https?://(' . strtr($this->getDomain(), ['.' => '\.']) . ')/(?<category>[^/]+)/(?<album>[^/]+)~';
        return (bool)preg_match($sReg, $this->sUrl, $this->aMatches);
    }

    protected function getDomain(): string
    {
        return self::DOMAIN;
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function getDownloadables(): array
    {
        /**
         * @var Traversable $oChapters
         */
        $sUrl = 'https://' . $this->getDomain() . '/' . $this->aMatches['category']
            . '/' . $this->aMatches['album'] . '/';
        $oRes = $this->getClient()->request('GET', $sUrl);
        $aMatches = [];
        $sRegExp = '~<li class="wp-manga-chapter[^"]+">[^<]+<a href=([^">]+)~us';
        if (!preg_match_all($sRegExp, (string)$oRes->getBody(), $aMatches)) {
            return [];
        }
        $aChapters = $aMatches[1];
        krsort($aChapters);
        $index = 0;
        $aReturn = [];
        foreach ($aChapters as $sChapter) {
            $oRes = $this->getClient()->request('GET', $sChapter);
            $aMatches = [];
            if (!preg_match_all('~src="([^"> ]+)" class=wp-manga-chapter-img~us', (string)$oRes->getBody(), $aMatches)) {
                continue;
            }
            foreach ($aMatches[1] as $sFilename) {
                $sFilename = trim($sFilename);
                $sBasename = $this->getFolder() . DIRECTORY_SEPARATOR . str_pad($index++, 5, '0', STR_PAD_LEFT)
                    . '-' . basename($sFilename);
                $aReturn[$sBasename] = $sFilename;
            }
        }
        return $aReturn;
    }

    private function getFolder(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getDomain(), $this->aMatches['album']]);
    }

    /**
     * @param array $aOptions
     * @return Client
     */
    public function getClient(array $aOptions = []): Client
    {
        return parent::getClient(['headers' => ['User-Agent' => self::USER_AGENT]]);
    }
}
