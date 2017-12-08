<?php 
	header('Location: http://altodeco.fr/alertdeco/balises.php');
  	exit();
?>
<?php require_once('common/header-inc.php');?>

<?php

	$alertDeco  = new AlertDeco($GLOBALS['db']);
	$alert 		= "";

	//enregistrement des informations de mise à jour
	if(isset($_SESSION['jeton']) && isset($_POST['jeton']) && $_SESSION['jeton'] == $_POST['jeton'])
	{
		$alertDeco->insertMAJ($_POST);
		$alert = "<div class=\"alert alert-success\" role=\"alert\">Mise à jour enregistrée ! Merci</div>";
		unset($_SESSION['jeton']);

		//set pseudo in session
		if($_POST['f_pseudo']!="")$_SESSION['pseudo']=$_POST['f_pseudo'];

	}

	$infos = $alertDeco->getLastMaj();

	//read - unread info
	$items = array();
	$new   = array();
	$read  = array();
	$today = strtotime('today GMT');
	$cnt   = count($infos);

	if(isset($_SESSION['read']))$read = $_SESSION['read'];

	if($cnt>0){

		foreach ($infos as $value) {
			$items[$today][] = $value['id'];
		}

		if(array_key_exists($today, $read))
		{
			$read 	= cleanOldItem($read, $today);
			$cnt 	= 0;

			foreach ($items[$today] as $value) {
				if(!in_array($value, $read[$today])){
					$read[$today][] = $value;
					$new[] = $value;
					$cnt++;
				}
			}

		}else
		{
			$read = $items;

			foreach ($items[$today] as $value) {
				$new[] = $value;
			}
		}

		$_SESSION['read'] = $read;

	}

	//-----------------------------------------------------WEATHER - PiouPiou
	$ids 	= array(65 => 'NE', 195 => 'SO' );
	$op		= "";

	foreach ($ids as $key => $value) {

		$pioupiou 	= AlertDeco::getPiouPiou($key);
		$dWeather 	= $pioupiou['data']['measurements'];

		//time
		$dt 		= DateTime::createFromFormat('Y-m-d\TH:i:s+', $dWeather['date']);
		$wTime 	= ($dt->format('H')+2).'h'.$dt->format('i').'m'.$dt->format('s').'s';

		//progress bar
		$vProgB = round(($dWeather['wind_speed_max'])*100/40);
		$cProgB = 'success';

		if($vProgB >= 40 && $vProgB < 75){$cProgB='warning';}elseif ($vProgB >= 75){$cProgB='danger';}

		$op .= '<div class="row">
					<div class="col-md-12">
				  		<div class="alert alert-warning" role="alert" style="padding:4px 15px 12px; margin-bottom:12px">
				  			<span class="glyphicon glyphicon-flag" aria-hidden="true"></span> '.$value.' '.substr($wTime,0,-4).' - '.round($dWeather['wind_speed_avg']).'km/h <span style="font-size:18px;">'.round($dWeather['wind_speed_max']).'km/h</span> <span style="font-size:12px;">'.round($dWeather['wind_speed_min']).'km/h</span> <span class="wind_heading glyphicon glyphicon-arrow-up" aria-hidden="true" data-heading="'.$dWeather['wind_heading'].'" style="font-size:18px;"></span>
				  			<div class="progress" style="height:15px">
								<div class="progress-bar progress-bar-'.$cProgB.' progress-bar-striped" role="progressbar" aria-valuenow="'.$vProgB.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$vProgB.'%">
								<span class="sr-only"></span>
							</div>
						</div>
				  	</div>
			  	</div>
			</div>';
	}
	

?>

