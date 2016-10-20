<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 페이지 이동 네비게이션을 출력한다.
 *
 * @file /includes/pagination.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 * @see /classes/iModule.class.php -> printError()
 */

if (defined('__IM__') == false) exit;

$IM->loadWebFont('FontAwesome');
?>
<div data-role="pagination">
	<div>
		<ul>
			<?php if ($prevPageStart === null) { ?>
			<li<?php echo $prevPage == false ? ' class="disabled"' : ''; ?>>
				<a href="<?php echo $prevPage == false ? '#' : str_replace('{PAGE}',$prevPage,$link); ?>"><span class="fa fa-caret-left"></span></a>
			</li>
			<?php } else { ?>
			<li<?php echo $prevPageStart == false ? ' class="disabled"' : ''; ?>>
				<a href="<?php echo $prevPageStart == false ? '#' : str_replace('{PAGE}',$prevPageStart,$link); ?>"><span class="fa fa-angle-double-left"></span></a>
			</li>
			<?php } ?>
		
			<?php for ($i=$startPage;$i<=$endPage;$i++) { ?>
			<li<?php echo $p == $i ? ' class="active"' : ''; ?>>
				<a href="<?php echo str_replace('{PAGE}',$i,$link); ?>" data-page="<?php echo $i; ?>"><span><?php echo $i; ?></span></a>
			</li>
			<?php } ?>
			
			<?php if ($nextPageStart === null) { ?>
			<li<?php echo $nextPage == false ? ' class="disabled"' : ''; ?>>
				<a href="<?php echo $nextPage == false ? '#' : str_replace('{PAGE}',$nextPage,$link); ?>"><span class="fa fa-caret-right"></span></a>
			</li>
			<?php } else { ?>
			<li<?php echo $nextPageStart == false ? ' class="disabled"' : ''; ?>>
				<a href="<?php echo $nextPageStart == false ? '#' : str_replace('{PAGE}',$nextPageStart,$link); ?>"><span class="fa fa-angle-double-right"></span></a>
			</li>
			<?php } ?>
		</ul>
	</div>
</div>