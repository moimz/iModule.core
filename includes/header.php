<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 사이트 레이아웃을 구성하기 위한 기본 헤더파일로 기본 HTML 을 출력한다.
 * 
 * @file /includes/header.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 */
?>
<!DOCTYPE HTML>
<html lang="<?php echo $IM->language; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width">
<title><?php echo $IM->getSiteTitle(); ?></title>
<?php echo $IM->getHeadResource(); ?>
</head>
<body>
	<!-- iModule Wrapper -->
	<div data-role="wrapper">