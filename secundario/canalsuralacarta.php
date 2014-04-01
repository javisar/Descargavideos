<?php
function canalsuralacarta(){
global $web,$web_descargada;

//$ret=CargaWebCurl($web);

$obtenido=array('enlaces' => array());

//http://www.canalsuralacarta.es/television/video/presenta-enrique-romero/7718/44

//video no admitido
if(enString($web_descargada,"_url_xml_datos")){
	dbug("_url_xml_datos encontrado");

	//<param name="flashVars" value="_width=630&_height=354&_url_xml_datos=http://www.canalsuralacarta.es/webservice/video/7718" />
	//flashVars="_width=630&_height=354&_url_xml_datos="

	$p=strrpos($web_descargada,"_url_xml_datos=")+15;
	$f=strpos($web_descargada,'"',$p);
	$xml=substr($web_descargada,$p,$f-$p);

	dbug("xml=".$xml);
	//http://www.canalsuralacarta.es/webservice/video/7718



	$p=strpos($web_descargada,"<title>")+7;
	$f=strpos($web_descargada,' ::',$p);
	$titulo=substr($web_descargada,$p,$f-$p);
	//$titulo=utf8_encode($titulo);
	$titulo=limpiaTitulo($titulo);
	dbug('titulo='.$titulo);

	$ret=CargaWebCurl($xml);

	//imagen
	//<archivoMultimediaMaxi><archivo>clipping/2012/02/08/00127/30.jpg</archivo><alt></alt></archivoMultimediaMaxi>
	$p=strpos($ret,"<picture>")+9;
	$f=strpos($ret,'</',$p);
	$imagen=substr($ret,$p,$f-$p);
	dbug('imagen='.$imagen);

	//<video type="content">
	//</video>
	$p=strpos($ret,'<video type="content">');
	$f=strlen($ret);
	$ret=substr($ret,$p,$f-$p);

	//</end_video_point>	
	$videos=substr_count($ret,'<url>');
	dbug('total videos='.$videos);

	//dbug($ret);

	$last=0;
	$total=array();
	if($videos>1){
		$total=array();
		$last=0;
		for($i=0;$i<$videos;$i++){
			$p=strpos($ret,'<url>',$last)+5;
			$f=strpos($ret,'<',$p);
			$last=$f+2;
			$url=substr($ret,$p,$f-$p);
			$repetido=false;
			$total_length=count($total);
			for($n=0;$n<$total_length;$n++)
				if($total[$n]==$url)
					$repetido=true;
			if(!$repetido)
				$total[$i]=$url;
		}

		if(count($total)>1)
			for($i=0;$i<$videos;$i++)
				array_push($obtenido['enlaces'],array(
					'url'     => $total[$i],
					'tipo'    => 'http',
					'url_txt' => 'parte '.($i+1)
				));
		else
			$ret=$total[0];
		dbug($ret);
	}
	else{
		//<url>http://ondemand.rtva.ondemand.flumotion.com/rtva/ondemand/flash8/programas/toros-para-todos/20110921122144-7-toros-para-todos-245--domingo.flv</url>
		$p=strrpos($ret,'<url>')+5;
		$f=strpos($ret,'</url>',$p);
		$ret=substr($ret,$p,$f-$p);

		array_push($obtenido['enlaces'],array(
			'url'  => $ret,
			'tipo' => 'http'
		));
	}
}
elseif(enString($web_descargada,"var elementos = [];")){
	dbug('var elementos = [];');
	
	$ret = utf8_encode($web_descargada);
	$ret = strtr($ret,array('\\"'=>"'"));

	$videos=substr_count($ret,'elementos.push');
	dbug('total videos='.$videos);
	
	$last=strpos($ret,"elementos.push");
	
	$imagen="http://www.canalsur.es/".entre1y2_a($ret,$last,'"urlPrevia": "','"');
	if($videos>1){
		$titulo="Canal Sur";
		for($i=0;$i<$videos;$i++){
			$obtenido['enlaces'][$i]=array(
				'url'     => entre1y2_a($ret,$last,'"url": "','"'),
				'tipo'    => 'http',
				'url_txt' => entre1y2_a($ret,$last,'"pie": "','"')
			);
			$last = strpos($ret, "});", $last)+1;
		}
	}
	else{
		$titulo=entre1y2_a($ret,$last,'"pie": "','",');
		$obtenido['enlaces'][$i]=array(
			'url'     => entre1y2_a($ret,$last,'"url": "','"'),
			'tipo'    => 'http'
		);
	}
}
else{
	
	dbug($ret);
	
	$titulo = utf8_encode(entre1y2($ret,'<title>','<'));
	
	if(enString($ret,"og:image")){
		$p=strpos($ret,"og:image");
		$imagen=entre1y2_a($ret, 'content="', '"');
	}
	else
		$imagen = "/canales/canalsur.png";
	
	preg_match("@http://[^ ]*?\.mp4@i", $ret, $matches);
	
	$url=$matches[0];
	$obtenido['enlaces'][]=array(
		'url'     => $url,
		'tipo'    => 'http'
	);
	
}

$obtenido['titulo']=$titulo;
$obtenido['imagen']=$imagen;

finalCadena($obtenido,0);
}
?>