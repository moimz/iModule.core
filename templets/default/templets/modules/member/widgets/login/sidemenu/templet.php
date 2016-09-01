<div class="WidgetMemberLoginSideMenu">
	<?php if ($Module->isLogged() == false) { ?>
	<div class="logout">
		<div class="inputBlock"><input type="email" name="email" placeholder="<?php echo $Module->getLanguage('info/email'); ?>" class="inputControl"></div>
		<div class="inputBlock"><input type="password" name="password" placeholder="<?php echo $Module->getLanguage('info/password'); ?>" class="inputControl"></div>
		
		<div class="buttons">
			<div class="social">
				<a href="<?php echo $IM->getProcessUrl('member','google'); ?>" class="btn btnGoogle"><i class="fa fa-google-plus"></i></a>
			</div>
			
			<div class="social">
				<a href="<?php echo $IM->getProcessUrl('member','facebook'); ?>" class="btn btnFacebook"><i class="fa fa-facebook-f"></i></a>
			</div>
			
			<div class="social">
				<a href="<?php echo $IM->getProcessUrl('member','github'); ?>" class="btn btnGithub"><i class="fa fa-github-alt"></i></a>
			</div>
			
			<div class="button">
				<button type="submit" class="btn btnRed" data-loading="<?php echo $Module->getLanguage('login/login_loading'); ?>"><i class="fa fa-lock"></i> <?php echo $Module->getLanguage('login/login'); ?></button>
			</div>
		</div>
		
		<div class="bottom">
			<label class="autoLogin"><input type="checkbox" name="auto"> <?php echo $Module->getLanguage('login/auto'); ?></label>
			<div class="link">
				<a href="<?php echo $signupUrl; ?>"><?php echo $Module->getLanguage('signup/title'); ?></a>
				<span>|</span>
				<a href="<?php echo $findUrl; ?>"><?php echo $Module->getLanguage('find/title'); ?></a>
			</div>
		</div>
	</div>
	<?php } else { ?>
	<div class="login">
		<div class="info">
			<div class="photo"><img src="<?php echo $member->photo; ?>" alt="<?php echo $member->nickname; ?>"></div>
			<div class="detail">
				<div class="nickname">
					<span class="reg_date">since <?php echo GetTime('y.m.d',$member->reg_date); ?></span><?php echo $member->nickname; ?>
				</div>
				
				<div class="level">
					<div class="text">LV.<b><?php echo $member->level->level; ?></b></div>
					<div class="graph">
						<div class="bar">
							<div class="percentage" style="width:<?php echo $member->level->exp / $member->level->next * 100; ?>%;"></div>
							
							<div class="levelDetail">
								<div class="arrowBox"><?php echo number_format($member->level->exp); ?>/<?php echo number_format($member->level->next); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="blocks">
			<div class="item">
				<i class="fa fa-inbox"></i> <?php echo $Module->getLanguage('messagebox/title'); ?> : <span class="number"><b>0</b>/1,000</span>
			</div>
			<div class="item">
				<a href="<?php echo $pushUrl; ?>"><i class="fa fa-rss"></i> <?php echo $Module->getLanguage('push/title'); ?> : <span class="number"><b><?php echo number_format($IM->getModule('push')->getPushCount('UNREAD')); ?></b>/<?php echo number_format($IM->getModule('push')->getPushCount('ALL')); ?></span></a>
			</div>
		</div>
		
		<div class="blocks">
			<div class="item">
				<a href="<?php echo $pointUrl; ?>"><i class="fa fa-rub"></i> <?php echo $Module->getLanguage('info/point'); ?> : <span class="number"><b><?php echo number_format($member->point); ?></b></span></a>
			</div>
			<div class="item">
				<i class="fa fa-trophy"></i> <?php echo $Module->getLanguage('info/exp'); ?> : <span class="number"><b><?php echo number_format($member->exp); ?></b></span>
			</div>
		</div>
		
		<button type="submit" class="btn btnRed" data-loading="<?php echo $Module->getLanguage('login/logout_loading'); ?>" onclick="Member.logout(this);"><i class="fa fa-power-off"></i> <?php echo $Module->getLanguage('login/logout'); ?></button>
		
		<div class="bottom">
			<div class="mypage"><a href="<?php echo $mypageUrl; ?>"><?php echo $Module->getLanguage('mypage/title'); ?></a></div>
			<div class="link">
				<a href="<?php echo $modifyUrl; ?>"><?php echo $Module->getLanguage('modify/title'); ?></a>
				<span>|</span>
				<a href="<?php echo $configUrl; ?>"><?php echo $Module->getLanguage('config/title'); ?></a>
			</div>
		</div>
	</div>
	<?php } ?>
</div>