<div class="WidgetBoardRecentlySidelist">
	<div class="listTitle">
		<?php echo $titleIcon ? $titleIcon : '<i class="fa fa-download"></i>'; ?> <?php echo $link ? '<b><a href="'.$link.'">'.$title.'</a></b>' : '<b>'.$title.'</b>'; ?>
		<div class="bar"><span></span></div>
	</div>
	
	<ul>
		<?php for ($i=0, $loop=count($lists);$i<$loop;$i++) { ?>
		<li<?php echo $lists[$i]->reg_date > time() - 60*60*24*3 ? ' class="new"' : ''; ?>>
			<a href="<?php echo $lists[$i]->link; ?>">
				<span class="<?php echo $type; ?>"><?php echo $type == 'post' ? ($lists[$i]->ment == 0 ? '' : number_format($lists[$i]->ment)) : $lists[$i]->name; ?></span>
				<i class="fa fa-caret-right"></i><?php echo $lists[$i]->title; ?>
			</a>
		</li>
		<?php } ?>
	</ul>
</div>