<div class="container">
	<div class="row">
    <div class="jumbotron">
      <h1 class="text-center"><span class="glyphicon glyphicon-cloud" aria-hidden="true"></span> AlertDeco</h1>
      <p class="text-center">En ce moment</p>
      <!--<p class="text-center">Nouvelles mises à jour <span class="badge"><?=$cnt?></span></p>-->
    </div>
  </div>

  <?php if($alert!=""):?>
	  <div class="row">
		  <div class="col-md-12">
		    <?=$alert?>
		  </div>
		</div>
  <?php endif?>

  <?=$op?>

  <?php if(count($infos)>0):?>
	  <?php foreach ($infos as $value):?>
		  <div class="row">
		  	<div class="col-md-12">
		  		<div class="panel panel-<?=(in_array($value['id'], $new))?"primary":"info"?>">
			      <div class="panel-heading">
			        <h3 class="panel-title"><?=(in_array($value['id'], $new))?"<span class=\"label label-info pull-right\">New</span>":""?><span class="glyphicon glyphicon-time" aria-hidden="true"></span> Aujourd'hui à <?=convertDateFormat($value['maj'])?><?=$value['pseudo']!=""?" par <strong>".$value['pseudo']."</strong>":""?><br/><span class="glyphicon glyphicon-screenshot" aria-hidden="true"></span> Déco <?=utf8_encode($value['deco'])?></h3>
			      </div>
			      <div class="panel-body">
			        <strong>En ce moment : </strong><?=utf8_encode($value['cond'])?><br/>
				  		<strong>Le vent est : </strong><?=utf8_encode($value['vent'])?><br/>
				  		<?php if($value['avis']!=""):?>
				  			<strong>Mon avis : </strong><?=htmlspecialchars($value['avis'])?>
				  		<?php endif?>
				  		
				  		<br/><br/>
				  		<a href="#" class="btn btn-primary pull-right" role="button" data-role="comment" data-id="<?=$value['id']?>"><span class="glyphicon glyphicon glyphicon-comment" aria-hidden="true"></span> Commenter</a>

				  		<div class="clearfix"></div>

				  		<form name="f_comment" action="lib/process-comment.php" method="POST" class="hidden mt20" data-id="<?=$value['id']?>">

						  	<div class="row form-group">
							  	<label class="col-md-2 control-label" for="f_pseudo-<?=$value['id']?>">Pseudo :</label>
							    <div class="col-md-10">
							    	<input type="text" class="form-control" id="f_pseudo-<?=$value['id']?>" name="f_pseudo" placeholder="Pseudo"<?=(isset($_SESSION['pseudo'])&&$_SESSION['pseudo']!="")?"value='".$_SESSION['pseudo']."'":"" ?>>
							    </div>
								</div>

								<div class="row form-group">
							  	<label class="col-md-2 control-label" for="f_avis-<?=$value['id']?>">Commentaire :</label>
							    <div class="col-md-10">
							    	<textarea class="form-control" name="f_commentaire" id="f_commentaire-<?=$value['id']?>" maxlength="200"></textarea>
							    </div>
								</div>

								<div class="row">
									<div class="col-md-12 form-group">

										<input type="hidden" name="f_maj_id" value="<?=$value['id']?>">

							      <a href="#" class="btn btn-info pull-right" title="Annuler" role="button" data-role="remove" data-id="<?=$value['id']?>"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> Annuler</a>

							      <button type="submit" class="btn btn-primary pull-right">
										  <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Valider
										</button>

							    </div>
								</div>

							</form>

							<div id="container-comment-<?=$value['id']?>">

							<?php $comments = $alertDeco->getMajComment($value['id']);?>

							<?php if(count($comments)>0):?>
	  						<?php foreach ($comments as $co):?>
									<div class="well well-sm"><span class="glyphicon glyphicon-user" aria-hidden="true"></span> <?=$co['pseudo']!=""?"<strong>".$co['pseudo']."</strong>":""?> à <?=convertDateFormat($co['upd_at'])?> : <?=htmlspecialchars($co['commentaire'])?></div>
								<?php endforeach?>
							<?php endif?>

							</div>

			      </div>
			    </div>
		  	</div>
		  </div>
	  <?php endforeach?>
	<?php else:?>
		<div class="row">
	    <div class="col-md-12">
	      <div class="alert alert-info" role="alert"><strong>Pas d'info</strong>, sois le premier à partager !</div>
	    </div>
	  </div>
	<?php endif?>
	<div class="row">
    <div class="col-md-6 form-group">
      <a href="update.php" class="btn btn-primary btn-lg btn-block" title="Ajuoter une nouvelle information" role="button"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> Ajouter une information</a>
    </div>
    <div class="col-md-6 form-group">
      <a href="#" id="btn-reload" class="btn btn-success btn-lg btn-block" title="Mettre à jour les informations" role="button"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> Mettre à jour</a>
    </div>
  </div>
</div>

<?php require_once('common/footer-inc.php');?>