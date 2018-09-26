<?php

/**
 * @author		${author.name} (${author.email})
 * @website		${author.url}
 * @copyright	${copyrights}
 * @license		${license.url} ${license.name}
 */

defined('_JEXEC') or die;

$this->subtemplatename = 'items';
echo JLayoutHelper::render('joomla.content.category_default', $this);
