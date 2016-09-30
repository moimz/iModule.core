<?php
/**
 * 이 파일은 iModule 사이트템플릿(default)의 일부입니다. (https://www.imodule.kr)
 *
 * iModule 사이트 템플릿의 외부PHP 파일로 직접 PHP파일을 작성해 사이트에 추가할때 사용한다.
 * 사이트관리자에서 외부페이지를 선택하고, /templets/default/externals/index.php 파일을 선택한 메뉴에 사용된다.
 * 
 * @file /templets/default/externals/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160906
 */
?>
Welcome to index page.

<select name="category">
			<option value="hi">hi</option>
			<?php for ($i=1;$i<=20;$i++) { ?>
			<option value="hello-<?php echo $i; ?>">hello-<?php echo $i; ?></option>
			<?php } ?>
			<option value="hello2" selected="selected">hell2o</option>
		</select>

<form style="width:500px; margin:0 auto;">
	<div data-role="input">
		<select name="category">
			<option value="hi">hi</option>
			<?php for ($i=1;$i<=20;$i++) { ?>
			<option value="hello-<?php echo $i; ?>">hello-<?php echo $i; ?></option>
			<?php } ?>
			<option value="hello2" selected="selected">hell2o</option>
		</select>
	</div>
	
	<div data-role="inputset" class="inline">
		<div data-role="input" style="width:150px;">
			<select name="category2">
				<option value="hi">hi</option>
				<option value="hello">hello</option>
				<option value="hello2" selected="selected">hell2o</option>
			</select>
		</div>
		
		<div data-role="input" style="width:200px;">
			<input type="date">
		</div>
		
		<div data-role="input" style="width:100px;">
			<input type="text">
		</div>
	</div>
	
	<div data-role="input">
		<input type="text">
	</div>
	
	<div data-role="input">
		<label><input type="checkbox">안녕</label>
	</div>
	
	<div data-role="inputset" class="inline">
		<div data-role="input">
			<label><input type="radio" name="say" value="hello" checked="checked">안녕</label>
		</div>
		
		<div data-role="input">
			<label><input type="radio" name="say" value="bye" checked="checked">바이</label>
		</div>
	</div>
	
	<div data-role="input">
		<input type="date" data-format="YYYY-MM-DD">
	</div>
</form>


<div style="width:300px; margin:0 auto;">
	
	
	<div id="test" style="font-size:16px; font-family:AppleSDGothicNeo; line-height:1.6;">
	전국 원전 정상가동, 인명피해는 없어…119 신고전화 빗발쳐<br><br>
	
	(전국종합=연합뉴스) 전준상 최평천 기자 = 12일 오후 7시 44분과 오후 8시 32분에 경북 경주에서 각각 규모 5.1, 5.8의 강력한 지진이 잇따라 발생했다.<br><br>
	
	규모 5.8의 지진은 한반도에서 발생한 지진 가운데 역대 가장 강력한 규모다.<br><br>
	
	이번 두 차례 지진으로 경남, 경북, 충남, 충북, 대전, 제주, 부산, 강원, 서울, 세종 등 전국 곳곳에서 강한 진동이 감지됐다.<br><br>
	
	전국에서 시민들이 강력한 진동을 느낀 뒤 불안감을 호소하는 가운데 119에 신고전화가 빗발쳤다.<br><br>
	
	이슈경주서 규모 5.8 지진 발생<br><br>
	기상청 "진도 2∼3 여진 45차례..더 늘어날 수도"<br><br>
	연합뉴스 | 입력 2016.09.12. 23:03 | 수정 2016.09.12. 23:04<br><br>
	댓글29카카오스토리트위터페이스북<br><br>
	툴바 메뉴<br><br>
	폰트변경하기<br><br>
	폰트 크게하기<br><br>
	폰트 작게하기<br><br>
	메일로 보내기<br><br>
	인쇄하기<br><br>
	스크랩하기<br><br>
	고객센터 이동<br><br>
	(서울=연합뉴스) 박경준 기자 = 12일 오후 8시 32분 경북 경주시 인근에서 발생한 5.8의 역대 최대규모 지진으로 인한 여진이 45여 회로 늘어났다.<br><br>
	
	기상청 관계자는 "오후 10시 50분 현재 진도 2.0에서 3.0 규모의 여진이 45차례 내외로 발생했다"며 이같이 밝혔다.<br><br>
	
	이 관계자는 "정밀 분석 결과에 따라 여진 횟수는 더 늘어날 수도 있다"고 말했다.<br><br>
	
	기상청은 앞서 오후 9시 20분에 열린 브리핑 당시에는 진도 2∼3의 여진이 22회 발생했다고 설명했다.<br><br>
	</div>
	
	<div id="test2" style="font-size:14px; line-height:1.6;">
	전국 원전 정상가동, 인명피해는 없어…119 신고전화 빗발쳐<br><br>
	
	(전국종합=연합뉴스) 전준상 최평천 기자 = 12일 오후 7시 44분과 오후 8시 32분에 경북 경주에서 각각 규모 5.1, 5.8의 강력한 지진이 잇따라 발생했다.<br><br>
	
	규모 5.8의 지진은 한반도에서 발생한 지진 가운데 역대 가장 강력한 규모다.<br><br>
	
	이번 두 차례 지진으로 경남, 경북, 충남, 충북, 대전, 제주, 부산, 강원, 서울, 세종 등 전국 곳곳에서 강한 진동이 감지됐다.<br><br>
	
	전국에서 시민들이 강력한 진동을 느낀 뒤 불안감을 호소하는 가운데 119에 신고전화가 빗발쳤다.<br><br>
	
	이슈경주서 규모 5.8 지진 발생<br><br>
	기상청 "진도 2∼3 여진 45차례..더 늘어날 수도"<br><br>
	연합뉴스 | 입력 2016.09.12. 23:03 | 수정 2016.09.12. 23:04<br><br>
	댓글29카카오스토리트위터페이스북<br><br>
	툴바 메뉴<br><br>
	폰트변경하기<br><br>
	폰트 크게하기<br><br>
	폰트 작게하기<br><br>
	메일로 보내기<br><br>
	인쇄하기<br><br>
	스크랩하기<br><br>
	고객센터 이동<br><br>
	(서울=연합뉴스) 박경준 기자 = 12일 오후 8시 32분 경북 경주시 인근에서 발생한 5.8의 역대 최대규모 지진으로 인한 여진이 45여 회로 늘어났다.<br><br>
	
	기상청 관계자는 "오후 10시 50분 현재 진도 2.0에서 3.0 규모의 여진이 45차례 내외로 발생했다"며 이같이 밝혔다.<br><br>
	
	이 관계자는 "정밀 분석 결과에 따라 여진 횟수는 더 늘어날 수도 있다"고 말했다.<br><br>
	
	기상청은 앞서 오후 9시 20분에 열린 브리핑 당시에는 진도 2∼3의 여진이 22회 발생했다고 설명했다.<br><br>
	</div>
</div>