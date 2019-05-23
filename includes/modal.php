<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * 모달창을 출력한다.
 *
 * @file /includes/modal.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 5. 23.
 */
if (defined('__IM__') == false) exit;
?>
<div data-role="modal" data-closable="<?php echo $is_closable == true ? 'TRUE' : 'FALSE'; ?>" data-fullsize="<?php echo $is_fullsize == true ? 'TRUE' : 'FALSE'; ?>" data-width="<?php echo $width; ?>" data-height="<?php echo $height; ?>" data-max-width="<?php echo $max_width; ?>" data-max-height="<?php echo $max_height; ?>">
	<?php echo $header; ?>
	<div data-role="header">
		<h1><?php echo $title; ?></h1>
		<button type="button" data-action="close"><i class="mi mi-close"></i></button>
	</div>
	
	<div data-role="context">
		<?php echo $content; ?>
	</div>
	
	<div data-role="footer">
		<?php foreach ($buttons as $button) { ?>
		<div><button <?php echo $button->type == 'submit' ? 'type="submit"' : 'type="button" data-action="'.$button->type.'"'; ?><?php echo isset($button->class) == true && $button->class ? ' class="'.$button->class.'"' : ''; ?>><?php echo $button->text; ?></button></div>
		<?php } ?>
	</div>
	<?php echo $footer; ?>
</div>