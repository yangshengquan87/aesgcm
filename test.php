<?php
require "Antiaddiction.php";

$Antiaddiction = new Antiaddiction();

$Antiaddiction->appid('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
$ai = '64774xxxxxxxxxxxx906096';
$name = '某一';
$idNum = '0621923475487518';
$string = ['ai'=>$ai,'name'=>$name,'idNum'=>$idNum];
$rawBody = ['ai'=>$ai];

$ret = $Antiaddiction->api_get('/idcard/authentication/query',$rawBody);

print_r($ret);
exit;


$sign = $Antiaddiction->createSign($rawBody);
$headers = $Antiaddiction->getHeaders($sign);



echo json_encode($headers) . "\r\n";
echo http_build_query($rawBody) . "\r\n";
exit;


$string = [
	'collections'=>[
		[
			'no'=>1,
			'si'=>'95edkzei5exh47pk0z2twm6zpielesrd',//一个会话标识只能对应唯一的实名用户，一个实名用户可以拥有多个会话标识；同一用户单次游戏会话中，上下线动作必须使用同一会话标识上报备注：会话标识仅标识一次用户会话，生命周期仅为一次上线和与之匹配的一次下线，不会对生命周期之外的任何业务有任何影响
			'bt'=>0,
			'ot'=>1608619311,
			'ct'=>2,
			'di'=>'ecvndx6r6xfwofmufs3lbimcr639r33t',
//                     'pi'=>'wbt8wws5hbmxyquaw24rqh9dzdhllxx1o7r5vv',//已通过实名认证用户的唯一标识，已认证通过用户必填
		]
	],
];
$string = [
	'collections'=>[
		[
			'no'=>1,
			'si'=>'95edkzei5exh47pk0z2twm6zpielesrd',//一个会话标识只能对应唯一的实名用户，一个实名用户可以拥有多个会话标识；同一用户单次游戏会话中，上下线动作必须使用同一会话标识上报备注：会话标识仅标识一次用户会话，生命周期仅为一次上线和与之匹配的一次下线，不会对生命周期之外的任何业务有任何影响
			'bt'=>0,
			'ot'=>1608619311,
			'ct'=>0,
			'di'=>'ecvndx6r6xfwofmufs3lbimcr639r33t',
			'pi'=>'wbt8wws5hbmxyquaw24rqh9dzdhllxx1o7r5vv',//已通过实名认证用户的唯一标识，已认证通过用户必填
		]
	],
];
$rawBody = $Antiaddiction->createBody(json_encode($string));
//         $rawBody = ['ai'=>'14235174175499132'];
$sign = $Antiaddiction->createSign($rawBody);
$headers = $Antiaddiction->getHeaders($sign);
echo json_encode($headers);

echo "\r\n";

echo $rawBody;

exit;
$ret = $Antiaddiction->api_post('/idcard/authentication/check',$string);


