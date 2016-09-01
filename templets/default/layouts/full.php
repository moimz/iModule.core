	<div class="nbreadcrumb">
		<div class="container">
			<h3>
			<?php
			$titleIcon = array('board'=>'file-text-o','dataroom'=>'floppy-o','apidocument'=>'book');
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
						$icon = empty($titleIcon[$pageInfo->context->module]) == true ? 'fa-file-o' : 'fa-'.$titleIcon[$pageInfo->context->module];
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
		<?php echo $context; ?>
	</div>