<?php
/** @license GNU General Public License version 2 or later */
namespace Joomla\Plugin\System\RPCustomScrollbar\Filter;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFilterInterface;
use Joomla\Plugin\System\RPCustomScrollbar\Support\Settings;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

final class ScrollbarFilter implements FormFilterInterface
{
    public function filter(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        return Settings::normalize((string) $element['name'], $value);
    }
}
