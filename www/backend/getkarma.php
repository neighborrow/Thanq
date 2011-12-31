<?php

	$id = $System->REST->request[1];

	if(intval($id))
		$user = $System->UserHandler->getUserById($id);
	else
		$user = $System->UserHandler->getUserByHash($id);

	$getkarma = $user->getKarma();

	$justgave = $_SESSION["just_gave"];

	$_SESSION["just_gave"] = false;
?>
<!DOCTYPE html>
<style>
.submitted-karma-instance { display: none; }
body { font-family: sans-serif; }
</style>
<title><?= $System->UserHandler->ids[$id]->display ?>'s Compliments</title>
<!--
<a style="position:absolute;top:20px;right:20px;padding:5px;border:1px solid black;border-radius:5px;display:inline-block;" href="/">
  Praise Someone
</a>
-->
<div style="clear:both;padding:10px;margin:10px;width:894px;">
  <a href="/postcard">Thank Someone</a> | <a href="http://share-the-oxytocin.blogspot.com/2010/12/positive-feedback-loop.html">Rules</a>
</div>
<div style="border:1px solid black;padding:10px;margin:10px;width:894px;position;relative;">
<?php if($user->id == 2): ?>
<div style="float:left;margin-right:20px;margin-top:-30px;">
<br /><br />
<!-- BEGIN DUCK DUCK GO KARMA WIDGET CODE //-->
<script type="text/javascript">
ddg_k_title='My Karma';
ddg_k_bold_karma='1';
ddg_k_link_karma='0';
ddg_k_link_label='0';
ddg_k_show_username='0';
ddg_k_show_service_name='0';
ddg_k_paren_karma='0';
ddg_k_vertical_spacing='5';
ddg_k_column_width='150';
ddg_k_padding='5';
ddg_k_font_size='9pt';
ddg_k_font_color='';
ddg_k_font_family='';
ddg_k_border='1px solid #AAA';
</script>
<div id="ddg_k" style="width:150px;font-size:9pt;border:1px solid #AAA;padding:5px;text-align:left;">-<div style="text-align: right; font-size: 80%; padding-top: 5px;">by <a style="display:inline;list-style-type:none;" href="http://duckduckgo.com/">DuckDuckGo</a></div></div>
<script type="text/javascript" src="http://karma.duckduckgo.com/k.js?t=D,G,6&u=adamberk,neighborrow,neighborrow"></script>
</div>
<?php endif; ?>
<h1>
<?= $user->display ?> (<?= $user->getKarmaCountDisplay() ?>) $ <a href="http://share-the-oxytocin.blogspot.com/2011/03/what-do-these-numbers-mean.html">?</a>
</h1>
<?php if(!$user->verified): ?><span>Is this you? <a href="http://u.vrfy.me/" target="_blank">Verify Account</a></span><br /><br /><?php else: ?><span>Verified user @<?php $str = explode("@",$user->email); echo $str[1]; ?></span><br /><br /><?php endif; ?>
</div>
<!--
<hr />
<em>Unverified compliments submitted "by the crowd"</em> (<a href="/">Submit Your Own</a>)
-->
<div style="position:relative;min-height:250px;">
<div style="position: absolute;top:0px;left:600px;text-align:center;background-color:white;border:1px solid black;padding:10px;">
Share your compliments!<br /><br />
<span class="st_twitter_large" displayText="Tweet"></span><span class="st_facebook_large" displayText="Facebook"></span><span class="st_ybuzz_large" displayText="Yahoo! Buzz"></span><span class="st_gbuzz_large" displayText="Google Buzz"></span><span class="st_email_large" displayText="Email"></span><span class="st_sharethis_large" displayText="ShareThis"></span>
<br /><br />
<!-- <div style="text-align:left;">
If this is your profile, post it to social networks<br />
using the buttons above, or just cut and paste the<br />
link to any bio section that allows links.  Show<br />
the web how wonderful you are and increase the<br />
chances of winning the first contest.  If you just<br />
left this review, use the envelope icon to alert <br />
the person - we will do that for you when we <br />
upgrade next.
</div> -->
<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script><script type="text/javascript">stLight.options({publisher:'e106fdd6-549f-4532-b787-9553e2eead0f'});</script>
</div>
<div style="margin:10px;padding:5px;"><a href="#" onclick="$('.submitted-karma-instance').each(function(index) { $(this).show(); });$(this).hide();">Show all</a></div>
<?php $i = 0; if(count($user->karma)) { ?>
<?php foreach($user->karma as $karma) { $i++ ?>
<div style="margin:10px;padding:5px;border:1px solid black;width:570px;" class="<?php if($user->id == $karma["submitted_uid"]): ?>submitted-karma-instance<?php else: ?>received-karma-instance<?php endif; ?>">
	<div style="float:right;padding:5px;display:inline-block;"><a href="/user/<?= $karma["submitted_uid"] ?>"><img src="<?= $System->UserHandler->ids[$karma["submitted_uid"]]->gravatar ?>"></a></div>
	<div style="float:left;padding:5px;display:inline-block;"><a href="/user/<?= $karma["referenced_uid"] ?>"><img src="<?= $System->UserHandler->ids[$karma["referenced_uid"]]->gravatar ?>"></a></div>
	<div style="float:left;width:440px;padding:5px;display:inline-block;"><a href="/user/<?= $karma["referenced_uid"] ?>"><?= $System->UserHandler->ids[$karma["referenced_uid"]]->display ?></a> is <strong><?= $karma["adjective"] ?></strong> because <em><?= $karma["reason"] ?></em><br /><br /><div style="text-align:right;"><?php if($karma["submitted_uid"] != null) { ?><a href="/user/<?= $karma["submitted_uid"] ?>"><?= $System->UserHandler->ids[$karma["submitted_uid"]]->display ?></a><?php } else { ?>Unknown User<?php } ?> <em>via <a href="<?= $karma["connection"]->url ?>"><?= $karma["connection"]->name ?></a></em></div></div>
	<br style="clear:both;" />
</div>
<?php if($i == 1 && $justgave): ?>
<div style="position:relative;top:-11px;margin:10px;padding:5px;border:1px solid black;border-top:0px;background-color:#E0E0E0;width:570px;">
	This is the compliment you just gave!  Would you like to <em><a href="/">give another</a></em>?
</div>
<?php endif; ?>
<?php } ?>
<?php } ?>
</div>
<div style="border:1px solid black;padding:10px;margin:10px;width:894px;">
  <span style="color:grey;">Verified Activity from Partner Sites: Coming Soon</span><br />
</div>
<script src="http://code.jquery.com/jquery-1.6.1.min.js"></script>