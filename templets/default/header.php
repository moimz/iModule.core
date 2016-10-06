<?php
/**
 * 이 파일은 iModule 사이트템플릿(default)의 일부입니다. (https://www.imodule.kr)
 *
 * iModule 사이트 템플릿으로 iModule 코어에 포함되어 있는 기본템플릿은 주석이 있다.
 * 주석이 없는 템플릿은 iModule 웹사이트에서 다운로드 받을 수 있다.
 * 
 * @file /templets/default/header.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 */
if (defined('__IM__') == false) exit;

/**
 * 헤더가 출력되었는지 확인하기 위한 상수 정의
 */
define('__IM_HEADER_INCLUDED__',true);

/**
 * 언어셋에 따라 웹폰트를 불러온다.
 */
if ($IM->language == 'ko') {
	$IM->loadWebFont('NanumBarunGothic',true);
	$IM->loadWebFont('OpenSans');
} else {
	$IM->loadWebFont('OpenSans',true);
}
?>
<header style="width:300px; overflow:hidden;">
	<?php
	/**
	 * 사이트로고를 가져온다.
	 * 사이트로고가 없을 경우 사이트타이틀을 출력한다.
	 * @see /classes/iModule.class.php -> getSiteLogo()
	 */
	?>
	<h1><?php echo $IM->getSiteLogo('default') == null ? $IM->getSiteTitle() : '<img src="'.$IM->getSiteLogo('default').'" alt="'.$IM->getSiteTitle().'">'; ?></h1>
	
	<nav>
		<ul>
			<?php
			/**
			 * 전체 사이트메뉴를 가져와 메뉴링크를 만든다.
			 * @see /classes/iModule.class.php -> getSitemap()
			 * @see /classes/iModule.class.php -> getUrl()
			 */
			foreach ($IM->getSitemap() as $menu) {
				/**
				 * 메뉴에 아이콘이 설정되어 있을 경우, 아이콘을 가져온다.
				 * @see /classes/iModule.class.php -> parseIconString()
				 */
				$icon = $IM->parseIconString($menu->icon);
			?>
			<li>
				<a href="<?php echo $IM->getUrl($menu->menu,false); ?>"><?php echo $icon.$menu->title; ?></a>
				
				<?php
				/**
				 * 2차 메뉴가 있다면 를 가져온다.
				 */
				if (count($menu->pages) > 0) {
				?>
				<ul>
					<?php
					foreach ($menu->pages as $page) {
						/**
						 * 메뉴에 아이콘이 설정되어 있을 경우, 아이콘을 가져온다.
						 * @see /classes/iModule.class.php -> parseIconString()
						 */
						$icon = $IM->parseIconString($page->icon);
					?>
					<li>
						<a href="<?php echo $IM->getUrl($page->menu,$page->page,false); ?>"><?php echo $icon.$page->title; ?></a>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</li>
			<?php } ?>
		</ul>
	</nav>
</header>

<div class="context">