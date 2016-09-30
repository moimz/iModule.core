<?php
/**
 * 이 파일은 iModule 사이트템플릿(default)의 일부입니다. (https://www.imodule.kr)
 *
 * iModule 사이트 템플릿의 컨텍스트를 구성하기 위한 레이아웃파일로 사이트관리자에서 [서브페이지 (상단에 네비게이션바 및 우측에 페이지목록이 포함되어 있습니다.)] 레이아웃을 선택한 메뉴에 사용된다.
 * 
 * @file /templets/default/layouts/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160906
 */
if (defined('__IM__') == false) exit;
?>
<main>
	<?php
	/**
	 * 이 레이아웃에서 컨텍스트가 들어갈 위치에 컨텍스트 HTML 을 출력한다.
	 * @see /classes/iModule.class.php -> getContextLayout()
	 */
	echo $context;
	?>
</main>
<input type="radio" name="label" value="1000">으아아아아아아</input>
<aside>
	<?php
	/**
	 * 현재 메뉴의 서브메뉴(2차메뉴)가 있을 경우, 서브메뉴 네비게이션을 출력한다.
	 * @see /classes/iModule.class.php -> getPages()
	 */
	$pages = $IM->getPages($IM->menu);
	if (count($pages) > 0) {
	?>
	<ul>
		<?php
		foreach ($pages as $page) {
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
</aside>