<?php

if(isset($_POST['image_url_field']) && !empty($_POST['image_url_field'])){

$url = $_POST['image_url_field'];
$name = basename($_POST['image_url_field']);
$img = '/home/amitusla/public_html/face_images/'.$name;
file_put_contents($img, file_get_contents($url));
header('Location: image.php?image_url='.$name);
}

?>