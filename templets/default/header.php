<?php
if (defined('__IM__') == false) exit;
define('__IM_HEADER_INCLUDED__',true);

$temp = explode('.',$_SERVER['HTTP_HOST']);
$domain = $temp[1];

$IM->addWebFont('NanumBarunGothic',true);
$IM->addWebFont('OpenSans');
$IM->addSiteHeader('style',$IM->getTempletDir().'/styles/style.css');
$IM->addSiteHeader('script',$IM->getTempletDir().'/scripts/script.js');
?>
<!DOCTYPE HTML>
<html lang="<?php echo $IM->language; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?php echo $IM->getSiteTitle(); ?></title>
<?php echo $IM->getSiteHeader(); ?>
<?php echo $IM->getSiteTempletConfig('head'); ?>
</head>
<body>

<div id="iModuleWrapper">
	<div id="iModuleAlertMessage"></div>
	
	<header id="iModuleHeader" class="hidden-xs">
		<div class="topmenu">
			<div class="container">
				<ul class="familySite">
					<?php foreach ($IM->getSites(null,$IM->language) as $site) { ?>
					<li><a href="<?php echo $site->is_ssl == 'TRUE' ? 'https' : 'http'; ?>://<?php echo $site->domain; ?>"><?php echo $site->title; ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
		
		<div class="header">
			<div class="container">
				<h1<?php echo $IM->getSiteLogo('default') == null ? ' class="text"' : ' style="background-image:url('.$IM->getSiteLogo('default').');"'; ?>><a href="<?php echo __IM_DIR__.'/'.$IM->language.'/'; ?>"><?php echo $IM->getSite()->title; ?></a></h1>
				
				<div class="topRight">
					<?php echo $IM->getSiteTempletConfig('ad_top'); ?>
				</div>
			</div>
		</div>
	</header>
	
	<div class="naviWrapper">
		<nav id="iModuleNavigation" class="navigation" role="navigation">
			<div class="container">
				<ul class="hidden-xs hidden-sm">
					<?php $menus = $IM->getMenus(); for ($i=0, $loop=count($menus);$i<$loop;$i++) { $pages = $IM->getPages($menus[$i]->menu); ?>
					<li>
						<a href="<?php echo $IM->getUrl($menus[$i]->menu,false); ?>"<?php echo $IM->menu == $menus[$i]->menu ? ' class="selected"' : ''; ?>><?php echo $menus[$i]->title; ?></a>
						<?php if (count($pages) > 0) { ?>
						<ul class="dropdown">
							<?php for ($j=0, $loopj=count($pages);$j<$loopj;$j++) { $pageCountInfo = $IM->getPageCountInfo($pages[$j]); ?>
							<li>
								<?php if ($pages[$j]->type == 'LINK') { ?>
								<a href="<?php echo $pages[$j]->context->link; ?>" target="<?php echo $pages[$j]->context->target; ?>">
									<span class="link"><i class="fa fa-share-square-o"></i></span>
									<?php echo $pages[$j]->title; ?>
								</a>
								<?php } else { ?>
								<a href="<?php echo $IM->getUrl($menus[$i]->menu,$pages[$j]->page,false); ?>">
									<?php if ($pageCountInfo != null) { ?>
									<span class="badge<?php echo $pageCountInfo->latest_date > time() - 60*60*24*3 ? ' new' : ''; ?>"><?php echo isset($pageCountInfo->count) == true ? number_format($pageCountInfo->count) : $pageCountInfo->text; ?></span>
									<?php } ?>
									<?php echo $pages[$j]->title; ?>
								</a>
								<?php } ?>
							</li>
							<?php } ?>
						</ul>
						<?php } ?>
					</li>
					<?php } ?>
				</ul>
				
				<a href="<?php echo __IM_DIR__.'/'; ?>" class="emblem visible-xs-inline-block visible-sm-inline-block"<?php echo $IM->getSite()->emblem !== null ? ' style="background-image:url('.$IM->getSite()->emblem.');"' : ''; ?>><?php echo $IM->getSite()->title; ?></a>
				
				<button class="menu visible-xs-inline-block visible-sm-inline-block" onclick="iModule.slideMenu.toggle(true);"><i class="fa fa-bars"></i> MENU</button>
				
				<div class="menu push" onclick="TogglePush(this);">
					<i class="fa fa-bell"></i>
					<span class="badge" data-push-badge="true"><?php echo $IM->getModule('push')->getPushCount('UNCHECK'); ?></span>
					
					<div class="list">
						<div class="arrowBox">
							<b><?php echo $IM->getModule('push')->getLanguage('title'); ?></b>
							<button><?php echo $IM->getModule('push')->getLanguage('button/config'); ?></button>
							<i></i>
							<button onclick="Push.readAll(event);"><?php echo $IM->getModule('push')->getLanguage('button/read_all'); ?></button>
						</div>
						
						<ul>
							<li class="loading"></li>
							<li class="noitem"><?php echo $IM->getModule('push')->getLanguage('error/notFound'); ?></li>
						</ul>
						
						<?php $pushPage = $IM->getModule('member')->getMemberPage('push'); ?>
						<a href="<?php echo $IM->getUrl($pushPage->menu,$pushPage->page,false); ?>"><?php echo $IM->getModule('push')->getLanguage('button/show_all'); ?></a>
					</div>
				</div>
			</div>
		</nav>
	</div>
	
	<div class="context">