<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 사이트를 구성할 수 없는 치명적인 에러가 발생할 경우 사이트 레이아웃 구성을 모두 취소하고 이 에러페이지를 출력합니다.
 * 사이트 레이아웃을 구성할 수 있는 에러일 경우 사이트 템플릿의 에러메세지 템플릿이나 모듈 에러메세지 이용하여 에러메세지를 출력하게 됩니다.
 *
 * @file /classes/iModule.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 * @see /classes/iModule.class.php -> printError()
 */

$IM->loadWebFont('OpenSans');
?>
<div class="errorbox">
	<h1><i class="mi mi-attention-o"></i> ERROR!</h1>
	
	<h2><?php echo $message; ?></h2>
	<?php if ($description) { ?><p><?php echo $description; ?></p><?php } ?>
	
	<a href="<?php echo $IM->getUrl(false); ?>"><?php echo $IM->getText('button/back_to_main'); ?></a>
</div>