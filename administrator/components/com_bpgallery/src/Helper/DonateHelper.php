<?php

/**
 * @author      ${author.name} (${author.email})
 * @website     ${author.url}
 * @copyright   ${copyrights}
 * @license     ${license.url} ${license.name}
 * @package     ${package}.Component
 * @subpackage  BPGallery
 */

namespace BPExtensions\Component\BPGallery\Administrator\Helper;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

abstract class DonateHelper
{
    /**
     * Session variable name.
     */
    protected const  SESSION_VAR_NAME = 'bpextensions_donation';

    /**
     * Donate page URL.
     */
    protected const DONATE_URL = '${support.donate_url}';

    /**
     * Show donation message.
     */
    public static function showMessage(): void
    {
        // Show popup if needed
        /**
         * @var Session        $session
         * @var CMSApplication $app
         */
        $app     = Factory::getApplication();
        $session = $app->getSession();
        if (!$session->get(static::SESSION_VAR_NAME)) {

            // Make a notice
            $app->enqueueMessage(static::getDonateMessage(
                Text::_('BPEXTENSIONS_DONATE_INTRO_TEXT'), static::DONATE_URL,
                Text::_('BPEXTENSIONS_BUTTON_DONATE_TEXT')
            ), 'notice');

            // Disable popup in this session
            $session->set(static::SESSION_VAR_NAME, true);
        }
    }


    /**
     * Get donation pop-up message content.
     *
     * @return string
     * @var string $url         Donation page url.
     * @var string $button_text Donation button text.
     *
     * @var string $intro       Donation intro.
     */
    protected static function getDonateMessage(string $intro, string $url, string $button_text): string
    {
        return "<p>{$intro}</p>
        <span class=\"btn-wrapper\">
            <a href=\"{$url}\" target=\"_blank\" class=\"btn\">
                <span class=\"icon-thumbs-up\" aria-hidden=\"true\" style=\"border-radius: 3px 0 0 3px;border-right: 1px solid #b3b3b3;height: auto;line-height: inherit;margin: 0 6px 0 -10px;opacity: 1;text-shadow: none;width: 28px;\"></span>
                {$button_text}
            </a>
        </span>";
    }

}
