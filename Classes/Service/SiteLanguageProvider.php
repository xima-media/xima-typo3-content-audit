<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Service;

use TYPO3\CMS\Core\Site\SiteFinder;

class SiteLanguageProvider
{
    public function __construct(private readonly SiteFinder $siteFinder)
    {
    }

    public function hasAdditionalLanguagesConfigured(): bool
    {
        return $this->getAdditionalLanguageUids() !== [];
    }

    /**
    * @return list<int>
    */
    public function getAdditionalLanguageUids(): array
    {
        $languageUids = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach (array_keys($site->getLanguages()) as $languageUid) {
                if ($languageUid !== 0) {
                    $languageUids[$languageUid] = $languageUid;
                }
            }
        }

        return array_values($languageUids);
    }
}
