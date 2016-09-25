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

/**
 * 템플릿 전용 자바스크립트와 스타일시트를 불러온다.
 * common.css 파일은 iModule core 에 의하여 자동으로 로딩된다.
 * @see /styles/common.css
 * @see /templets/default/styles/common.css.sample
 */
$IM->addHeadResource('style',$IM->getTempletDir().'/styles/style.css');
$IM->addHeadResource('script',$IM->getTempletDir().'/scripts/script.js');
?>
<!DOCTYPE HTML>
<html lang="<?php echo $IM->language; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width">
<title><?php echo $IM->getSiteTitle(); ?></title>
<?php
/**
 * 사이트 <HEAD> 태그 내부의 리소스를 출력한다.
 * <HEAD> 태그 내부 리소스는 iModule core 에 의하여 관리됩니다.
 * @see /classes/iModule.class.php -> getHeadResource()
 */
echo $IM->getHeadResource();

/**
 * 사이트 템플릿 설정에서 head 값을 가져온다.
 * @see /templets/default/package.json -> configs
 * @see /classes/iModule.class.php -> getSiteTempletConfig()
 */
echo $IM->getSiteTempletConfig('head');
?>
</head>
<body>

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