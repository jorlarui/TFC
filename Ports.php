<?php ob_start(); session_start(); require('routeros_api.class.php'); ?>
<?php error_reporting (E_ALL ^ E_NOTICE); ?>
<?php
		$API = new routeros_api();
		$IP = $_SESSION[ 'ip' ];
		$user = $_SESSION[ 'user' ];
		$password = $_SESSION[ 'password' ];
		//Comprobamos conexion API
		if ($API->connect($IP, $user, $password)) {

		//Comprobamos interfaces
		$ARRAY = $API->comm("/interface/print");
		$interfaces = count($ARRAY);
		$Ports = $API->comm("/interface/ethernet/print");
		$numPorts = count($Ports);

		//Modelo
		$modeloCom = $API->comm("/system/routerboard/print");
		$modelo=$modeloCom[0]['model'];
		//Estado Link

		$valoresPar= json_encode(range(0, $numPorts-1));
		$valores = substr($valoresPar, 1, -1);
		echo $valores;


		$API->write("/interface/ethernet/monitor",false);
		$API->write("=numbers=".$valores,false);  
		$API->write("=once=",true);
		$READ = $API->read(false);
		$statusPorts = $API->parse_response($READ);
		$API->disconnect();
		}
		else {
			header( 'Location:Login.php?notLogin=true' );}

?>

<html>
<head>
	<title>Mikrotik Web Controller</title>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script> 
	<link href="http://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css"/>
	<link rel="stylesheet" href="bootstrap/css/bootstrap.css"/>
	<?php
		echo "<link rel='stylesheet' href='css/stylePorts$modelo.css'/>";
	?>
		

	
<!--Script para dibujar las graficas-->
<script> 
	var chart;
	function requestDatta(interface) {
		$.ajax({
			url: 'datosGraficas.php?interface='+interface,
			datatype: "json",
			success: function(data) {
				var midata = JSON.parse(data);
				if( midata.length > 0 ) {
					var TX=parseInt(midata[0].data);
					var RX=parseInt(midata[1].data);
					var x = (new Date()).getTime(); 
					shift=chart.series[0].data.length > 19;
					chart.series[0].addPoint([x, TX], true, shift);
					chart.series[1].addPoint([x, RX], true, shift);
					document.getElementById("trafico").innerHTML=TX + " / " + RX;
				}else{
					document.getElementById("trafico").innerHTML="- / -";
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) { 
				console.error("Status: " + textStatus + " request: " + XMLHttpRequest); console.error("Error: " + errorThrown); 
			}       
		});
	}	

	$(document).ready(function() {
			Highcharts.setOptions({
				global: {
					useUTC: false
				}
			});
	

           chart = new Highcharts.Chart({
			   chart: {
				renderTo: 'container',
				animation: Highcharts.svg,
				type: 'spline',
				events: {
					load: function () {
						setInterval(function () {
							requestDatta(document.getElementById("interface").value);
						}, 1000);
					}				
			}
		 },
		 title: {
			text: ''
		 },
		 xAxis: {
			type: 'datetime',
				tickPixelInterval: 150,
				maxZoom: 20 * 1000
		 },
		 yAxis: {
			minPadding: 0.2,
				maxPadding: 0.2,
				title: {
					text: 'Trafico Kbps',
					margin: 80
				}
		 },
            series: [{
                name: 'TX',
                data: []
            }, {
                name: 'RX',
                data: []
            }]
	  });
  });
</script>


</head>
<body>


	
    <nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
            <p class="navbar-text"><img src="images/logolittle.png"></p>
        </div>
       
        <div id="navbar" class="collapse navbar-collapse">

	    <ul class="nav navbar-nav navbar-right">
                <li><a href="#">Log Out&nbsp&nbsp&nbsp</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right"  style="margin-right:70px;">
                <li><a href="Status.php">Status</a></li>
                <li class="active"><a href="Ports.php">Ports</a></li>
		<li><a href="Switch.php">Switch</a></li>
                <li><a href="Vlans.php">Vlans</a></li>
		<li><a href="ACLs.php">ACLs</a></li>
            </ul>
        </div> 
    	</div>
    </nav>



<div class="container" style="margin-top:50px;">

      	<div class="row">
		<div class="col-lg-12 switch-box">
			<div class="col-lg-2"></div>
			<div class="col-lg-6">
				
				<?php
				for ($cont = 0; $cont < $numPorts; $cont++){
				
				if($statusPorts[$cont]['status']=='link-ok'){
					echo "<div class='etherGreen' id='etherGreen$cont'><img src='images/etherGreen.png'></div>";			
					}
				else if($statusPorts[$cont]['status']=='no-link'){			
					}
				
				}
				echo "<img src='images/$modelo.png'>";			
				?>
			</div>
			<div class="col-lg-2">INFO HERE</div>
			<div class="col-lg-2"></div>		
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12 info-box">
			<div class="col-lg-2"></div>
			<div class="col-lg-8 ports-box">
				<?php
				echo "<table>";
					for ($cont = 0; $cont < $numPorts; $cont++){
						echo "<tr>";
						
						echo "<td>".$Ports[$cont]['name']."</td>";
						if($statusPorts[$cont]['status']=='link-ok'){
							echo "<td class='link-ok'>";			
						}
						else if($statusPorts[$cont]['status']=='no-link'){
							echo "<td class='no-link'>";				
						}
				
				
						echo $statusPorts[$cont]['status']."</td>";
						echo "<td><form name='button$cont' method='post' action='Ports.php'>
							<input type='submit' name='enablePort$cont' value='&#10004' class='button'/>
							<input type='submit' name='disablePort$cont' value='X' class='button'/>
							</form></td>";
						echo "</tr>";
					}			
				echo "</table>";

			?>
			</div>
			<div class="col-lg-2">

			
			</div>		
		</div>
	</div>

</div>

<?php
		for ($cont = 0; $cont < $numPorts; $cont++){
		if(isset($_POST['enablePort'.$cont])){
			echo $Ports[$cont]['name'];
			$API = new routeros_api();
			$IP = $_SESSION[ 'ip' ];
			$user = $_SESSION[ 'user' ];
			$password = $_SESSION[ 'password' ];
			if ($API->connect($IP, $user, $password)) {
				$API->write("/interface/ethernet/set",false);
				$API->write("=disabled=no",false);
				$API->write("=.id=".$Ports[$cont]['name']);
				$ARRAY = $API->read();
				$API->disconnect();
		}}
		}

		for ($cont = 0; $cont < $numPorts; $cont++){
		if(isset($_POST['disablePort'.$cont])){
			echo $Ports[$cont]['name'];
			$API = new routeros_api();
			$IP = $_SESSION[ 'ip' ];
			$user = $_SESSION[ 'user' ];
			$password = $_SESSION[ 'password' ];
			if ($API->connect($IP, $user, $password)) {
				$API->write("/interface/ethernet/set",false);
				$API->write("=disabled=yes",false);
				$API->write("=.id=".$Ports[$cont]['name']);
				$ARRAY = $API->read();
				$API->disconnect();
		}}
		}


?>
<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>			
			
			
<!-- Dibujamos las graficas-->


<script type="text/javascript" src="highchart/js/highcharts.js"></script>
<script type="text/javascript" src="highchart/js/themes/gray2.js"></script>

</body>
</html>
