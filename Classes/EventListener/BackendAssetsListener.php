<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Page\PageRenderer;

final class BackendAssetsListener
{
    public function __construct(
        private readonly PageRenderer $pageRenderer,
    ) {
    }

    public function __invoke(AfterBackendPageRenderEvent $event): void
    {
        $this->pageRenderer->loadJavaScriptModule('@xima/xima-typo3-content-audit/pagetree-filter.js');
    }
}
