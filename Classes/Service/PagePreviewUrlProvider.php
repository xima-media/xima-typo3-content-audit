<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Service;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;

/**
* Resolves the frontend preview URL for a page, used to link the page path
* shown in the widget tables directly to the live/preview page.
*/
class PagePreviewUrlProvider
{
    public function getUrl(int $pageUid): ?string
    {
        if ($pageUid <= 0) {
            return null;
        }

        try {
            $uri = PreviewUriBuilder::create($pageUid)->buildUri();
        } catch (\Throwable $th) {
            return null;
        }

        return $uri !== null ? (string)$uri : null;
    }
}
