<?php
if (defined('__IM__') == false) exit;
define('__IM_FOOTER_INCLUDED__',true);
?>
	</div>
	
	<div class="footer">
		<div class="menu">
			<div class="container">
				<ul>
					<?php foreach ($IM->getFooterPages() as $footer) { ?>
					<li><a href="<?php echo $IM->getUrl($footer->menu,$footer->page,false); ?>"><?php echo $footer->title; ?></a></li>
					<?php } ?>
				</ul>
				
				<button class="top" onclick="$('html, body').animate({scrollTop:0},'fast');"><i class="fa fa-caret-up"></i> TOP</button>
			</div>
		</div>
		
		<div class="copyright">
			<div class="container">
				<?php if ($IM->getSiteLogo('footer') != null) { ?>
				<div class="logo" style="background-image:url(<?php echo $IM->getSiteLogo('footer'); ?>);"></div>
				<?php } elseif ($IM->getSiteEmblem() != null) { ?>
				<div class="logo" style="background-image:url(<?php echo $IM->getSiteEmblem(); ?>);"></div>
				<?php } ?>
				<div class="text">
					<div class="hidden-xs">
						<?php echo $IM->getSiteTempletConfig('company'); ?> | <?php echo $IM->getSiteTempletConfig('address'); ?> | <a href="mailto:<?php echo $IM->getSiteTempletConfig('contact'); ?>"><?php echo $IM->getSiteTempletConfig('contact'); ?></a><br>
						Copyright<i class="fa fa-copyright"></i> <?php echo str_replace('{year}',date('Y'),$IM->getSiteTempletConfig('copyright')); ?>. All rights reserved.
					</div>
					
					<div class="visible-xs">
						<?php echo $IM->getSiteTempletConfig('company'); ?> | <a href="mailto:<?php echo $IM->getSiteTempletConfig('contact'); ?>"><?php echo $IM->getSiteTempletConfig('contact'); ?></a><br>
						<i class="fa fa-copyright"></i> <?php echo str_replace('{year}',date('Y'),$IM->getSiteTempletConfig('copyright')); ?>
					</div>
				</div>
				<div class="social">
					<?php if ($IM->getSiteTempletConfig('twitter')) { ?><a href="<?php echo $IM->getSiteTempletConfig('twitter'); ?>" target="_blank"><i class="fa fa-twitter-square"></i></a><?php } ?>
					<?php if ($IM->getSiteTempletConfig('facebook')) { ?><a href="<?php echo $IM->getSiteTempletConfig('facebook'); ?>" target="_blank"><i class="fa fa-facebook-square"></i></a><?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<nav id="iModuleSlideMenu" class="sidemenu" role="navigation">
	<div>
		<div class="loginform">
			<?php $IM->getWidget('member/login')->setTemplet('@sidemenu')->doLayout(); ?>
		</div>
		
		<ul>
		<?php $menus = $IM->getMenus(); for ($i=0, $loop=count($menus);$i<$loop;$i++) { $pages = $IM->getPages($menus[$i]->menu); ?>
			<li class="menu<?php echo $IM->menu == $menus[$i]->menu ? ' opened' : ''; ?>">
				<a href="<?php echo $IM->getUrl($menus[$i]->menu,false); ?>">
					<?php if (count($pages) > 0) { ?><i class="fa fa-chevron-up"></i><i class="fa fa-chevron-down"></i><?php } ?>
					<i class="fa fa-plus"></i><i class="fa fa-minus"></i>
					&nbsp;&nbsp;<?php echo $menus[$i]->title; ?>
				</a>
				<?php if (count($pages) > 0) { ?>
				<ul>
					<?php for ($j=0, $loopj=count($pages);$j<$loopj;$j++) { ?>
					<li class="page">
						<?php if ($pages[$j]->type == 'LINK') { ?>
						<a href="<?php echo $pages[$j]->context->link; ?>" target="<?php echo $pages[$j]->context->link; ?>"><i class="fa fa-caret-right"></i>&nbsp;&nbsp;<?php echo $pages[$j]->title; ?></a>
						<?php } else { ?>
						<a href="<?php echo $IM->getUrl($menus[$i]->menu,$pages[$j]->page,false); ?>"><i class="fa fa-caret-right"></i>&nbsp;&nbsp;<?php echo $pages[$j]->title; ?></a>
						<?php } ?>
					</li>
					<?php } ?>
				</ul>
				<?php } ?>
			</li>
		<?php } ?>
		</ul>
		
		<div style="padding:5px;">
			<?php echo $IM->getSiteTempletConfig('ad_slide'); ?>
		</div>
	</div>
</nav>

<?php echo $IM->getSiteTempletConfig('body'); ?>
</body>
</html>