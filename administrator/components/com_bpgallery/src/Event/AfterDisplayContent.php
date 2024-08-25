<?php

namespace BPExtensions\Component\BPGallery\Administrator\Event;

use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class for BPGallery event.
 * Example:
 *  new ImagePrepareEvent('onEventName', ['context' => 'com_example.example', 'subject' => $contentObject, 'params' => $params]);
 */
class AfterDisplayContent extends AbstractImmutableEvent
{

    /**
     * Getter for the item argument.
     *
     * @return  object
     */
    public function getItem(): object
    {
        return $this->arguments['subject'];
    }

    /**
     * Getter for the item argument.
     *
     * @return  Registry
     */
    public function getParams(): Registry
    {
        return $this->arguments['params'];
    }

    /**
     * Setter for the subject argument.
     *
     * @param   object  $value  The value to set
     *
     * @return  object
     */
    protected function onSetSubject(object $value): object
    {
        return $value;
    }

    /**
     * Setter for the params argument.
     *
     * @param   Registry  $value  The value to set
     *
     * @return  Registry
     */
    protected function onSetParams($value): Registry
    {
        // This is for b/c compatibility, because some extensions pass a mixed types
        if (!$value instanceof Registry) {
            $value = new Registry($value);
        }

        return $value;
    }
}
