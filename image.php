<?php 

$image_url = "";

if(isset($_GET['image_url']) && !empty($_GET['image_url'])){
$image_url = $_GET['image_url'];
}

if(isset($_GET['image_url_field']) && !empty($_GET['image_url_field'])){

$url = $_GET['image_url_field'];
$name = basename($_GET['image_url_field']);
$img = '/home/amitusla/public_html/face_images/'.$name;
file_put_contents($img, file_get_contents($url));
header('Location: image.php?image_url='.$name);
}

?>


<!doctype html>
<html lang="en">
	<head>
		<title>Face Tracker</title>
		<meta charset="utf-8">
		<style>
			@import url(https://fonts.googleapis.com/css?family=Lato:300italic,700italic,300,700);

			body {
				font-family: 'Lato';
				background-color: #f0f0f0;
				margin: 0px auto;
				max-width: 1150px;
			}
			
			#overlay {
				position: absolute;
				top: 0px;
				left: 0px;
			}

			#container {
				position : relative;
				width : 700px;
				height : 600px;
				/*margin : 0px auto;*/
			}

			#content {
				margin-top : 70px;
				margin-left : 100px;
				margin-right : 100px;
				max-width: 950px;
			}
			
			#sketch {
				display: none;
			}
			
			#filter {
				display: none;
			}

			#convergence {
				display : none;
			}

			h2 {
				font-weight : 400;
			}

			.btn {
				font-family: 'Lato';
				font-size: 16px;
			}

			.hide {
				display : none;
			}
		</style>
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-32642923-1']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
	</head>
	<body>
		<script src="ext_js/utils.js"></script>
		<script src="ext_js/dat.gui.min.js"></script>
		<script src="ext_js/jsfeat-min.js"></script>
		<script src="ext_js/frontalface.js"></script>
		<script src="ext_js/jsfeat_detect.js"></script>
		<script src="ext_js/numeric-1.2.6.min.js"></script>
		<script src="ext_js/mosse.js"></script>
		<script src="ext_js/left_eye_filter.js"></script>
		<script src="ext_js/right_eye_filter.js"></script>
		<script src="ext_js/nose_filter.js"></script>
		<script src="models/model_pca_20_svm.js"></script>
		<script src="js/clm.js"></script>
		<script src="js/svmfilter_webgl.js"></script>
		<script src="js/svmfilter_fft.js"></script>
		<script src="js/mossefilter.js"></script>
		<script src="ext_js/Stats.js"></script>
		
		<link rel="stylesheet" type="text/css" href="./styles/imgareaselect-default.css" />
		<script src="ext_js/jquery.min.js"></script>
		<script src="ext_js/jquery.imgareaselect.pack.js"></script>
		<script src="ext_js/BlobBuilder.min.js"></script>
		<script src="ext_js/Filesaver.min.js"></script>
		
		<div id="content">
			<h2>Face tracking in images</h2>
			<div id="container">
				<canvas id="image" width="625" height="600"></canvas>
				<canvas id="overlay" width="625" height="600"></canvas>
			</div>
			<br/>
			<input type="button" class="btn" value="Detect" onclick="animate()"></input>
			
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" style="margin-bottom:62px;">
			<input type="text" name="image_url_field" class="image_url_field" placeholder="Enter Image URL" />
			<input type="submit" value="Get image" />
			
			</form>
			<div id="text">
			<?php
			if($image_url != ""){
			echo '<img src="/face_images/'.$image_url.'" style="display:none;" id="hidden_image" />';
			}
			?>
			<script>
			
				
			
			
				var cc = document.getElementById('image').getContext('2d');
				var overlay = document.getElementById('overlay');
				var overlayCC = overlay.getContext('2d');
			
				var img = new Image();
				img.onload = function() {
					cc.drawImage(img,0,0);
				};
				var hidden_image = document.getElementById('hidden_image');
				img.src = hidden_image.getAttribute('src');
			
				var ctrack = new clm.tracker({stopOnConvergence : true});
				ctrack.init(pModel);
			
				stats = new Stats();
				stats.domElement.style.position = 'absolute';
				stats.domElement.style.top = '0px';
				document.getElementById('container').appendChild( stats.domElement );
				hide_stat();
				
				var drawRequest;
				
				function animate(box) {
					ctrack.start(document.getElementById('image'), box);
					drawLoop();
				}
				
				function drawLoop() {
				hide_stat();
					drawRequest = requestAnimFrame(drawLoop);
					overlayCC.clearRect(0, 0, 720, 676);
					if (ctrack.getCurrentPosition()) {
						ctrack.draw(overlay);
					}
				}
				
				// detect if tracker fails to find a face
				document.addEventListener("clmtrackrNotFound", function(event) {
					//ctrack.stop();
					
				}, false);
				
				// detect if tracker loses tracking of face
				document.addEventListener("clmtrackrLost", function(event) {
					//ctrack.stop();
					//alert("The tracking had problems converging on a face in this image. Try selecting the face in the image manually.")
				}, false);
				
				// detect if tracker has converged
				document.addEventListener("clmtrackrConverged", function(event) {
					get_position();
					//document.getElementById('convergence').innerHTML = "CONVERGED";
					//document.getElementById('convergence').style.backgroundColor = "#00FF00";
					// stop drawloop
					cancelRequestAnimFrame(drawRequest);
				}, false);
				
				// update stats on iteration
				document.addEventListener("clmtrackrIteration", function(event) {
					stats.update();
				}, false);

				// manual selection of faces (with jquery imgareaselect plugin)
				function selectBox() {
					overlayCC.clearRect(0, 0, 720, 676);
					document.getElementById('convergence').innerHTML = "";
					ctrack.reset();
					$('#overlay').addClass('hide');
					$('#image').imgAreaSelect({
						handles : true,
						onSelectEnd : function(img, selection) {
						
							var box = [selection.x1, selection.y1, selection.width, selection.height];
							
						
							animate(box);
							$('#overlay').removeClass('hide');
						},
						autoHide : true
					});
				}

				function loadImage() {
					if (fileList.indexOf(fileIndex) < 0) {
						var reader = new FileReader();
						reader.onload = (function(theFile) {
							return function(e) {
								// check if positions already exist in storage
							
								// Render thumbnail.
								var canvas = document.getElementById('image')
								var cc = canvas.getContext('2d');
								var img = new Image();
								img.onload = function() {
									if (img.height > 600 || img.width > 700) {
										var rel = img.height/img.width;
										var neww = 700;
										var newh = neww*rel;
										if (newh > 600) {
											newh = 600;
											neww = newh/rel;
										}
										canvas.setAttribute('width', neww);
										canvas.setAttribute('height', newh);
										cc.drawImage(img,0,0,neww, newh);
									} else {
										canvas.setAttribute('width', img.width);
										canvas.setAttribute('height', img.height);
										cc.drawImage(img,0,0,img.width, img.height);
									}
								}
								img.src = e.target.result;
							};
						})(fileList[fileIndex]);
						reader.readAsDataURL(fileList[fileIndex]);
						overlayCC.clearRect(0, 0, 720, 676);
						document.getElementById('convergence').innerHTML = "";
						ctrack.reset();
						hide_stat();
					}

				}

				var fileList, fileIndex;
				if (window.File && window.FileReader && window.FileList) {
					function handleFileSelect(evt) {
						var files = evt.target.files;
						fileList = [];
						for (var i = 0;i < files.length;i++) {
							if (!files[i].type.match('image.*')) {
								continue;
							}
							fileList.push(files[i]);
						}
						if (files.length > 0) {
							fileIndex = 0;
						}
						
						loadImage();
					}
					document.getElementById('files').addEventListener('change', handleFileSelect, false);
				} else {
					$('#files').addClass("hide");
					$('#loadimagetext').addClass("hide");
				}
				
				
				function get_position(){
				var positions = ctrack.getCurrentPosition();
				var json_data = {};
				var result = {};
				var landmark = {};
				if(positions){
				for(var i=0;i<positions.length;i++){
				var nose_contour_left1,nose_contour_left2,left_eyebrow_lower_middle,nose_left,mouth_upperlip_left1,mouth_upperlip_left2,left_eye_top,right_eye_upper_left_quarter,right_eye_bottom, right_eyebrow_lower_right_quarter,mouth_upper_lip_left,left_eyebrow_upper_left_quarter,left_eyebrow_lower_left_quarter = {};
				var right_eye_bottom,left_eye_bottom,right_eye_top,left_eye_top,right_eye_right_corner,left_eye_right_corner,right_eye_left_corner,left_eye_left_corner,contour_left3,contour_right3,nose_contour_left2,nose_contour_right2 = {};
				var nose_pos = {x:positions[34][0],y:positions[34][1]};
				var noseleft2 = {x:positions[35][0],y:positions[35][1]};
				var lefteyebrowlowermiddle = {x:positions[20][0],y:positions[20][1]};
				var noseleft = {x:positions[36][0],y:positions[36][1]};
				var mouthupperlipleft1 = {x:positions[44][0],y:positions[44][1]};
				var righteyebottom = {x:positions[31][0],y:positions[31][1]};
				var lefteyebottom = {x:positions[26][0],y:positions[26][1]};
				var righteyetop = {x:positions[29][0],y:positions[29][1]};
				var lefteyetop = {x:positions[24][0],y:positions[24][1]};
				var righteyerightcorner = {x:positions[28][0],y:positions[28][1]};
				var lefteyerightcorner = {x:positions[25][0],y:positions[25][1]};
				var righteyeleftcorner = {x:positions[30][0],y:positions[30][1]};
				var lefteyeleftcorner = {x:positions[23][0],y:positions[23][1]};
				var nosecontourleft2 = {x:positions[35][0],y:positions[35][1]};
				var nosecontourright2 = {x:positions[39][0],y:positions[39][1]};
				var contourleft3 = {x:positions[36][0],y:positions[23][1]};
				var contourright3 = {x:positions[38][0],y:positions[23][1]};
				
				
				landmark = {'right_eye_bottom':righteyebottom,'left_eye_bottom':lefteyebottom,'right_eye_top':righteyetop,'left_eye_top':lefteyetop,'right_eye_right_corner':righteyerightcorner,'left_eye_right_corner':lefteyerightcorner,'right_eye_left_corner':righteyeleftcorner,'left_eye_left_corner':lefteyeleftcorner,'contour_left3':contourleft3,'contour_right3':contourright3,'nose_contour_left2':nosecontourleft2,'nose_contour_right2':nosecontourright2
			    };
				
				
				}
				result['landmark'] = landmark;
                result['image_height'] = $("#hidden_image").height();
				result['image_width'] = $("#hidden_image").width();
				var face_id = Math.ceil(Math.random()*10000000000);
				json_data = {"result" : result,'face_id':face_id};
                window.json_data = json_data;

				//console.log(positions[i][0]);
				}
				
				}
				
				function hide_stat(){
				
					$('#container div').css({
				'opacity' : '0'
				});
				
				}
				
				setTimeout(function(){
				hide_stat();
				},700);
				
				
				
				
			</script>
		</div>
	</body>
</html>
