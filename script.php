<?php
/** @license GNU General Public License version 2 or later */
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\Database\DatabaseInterface;

class PlgSystemRPCustomscrollbarInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '6.0.0';
    protected $minimumPhp = '8.3.0';
    protected $deleteFiles = ['/plugins/system/rpcustomscrollbar/rpcustomscrollbar.php'];

    public function postflight($type, $parent): void
    {
        if (!in_array($type, ['install', 'update', 'discover_install'], true)) {
            return;
        }
        $this->removeFiles();
        // This custom build must not be replaced by the obsolete upstream release.
        // Remove only this plugin's association with that exact legacy feed.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('rpcustomscrollbar'));
        $extensionId = (int) $db->setQuery($query)->loadResult();
        if (!$extensionId) {
            return;
        }
        $query = $db->getQuery(true)->select($db->quoteName('update_site_id'))
            ->from($db->quoteName('#__update_sites'))
            ->where($db->quoteName('location') . ' = ' . $db->quote('https://raw.githubusercontent.com/rpextensions/custom-scrollbar-joomla/master/update.xml'));
        $siteIds = array_map('intval', $db->setQuery($query)->loadColumn());
        if ($siteIds === []) {
            return;
        }
        $query = $db->getQuery(true)->delete($db->quoteName('#__update_sites_extensions'))
            ->where($db->quoteName('extension_id') . ' = ' . $extensionId)
            ->whereIn($db->quoteName('update_site_id'), $siteIds);
        $db->setQuery($query)->execute();
        $query = $db->getQuery(true)->delete($db->quoteName('#__updates'))
            ->where($db->quoteName('extension_id') . ' = ' . $extensionId)
            ->whereIn($db->quoteName('update_site_id'), $siteIds);
        $db->setQuery($query)->execute();
        foreach ($siteIds as $siteId) {
            $query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__update_sites_extensions'))
                ->where($db->quoteName('update_site_id') . ' = ' . $siteId);
            if ((int) $db->setQuery($query)->loadResult() === 0) {
                $query = $db->getQuery(true)->delete($db->quoteName('#__update_sites'))
                    ->where($db->quoteName('update_site_id') . ' = ' . $siteId);
                $db->setQuery($query)->execute();
            }
        }
    }
}
