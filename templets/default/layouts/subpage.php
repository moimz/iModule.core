	<div class="nbreadcrumb">
		<div class="container">
			<h3>
			<?php
			if ($IM->page == null) {
				$pageInfo = $IM->getMenus($IM->menu);
				if (isset($pageInfo->context->icon) == true && preg_match('/^fa\-/',$pageInfo->context->icon) == true) {
					$icon = $pageInfo->context->icon;
				} else {
					$icon = 'fa-file-o';
				}
			} else {
				$pageInfo = $IM->getPages($IM->menu,$IM->page);
				if (isset($pageInfo->context->icon) == true && preg_match('/^fa\-/',$pageInfo->context->icon) == true) {
					$icon = $pageInfo->context->icon;
				} else {
					if ($pageInfo->type == 'MODULE') {
						$mPackage = $IM->Module->getPackage($pageInfo->context->module);
						$icon = isset($mPackage->icon) == true ? $mPackage->icon : 'fa-file-o';
					} else {
						$icon = 'fa-file-o';
					}
				}
			}
			echo '<i class="fa '.$icon.'"></i> '.$pageInfo->title;
			?>
			</h3>
			
			<ol>
				<li><a href="<?php echo __IM_DIR__.'/'; ?>"><i class="fa fa-home"></i></a></li>
				<?php if ($IM->menu != null) { ?>
				<li><i class="fa fa-angle-right"></i></li>
				<li<?php echo $IM->page == null ? ' class="current"' : ''; ?>><a href="<?php echo $IM->getUrl(null,false); ?>"><?php echo $IM->getMenus($IM->menu)->title; ?></a></li>
				<?php if ($IM->page != null) { ?>
				<li><i class="fa fa-angle-right"></i></li>
				<li class="current"><a href="<?php echo $IM->getUrl(null,null,false); ?>"><?php echo $IM->getPages($IM->menu,$IM->page)->title; ?></a></li>
				<?php } } ?>
			</ol>
		</div>
	</div>
	
	<div class="container">
		<div class="row">
			<div class="col-md-9">
				<?php echo $context; ?>
			</div>
			
			<div class="col-md-3 hidden-sm hidden-xs">
				<?php $IM->getWidget('member/login')->setTemplet('@sidebar')->doLayout(); ?>
				<div class="blankSpace"></div>
				<?php $IM->getWidget('pagelist')->setTemplet('@sidemenu')->setValue('menu',$IM->menu)->doLayout(); ?>
				<div class="blankSpace"></div>
				
				<?php
				if ($IM->getPages('index','notice') !== null && $IM->getPages('index','notice')->type == 'MODULE' && $IM->getPages('index','notice')->context->module == 'board') {
					$notice = $IM->getWidget('board/recently')->setTemplet('@sidelist')->setValue('type','post')->setValue('bid',$IM->getPages('index','notice')->context->context)->setValue('titleIcon','<i class="fa fa-bell"></i>')->setValue('count',3);
					if ($IM->getPages('index','notice')->context->config != null && $IM->getPages('index','notice')->context->config->category) {
						$notice->setValue('category',$IM->getPages('index','notice')->context->config->category);
					}
					$notice->doLayout();
					echo '<div class="blankSpace"></div>';
				}
				?>
				
				<div class="tabTitle" role="tab" data-type="mouseover">
					<ul>
						<li data-toggle="latestPost" style="width:50%;" class="selected">최근글</li>
						<li data-toggle="latestMent" style="width:50%;">최근댓글</li>
					</ul>
				</div>
				
				<div class="tabContent" role="tabpanel" data-toggle="latestPost">
					<?php $IM->getWidget('article')->setTemplet('@sidelist')->setValue('type','post')->setValue('count',10)->setValue('titleIcon','<i class="fa fa-leaf"></i>')->doLayout(); ?>
				</div>
				
				<div class="tabContent" role="tabpanel" data-toggle="latestMent" style="display:none;">
					<?php $IM->getWidget('article')->setTemplet('@sidelist')->setValue('type','ment')->setValue('count',10)->setValue('titleIcon','<i class="fa fa-comments"></i>')->doLayout(); ?>
				</div>
				
				<div class="blankSpace"></div>
				
				<div style="min-height:600px;">
					<div class="rightFixed">
						<div class="rightFixedInner">
							<?php echo $IM->getSiteTempletConfig('ad_sidebar'); ?>
						</div>
					</div>
					
					<script>$(".rightFixedInner").data("html",<?php echo json_encode($IM->getSiteTempletConfig('ad_sidebar')); ?>);</script>
				</div>
			</div>
		</div>
	</div>