		<div class="index container">
			<div class="row">
				<div class="col-md-9">
					<div class="notice">
						<section>
							<?php
							if ($IM->getPages('index','notice') !== null && $IM->getPages('index','notice')->type == 'MODULE' && $IM->getPages('index','notice')->context->module == 'board') {
								$notice = $IM->getWidget('board/recently')->setTemplet('@notice')->setValue('type','post')->setValue('bid',$IM->getPages('index','notice')->context->context)->setValue('titleIcon','<i class="fa fa-bell"></i>')->setValue('count',3);
								if ($IM->getPages('index','notice')->context->config != null && $IM->getPages('index','notice')->context->config->category) {
									$notice->setValue('category',$IM->getPages('index','notice')->context->config->category);
								}
								$notice->doLayout();
							}
							?>
						</section>
						
						<aside>
							<?php $IM->getWidget('member/recently')->setTemplet('default')->setValue('photoOnly',true)->setValue('count',16)->doLayout(); ?>
						</aside>
					</div>
					
					<div class="blankSpace"></div>
					
					<div class="row">
						<div class="col-sm-6">
							<?php $IM->getWidget('article')->setTemplet('default')->setValue('type',array('post','question','answer','version'))->setValue('count',10)->setValue('titleIcon','<i class="fa fa-leaf"></i>')->doLayout(); ?>
						</div>
						
						<div class="col-sm-6">
							<?php $IM->getWidget('article')->setTemplet('default')->setValue('type','ment')->setValue('count',10)->setValue('titleIcon','<i class="fa fa-leaf"></i>')->doLayout(); ?>
						</div>
					</div>
				</div>
				
				<div class="col-md-3 hidden-sm hidden-xs">
					<?php $IM->getWidget('member/login')->setTemplet('@sidebar')->doLayout(); ?>
					
					<div style="min-height:600px;">
						<div class="rightFixed">
							<!-- Banner Area (responsive) -->
						</div>
					</div>
				</div>
			</div>
		</div>