<?php
/** @license GNU General Public License version 2 or later */
namespace Joomla\Plugin\System\RPCustomScrollbar\Extension;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Application\BeforeCompileHeadEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\RPCustomScrollbar\Support\Settings;

\defined('_JEXEC') or die;

final class RPCustomScrollbar extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onBeforeCompileHead' => 'onBeforeCompileHead'];
    }

    public function onBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $app = $this->getApplication();
        $document = $event->getDocument();
        if (!$app || !($document instanceof HtmlDocument)) {
            return;
        }
        $client = $app->isClient('site') ? 'site' : ($app->isClient('administrator') ? 'administrator' : '');
        $css = Settings::css($this->params->toArray(), $client);
        if ($css !== '') {
            $document->getWebAssetManager()->addInlineStyle($css, ['name' => 'plg_system_rpcustomscrollbar']);
        }
    }
}
