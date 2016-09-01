<div class="WidgetArticleSidelist">
	<ul>
		<?php for ($i=0, $loop=count($lists);$i<$loop;$i++) { ?>
		<li<?php echo $lists[$i]->reg_date > time() - 60*60*24*3 ? ' class="new"' : ''; ?>>
			<a href="<?php echo $lists[$i]->link; ?>">
				<?php if (isset($lists[$i]->ment) == true) { ?>
				<?php if ($lists[$i]->module == 'dataroom') { ?>
				<span class="name"><?php echo $lists[$i]->name; ?></span>
				<span class="ment"><?php echo $lists[$i]->last_version; ?></span>
				<?php } else { ?>
				<span class="ment"><?php echo $lists[$i]->ment == 0 ? '' : number_format($lists[$i]->ment); ?></span>
				<?php } ?>
				<?php } else { ?>
				<span class="name"><?php echo $lists[$i]->name; ?></span>
				<?php } ?>
				
				<i class="fa fa-caret-right"></i>
				<?php if ($lists[$i]->module == 'qna') { ?>
				<span class="type <?php echo $lists[$i]->type; ?><?php echo $lists[$i]->is_select == 'TRUE' ? ' selected' : ''; ?>"><?php echo substr(strtoupper($lists[$i]->type),0,1); ?></span>
				<?php } ?>
				<?php echo isset($lists[$i]->title) == true ? $lists[$i]->title : $lists[$i]->search; ?>
			</a>
		</li>
		<?php } ?>
	</ul>
</div>