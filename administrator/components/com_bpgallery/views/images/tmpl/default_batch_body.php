<?php

/**
 * @author		${author.name} (${author.email})
 * @website		${author.url}
 * @copyright	${copyrights}
 * @license		${license.url} ${license.name}
 */

defined('_JEXEC') or die;

$published = $this->state->get('filter.published');
?>

<div class="row-fluid">
	<div class="control-group span6">
		<div class="controls">
			<?php echo JHtml::_('batch.language'); ?>
		</div>
	</div>
</div>
<div class="row-fluid">
	<?php if ($published >= 0) : ?>
		<div class="control-group span6">
			<div class="controls">
				<?php echo JHtml::_('batch.item', 'com_bpgallery'); ?>
			</div>
		</div>
	<?php endif; ?>
</div>
