<?php
session_start();
set_time_limit(0);   //script will run for infinite time
ini_set('default_socket_timeout',300);

/****************instagram API keys******************/
define('clientID','********************************');
define('clientSecret','****************************');
define('redirectUrl','http://localhost/instagram/insta_download.php');
define('imageDirectory','pics/'); 
function connectToInstagram($url)
{
	//connect with instagram
	$ch=curl_init();
	curl_setopt_array($ch,array(
	CURLOPT_URL => $url,
	CURLOPT_RETURNTRANSFER=>true,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_SSL_VERIFYHOST=>2
	));
	$result=curl_exec($ch);
	curl_close($ch);
	return $result;
}
//get instagram user id
function getUserId($userName,$access_token)
{
	$url='https://api.instagram.com/v1/users/search?q=' . $userName .'&access_token=' . $access_token;
	$instagramInfo = connectToInstagram($url);
	$results=json_decode($instagramInfo,true);
    return $results['data'][0]['id'];
}
//print out images on screen
function printImages($userID,$access_token)
{
	
	$url='https://api.instagram.com/v1/users/'.$userID.'/media/recent?client_id='.clientID.'&access_token=' . $access_token.'&count=5';
	$instagramInfo = connectToInstagram($url);
	$results=json_decode($instagramInfo,true);

		//parse through images
		foreach($results['data'] as $items)
		{
			$image_url=$items['images']['low_resolution']['url'];
			echo '<img src="'.$image_url.'"/><br/>';
			savePicture($image_url);
		}
}
//save the picture
function savePicture($image_url)
{
	echo $image_url.'</br/>';
	$filename=basename($image_url);
	//make sure that images doesnt exist in database yet(REMAINING)
	$destination=imageDirectory.$filename;
	file_put_contents($destination,file_get_contents($image_url));
}
if(@($_GET['code']))//through this code we are allowed to access user's info
{
	
	$code=$_GET['code'];
	$url='https://api.instagram.com/oauth/access_token';
	$access_token_settings=array(
	'client_id'     => clientID    ,
	'client_secret'  =>clientSecret,
	'grant_type'  => 'authorization_code',
	'redirect_uri'   =>redirectUrl,
	'code'        => $code,
	);

	/********we will use curl to tansfer information from one website(instagram)to other website (insta_downloader)***********/
	$curl=curl_init($url);
	curl_setopt($curl,CURLOPT_POST,true);
	curl_setopt($curl,CURLOPT_POSTFIELDS,$access_token_settings);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);//RETURN ALL THE STRING AS STRING
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
	//all information is stored in result variable
	$result=curl_exec($curl);
	curl_close($curl);
	$results=json_decode($result,true);
	$userName=$results['user']['username'];
	$access_token=$results['access_token'];
	$userID=getUserId($userName,$access_token);
	printImages($userID,$access_token);
}else
{?>

<!doctype html>
<html>
	<body>
		<a href='https://api.instagram.com/oauth/authorize/?client_id=<?php echo clientID?>&redirect_uri=<?php echo redirectUrl?>&response_type=code&scope=public_content'>login</a>
	</body>
</html>
<?php
}
?>