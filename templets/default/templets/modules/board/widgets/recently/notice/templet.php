<div class="WidgetBoardRecentlyNotice">
	<div class="listTitle">
		<?php echo $titleIcon ? $titleIcon : '<i class="fa fa-download"></i>'; ?> <?php echo $link ? '<b><a href="'.$link.'">'.$title.'</a></b>' : '<b>'.$title.'</b>'; ?>
		<div class="bar"><span></span></div>
	</div>
	
	<?php for ($i=0;$i<count($lists);$i++) { $preview = $lists[$i]; ?>
	
	<section class="preview">
		<aside>
			<div class="day">
				<div class="date"><?php echo GetTime('d',$preview->reg_date); ?></div>
				
				<div class="month"><?php echo GetTIme('M',$preview->reg_date); ?></div>
				<div class="year">'<?php echo GetTIme('y',$preview->reg_date); ?></div>
			</div>
		</aside>
		
		<article>
			<h4><a href="<?php echo $lists[$i]->link; ?>"><?php echo $lists[$i]->title; ?></a></h4>
			
			<div class="content">
				<?php echo $preview->content; ?>
			</div>
		</article>
	</section>
	
	<?php break; } ?>
	
	<ul>
		<?php for ($i=1, $loop=count($lists);$i<$loop;$i++) { ?>
		<li<?php echo $lists[$i]->reg_date > time() - 60*60*24*3 ? ' class="new"' : ''; ?>>
			<a href="<?php echo $lists[$i]->link; ?>">
				<span class="reg_date"><?php echo GetTime('M d, Y',$lists[$i]->reg_date); ?></span>
				<i class="fa fa-caret-right"></i><?php echo $lists[$i]->title; ?>
			</a>
		</li>
		<?php } ?>
	</ul>
</div>