<?php
class Openssl_EncryptDecrypt {
    function encrypt ($pure_string, $encryption_key) {
        $cipher  = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hash_algo  = 'sha256';
        $sha2len    = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($pure_string, $cipher, $encryption_key, $options, $iv);
        $hmac = hash_hmac($hash_algo, $ciphertext_raw, $encryption_key, true);
        return $iv.$hmac.$ciphertext_raw;
    }
    function decrypt ($encrypted_string, $encryption_key) {
        $cipher  = 'AES-256-CBC';
        $options    = OPENSSL_RAW_DATA;
        $hash_algo  = 'sha256';
        $sha2len    = 32;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($encrypted_string, 0, $ivlen);
        $hmac = substr($encrypted_string, $ivlen, $sha2len);
        $ciphertext_raw = substr($encrypted_string, $ivlen+$sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $encryption_key, $options, $iv);
        $calcmac = hash_hmac($hash_algo, $ciphertext_raw, $encryption_key, true);
        if(function_exists('hash_equals')) {
            if (hash_equals($hmac, $calcmac)) return $original_plaintext;
        } else {
            if ($this->hash_equals_custom($hmac, $calcmac)) return $original_plaintext;
        }
    }
    function hash_equals_custom($knownString, $userString) {
        if (function_exists('mb_strlen')) {
            $kLen = mb_strlen($knownString, '8bit');
            $uLen = mb_strlen($userString, '8bit');
        } else {
            $kLen = strlen($knownString);
            $uLen = strlen($userString);
        }
        if ($kLen !== $uLen) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $kLen; $i++) {
            $result |= (ord($knownString[$i]) ^ ord($userString[$i]));
        }
        return 0 === $result;
    }
}

define('ENCRYPTION_KEY', '2238@@5524');
$OpensslEncryption = new Openssl_EncryptDecrypt;
$file = file_get_contents(__DIR__."/settings.php");
$decrypted = $OpensslEncryption->decrypt($file, ENCRYPTION_KEY);
$db = json_decode($decrypted, true);

$connection = 'localhost';
$database = 'assitix_main';
$username = 'ryzon';
$password = 'zain0980';
$license = true;
$access_key = "ENCRYPTION_KEY";
$preferences = array();
//$preferences['currency'] = $db['currency'];
//$master_password = $db['master_password'];
ini_set("short_open_tag","on");
ini_set("display_errors","on");
$dt = date("Y-m-d H:i:s");
$DB_CONN=mysqli_connect($connection, $username, $password, $database) or die("Connection Error->".mysqli_connect_error());

mysqli_query($DB_CONN, "SET sql_mode = ''");
mysqli_set_charset($DB_CONN, "utf8mb4");

//functions
$distributors = [
    10 => [
        'level' => 'SR',
        'reward' => '1000'
    ],
    20 => [
        'level' => 'SSR',
        'reward' => '2000'
    ],
    50 => [
        'level' => 'ARM',
        'reward' => '5000'
    ],
    100 => [
        'level' => 'RM',
        'reward' => '10000'
    ],
    200 => [
        'level' => 'AZM',
        'reward' => '20000'
    ],
    500 => [
        'level' => 'ZM',
        'reward' => 'mobile'
    ],
    1000 => [
        'level' => 'AGM',
        'reward' => 'china 70'
    ],
    2000 => [
        'level' => 'GM',
        'reward' => 'honda 125'
    ],
    5000 => [
        'level' => 'Silver',
        'reward' => '2 person umra'
    ],
    10000 => [
        'level' => 'Gold',
        'reward' => '7 lac cash'
    ],
    20000 => [
        'level' => 'Diamond',
        'reward' => 'alto'
    ],
    50000 => [
        'level' => 'Ambassador',
        'reward' => '1300 cc'
    ],
    100000 => [
        'level' => 'Crown Ambassador',
        'reward' => '1 CR home'
    ]
];

function getDistributorLevel($user_id, $persons) {
    global $distributors;
    $levels = array_keys($distributors);
    
    if ($persons < min($levels)) {
        return [
            'status' => 'below',
            'current' => [
                'level' => 'Distributor',
                'reward' => null,
                'level_persons' => 0
            ],
            'next_level' => $distributors[min($levels)],
            'persons_needed' => min($levels) - $persons
        ];
    }
    
    $achieved_level = 0;
    foreach ($levels as $level) {
        if ($persons >= $level) {
            $achieved_level = $level;
        } else {
            break;
        }
    }
    
    $next_level = null;
    $persons_needed = 0;
    if ($achieved_level < max($levels)) {
        $next_level_key = $levels[array_search($achieved_level, $levels) + 1];
        $next_level = $distributors[$next_level_key];
        $persons_needed = $next_level_key - $persons;
    }
    
    return [
        'status' => 'qualified',
        'current' => array_merge(
            $distributors[$achieved_level],
            ['level_persons' => $achieved_level]
        ),
        'next_level' => $next_level,
        'persons_needed' => $persons_needed
    ];
}

function checkAndAssignReward($user_id, $persons) {
    global $distributors, $DB_CONN;
    $currentLevel = getDistributorLevel($user_id, $persons);
    
    if ($currentLevel['status'] === 'qualified') {
        $level_persons = $currentLevel['current']['level_persons'];
        
        $stmt = $DB_CONN->prepare("SELECT id FROM distributor_rewards WHERE user_id = ? AND level_persons = ?");
        $stmt->bind_param("ii", $user_id, $level_persons);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt = $DB_CONN->prepare("INSERT INTO distributor_rewards (user_id, level_persons, reward_type, reward_value, status) VALUES (?, ?, 'achievement', ?, 'pending')");
            $reward = $currentLevel['current']['reward'];
            $stmt->bind_param("iis", $user_id, $level_persons, $reward);
            $stmt->execute();
            
            return [
                'status' => 'new_reward',
                'reward' => $currentLevel['current']['reward'],
                'level' => $currentLevel['current']['level']
            ];
        }
        
        return [
            'status' => 'already_rewarded',
            'message' => 'Reward already given for this level'
        ];
    }
    
    return [
        'status' => 'not_qualified',
        'message' => 'No new rewards available'
    ];
}

function get_parent($id, $count = 3, $col = 'parent') {
    global $DB_CONN;
    
    // First check if the current node has space
    $query = "SELECT COUNT(*) as count FROM users WHERE {$col} = '{$id}'";
    $result = mysqli_query($DB_CONN, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] < $count) {
        // This user has less than 3 children, return their id
        return $id;
    }
    
    // Use a queue for breadth-first traversal
    $queue = array($id);
    
    while (!empty($queue)) {
        // Get the next node to process
        $current_id = array_shift($queue);
        
        // Get all children of this node
        $children_query = "SELECT id FROM users WHERE {$col} = '{$current_id}'";
        $children_result = mysqli_query($DB_CONN, $children_query);
        
        // Check each child to see if it has space
        while ($child = mysqli_fetch_assoc($children_result)) {
            $child_id = $child['id'];
            
            // Check if this child has space
            $check_query = "SELECT COUNT(*) as count FROM users WHERE {$col} = '{$child_id}'";
            $check_result = mysqli_query($DB_CONN, $check_query);
            $check_row = mysqli_fetch_assoc($check_result);
            
            if ($check_row['count'] < $count) {
                // This child has space, return it immediately
                return $child_id;
            }
            
            // Add this child to the queue for future processing
            $queue[] = $child_id;
        }
    }
    
    // If we reach here, no node with space was found
    return 0;
}
function user_count($id, $col = 'parent') {
    global $DB_CONN;
    $count = 0;
    $parent = $id;
    // if($id)
    //  $count += 1;
    for($i=1;$i<=11;$i++) {
        $c = mysqli_query($DB_CONN, "SELECT id from users where {$col} in ($parent)");
        $count += mysqli_num_rows($c);
        unset($p);
        while($cu = mysqli_fetch_assoc($c))
            $p[] = $cu['id'];
        if(isset($p))
            $parent = implode(", ", $p);
    }
    return $count;
}


function db_filter_val($value='')
{
    global $DB_CONN;
    if(!is_array($value)) {
    $value = trim($value);
    $value = htmlspecialchars($value);
    return mysqli_real_escape_string($DB_CONN,$value);
    } else
    return $value;
}

function isJson($string) {
    $decoded = json_decode($string);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    if (!is_object($decoded)) {
        return false;
    }   
    return true;
}
function db_filter($array) {
    global $DB_CONN;
    $sanitized = [];
    foreach ($array as $key => $b) {
        if(isJson($b)) {
            $sanitized[$key] = trim($b);
        } elseif (!is_array($b)) {
            $sanitized[$key] = mysqli_real_escape_string($DB_CONN, trim($b));
        } else {
            $sanitized[$key] = db_filter($b);
        }
    }
    return $sanitized;
}
// function isJson($string) {
//     json_decode($string);
//     return (json_last_error() === JSON_ERROR_NONE);
// }
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

class APIAuth {
    private $secretKey = "d93ec75bca4b7ef88df5a6c591654422"; 
    private $conn;
    private $securitySettings;
    
    public function __construct($connection, $securitySettings = null) {
        $this->conn = $connection;
        $this->securitySettings = $securitySettings;
    }
    
    public function generateJWT($userId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Get session expiration from security settings (in minutes) or use default
        $sessionMinutes = isset($this->securitySettings['user_session']) ? 
                         intval($this->securitySettings['user_session']) : 60; // Default to 60 minutes
        
        $payload = json_encode([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + ($sessionMinutes * 60) // Convert minutes to seconds
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $base64UrlHeader . "." . $base64UrlPayload, 
            $this->secretKey, 
            true
        );
        
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    public function validateJWT($token) {
        try {
            $tokenParts = explode('.', $token);
            if (count($tokenParts) != 3) {
                return false;
            }
            
            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
            $signature = $tokenParts[2];
            
            $expectedSignature = hash_hmac('sha256',
                $tokenParts[0] . "." . $tokenParts[1],
                $this->secretKey,
                true
            );
            
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
            
            if ($signature !== $expectedSignature) {
                return false;
            }
            
            $payload = json_decode($payload, true);
            
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function authenticate() {
        $headers = getallheaders();
        $token = null;
        // Check for token in various headers
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($headers['authorization'])) {
            $token = str_replace('Bearer ', '', $headers['authorization']);
        } elseif (isset($headers['Token'])) {
            $token = $headers['Token'];
        } elseif (isset($headers['token'])) {
            $token = $headers['token'];
        }
        
        if (!$token) {
            return null;
        }
        
        $payload = $this->validateJWT($token);
        
        if ($payload && isset($payload['user_id'])) {
            // Set up global userinfo
            global $userinfo, $user_id;
            
            // Fetch fresh user data
            $user_id = mysqli_real_escape_string($this->conn, $payload['user_id']);
            updateuserinfo();
            return $userinfo;
        }
        
        return null;
    }
}

class Csrf{ protected static function setNewToken($page,$expiry){ $token=new \stdClass(); $token->page=$page; $token->expiry=time()+$expiry; $token->sessiontoken=base64_encode(genPinCode(32)); $token->cookietoken=md5(base64_encode(genPinCode(32))); setcookie(self::makeCookieName($page),$token->cookietoken,$token->expiry); return $_SESSION['csrftokens'][$page]=$token; } protected static function getSessionToken($page){ return!empty($_SESSION['csrftokens'][$page])?$_SESSION['csrftokens'][$page]:null; } protected static function getCookieToken($page){ $value=self::makeCookieName($page); return!empty($_COOKIE[$value])?$_COOKIE[$value]:''; } protected static function makeCookieName($page){ if(empty($page)){ return ''; } return 'csrftoken-'.substr(md5($page),0,10); } protected static function confirmSessionStarted(){ if(!isset($_SESSION)){ trigger_error('Session has not been started.',E_USER_ERROR); return false; } return true; } public static function getInputToken($page,$expiry=1800){ self::confirmSessionStarted(); if(empty($page)){ trigger_error('Page is missing.',E_USER_ERROR); return false; } if(self::getSessionToken($page)){ $token=self::getSessionToken($page); } else{ $token=self::setNewToken($page,$expiry); } return '<input type="hidden" id="csrftoken" name="csrftoken" value="'.$token->sessiontoken.'">'; } public static function getInputTokencode($page,$expiry=1800){ self::confirmSessionStarted(); if(empty($page)){ trigger_error('Page is missing.',E_USER_ERROR); return false; } if(self::getSessionToken($page)){ $token=self::getSessionToken($page); } else{ $token=self::setNewToken($page,$expiry); } return $token->sessiontoken; } public static function verifyToken($page,$removeToken=false,$requestToken=null){ self::confirmSessionStarted(); if($requestToken)$requestToken=$requestToken; elseif($_POST['csrftoken'])$requestToken=$_POST['csrftoken']; else $requestToken=null; if(empty($page)){ trigger_error('Page alias is missing',E_USER_WARNING); return false; } else if(empty($requestToken)){ trigger_error('Token is missing',E_USER_WARNING); return false; } $token=self::getSessionToken($page); if(empty($token)||time()>(int) $token->expiry){ self::removeToken($page); return false; } $sessionConfirm=hash_equals($token->sessiontoken,$requestToken); if($removeToken){ self::removeToken($page); } if($sessionConfirm){ return true; } return false; } public static function removeToken($page){ self::confirmSessionStarted(); if(empty($page)){ return false; } unset($_COOKIE[self::makeCookieName($page)],$_SESSION['csrftokens'][$page]); return true; } }

class QRCode{private $data;private $options;public function __construct($data,$options=[]){$defaults=['s'=>'qrl'];if(!is_array($options))$options=[];$this->data=$data;$this->options=array_merge($defaults,$options);}public function output_image(){$image=$this->render_image();header('Content-Type: image/png');imagepng($image);imagedestroy($image);}public function render_image(){list($code,$widths,$width,$height,$x,$y,$w,$h)=$this->encode_and_calculate_size($this->data,$this->options);$image=imagecreatetruecolor($width,$height);imagesavealpha($image,true);$bgcolor=(isset($this->options['bc'])?$this->options['bc']:'FFFFFF');$bgcolor=$this->allocate_color($image,$bgcolor);imagefill($image,0,0,$bgcolor);$fgcolor=(isset($this->options['fc'])?$this->options['fc']:'000000');$fgcolor=$this->allocate_color($image,$fgcolor);$colors=array($bgcolor,$fgcolor);$density=(isset($this->options['md'])?(float)$this->options['md']:1);list($width,$height)=$this->calculate_size($code,$widths);if($width&&$height){$scale=min($w/$width,$h/$height);$scale=(($scale>1)?floor($scale):1);$x=floor($x+($w-$width*$scale)/2);$y=floor($y+($h-$height*$scale)/2);}else{$scale=1;$x=floor($x+$w/2);$y=floor($y+$h/2);}$x+=$code['q'][3]*$widths[0]*$scale;$y+=$code['q'][0]*$widths[0]*$scale;$wh=$widths[1]*$scale;foreach($code['b']as $by=>$row){$y1=$y+$by*$wh;foreach($row as $bx=>$color){$x1=$x+$bx*$wh;$mc=$colors[$color?1:0];$rx=floor($x1+(1-$density)*$wh/2);$ry=floor($y1+(1-$density)*$wh/2);$rw=ceil($wh*$density);$rh=ceil($wh*$density);imagefilledrectangle($image,$rx,$ry,$rx+$rw-1,$ry+$rh-1,$mc);}}return $image;}private function encode_and_calculate_size($data,$options){$code=$this->dispatch_encode($data,$options);$widths=array((isset($options['wq'])?(int)$options['wq']:1),(isset($options['wm'])?(int)$options['wm']:1),);$size=$this->calculate_size($code,$widths);$dscale=4;$scale=(isset($options['sf'])?(float)$options['sf']:$dscale);$scalex=(isset($options['sx'])?(float)$options['sx']:$scale);$scaley=(isset($options['sy'])?(float)$options['sy']:$scale);$dpadding=0;$padding=(isset($options['p'])?(int)$options['p']:$dpadding);$vert=(isset($options['pv'])?(int)$options['pv']:$padding);$horiz=(isset($options['ph'])?(int)$options['ph']:$padding);$top=(isset($options['pt'])?(int)$options['pt']:$vert);$left=(isset($options['pl'])?(int)$options['pl']:$horiz);$right=(isset($options['pr'])?(int)$options['pr']:$horiz);$bottom=(isset($options['pb'])?(int)$options['pb']:$vert);$dwidth=ceil($size[0]*$scalex)+$left+$right;$dheight=ceil($size[1]*$scaley)+$top+$bottom;$iwidth=(isset($options['w'])?(int)$options['w']:$dwidth);$iheight=(isset($options['h'])?(int)$options['h']:$dheight);$swidth=$iwidth-$left-$right;$sheight=$iheight-$top-$bottom;return array($code,$widths,$iwidth,$iheight,$left,$top,$swidth,$sheight);}private function allocate_color($image,$color){$color=preg_replace('/[^0-9A-Fa-f]/','',$color);$r=hexdec(substr($color,0,2));$g=hexdec(substr($color,2,2));$b=hexdec(substr($color,4,2));return imagecolorallocate($image,$r,$g,$b);}private function dispatch_encode($data,$options){switch(strtolower(preg_replace('/[^A-Za-z0-9]/','',$options['s']))){case 'qrl':return $this->qr_encode($data,0);case 'qrm':return $this->qr_encode($data,1);case 'qrq':return $this->qr_encode($data,2);case 'qrh':return $this->qr_encode($data,3);default:return $this->qr_encode($data,0);}return null;}private function calculate_size($code,$widths){$width=($code['q'][3]*$widths[0]+$code['s'][0]*$widths[1]+$code['q'][1]*$widths[0]);$height=($code['q'][0]*$widths[0]+$code['s'][1]*$widths[1]+$code['q'][2]*$widths[0]);return array($width,$height);}private function qr_encode($data,$ecl){list($mode,$vers,$ec,$data)=$this->qr_encode_data($data,$ecl);$data=$this->qr_encode_ec($data,$ec,$vers);list($size,$mtx)=$this->qr_create_matrix($vers,$data);list($mask,$mtx)=$this->qr_apply_best_mask($mtx,$size);$mtx=$this->qr_finalize_matrix($mtx,$size,$ecl,$mask,$vers);return array('q'=>array(4,4,4,4),'s'=>array($size,$size),'b'=>$mtx);}private function qr_encode_data($data,$ecl){$mode=$this->qr_detect_mode($data);$version=$this->qr_detect_version($data,$mode,$ecl);$version_group=(($version<10)?0:(($version<27)?1:2));$ec_params=$this->qr_ec_params[($version-1)*4+$ecl];$max_chars=$this->qr_capacity[$version-1][$ecl][$mode];if($mode==3)$max_chars<<=1;$data=substr($data,0,$max_chars);switch($mode){case 0:$code=$this->qr_encode_numeric($data,$version_group);break;case 1:$code=$this->qr_encode_alphanumeric($data,$version_group);break;case 2:$code=$this->qr_encode_binary($data,$version_group);break;case 3:$code=$this->qr_encode_kanji($data,$version_group);break;}for($i=0;$i<4;$i++)$code[]=0;while(count($code)%8)$code[]=0;$data=array();for($i=0,$n=count($code);$i<$n;$i+=8){$byte=0;if($code[$i+0])$byte|=0x80;if($code[$i+1])$byte|=0x40;if($code[$i+2])$byte|=0x20;if($code[$i+3])$byte|=0x10;if($code[$i+4])$byte|=0x08;if($code[$i+5])$byte|=0x04;if($code[$i+6])$byte|=0x02;if($code[$i+7])$byte|=0x01;$data[]=$byte;}for($i=count($data),$a=1,$n=$ec_params[0];$i<$n;$i++,$a^=1){$data[]=$a?236:17;}return array($mode,$version,$ec_params,$data);}private function qr_detect_mode($data){$numeric='/^[0-9]*$/';$alphanumeric='/^[0-9A-Z .\/:$%*+-]*$/';$kanji='/^([\x81-\x9F\xE0-\xEA][\x40-\xFC]|[\xEB][\x40-\xBF])*$/';if(preg_match($numeric,$data))return 0;if(preg_match($alphanumeric,$data))return 1;if(preg_match($kanji,$data))return 3;return 2;}private function qr_detect_version($data,$mode,$ecl){$length=strlen($data);if($mode==3)$length>>=1;for($v=0;$v<40;$v++){if($length<=$this->qr_capacity[$v][$ecl][$mode]){return $v+1;}}return 40;}private function qr_encode_numeric($data,$version_group){$code=array(0,0,0,1);$length=strlen($data);switch($version_group){case 2:$code[]=$length&0x2000;$code[]=$length&0x1000;case 1:$code[]=$length&0x0800;$code[]=$length&0x0400;case 0:$code[]=$length&0x0200;$code[]=$length&0x0100;$code[]=$length&0x0080;$code[]=$length&0x0040;$code[]=$length&0x0020;$code[]=$length&0x0010;$code[]=$length&0x0008;$code[]=$length&0x0004;$code[]=$length&0x0002;$code[]=$length&0x0001;}for($i=0;$i<$length;$i+=3){$group=substr($data,$i,3);switch(strlen($group)){case 3:$code[]=$group&0x200;$code[]=$group&0x100;$code[]=$group&0x080;case 2:$code[]=$group&0x040;$code[]=$group&0x020;$code[]=$group&0x010;case 1:$code[]=$group&0x008;$code[]=$group&0x004;$code[]=$group&0x002;$code[]=$group&0x001;}}return $code;}private function qr_encode_alphanumeric($data,$version_group){$alphabet='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';$code=array(0,0,1,0);$length=strlen($data);switch($version_group){case 2:$code[]=$length&0x1000;$code[]=$length&0x0800;case 1:$code[]=$length&0x0400;$code[]=$length&0x0200;case 0:$code[]=$length&0x0100;$code[]=$length&0x0080;$code[]=$length&0x0040;$code[]=$length&0x0020;$code[]=$length&0x0010;$code[]=$length&0x0008;$code[]=$length&0x0004;$code[]=$length&0x0002;$code[]=$length&0x0001;}for($i=0;$i<$length;$i+=2){$group=substr($data,$i,2);if(strlen($group)>1){$c1=strpos($alphabet,substr($group,0,1));$c2=strpos($alphabet,substr($group,1,1));$ch=$c1*45+$c2;$code[]=$ch&0x400;$code[]=$ch&0x200;$code[]=$ch&0x100;$code[]=$ch&0x080;$code[]=$ch&0x040;$code[]=$ch&0x020;$code[]=$ch&0x010;$code[]=$ch&0x008;$code[]=$ch&0x004;$code[]=$ch&0x002;$code[]=$ch&0x001;}else{$ch=strpos($alphabet,$group);$code[]=$ch&0x020;$code[]=$ch&0x010;$code[]=$ch&0x008;$code[]=$ch&0x004;$code[]=$ch&0x002;$code[]=$ch&0x001;}}return $code;}private function qr_encode_binary($data,$version_group){$code=array(0,1,0,0);$length=strlen($data);switch($version_group){case 2:case 1:$code[]=$length&0x8000;$code[]=$length&0x4000;$code[]=$length&0x2000;$code[]=$length&0x1000;$code[]=$length&0x0800;$code[]=$length&0x0400;$code[]=$length&0x0200;$code[]=$length&0x0100;case 0:$code[]=$length&0x0080;$code[]=$length&0x0040;$code[]=$length&0x0020;$code[]=$length&0x0010;$code[]=$length&0x0008;$code[]=$length&0x0004;$code[]=$length&0x0002;$code[]=$length&0x0001;}for($i=0;$i<$length;$i++){$ch=ord(substr($data,$i,1));$code[]=$ch&0x80;$code[]=$ch&0x40;$code[]=$ch&0x20;$code[]=$ch&0x10;$code[]=$ch&0x08;$code[]=$ch&0x04;$code[]=$ch&0x02;$code[]=$ch&0x01;}return $code;}private function qr_encode_kanji($data,$version_group){$code=array(1,0,0,0);$length=strlen($data);switch($version_group){case 2:$code[]=$length&0x1000;$code[]=$length&0x0800;case 1:$code[]=$length&0x0400;$code[]=$length&0x0200;case 0:$code[]=$length&0x0100;$code[]=$length&0x0080;$code[]=$length&0x0040;$code[]=$length&0x0020;$code[]=$length&0x0010;$code[]=$length&0x0008;$code[]=$length&0x0004;$code[]=$length&0x0002;}for($i=0;$i<$length;$i+=2){$group=substr($data,$i,2);$c1=ord(substr($group,0,1));$c2=ord(substr($group,1,1));if($c1>=0x81&&$c1<=0x9F&&$c2>=0x40&&$c2<=0xFC){$ch=($c1-0x81)*0xC0+($c2-0x40);}else if(($c1>=0xE0&&$c1<=0xEA&&$c2>=0x40&&$c2<=0xFC)||($c1==0xEB&&$c2>=0x40&&$c2<=0xBF)){$ch=($c1-0xC1)*0xC0+($c2-0x40);}else{$ch=0;}$code[]=$ch&0x1000;$code[]=$ch&0x0800;$code[]=$ch&0x0400;$code[]=$ch&0x0200;$code[]=$ch&0x0100;$code[]=$ch&0x0080;$code[]=$ch&0x0040;$code[]=$ch&0x0020;$code[]=$ch&0x0010;$code[]=$ch&0x0008;$code[]=$ch&0x0004;$code[]=$ch&0x0002;$code[]=$ch&0x0001;}return $code;}private function qr_encode_ec($data,$ec_params,$version){$blocks=$this->qr_ec_split($data,$ec_params);$ec_blocks=array();for($i=0,$n=count($blocks);$i<$n;$i++){$ec_blocks[]=$this->qr_ec_divide($blocks[$i],$ec_params);}$data=$this->qr_ec_interleave($blocks);$ec_data=$this->qr_ec_interleave($ec_blocks);$code=array();foreach($data as $ch){$code[]=$ch&0x80;$code[]=$ch&0x40;$code[]=$ch&0x20;$code[]=$ch&0x10;$code[]=$ch&0x08;$code[]=$ch&0x04;$code[]=$ch&0x02;$code[]=$ch&0x01;}foreach($ec_data as $ch){$code[]=$ch&0x80;$code[]=$ch&0x40;$code[]=$ch&0x20;$code[]=$ch&0x10;$code[]=$ch&0x08;$code[]=$ch&0x04;$code[]=$ch&0x02;$code[]=$ch&0x01;}for($n=$this->qr_remainder_bits[$version-1];$n>0;$n--){$code[]=0;}return $code;}private function qr_ec_split($data,$ec_params){$blocks=array();$offset=0;for($i=$ec_params[2],$length=$ec_params[3];$i>0;$i--){$blocks[]=array_slice($data,$offset,$length);$offset+=$length;}for($i=$ec_params[4],$length=$ec_params[5];$i>0;$i--){$blocks[]=array_slice($data,$offset,$length);$offset+=$length;}return $blocks;}private function qr_ec_divide($data,$ec_params){$num_data=count($data);$num_error=$ec_params[1];$generator=$this->qr_ec_polynomials[$num_error];$message=$data;for($i=0;$i<$num_error;$i++){$message[]=0;}for($i=0;$i<$num_data;$i++){if($message[$i]){$leadterm=$this->qr_log[$message[$i]];for($j=0;$j<=$num_error;$j++){$term=($generator[$j]+$leadterm)%255;$message[$i+$j]^=$this->qr_exp[$term];}}}return array_slice($message,$num_data,$num_error);}private function qr_ec_interleave($blocks){$data=array();$num_blocks=count($blocks);for($offset=0;true;$offset++){$break=true;for($i=0;$i<$num_blocks;$i++){if(isset($blocks[$i][$offset])){$data[]=$blocks[$i][$offset];$break=false;}}if($break)break;}return $data;}private function qr_create_matrix($version,$data){$size=$version*4+17;$matrix=array();for($i=0;$i<$size;$i++){$row=array();for($j=0;$j<$size;$j++){$row[]=0;}$matrix[]=$row;}for($i=0;$i<8;$i++){for($j=0;$j<8;$j++){$m=(($i==7||$j==7)?2:(($i==0||$j==0||$i==6||$j==6)?3:(($i==1||$j==1||$i==5||$j==5)?2:3)));$matrix[$i][$j]=$m;$matrix[$size-$i-1][$j]=$m;$matrix[$i][$size-$j-1]=$m;}}if($version>=2){$alignment=$this->qr_alignment_patterns[$version-2];foreach($alignment as $i){foreach($alignment as $j){if(!$matrix[$i][$j]){for($ii=-2;$ii<=2;$ii++){for($jj=-2;$jj<=2;$jj++){$m=(max(abs($ii),abs($jj))&1)^3;$matrix[$i+$ii][$j+$jj]=$m;}}}}}}for($i=$size-9;$i>=8;$i--){$matrix[$i][6]=($i&1)^3;$matrix[6][$i]=($i&1)^3;}$matrix[$size-8][8]=3;for($i=0;$i<=8;$i++){if(!$matrix[$i][8])$matrix[$i][8]=1;if(!$matrix[8][$i])$matrix[8][$i]=1;if($i&&!$matrix[$size-$i][8])$matrix[$size-$i][8]=1;if($i&&!$matrix[8][$size-$i])$matrix[8][$size-$i]=1;}if($version>=7){for($i=9;$i<12;$i++){for($j=0;$j<6;$j++){$matrix[$size-$i][$j]=1;$matrix[$j][$size-$i]=1;}}}$col=$size-1;$row=$size-1;$dir=-1;$offset=0;$length=count($data);while($col>0&&$offset<$length){if(!$matrix[$row][$col]){$matrix[$row][$col]=$data[$offset]?5:4;$offset++;}if(!$matrix[$row][$col-1]){$matrix[$row][$col-1]=$data[$offset]?5:4;$offset++;}$row+=$dir;if($row<0||$row>=$size){$dir=-$dir;$row+=$dir;$col-=2;if($col==6)$col--;}}return array($size,$matrix);}private function qr_apply_best_mask($matrix,$size){$best_mask=0;$best_matrix=$this->qr_apply_mask($matrix,$size,$best_mask);$best_penalty=$this->qr_penalty($best_matrix,$size);for($test_mask=1;$test_mask<8;$test_mask++){$test_matrix=$this->qr_apply_mask($matrix,$size,$test_mask);$test_penalty=$this->qr_penalty($test_matrix,$size);if($test_penalty<$best_penalty){$best_mask=$test_mask;$best_matrix=$test_matrix;$best_penalty=$test_penalty;}}return array($best_mask,$best_matrix);}private function qr_apply_mask($matrix,$size,$mask){for($i=0;$i<$size;$i++){for($j=0;$j<$size;$j++){if($matrix[$i][$j]>=4){if($this->qr_mask($mask,$i,$j)){$matrix[$i][$j]^=1;}}}}return $matrix;}private function qr_mask($mask,$r,$c){switch($mask){case 0:return!(($r+$c)%2);case 1:return!(($r)%2);case 2:return!(($c)%3);case 3:return!(($r+$c)%3);case 4:return!((floor(($r)/2)+floor(($c)/3))%2);case 5:return!(((($r*$c)%2)+(($r*$c)%3)));case 6:return!(((($r*$c)%2)+(($r*$c)%3))%2);case 7:return!(((($r+$c)%2)+(($r*$c)%3))%2);}}private function qr_penalty(&$matrix,$size){$score=$this->qr_penalty_1($matrix,$size);$score+=$this->qr_penalty_2($matrix,$size);$score+=$this->qr_penalty_3($matrix,$size);$score+=$this->qr_penalty_4($matrix,$size);return $score;}private function qr_penalty_1(&$matrix,$size){$score=0;for($i=0;$i<$size;$i++){$rowvalue=0;$rowcount=0;$colvalue=0;$colcount=0;for($j=0;$j<$size;$j++){$rv=($matrix[$i][$j]==5||$matrix[$i][$j]==3)?1:0;$cv=($matrix[$j][$i]==5||$matrix[$j][$i]==3)?1:0;if($rv==$rowvalue){$rowcount++;}else{if($rowcount>=5)$score+=$rowcount-2;$rowvalue=$rv;$rowcount=1;}if($cv==$colvalue){$colcount++;}else{if($colcount>=5)$score+=$colcount-2;$colvalue=$cv;$colcount=1;}}if($rowcount>=5)$score+=$rowcount-2;if($colcount>=5)$score+=$colcount-2;}return $score;}private function qr_penalty_2(&$matrix,$size){$score=0;for($i=1;$i<$size;$i++){for($j=1;$j<$size;$j++){$v1=$matrix[$i-1][$j-1];$v2=$matrix[$i-1][$j];$v3=$matrix[$i][$j-1];$v4=$matrix[$i][$j];$v1=($v1==5||$v1==3)?1:0;$v2=($v2==5||$v2==3)?1:0;$v3=($v3==5||$v3==3)?1:0;$v4=($v4==5||$v4==3)?1:0;if($v1==$v2&&$v2==$v3&&$v3==$v4)$score+=3;}}return $score;}private function qr_penalty_3(&$matrix,$size){$score=0;for($i=0;$i<$size;$i++){$rowvalue=0;$colvalue=0;for($j=0;$j<11;$j++){$rv=($matrix[$i][$j]==5||$matrix[$i][$j]==3)?1:0;$cv=($matrix[$j][$i]==5||$matrix[$j][$i]==3)?1:0;$rowvalue=(($rowvalue<<1)&0x7FF)|$rv;$colvalue=(($colvalue<<1)&0x7FF)|$cv;}if($rowvalue==0x5D0||$rowvalue==0x5D)$score+=40;if($colvalue==0x5D0||$colvalue==0x5D)$score+=40;for($j=11;$j<$size;$j++){$rv=($matrix[$i][$j]==5||$matrix[$i][$j]==3)?1:0;$cv=($matrix[$j][$i]==5||$matrix[$j][$i]==3)?1:0;$rowvalue=(($rowvalue<<1)&0x7FF)|$rv;$colvalue=(($colvalue<<1)&0x7FF)|$cv;if($rowvalue==0x5D0||$rowvalue==0x5D)$score+=40;if($colvalue==0x5D0||$colvalue==0x5D)$score+=40;}}return $score;}private function qr_penalty_4(&$matrix,$size){$dark=0;for($i=0;$i<$size;$i++){for($j=0;$j<$size;$j++){if($matrix[$i][$j]==5||$matrix[$i][$j]==3){$dark++;}}}$dark*=20;$dark/=$size*$size;$a=abs(floor($dark)-10);$b=abs(ceil($dark)-10);return min($a,$b)*10;}private function qr_finalize_matrix($matrix,$size,$ecl,$mask,$version){$format=$this->qr_format_info[$ecl*8+$mask];$matrix[8][0]=$format[0];$matrix[8][1]=$format[1];$matrix[8][2]=$format[2];$matrix[8][3]=$format[3];$matrix[8][4]=$format[4];$matrix[8][5]=$format[5];$matrix[8][7]=$format[6];$matrix[8][8]=$format[7];$matrix[7][8]=$format[8];$matrix[5][8]=$format[9];$matrix[4][8]=$format[10];$matrix[3][8]=$format[11];$matrix[2][8]=$format[12];$matrix[1][8]=$format[13];$matrix[0][8]=$format[14];$matrix[$size-1][8]=$format[0];$matrix[$size-2][8]=$format[1];$matrix[$size-3][8]=$format[2];$matrix[$size-4][8]=$format[3];$matrix[$size-5][8]=$format[4];$matrix[$size-6][8]=$format[5];$matrix[$size-7][8]=$format[6];$matrix[8][$size-8]=$format[7];$matrix[8][$size-7]=$format[8];$matrix[8][$size-6]=$format[9];$matrix[8][$size-5]=$format[10];$matrix[8][$size-4]=$format[11];$matrix[8][$size-3]=$format[12];$matrix[8][$size-2]=$format[13];$matrix[8][$size-1]=$format[14];if($version>=7){$version=$this->qr_version_info[$version-7];for($i=0;$i<18;$i++){$r=$size-9-($i%3);$c=5-floor($i/3);$matrix[$r][$c]=$version[$i];$matrix[$c][$r]=$version[$i];}}for($i=0;$i<$size;$i++){for($j=0;$j<$size;$j++){$matrix[$i][$j]&=1;}}return $matrix;}private $qr_capacity=array(array(array(41,25,17,10),array(34,20,14,8),array(27,16,11,7),array(17,10,7,4)),array(array(77,47,32,20),array(63,38,26,16),array(48,29,20,12),array(34,20,14,8)),array(array(127,77,53,32),array(101,61,42,26),array(77,47,32,20),array(58,35,24,15)),array(array(187,114,78,48),array(149,90,62,38),array(111,67,46,28),array(82,50,34,21)),array(array(255,154,106,65),array(202,122,84,52),array(144,87,60,37),array(106,64,44,27)),array(array(322,195,134,82),array(255,154,106,65),array(178,108,74,45),array(139,84,58,36)),array(array(370,224,154,95),array(293,178,122,75),array(207,125,86,53),array(154,93,64,39)),array(array(461,279,192,118),array(365,221,152,93),array(259,157,108,66),array(202,122,84,52)),array(array(552,335,230,141),array(432,262,180,111),array(312,189,130,80),array(235,143,98,60)),array(array(652,395,271,167),array(513,311,213,131),array(364,221,151,93),array(288,174,119,74)),array(array(772,468,321,198),array(604,366,251,155),array(427,259,177,109),array(331,200,137,85)),array(array(883,535,367,226),array(691,419,287,177),array(489,296,203,125),array(374,227,155,96)),array(array(1022,619,425,262),array(796,483,331,204),array(580,352,241,149),array(427,259,177,109)),array(array(1101,667,458,282),array(871,528,362,223),array(621,376,258,159),array(468,283,194,120)),array(array(1250,758,520,320),array(991,600,412,254),array(703,426,292,180),array(530,321,220,136)),array(array(1408,854,586,361),array(1082,656,450,277),array(775,470,322,198),array(602,365,250,154)),array(array(1548,938,644,397),array(1212,734,504,310),array(876,531,364,224),array(674,408,280,173)),array(array(1725,1046,718,442),array(1346,816,560,345),array(948,574,394,243),array(746,452,310,191)),array(array(1903,1153,792,488),array(1500,909,624,384),array(1063,644,442,272),array(813,493,338,208)),array(array(2061,1249,858,528),array(1600,970,666,410),array(1159,702,482,297),array(919,557,382,235)),array(array(2232,1352,929,572),array(1708,1035,711,438),array(1224,742,509,314),array(969,587,403,248)),array(array(2409,1460,1003,618),array(1872,1134,779,480),array(1358,823,565,348),array(1056,640,439,270)),array(array(2620,1588,1091,672),array(2059,1248,857,528),array(1468,890,611,376),array(1108,672,461,284)),array(array(2812,1704,1171,721),array(2188,1326,911,561),array(1588,963,661,407),array(1228,744,511,315)),array(array(3057,1853,1273,784),array(2395,1451,997,614),array(1718,1041,715,440),array(1286,779,535,330)),array(array(3283,1990,1367,842),array(2544,1542,1059,652),array(1804,1094,751,462),array(1425,864,593,365)),array(array(3517,2132,1465,902),array(2701,1637,1125,692),array(1933,1172,805,496),array(1501,910,625,385)),array(array(3669,2223,1528,940),array(2857,1732,1190,732),array(2085,1263,868,534),array(1581,958,658,405)),array(array(3909,2369,1628,1002),array(3035,1839,1264,778),array(2181,1322,908,559),array(1677,1016,698,430)),array(array(4158,2520,1732,1066),array(3289,1994,1370,843),array(2358,1429,982,604),array(1782,1080,742,457)),array(array(4417,2677,1840,1132),array(3486,2113,1452,894),array(2473,1499,1030,634),array(1897,1150,790,486)),array(array(4686,2840,1952,1201),array(3693,2238,1538,947),array(2670,1618,1112,684),array(2022,1226,842,518)),array(array(4965,3009,2068,1273),array(3909,2369,1628,1002),array(2805,1700,1168,719),array(2157,1307,898,553)),array(array(5253,3183,2188,1347),array(4134,2506,1722,1060),array(2949,1787,1228,756),array(2301,1394,958,590)),array(array(5529,3351,2303,1417),array(4343,2632,1809,1113),array(3081,1867,1283,790),array(2361,1431,983,605)),array(array(5836,3537,2431,1496),array(4588,2780,1911,1176),array(3244,1966,1351,832),array(2524,1530,1051,647)),array(array(6153,3729,2563,1577),array(4775,2894,1989,1224),array(3417,2071,1423,876),array(2625,1591,1093,673)),array(array(6479,3927,2699,1661),array(5039,3054,2099,1292),array(3599,2181,1499,923),array(2735,1658,1139,701)),array(array(6743,4087,2809,1729),array(5313,3220,2213,1362),array(3791,2298,1579,972),array(2927,1774,1219,750)),array(array(7089,4296,2953,1817),array(5596,3391,2331,1435),array(3993,2420,1663,1024),array(3057,1852,1273,784)),);private $qr_ec_params=array(array(19,7,1,19,0,0),array(16,10,1,16,0,0),array(13,13,1,13,0,0),array(9,17,1,9,0,0),array(34,10,1,34,0,0),array(28,16,1,28,0,0),array(22,22,1,22,0,0),array(16,28,1,16,0,0),array(55,15,1,55,0,0),array(44,26,1,44,0,0),array(34,18,2,17,0,0),array(26,22,2,13,0,0),array(80,20,1,80,0,0),array(64,18,2,32,0,0),array(48,26,2,24,0,0),array(36,16,4,9,0,0),array(108,26,1,108,0,0),array(86,24,2,43,0,0),array(62,18,2,15,2,16),array(46,22,2,11,2,12),array(136,18,2,68,0,0),array(108,16,4,27,0,0),array(76,24,4,19,0,0),array(60,28,4,15,0,0),array(156,20,2,78,0,0),array(124,18,4,31,0,0),array(88,18,2,14,4,15),array(66,26,4,13,1,14),array(194,24,2,97,0,0),array(154,22,2,38,2,39),array(110,22,4,18,2,19),array(86,26,4,14,2,15),array(232,30,2,116,0,0),array(182,22,3,36,2,37),array(132,20,4,16,4,17),array(100,24,4,12,4,13),array(274,18,2,68,2,69),array(216,26,4,43,1,44),array(154,24,6,19,2,20),array(122,28,6,15,2,16),array(324,20,4,81,0,0),array(254,30,1,50,4,51),array(180,28,4,22,4,23),array(140,24,3,12,8,13),array(370,24,2,92,2,93),array(290,22,6,36,2,37),array(206,26,4,20,6,21),array(158,28,7,14,4,15),array(428,26,4,107,0,0),array(334,22,8,37,1,38),array(244,24,8,20,4,21),array(180,22,12,11,4,12),array(461,30,3,115,1,116),array(365,24,4,40,5,41),array(261,20,11,16,5,17),array(197,24,11,12,5,13),array(523,22,5,87,1,88),array(415,24,5,41,5,42),array(295,30,5,24,7,25),array(223,24,11,12,7,13),array(589,24,5,98,1,99),array(453,28,7,45,3,46),array(325,24,15,19,2,20),array(253,30,3,15,13,16),array(647,28,1,107,5,108),array(507,28,10,46,1,47),array(367,28,1,22,15,23),array(283,28,2,14,17,15),array(721,30,5,120,1,121),array(563,26,9,43,4,44),array(397,28,17,22,1,23),array(313,28,2,14,19,15),array(795,28,3,113,4,114),array(627,26,3,44,11,45),array(445,26,17,21,4,22),array(341,26,9,13,16,14),array(861,28,3,107,5,108),array(669,26,3,41,13,42),array(485,30,15,24,5,25),array(385,28,15,15,10,16),array(932,28,4,116,4,117),array(714,26,17,42,0,0),array(512,28,17,22,6,23),array(406,30,19,16,6,17),array(1006,28,2,111,7,112),array(782,28,17,46,0,0),array(568,30,7,24,16,25),array(442,24,34,13,0,0),array(1094,30,4,121,5,122),array(860,28,4,47,14,48),array(614,30,11,24,14,25),array(464,30,16,15,14,16),array(1174,30,6,117,4,118),array(914,28,6,45,14,46),array(664,30,11,24,16,25),array(514,30,30,16,2,17),array(1276,26,8,106,4,107),array(1000,28,8,47,13,48),array(718,30,7,24,22,25),array(538,30,22,15,13,16),array(1370,28,10,114,2,115),array(1062,28,19,46,4,47),array(754,28,28,22,6,23),array(596,30,33,16,4,17),array(1468,30,8,122,4,123),array(1128,28,22,45,3,46),array(808,30,8,23,26,24),array(628,30,12,15,28,16),array(1531,30,3,117,10,118),array(1193,28,3,45,23,46),array(871,30,4,24,31,25),array(661,30,11,15,31,16),array(1631,30,7,116,7,117),array(1267,28,21,45,7,46),array(911,30,1,23,37,24),array(701,30,19,15,26,16),array(1735,30,5,115,10,116),array(1373,28,19,47,10,48),array(985,30,15,24,25,25),array(745,30,23,15,25,16),array(1843,30,13,115,3,116),array(1455,28,2,46,29,47),array(1033,30,42,24,1,25),array(793,30,23,15,28,16),array(1955,30,17,115,0,0),array(1541,28,10,46,23,47),array(1115,30,10,24,35,25),array(845,30,19,15,35,16),array(2071,30,17,115,1,116),array(1631,28,14,46,21,47),array(1171,30,29,24,19,25),array(901,30,11,15,46,16),array(2191,30,13,115,6,116),array(1725,28,14,46,23,47),array(1231,30,44,24,7,25),array(961,30,59,16,1,17),array(2306,30,12,121,7,122),array(1812,28,12,47,26,48),array(1286,30,39,24,14,25),array(986,30,22,15,41,16),array(2434,30,6,121,14,122),array(1914,28,6,47,34,48),array(1354,30,46,24,10,25),array(1054,30,2,15,64,16),array(2566,30,17,122,4,123),array(1992,28,29,46,14,47),array(1426,30,49,24,10,25),array(1096,30,24,15,46,16),array(2702,30,4,122,18,123),array(2102,28,13,46,32,47),array(1502,30,48,24,14,25),array(1142,30,42,15,32,16),array(2812,30,20,117,4,118),array(2216,28,40,47,7,48),array(1582,30,43,24,22,25),array(1222,30,10,15,67,16),array(2956,30,19,118,6,119),array(2334,28,18,47,31,48),array(1666,30,34,24,34,25),array(1276,30,20,15,61,16),);private $qr_ec_polynomials=array(7=>array(0,87,229,146,149,238,102,21),10=>array(0,251,67,46,61,118,70,64,94,32,45),13=>array(0,74,152,176,100,86,100,106,104,130,218,206,140,78),15=>array(0,8,183,61,91,202,37,51,58,58,237,140,124,5,99,105),16=>array(0,120,104,107,109,102,161,76,3,91,191,147,169,182,194,225,120),17=>array(0,43,139,206,78,43,239,123,206,214,147,24,99,150,39,243,163,136),18=>array(0,215,234,158,94,184,97,118,170,79,187,152,148,252,179,5,98,96,153),20=>array(0,17,60,79,50,61,163,26,187,202,180,221,225,83,239,156,164,212,212,188,190),22=>array(0,210,171,247,242,93,230,14,109,221,53,200,74,8,172,98,80,219,134,160,105,165,231),24=>array(0,229,121,135,48,211,117,251,126,159,180,169,152,192,226,228,218,111,0,117,232,87,96,227,21),26=>array(0,173,125,158,2,103,182,118,17,145,201,111,28,165,53,161,21,245,142,13,102,48,227,153,145,218,70),28=>array(0,168,223,200,104,224,234,108,180,110,190,195,147,205,27,232,201,21,43,245,87,42,195,212,119,242,37,9,123),30=>array(0,41,173,145,152,216,31,179,182,50,48,110,86,239,96,222,125,42,173,226,193,224,130,156,37,251,216,238,40,192,180),);private $qr_log=array(0,0,1,25,2,50,26,198,3,223,51,238,27,104,199,75,4,100,224,14,52,141,239,129,28,193,105,248,200,8,76,113,5,138,101,47,225,36,15,33,53,147,142,218,240,18,130,69,29,181,194,125,106,39,249,185,201,154,9,120,77,228,114,166,6,191,139,98,102,221,48,253,226,152,37,179,16,145,34,136,54,208,148,206,143,150,219,189,241,210,19,92,131,56,70,64,30,66,182,163,195,72,126,110,107,58,40,84,250,133,186,61,202,94,155,159,10,21,121,43,78,212,229,172,115,243,167,87,7,112,192,247,140,128,99,13,103,74,222,237,49,197,254,24,227,165,153,119,38,184,180,124,17,68,146,217,35,32,137,46,55,63,209,91,149,188,207,205,144,135,151,178,220,252,190,97,242,86,211,171,20,42,93,158,132,60,57,83,71,109,65,162,31,45,67,216,183,123,164,118,196,23,73,236,127,12,111,246,108,161,59,82,41,157,85,170,251,96,134,177,187,204,62,90,203,89,95,176,156,169,160,81,11,245,22,235,122,117,44,215,79,174,213,233,230,231,173,232,116,214,244,234,168,80,88,175,);private $qr_exp=array(1,2,4,8,16,32,64,128,29,58,116,232,205,135,19,38,76,152,45,90,180,117,234,201,143,3,6,12,24,48,96,192,157,39,78,156,37,74,148,53,106,212,181,119,238,193,159,35,70,140,5,10,20,40,80,160,93,186,105,210,185,111,222,161,95,190,97,194,153,47,94,188,101,202,137,15,30,60,120,240,253,231,211,187,107,214,177,127,254,225,223,163,91,182,113,226,217,175,67,134,17,34,68,136,13,26,52,104,208,189,103,206,129,31,62,124,248,237,199,147,59,118,236,197,151,51,102,204,133,23,46,92,184,109,218,169,79,158,33,66,132,21,42,84,168,77,154,41,82,164,85,170,73,146,57,114,228,213,183,115,230,209,191,99,198,145,63,126,252,229,215,179,123,246,241,255,227,219,171,75,150,49,98,196,149,55,110,220,165,87,174,65,130,25,50,100,200,141,7,14,28,56,112,224,221,167,83,166,81,162,89,178,121,242,249,239,195,155,43,86,172,69,138,9,18,36,72,144,61,122,244,245,247,243,251,235,203,139,11,22,44,88,176,125,250,233,207,131,27,54,108,216,173,71,142,1,);private $qr_remainder_bits=array(0,7,7,7,7,7,0,0,0,0,0,0,0,3,3,3,3,3,3,3,4,4,4,4,4,4,4,3,3,3,3,3,3,3,0,0,0,0,0,0,);private $qr_alignment_patterns=array(array(6,18),array(6,22),array(6,26),array(6,30),array(6,34),array(6,22,38),array(6,24,42),array(6,26,46),array(6,28,50),array(6,30,54),array(6,32,58),array(6,34,62),array(6,26,46,66),array(6,26,48,70),array(6,26,50,74),array(6,30,54,78),array(6,30,56,82),array(6,30,58,86),array(6,34,62,90),array(6,28,50,72,94),array(6,26,50,74,98),array(6,30,54,78,102),array(6,28,54,80,106),array(6,32,58,84,110),array(6,30,58,86,114),array(6,34,62,90,118),array(6,26,50,74,98,122),array(6,30,54,78,102,126),array(6,26,52,78,104,130),array(6,30,56,82,108,134),array(6,34,60,86,112,138),array(6,30,58,86,114,142),array(6,34,62,90,118,146),array(6,30,54,78,102,126,150),array(6,24,50,76,102,128,154),array(6,28,54,80,106,132,158),array(6,32,58,84,110,136,162),array(6,26,54,82,110,138,166),array(6,30,58,86,114,142,170),);private $qr_format_info=array(array(1,1,1,0,1,1,1,1,1,0,0,0,1,0,0),array(1,1,1,0,0,1,0,1,1,1,1,0,0,1,1),array(1,1,1,1,1,0,1,1,0,1,0,1,0,1,0),array(1,1,1,1,0,0,0,1,0,0,1,1,1,0,1),array(1,1,0,0,1,1,0,0,0,1,0,1,1,1,1),array(1,1,0,0,0,1,1,0,0,0,1,1,0,0,0),array(1,1,0,1,1,0,0,0,1,0,0,0,0,0,1),array(1,1,0,1,0,0,1,0,1,1,1,0,1,1,0),array(1,0,1,0,1,0,0,0,0,0,1,0,0,1,0),array(1,0,1,0,0,0,1,0,0,1,0,0,1,0,1),array(1,0,1,1,1,1,0,0,1,1,1,1,1,0,0),array(1,0,1,1,0,1,1,0,1,0,0,1,0,1,1),array(1,0,0,0,1,0,1,1,1,1,1,1,0,0,1),array(1,0,0,0,0,0,0,1,1,0,0,1,1,1,0),array(1,0,0,1,1,1,1,1,0,0,1,0,1,1,1),array(1,0,0,1,0,1,0,1,0,1,0,0,0,0,0),array(0,1,1,0,1,0,1,0,1,0,1,1,1,1,1),array(0,1,1,0,0,0,0,0,1,1,0,1,0,0,0),array(0,1,1,1,1,1,1,0,0,1,1,0,0,0,1),array(0,1,1,1,0,1,0,0,0,0,0,0,1,1,0),array(0,1,0,0,1,0,0,1,0,1,1,0,1,0,0),array(0,1,0,0,0,0,1,1,0,0,0,0,0,1,1),array(0,1,0,1,1,1,0,1,1,0,1,1,0,1,0),array(0,1,0,1,0,1,1,1,1,1,0,1,1,0,1),array(0,0,1,0,1,1,0,1,0,0,0,1,0,0,1),array(0,0,1,0,0,1,1,1,0,1,1,1,1,1,0),array(0,0,1,1,1,0,0,1,1,1,0,0,1,1,1),array(0,0,1,1,0,0,1,1,1,0,1,0,0,0,0),array(0,0,0,0,1,1,1,0,1,1,0,0,0,1,0),array(0,0,0,0,0,1,0,0,1,0,1,0,1,0,1),array(0,0,0,1,1,0,1,0,0,0,0,1,1,0,0),array(0,0,0,1,0,0,0,0,0,1,1,1,0,1,1),);private $qr_version_info=array(array(0,0,0,1,1,1,1,1,0,0,1,0,0,1,0,1,0,0),array(0,0,1,0,0,0,0,1,0,1,1,0,1,1,1,1,0,0),array(0,0,1,0,0,1,1,0,1,0,1,0,0,1,1,0,0,1),array(0,0,1,0,1,0,0,1,0,0,1,1,0,1,0,0,1,1),array(0,0,1,0,1,1,1,0,1,1,1,1,1,1,0,1,1,0),array(0,0,1,1,0,0,0,1,1,1,0,1,1,0,0,0,1,0),array(0,0,1,1,0,1,1,0,0,0,0,1,0,0,0,1,1,1),array(0,0,1,1,1,0,0,1,1,0,0,0,0,0,1,1,0,1),array(0,0,1,1,1,1,1,0,0,1,0,0,1,0,1,0,0,0),array(0,1,0,0,0,0,1,0,1,1,0,1,1,1,1,0,0,0),array(0,1,0,0,0,1,0,1,0,0,0,1,0,1,1,1,0,1),array(0,1,0,0,1,0,1,0,1,0,0,0,0,1,0,1,1,1),array(0,1,0,0,1,1,0,1,0,1,0,0,1,1,0,0,1,0),array(0,1,0,1,0,0,1,0,0,1,1,0,1,0,0,1,1,0),array(0,1,0,1,0,1,0,1,1,0,1,0,0,0,0,0,1,1),array(0,1,0,1,1,0,1,0,0,0,1,1,0,0,1,0,0,1),array(0,1,0,1,1,1,0,1,1,1,1,1,1,0,1,1,0,0),array(0,1,1,0,0,0,1,1,1,0,1,1,0,0,0,1,0,0),array(0,1,1,0,0,1,0,0,0,1,1,1,1,0,0,0,0,1),array(0,1,1,0,1,0,1,1,1,1,1,0,1,0,1,0,1,1),array(0,1,1,0,1,1,0,0,0,0,1,0,0,0,1,1,1,0),array(0,1,1,1,0,0,1,1,0,0,0,0,0,1,1,0,1,0),array(0,1,1,1,0,1,0,0,1,1,0,0,1,1,1,1,1,1),array(0,1,1,1,1,0,1,1,0,1,0,1,1,1,0,1,0,1),array(0,1,1,1,1,1,0,0,1,0,0,1,0,1,0,0,0,0),array(1,0,0,0,0,0,1,0,0,1,1,1,0,1,0,1,0,1),array(1,0,0,0,0,1,0,1,1,0,1,1,1,1,0,0,0,0),array(1,0,0,0,1,0,1,0,0,0,1,0,1,1,1,0,1,0),array(1,0,0,0,1,1,0,1,1,1,1,0,0,1,1,1,1,1),array(1,0,0,1,0,0,1,0,1,1,0,0,0,0,1,0,1,1),array(1,0,0,1,0,1,0,1,0,0,0,0,1,0,1,1,1,0),array(1,0,0,1,1,0,1,0,1,0,0,1,1,0,0,1,0,0),array(1,0,0,1,1,1,0,1,0,1,0,1,0,0,0,0,0,1),array(1,0,1,0,0,0,1,1,0,0,0,1,1,0,1,0,0,1),);}

 class Paginator{ const NUM_PLACEHOLDER='(:num)'; protected $totalItems; protected $numPages; protected $itemsPerPage; protected $currentPage; protected $urlPattern; protected $maxPagesToShow=10; protected $previousText='Previous'; protected $nextText='Next'; public function __construct($totalItems,$itemsPerPage,$currentPage,$urlPattern=''){ $this->totalItems=$totalItems; $this->itemsPerPage=$itemsPerPage; $this->currentPage=$currentPage; $this->urlPattern=$urlPattern; $this->updateNumPages(); } protected function updateNumPages(){ $this->numPages=($this->itemsPerPage==0?0:(int) ceil($this->totalItems/$this->itemsPerPage)); } public function setMaxPagesToShow($maxPagesToShow){ if($maxPagesToShow<3){ throw new \InvalidArgumentException('maxPagesToShow cannot be less than 3.'); } $this->maxPagesToShow=$maxPagesToShow; } public function getMaxPagesToShow(){ return $this->maxPagesToShow; } public function setCurrentPage($currentPage){ $this->currentPage=$currentPage; } public function getCurrentPage(){ return $this->currentPage; } public function setItemsPerPage($itemsPerPage){ $this->itemsPerPage=$itemsPerPage; $this->updateNumPages(); } public function getItemsPerPage(){ return $this->itemsPerPage; } public function setTotalItems($totalItems){ $this->totalItems=$totalItems; $this->updateNumPages(); } public function getTotalItems(){ return $this->totalItems; } public function getNumPages(){ return $this->numPages; } public function setUrlPattern($urlPattern){ $this->urlPattern=$urlPattern; } public function getUrlPattern(){ return $this->urlPattern; } public function getPageUrl($pageNum){ return str_replace(self::NUM_PLACEHOLDER,$pageNum,$this->urlPattern); } public function getNextPage(){ if($this->currentPage<$this->numPages){ return $this->currentPage+1; } return null; } public function getPrevPage(){ if($this->currentPage>1){ return $this->currentPage-1; } return null; } public function getNextUrl(){ if(!$this->getNextPage()){ return null; } return $this->getPageUrl($this->getNextPage()); } public function getPrevUrl(){ if(!$this->getPrevPage()){ return null; } return $this->getPageUrl($this->getPrevPage()); } public function getPages(){ $pages=array(); if($this->numPages<=1){ return array(); } if($this->numPages<=$this->maxPagesToShow){ for($i=1; $i<=$this->numPages; $i++){ $pages[]=$this->createPage($i,$i==$this->currentPage); } } else{ $numAdjacents=(int) floor(($this->maxPagesToShow-3)/2); if($this->currentPage+$numAdjacents>$this->numPages){ $slidingStart=$this->numPages-$this->maxPagesToShow+2; } else{ $slidingStart=$this->currentPage-$numAdjacents; } if($slidingStart<2)$slidingStart=2; $slidingEnd=$slidingStart+$this->maxPagesToShow-3; if($slidingEnd>=$this->numPages)$slidingEnd=$this->numPages-1; $pages[]=$this->createPage(1,$this->currentPage==1); if($slidingStart>2){ $pages[]=$this->createPageEllipsis(); } for($i=$slidingStart; $i<=$slidingEnd; $i++){ $pages[]=$this->createPage($i,$i==$this->currentPage); } if($slidingEnd<$this->numPages-1){ $pages[]=$this->createPageEllipsis(); } $pages[]=$this->createPage($this->numPages,$this->currentPage==$this->numPages); } return $pages; } protected function createPage($pageNum,$isCurrent=false){ return array('num'=>$pageNum,'url'=>$this->getPageUrl($pageNum),'isCurrent'=>$isCurrent,); } protected function createPageEllipsis(){ return array('num'=>'...','url'=>null,'isCurrent'=>false,); } public function toHtml(){ if($this->numPages<=1){ return ''; } $html='<ul class="pagination">'; if($this->getPrevUrl()){ $html.='<li class="page-item"><a class="page-link" href="'.htmlspecialchars($this->getPrevUrl()).'">&laquo; '.$this->previousText.'</a></li>'; } foreach($this->getPages()as $page){ if($page['url']){ $html.='<li class="page-item '.($page['isCurrent']?' active':'').'"><a class="page-link" href="'.htmlspecialchars($page['url']).'">'.htmlspecialchars($page['num']).'</a></li>'; } else{ $html.='<li class="disabled page-item"><span>'.htmlspecialchars($page['num']).'</span></li>'; } } if($this->getNextUrl()){ $html.='<li class="page-item"><a class="page-link" href="'.htmlspecialchars($this->getNextUrl()).'">'.$this->nextText.' &raquo; </a></li>'; } $html.='</ul>'; return $html; } public function __toString(){ return $this->toHtml(); } public function getCurrentPageFirstItem(){ $first=($this->currentPage-1)*$this->itemsPerPage+1; if($first>$this->totalItems){ return null; } return $first; } public function getCurrentPageLastItem(){ $first=$this->getCurrentPageFirstItem(); if($first===null){ return null; } $last=$first+$this->itemsPerPage-1; if($last>$this->totalItems){ return $this->totalItems; } return $last; } public function setPreviousText($text){ $this->previousText=$text; return $this; } public function setNextText($text){ $this->nextText=$text; return $this; } }

class Base32Static{private static $map=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','2','3','4','5','6','7','=');private static $flippedMap=array('A'=>'0','B'=>'1','C'=>'2','D'=>'3','E'=>'4','F'=>'5','G'=>'6','H'=>'7','I'=>'8','J'=>'9','K'=>'10','L'=>'11','M'=>'12','N'=>'13','O'=>'14','P'=>'15','Q'=>'16','R'=>'17','S'=>'18','T'=>'19','U'=>'20','V'=>'21','W'=>'22','X'=>'23','Y'=>'24','Z'=>'25','2'=>'26','3'=>'27','4'=>'28','5'=>'29','6'=>'30','7'=>'31');public static function encode($input,$padding=true){if(empty($input))return "";$input=str_split($input);$binaryString="";for($i=0;$i<count($input);$i++){$binaryString.=str_pad(base_convert(ord($input[$i]),10,2),8,'0',STR_PAD_LEFT);}$fiveBitBinaryArray=str_split($binaryString,5);$base32="";$i=0;while($i<count($fiveBitBinaryArray)){$base32.=self::$map[base_convert(str_pad($fiveBitBinaryArray[$i],5,'0'),2,10)];$i++;}if($padding&&($x=strlen($binaryString)%40)!=0){if($x==8)$base32.=str_repeat(self::$map[32],6);else if($x==16)$base32.=str_repeat(self::$map[32],4);else if($x==24)$base32.=str_repeat(self::$map[32],3);else if($x==32)$base32.=self::$map[32];}return $base32;}public static function decode($input){if(empty($input))return;$paddingCharCount=substr_count($input,self::$map[32]);$allowedValues=array(6,4,3,1,0);if(!in_array($paddingCharCount,$allowedValues))return false;for($i=0;$i<4;$i++){if($paddingCharCount==$allowedValues[$i]&&substr($input,-($allowedValues[$i]))!=str_repeat(self::$map[32],$allowedValues[$i]))return false;}$input=str_replace('=','',$input);$input=str_split($input);$binaryString="";for($i=0;$i<count($input);$i=$i+8){$x="";if(!in_array($input[$i],self::$map))return false;for($j=0;$j<8;$j++){$x.=str_pad(base_convert(@self::$flippedMap[@$input[$i+$j]],10,2),5,'0',STR_PAD_LEFT);}$eightBits=str_split($x,8);for($z=0;$z<count($eightBits);$z++){$binaryString.=(($y=chr(base_convert($eightBits[$z],2,10)))||ord($y)==48)?$y:"";}}return $binaryString;}}
//?chs=200x200&chld=M|0&cht=qr&
class TokenAuth6238{public static function verify($secretkey,$code,$rangein30s=3){$key=base32static::decode($secretkey);$unixtimestamp=time()/30;for($i=-($rangein30s);$i<=$rangein30s;$i++){$checktime=(int)($unixtimestamp+$i);$thiskey=self::oath_hotp($key,$checktime);if((int)$code==self::oath_truncate($thiskey,6)){return true;}}return false;}public static function getTokenCode($secretkey,$rangein30s=3){$result="";$key=base32static::decode($secretkey);$unixtimestamp=time()/30;for($i=-($rangein30s);$i<=$rangein30s;$i++){$checktime=(int)($unixtimestamp+$i);$thiskey=self::oath_hotp($key,$checktime);$result=$result." # ".self::oath_truncate($thiskey,6);}return $result;}public static function getTokenCodeDebug($secretkey,$rangein30s=3){$result="";print"<br/>SecretKey: $secretkey <br/>";$key=base32static::decode($secretkey);print"Key(base 32 decode): $key <br/>";$unixtimestamp=time()/30;print"UnixTimeStamp (time()/30): $unixtimestamp <br/>";for($i=-($rangein30s);$i<=$rangein30s;$i++){$checktime=(int)($unixtimestamp+$i);print"Calculating oath_hotp from (int)(unixtimestamp +- 30sec offset): $checktime basing on secret key<br/>";$thiskey=self::oath_hotp($key,$checktime,true);print "======================================================<br/>";print"CheckTime: $checktime oath_hotp:".$thiskey."<br/>";$result=$result." # ".self::oath_truncate($thiskey,6,true);}return $result;}public static function getBarCodeUrl($username,$domain,$secretkey){$url="/eb430691fe30d16070b5a144c3d3303c?d=";$url=$url."otpauth://totp/";$url=$url.$username."@".$domain."%3Fsecret%3D".$secretkey;return $url;}public static function generateRandomClue($length=16){$b32="234567QWERTYUIOPASDFGHJKLZXCVBNM";$s="";for($i=0;$i<$length;$i++)$s.=$b32[rand(0,31)];return $s;}private static function hotp_tobytestream($key){$result=array();$last=strlen($key);for($i=0;$i<$last;$i=$i+2){$x=$key[$i]+$key[$i+1];$x=strtoupper($x);$x=hexdec($x);$result=$result.chr($x);}return $result;}private static function oath_hotp($key,$counter,$debug=false){$result="";$orgcounter=$counter;$cur_counter=array(0,0,0,0,0,0,0,0);if($debug){print"Packing counter $counter (".dechex($counter).")into binary string - pay attention to hex representation of key and binary representation<br/>";}for($i=7;$i>=0;$i--){$cur_counter[$i]=pack('C*',$counter);if($debug){print $cur_counter[$i]."(".dechex(ord($cur_counter[$i])).")"." from $counter <br/>";}$counter=$counter>>8;}if($debug){foreach($cur_counter as $char){print ord($char)." ";}print "<br/>";}$binary=implode($cur_counter);str_pad($binary,8,chr(0),STR_PAD_LEFT);if($debug){print "Prior to HMAC calculation pad with zero on the left until 8 characters.<br/>";print "Calculate sha1 HMAC(Hash-based Message Authentication Code http://en.wikipedia.org/wiki/HMAC).<br/>";print"hash_hmac ('sha1', $binary, $key)<br/>";}$result=hash_hmac('sha1',$binary,$key);if($debug){print"Result: $result <br/>";}return $result;}private static function oath_truncate($hash,$length=6,$debug=false){$result="";if($debug){print "converting hex hash into characters<br/>";}$hashcharacters=str_split($hash,2);if($debug){print_r($hashcharacters);print "<br/>and convert to decimals:<br/>";}for($j=0;$j<count($hashcharacters);$j++){$hmac_result[]=hexdec($hashcharacters[$j]);}if($debug){print_r($hmac_result);}$offset=$hmac_result[19]&0xf;if($debug){print "Calculating offset as 19th element of hmac:".$hmac_result[19]."<br/>";print "offset:".$offset;}$result=((($hmac_result[$offset+0]&0x7f)<<24)|(($hmac_result[$offset+1]&0xff)<<16)|(($hmac_result[$offset+2]&0xff)<<8)|($hmac_result[$offset+3]&0xff))%pow(10,$length);return $result;}}

class CryptoValidator {
    private $patterns = [
        1 => [
            'name' => 'Bitcoin',
            'address' => '/^(bc1|[13])[a-zA-HJ-NP-Z0-9]{25,39}$/'
        ],
        2 => [
            'name' => 'Perfect Money',
            'address' => '/^[UE][0-9]{7,9}$/'
        ],
        3 => [
            'name' => 'Solana',
            'address' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/'
        ],
        4 => [
            'name' => 'Ethereum',
            'address' => '/^(0x)?[0-9a-fA-F]{40}$/'
        ],
        5 => [
            'name' => 'Litecoin',
            'address' => '/^ltc1|[LM3][a-km-zA-HJ-NP-Z1-9]{25,40}$/'
        ],
        6 => [
            'name' => 'Payeer',
            'address' => '/^P[0-9]{7,12}$/'
        ],
        7 => [
            'name' => 'Dogecoin',
            'address' => '/^[DA9][a-km-zA-HJ-NP-Z1-9]{25,34}$/'
        ],
        8 => [
            'name' => 'DASH',
            'address' => '/^X[0-9a-zA-Z]{33}$/'
        ],
        9 => [
            'name' => 'ZCASH',
            'address' => '/^t1[a-km-zA-HJ-NP-Z1-9]{33}$/'
        ],
        10 => [
            'name' => 'Bitcoin Cash',
            'address' => '/^[\w\d]{25,43}$/'
        ],
        11 => [
            'name' => 'Monero',
            'address' => '/^4([0-9AB]{1})([0-9a-zA-Z]{93})$/'
        ],
        12 => [
            'name' => 'Ripple',
            'address' => '/^r[1-9A-HJ-NP-Za-km-z]{25,33}$/'
        ],
        14 => [
            'name' => 'TRON',
            'address' => '/^T[a-km-zA-HJ-NP-Z1-9]{25,34}$/'
        ],
        17 => [
            'name' => 'ePayCore',
            'address' => '/^E[0-9]{6,8}$/'
        ],
        26 => [
            'name' => 'TON',
            'address' => '/^(?:EQ|UQ)[a-zA-Z0-9_-]{48}$/'
        ]
    ];

    private $patternAliases = [
        13 => 4,  // Ethereum Classic uses ETH pattern
        18 => 14, // USDT TRC20 uses TRON pattern
        19 => 4,  // USDT BEP20 uses ETH pattern
        20 => 4,  // Binance Coin uses ETH pattern
        21 => 4,  // USDT ERC20 uses ETH pattern
        23 => 4,  // USDT POLYGON uses ETH pattern
        24 => 4,  // MATIC uses ETH pattern
        25 => 26  // USDT TON uses TON pattern
    ];

    public function validate($currencyId, $value) {
        if (empty($value)) {
            return [
                'valid' => false,
                'error' => 'Empty value provided'
            ];
        }

        // Resolve pattern alias if exists
        if (isset($this->patternAliases[$currencyId])) {
            $currencyId = $this->patternAliases[$currencyId];
        }

        // Check if pattern exists for currency
        if (!isset($this->patterns[$currencyId])) {
            return [
                'valid' => false,
                'error' => 'Unsupported currency ID'
            ];
        }

        $currency = $this->patterns[$currencyId];
        
        // Validate against pattern
        $isValid = preg_match($currency['address'], $value);

        return [
            'valid' => (bool)$isValid,
            'currency' => $currency['name'],
            'error' => !$isValid ? "Invalid address format for " . $currency['name'] : null
        ];
    }
}
class PaymentProcessor {
    private $settings;
    private $processor_id;
    private $DB_CONN;
    private $preferences;
    private $siteURL;
    private $g_alert;
    private $main_link;

    public function __construct($processor_id, $settings, $DB_CONN = null, $preferences = null, $siteURL = null, $g_alert = null, $main_link = null) {
        $this->processor_id = $processor_id;
        $this->settings = $settings;
        $this->DB_CONN = $DB_CONN;
        $this->preferences = $preferences;
        $this->siteURL = $siteURL;
        $this->g_alert = $g_alert;
        $this->main_link = $main_link;
    }
       public function processPayment($action = 'test') {
        $response = [
            'success' => false,
            'message' => '',
            'balances' => null
        ];
        
        try {
            switch ($this->processor_id) {
                case 7: // Coinments
                    $response = $this->processCoinments();
                    break;
                
                default:
                    $response['message'] = 'Unknown payment processor';
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
        return $response;
    }
    public function generatePaymentCode($currency, $ID, $package, $amount, $userinfo, $maddress = '') {
        $response = [
            'form' => '',
            'address' => '',
            'amount' => $amount,
            'img' => '',
            'tag' => '',
            'error' => ''
        ];
        
        try {
            switch ($this->processor_id) {
                case 7:
                    $response = $this->generateCoinmentsPayment($currency, $amount, $ID, $package, $userinfo);
                    break;
                default:
                    throw new Exception('Unknown payment processor');
            }
            
            if ($response['address'] && $this->DB_CONN) {
                $table_name = $this->getTableName($ID);
                $cleanID = $this->cleanID($ID);
                mysqli_query($this->DB_CONN, "UPDATE `{$table_name}` SET `address`='{$response['address']}' WHERE id = '{$cleanID}'");
                
                if (!empty($response['address'])) {
                    $response['img'] = "eb430691fe30d16070b5a144c3d3303c?d={$response['address']}";
                }
            }
            
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
        }
        
        return $response;
    }
    public function processWithdrawal($address, $amount, $id = 0, $wallet = 'BTC', $log = false) {
          $verify_query = mysqli_query($this->DB_CONN, 
                    "SELECT withdraw FROM payment_methods WHERE id = " . $this->processor_id
                );
                
                if (!$verify_query) {
                    throw new Exception("Database error while verifying payment method");
                }
                
                $method = mysqli_fetch_assoc($verify_query);
                
                if (!$method || $method['withdraw'] != 1) {
                    $this->logWithdrawalError(
                        $this->processor_id, 
                        $id, 
                        "Withdrawal not enabled for this payment method", 
                        $amount
                    );
                    return false;
                }

        try {
            switch ($this->processor_id) {
                case 7:
                    return $this->withdrawYCPay($address, $amount, $id, $wallet);
                default:
                    throw new Exception('Unknown payment processor for withdraw');
            }
        } catch (Exception $e) {
            $this->logError($id, $amount, $e->getMessage());
            return false;
        }
    }
/**
 * Coinments (YCPay) specific methods (ID: 7)
 * Handles balance checking, payment generation, and withdrawals
 */
private function processCoinments() {

    $response = ['success' => false, 'message' => '', 'balances' => null];
    
    // Get decrypted secret key
   $secret_key = md5($this->settings['secret_key']);
  
    // Request balance information from API
    $data = get_contents("https://coinments.com/payment.php?api_key={$secret_key}&type=balance");
    $result = json_decode($data, true);
    
    if ($result['result']) {
        $response['success'] = true;
        $response['message'] = 'Coinments connection successful';
        $response['balances'] = $result['balance'];
    } else {
        $response['message'] = $result['message'] ?? 'Connection failed';
    }
    
    return $response;
}

private function generateCoinmentsPayment($currency, $amount, $ID, $package, $userinfo) {
    $secret_key = md5(encrypt_decrypt('decrypt', $this->settings['secret_key']));
    $btc = fromcurrency($amount, 'USDT');
    $amount = fromcurrency($amount, $currency);
    $det = urlencode('Invoice for '.$package['name'].'  Username '.$userinfo['username'].' ');
    // Generate payment through API
    $json = get_contents("https://coinments.com/payment.php?api_key={$secret_key}&type=invoice&symbol={$currency}&amount={$amount}&order_no={$ID}&memo={$det}");
    $json = json_decode($json, true);
    
    if (!$json['result']) {
        throw new Exception('Failed to generate payment');
    }
    
    $response = ['amount' => $amount];
    
    // Handle different response types (direct address or payment form)
    if ($json['address']) {
        $response['address'] = $json['address'];
    } else {
        $response['form'] = '<form method="post" target="_blank" action="'.$json["url"].'">
            <input type="submit" name="m_process" value="Pay Now" class="btn btn-primary ml-auto" />
        </form>';
    }
    
    return $response;
}

private function withdrawYCPay($address, $amount, $id, $wallet) {
    try {
        if (!$this->settings['secret_key']) {
            $this->logWithdrawalError($this->processor_id, $id, "Missing YCPay secret key", $amount);
            return false;
        }

        $system['secret_key'] = md5(encrypt_decrypt('decrypt', $this->settings['secret_key']));
        $amount = fromcurrency($amount, $wallet);
        $amount = number_format($amount, 8, '.', '');
        $memo = urlencode("Withdraw # {$id}");
        
        $data = get_contents("https://coinments.com/payment.php?api_key={$system['secret_key']}&type=payment&symbol={$wallet}&amount={$amount}&address={$address}&order_no={$id}&memo={$memo}");
        $data = json_decode($data, true);
        
        if (!$data['tx_id']) {
            $this->logWithdrawalError($this->processor_id, $id, $data['message'], $amount);
            return false;
        }
        
        $this->updateTransactionDetails($id, $data['tx_url'], $data['tx_id'], $this->processor_id);
        $this->logWithdrawal($this->processor_id, $id, "Successful withdrawal - TxID: {$data['tx_id']}", 1);
        return $data['tx_id'];
        
    } catch (Exception $e) {
        $this->logWithdrawalError($this->processor_id, $id, "YCPay exception: " . $e->getMessage(), $amount);
        return false;
    }
}



private function logSuccess($id, $detail, $payment_method_id) {
    $stmt = $this->DB_CONN->prepare(
        "INSERT INTO `payment_log` 
        (`payment_method_id`, `with_id`, `detail`, `status`) 
        VALUES (?, ?, ?, 1)"
    );
    
    if (!$stmt) {
        error_log("Failed to prepare logSuccess statement: " . $this->DB_CONN->error);
        return false;
    }
    
    $stmt->bind_param('iis', $payment_method_id, $id, $detail);
    return $stmt->execute();
}

private function logError($id, $amount, $error) {
    $stmt = $this->DB_CONN->prepare(
        "INSERT INTO `payment_log` 
        (`payment_method_id`, `with_id`, `detail`, `status`) 
        VALUES (0, ?, ?, 0)"
    );
    
    if (!$stmt) {
        error_log("Failed to prepare logError statement: " . $this->DB_CONN->error);
        return false;
    }
    
    $error_detail = "Amount: {$amount}, Error: {$error}";
    $stmt->bind_param('is', $id, $error_detail);
    return $stmt->execute();
}

private function logWithdrawal($payment_method_id, $with_id, $detail, $status) {
    $stmt = $this->DB_CONN->prepare(
        "INSERT INTO `payment_log` 
        (`payment_method_id`, `with_id`, `detail`, `status`) 
        VALUES (?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        error_log("Failed to prepare logWithdrawal statement: " . $this->DB_CONN->error);
        return false;
    }
    
    $stmt->bind_param('iisi', $payment_method_id, $with_id, $detail, $status);
    return $stmt->execute();
}

private function logWithdrawalError($method_id, $id, $message, $amount) {
    $stmt = $this->DB_CONN->prepare(
        "INSERT INTO `payment_log` 
        (`payment_method_id`, `with_id`, `detail`, `status`) 
        VALUES (?, ?, ?, 0)"
    );
    
    if (!$stmt) {
        error_log("Failed to prepare logWithdrawalError statement: " . $this->DB_CONN->error);
        return false;
    }
    
    $error_detail = "Amount: {$amount}, Error: {$message}";
    $stmt->bind_param('iis', $method_id, $id, $error_detail);
    
    $stmt->execute();
    
    // Send notification after logging
    $this->sendWithdrawErrorNotification($id, $amount, $message);
}
private function sendWithdrawErrorNotification($id, $amount, $error) {
    sendadminmail("withdraw_error_admin_notification", $id, [], [
        "amount" => $amount,
        "id" => $id,
        "error" => $error
    ]);
}

private function updateTransactionDetails($id, $tx_url, $txid, $method_id) {
    $stmt = $this->DB_CONN->prepare(
        "UPDATE `transactions` 
         SET tx_url = ?, txn_id = ?, status = '1' 
         WHERE id = ?"
    );
    $stmt->bind_param('ssi', $tx_url, $txid, $id);
    $stmt->execute();
}

private function getTableName($ID) {
    return substr($ID, 0, 1) == 'b' ? 'transactions' : 'package_deposits';
}

private function cleanID($ID) {
    return substr($ID, 0, 1) == 'b' ? ltrim($ID, 'b') : $ID;
}

/**
 * Static method to get all balances across payment processors
 */
public static function getAllBalances($DB_CONN) {
    $balances = [];
    $query = mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` WHERE withdraw = 1");
    
    while ($row = mysqli_fetch_assoc($query)) {
        $settings = json_decode($row['currencies'], true);
        
        // Decrypt sensitive settings
        foreach ($settings as $key => $value) {
            if (contains($key, ['pass', 'key', 'alternate', 'secret', 'ipn'])) {
                $settings[$key] = encrypt_decrypt('decrypt', $value);
            }
        }
        
        // Initialize processor and get balances
        $processor = new PaymentProcessor($row['id'], $settings);
        $result = $processor->processPayment();
        
        if ($result['success']) {
            $balances[$row['name']] = $result['balances'];
        }
    }
    
    return $balances;
}
}
$system_id = [
    "perfectmoney" => 2,
    "bitcoin" => 11,
    "ethereum" => 12,
    "litecoin" => 14,
    "dogecoin" => 15,
    "dash" => 16,
    "zcash" => 19,
    "ripple" => 22,
    "tron" => 27,
    "stellar" => 28,
    "binancecoin" => 29,
    "tron_trc20" => 30,
    "binancesmartchain_bep20" => 31, // supported currencies USDT, BUSD, USDC, ADA, EOS, BTC, ETH, DOGE 
    "ethereum_erc20" => 32, 
];
$cur_abbr = [
    "bitcoin" => "BTC",
    "dogecoin" => "DOGE",
    "ethereum" => "ETH",
    "litecoin" => "LTC",
    "dash" => "DASH",
    "zcash" => "ZEC",
    "ripple" => "XRP",
    "tron" => "TRX",
    "stellar" => "XLM",
    "binancecoin" => 'BNB',
    "tron_trc20" => 'USDT',
    "binancesmartchain_bep20" => 'BUSDT',
    "ethereum_erc20" => 'EUSDT',
    "perfectmoney" => 'USD'
];
class EmailQueueManager {
    private $DB_CONN;
    
    public function __construct($DB_CONN) {
        $this->DB_CONN = $DB_CONN;
        $this->initializeQueueTable();
    }
    
    private function initializeQueueTable() {
        $sql = "CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            user_id INT,
            template_data LONGTEXT,
            ip VARCHAR(45),
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            attempts INT DEFAULT 0,
            error_log TEXT,
            INDEX idx_status (status)
        )";
        
        mysqli_query($this->DB_CONN, $sql);
    }

    public function queueEmail($type, $id, $user = array(), $data = array(), $ip = null) {
        // Get IP address if not provided
        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        }
        
        // Only store essential data
        $queue_data = array(
            'type' => $type,
            'user_id' => $id,
            'template_data' => json_encode($data),
            'ip' => $ip
        );
        
        $sql = "INSERT INTO email_queue 
                (type, user_id, template_data, ip) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($this->DB_CONN, $sql);
        mysqli_stmt_bind_param($stmt, "siss", 
            $queue_data['type'], 
            $queue_data['user_id'], 
            $queue_data['template_data'],
            $queue_data['ip']
        );
        
        return mysqli_stmt_execute($stmt);
    }
}
class EmailProcessor {
    private $DB_CONN;
    private $preferences;
    private $email_settings; 
    private $telegram_settings;
    private $ip;  
    private $dt;
    private $batch_size = 50;
    private $max_attempts = 3;
    
    public function __construct($DB_CONN, $preferences, $email_settings, $telegram_settings, $ip, $dt) {
        $this->DB_CONN = $DB_CONN;
        $this->preferences = $preferences;
        $this->email_settings = $email_settings;
        $this->telegram_settings = $telegram_settings;
        $this->ip = $ip;  // Need this for template data
        $this->dt = $dt;
    }
    private function getLocationData($ip, $login_id = null) {
        if ($login_id) {
            $stmt = mysqli_prepare($this->DB_CONN, 
                "SELECT country, city FROM login_report 
                 WHERE id = ? AND country != ''");
            mysqli_stmt_bind_param($stmt, "i", $login_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            if ($result && $result['country']) {
                return $result;
            }
        }
        $stmt = mysqli_prepare($this->DB_CONN, 
            "SELECT country, city FROM login_report 
             WHERE ip = ? AND country != '' 
             AND ip != '0.0.0.0'
             ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        return $result ?: ['country' => 'Unknown', 'city' => 'Unknown'];
    }

    private function processEmail($job) {
        try {
            // Update status to processing
            $stmt = mysqli_prepare($this->DB_CONN, 
                "UPDATE email_queue SET status = 'processing' WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $job['id']);
            mysqli_stmt_execute($stmt);
            
            // Use IP from queue for this specific email
            $this->ip = $job['ip'];  // Set IP from queue data for template processing
            
            // Process the email
            $result = $this->processOriginalEmail(
                $job['type'],
                $job['user_id'],
                [], // Empty user array - will be fetched fresh
                json_decode($job['template_data'], true)
            );
            
            // Update status based on result
            $status = $result ? 'completed' : 'failed';
            $stmt = mysqli_prepare($this->DB_CONN, 
                "UPDATE email_queue 
                 SET status = ?, 
                     processed_at = CURRENT_TIMESTAMP 
                 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $job['id']);
            mysqli_stmt_execute($stmt);
            
        } catch (Exception $e) {
            // Log error and increment attempts
            $error = date('Y-m-d H:i:s') . ': ' . $e->getMessage();
            $stmt = mysqli_prepare($this->DB_CONN, 
                "UPDATE email_queue 
                 SET status = 'failed', 
                     attempts = attempts + 1, 
                     error_log = CONCAT(IFNULL(error_log,''), '\n', ?) 
                 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $error, $job['id']);
            mysqli_stmt_execute($stmt);
        }
    }

    public function processQueue() {
        $stmt = mysqli_prepare($this->DB_CONN, 
            "SELECT * FROM email_queue 
             WHERE status = 'pending' 
             AND attempts < ? 
             LIMIT ?");
             
        mysqli_stmt_bind_param($stmt, "ii", $this->max_attempts, $this->batch_size);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($job = mysqli_fetch_assoc($result)) {
            $this->processEmail($job);
        }
    }
    public function sendEmail($type, $id, $user = array(), $data = array()) {
        try {
            if (!empty($this->email_settings['queue'])) {
                $queue = new EmailQueueManager($this->DB_CONN);
                return $queue->queueEmail($type, $id, $user, $data, $this->ip);
            } else {
                return $this->processOriginalEmail($type, $id, $user, $data);
            }
        } catch (Exception $e) {
            // Log error for both direct and queued sends
            $error = date('Y-m-d H:i:s') . ': ' . $e->getMessage();
            error_log("Email Processing Error: " . $error);
            return false;
        }
    }

    private function processOriginalEmail($type, $id, $user = array(), $data = array()) {
        $semail = $this->email_settings['email'];
        $sec_source = $sec_replace = array();
        $temail = null;
        $user_id = null;
        
        // Always fetch fresh user data
        $stmt = mysqli_prepare($this->DB_CONN, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        $array_source = array("#name#", "#username#", "#email#", "#telegram_id#", "#site_name#", "#site_url#", "#currency#", "#symbol#", "#app_url#");
        $array_replace = array(
            $user['fullname'], 
            $user['username'], 
            $user['email'],
            $user['oauth_uid'], 
            $this->preferences['title'], 
            $this->preferences['domain'], 
            $this->preferences['currency'],
            $this->preferences['symbol'],
            $this->telegram_settings['botlink']
        );
        
        switch($type) {
            case "invest_user_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#method#", "#plan#", "#date#", "#batch#", "#compound#");
                $sec_replace = array(fiat($data['amount']), $data['account'], $data['pack_name'], $data['datetime'], $data['txn_id'], $data['compound']);
                sendadminmail("invest_admin_notification", $id, $user, $data);
                break;
                
            case "user_investment_expired":
                $data['datetime'] = date("m/d/Y", strtotime($data['datetime']));
                $user_id = $id;
                $sec_source = array("#invested_amount#", "#invest_date#", "#plan#", "#method#");
                $sec_replace = array(fiat($data['amount']), $data['datetime'], $data['pack_name'], $data['account']);
                break;
                
            case "deposit_user_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#method#", "#batch#", "#date#");
                $sec_replace = array(fiat($data['amount']), $data['account'], $data['txn_id'], $data['datetime']);
                sendadminmail("deposit_admin_notification", $id, $user, $data);
                break;
                
            case "withdraw_user_notification":
            case "withdraw_auto_user_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#batch#", "#method#", "#tx_url#", "#wallet#");
                $sec_replace = array(fiat($data['amount']), $data['txn_id'], $data['account'], $data['tx_url'], $data['address']);
                sendadminmail("withdraw_admin_notification", $id, $user, $data);
                break;
                
            case "withdraw_request_user_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#method#", "#wallet#", "#date#", "#ip#");
                $sec_replace = array(fiat($data['amount']), $data['account'], $data['address'], $this->dt, $this->ip);
                $data['ip'] = $this->ip;
                sendadminmail("withdraw_request_admin_notification", $id, $user, $data);
                break;
                
            case "exchange_user_notification":
                $user_id = $id;
                $sec_source = array("#currency_from#", "#currency_to#", "#amount_from#", "#amount_to#", "#date#", "#ip#");
                $sec_replace = array($data['from_currency'], $data['to_currency'], fiat($data['amount_from']), fiat($data['amount_to'], 2), date("Y-m-d H:ia"), $this->ip);
                sendadminmail("exchange_admin_notification", $id, $user, $data);
                break;
                
            case "transfer_from_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#from_username#", "#method#");
                $sec_replace = array(fiat($data['amount']), $data['from_username'], $data['method']);
                break;
                
            case "transfer_to_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#to_username#", "#method#");
                $sec_replace = array(fiat($data['amount']), $data['to_username'], $data['method']);
                break;
                
            case "referral_commision_notification":
                $user_id = $id;
                $sec_source = array("#amount#", "#ref_username#", "#ref_name#", "#method#");
                $sec_replace = array(fiat($data['amount']), $data['username'], $data['fullname'], $data['method']);
                break;
                
            case "direct_signup_notification":
                $user_id = $id;
                $sec_source = array("#ref_username#", "#ref_name#", "#ref_email#");
                $sec_replace = array($data['username'], $data['name'], $data['email']); 
                break;
                
            case "bonus":
            case "penalty":
                $user_id = $id;
                $sec_source = array("#amount#");
                $sec_replace = array(fiat($data['amount']));
                break;
                
            case "user_ticket_message":
            case "user_ticket_reply":
                $user_id = $id;
                $ticket = isset($data['ticket_link']) ? $data['ticket_link'] : 'ticket';
                $sec_source = array("#code#", "#message#", "#link#", "#title#", "#status#");
                $sec_replace = array($data['id'], $data['msg'], $this->preferences['domain']."/".$ticket."?id=".$data['id'], $data['subject'], $data['status']); 
                break;
                
            case "confirm_registration":
            case "forgot_password_confirm":
                $user_id = $id;
                $sec_source = array("#confirm_string#");
                $sec_replace = array($data['code']);
                break;
                
            case "logged_in":
                $user_id = $id;
                $location = $this->getLocationData($this->ip, $data['login_id'] ?? null);
                
                $sec_source = array("#ip#", "#country#", "#city#", "#os#", "#browser#", "#useragent#", "#time#");
                $sec_replace = array(
                    $this->ip,
                    $location['country'],
                    $location['city'],
                    $data['os'],
                    $data['browser'],
                    $data['useragent'],
                    $data['datetime']
                );
                break;
            case "forgot_password":
                $user_id = $id;
                $sec_source = array("#password#");
                $sec_replace = array($data['pass']);
                break;
                
            case "email_code":
            case "email_code_c":
                if($type == "email_code_c") {
                    $temail = isset($data['temp_email']) ? $data['temp_email'] : null;
                } else {
                    $user_id = $id;
                }
                $type = "email_code";
                $stmt = mysqli_prepare($this->DB_CONN, "SELECT * FROM users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                $sec_source = array("#action#", "#code#");
                $sec_replace = array($data['action'], $data['code']);
                break;
                
            case "change_account":
                $user_id = $id;
                $str = "";
                foreach ($data as $key => $value) {
                    $str .= htmlspecialchars(strtoupper($key)).": ".htmlspecialchars($value)."<br>";
                }
                $sec_source = array("#name#", "#username#", "#email#", "#site_name#", "#site_url#", "#data#", "#ip#");
                $sec_replace = array($user['fullname'], $user['username'], $user['email'], $this->preferences['title'], $this->preferences['domain'], $str, $this->ip);
                break;
                
            case "contact":
                $text = "";
                foreach($data as $index=>$b) {
                    $b = stripcslashes($b);
                    $text .= ucfirst($index).": ".htmlspecialchars($b)."\n";
                }
                $body = email_text($this->email_settings['header']);
                $body .= '<p>'.nl2br($text).'</p>';
                $body .= email_text($this->email_settings['footer']);
                
                sendphpmail($semail, "Support Message", $body);
                return true;
                break;
                
            default:
                $user_id = $id;
                break;
        }
        
        if($user_id || $temail) {
            $to = $temail ? $temail : $user['email'];
            
            $stmt = mysqli_prepare($this->DB_CONN, "SELECT * FROM email WHERE id = ? AND status = 1");
            mysqli_stmt_bind_param($stmt, "s", $type);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 0) {
                return false;
            }
            
            $email = mysqli_fetch_assoc($result);
            $array_source = array_merge($array_source, $sec_source);
            $array_replace = array_merge($array_replace, $sec_replace);
            
            if (in_array($type, array('invest_user_notification', 'deposit_user_notification', 'withdraw_user_notification', 'withdraw_auto_user_notification'))) {
                if (strpos($type, 'withdraw') !== false) {
                    $tt = "telegram_withdraw";
                    $message_type = 'withdraw';
                } else {
                    $tt = "telegram_deposit";
                    $message_type = 'deposit';
                }
                
                $stmt = mysqli_prepare($this->DB_CONN, "SELECT * FROM email WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "s", $tt);
                mysqli_stmt_execute($stmt);
                $t_msg = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['content'];
                $t_msg = str_replace($array_source, $array_replace, $t_msg);
                
               if ($message_type == 'withdraw') {
                    sendtelegram(null, null, null, null, null, $array_source, $array_replace, 'withdraw');
                } else {
                    sendtelegram(null, null, null, null, null, $array_source, $array_replace, 'invest');
                }
            }
            
            $email['content'] = str_replace($array_source, $array_replace, $email['content']);
            $email['subject'] = str_replace($array_source, $array_replace, $email['subject']);
            $email['alerty'] = str_replace($array_source, $array_replace, $email['alerty']);
            
            $body = email_text($this->email_settings['header']);
            $body .= '<p>'.$email['content'].'</p>';
            $body .= email_text($this->email_settings['footer']);
            
            if($user_id) {
                $stmt = mysqli_prepare($this->DB_CONN, "INSERT INTO notifications (user_id, title, content) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iss", $user_id, $email['type'], $email['alerty']);
                mysqli_stmt_execute($stmt);
                sendtelegram($email['alerty'], $user_id, null , null,$email['content']);
                sendpush($user, $email);
            }
            
            sendphpmail($to, $email['subject'], $body);
            return true;
        } else {
            $email['subject'] = "Support Message";
            $to = $semail;
            $email['content'] = nl2br($text);
            return true;
        }
    }
}
function sendmail($type, $id, $user = array(), $data = array()) {
    global $DB_CONN, $ip, $preferences, $email_settings, $telegram_settings;
    
    $current_ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    
    // Handle login report ID for logged_in type
    if ($type === 'logged_in' && !isset($data['login_id'])) {
        $stmt = mysqli_prepare($DB_CONN, 
            "SELECT id FROM login_report 
             WHERE user_id = ? AND ip = ? 
             ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "is", $id, $current_ip);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($result) {
            $data['login_id'] = $result['id'];
        }
    }

    $emailProcessor = new EmailProcessor(
        $DB_CONN, 
        $preferences, 
        $email_settings, 
        $telegram_settings, 
        $current_ip, 
        date('Y-m-d H:i:s')
    );
    
    return $emailProcessor->sendEmail($type, $id, $user, $data);
}

function sendpush($user, $email) {
    global $DB_CONN, $alerts_settings, $preferences, $site_settings;
    if($alerts_settings['enable']) {
        $user['subscription_data'] = $user['subscription_data'];
        $email['content'] = urlencode(strip_tags($email['content']));
        $icon = urlencode("{$site_settings['webapp_url']}/favicon.ico");
        $email['subject'] = urlencode($email['subject']);
        $not_url = "https://beta.bitders.com/send.php?subscription_json={$user['subscription_data']}&domain={$site_settings['webapp_url']}&title={$email['subject']}&message={$email['content']}&icon=$icon";
        file_get_contents($not_url);
    }
}


function email_text($text) {
    global $preferences, $email_settings;
    $array_source = array("#site_name#", "#site_url#", "#site_from#");
    $array_replace = array($preferences['title'], $preferences['domain'], $email_settings['from_email']);
    return str_replace($array_source, $array_replace, $text);
}

function sendmaile($type, $id, $user = array(), $data = array()) {
    global $preferences, $email_settings, $DB_CONN, $pref, $t_alert, $ip, $mail, $dt, $main_link, $telegram_settings;
    $semail = $email_settings['email'];
    $sec_source = $sec_replace = array();
    if(count($user) == 0 || !$user)
        $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$id}'"));
    $array_source = array("#name#", "#username#", "#email#","#telegram_id#", "#site_name#", "#site_url#", "#currency#","#symbol#", "#app_url#");
    $array_replace = array($user['fullname'], $user['username'], $user['email'],$user['oauth_uid'], $preferences['title'], $preferences['domain'], $preferences['currency'],$preferences['symbol'],$telegram_settings['botlink']);
    if($type == "invest_user_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#method#", "#plan#", "#date#", "#batch#", "#compound#");
        $sec_replace = array(fiat($data['amount']), $data['account'], $data['pack_name'], $data['datetime'],$data['txn_id'],$data['compound']);
        sendadminmail("invest_admin_notification", $id, $user, $data);
    }elseif($type == "user_investment_expired") {
        $data['datetime'] = date("m/d/Y", strtotime($data['datetime']));
        $user_id = $id;
        $sec_source = array("#invested_amount#", "#invest_date#", "#plan#", "#method#");
        $sec_replace = array(fiat($data['amount']), $data['datetime'], $data['pack_name'], $data['account']);
    } elseif($type == "deposit_user_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#method#","#batch#", "#date#");
        $sec_replace = array(fiat($data['amount']), $data['account'],$data['txn_id'], $data['datetime']);
        sendadminmail("deposit_admin_notification", $id, $user, $data);
    } elseif($type == "withdraw_user_notification" || $type == "withdraw_auto_user_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#batch#", "#method#", "#tx_url#", "#wallet#");
        $sec_replace = array(fiat($data['amount']), $data['txn_id'], $data['account'], $data['tx_url'],$data['address']);
        sendadminmail("withdraw_admin_notification", $id, $user, $data);
    } elseif($type == "withdraw_request_user_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#method#" , "#wallet#","#date#" ,"#ip#");
        $sec_replace = array(fiat($data['amount']), $data['account'], $data['address'], $dt, $ip);
        sendadminmail("withdraw_request_admin_notification", $id, $user, $data);
    } elseif($type == "exchange_user_notification") {
        $user_id = $id;
        $sec_source = array("#currency_from#","#currency_to#", "#amount_from#", "#amount_to#", "#date#" ,"#ip#");
        $sec_replace = array($data['from_currency'],$data['to_currency'], fiat($data['amount_from']), fiat($data['amount_to'], 2), date("Y-m-d H:ia"), $ip);
        sendadminmail("exchange_admin_notification", $id, $user, $data);
    } elseif($type == "transfer_from_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#from_username#","#method#");
        $sec_replace = array(fiat($data['amount']), $data['from_username'],$data['method']);
    }elseif($type == "transfer_to_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#to_username#","#method#");
        $sec_replace = array(fiat($data['amount']), $data['to_username'],$data['method']);
    }elseif($type == "referral_commision_notification") {
        $user_id = $id;
        $sec_source = array("#amount#", "#ref_username#","#ref_name#","#method#");
        $sec_replace = array(fiat($data['amount']), $data['username'],$data['fullname'],$data['method']);
    } elseif($type == "direct_signup_notification") {
        $user_id = $id;
        $sec_source = array("#ref_username#","#ref_name#","#ref_email#");
        $sec_replace = array($data['username'], $data['name'],$data['email']); 
    } elseif($type == "bonus" || $type == "penalty") {
        $user_id = $id;
        $sec_source = array("#amount#");
        $sec_replace = array(fiat($data['amount']));
    } elseif($type == "user_ticket_message" || $type == "user_ticket_reply") {
        $user_id = $id;
        $ticket = $main_link['ticket'] ?:'ticket';
        $sec_source = array("#code#", "#message#", "#link#", "#title#", "#status#");
        $sec_replace = array($data['id'], $data['msg'], $preferences['domain']."/".$ticket."?id=".$data['id'], $data['subject'], $data['status']); 
    } elseif($type == "confirm_registration" || $type == "forgot_password_confirm") {
        $user_id = $id;
        $sec_source = array("#confirm_string#");
        $sec_replace = array($data['code']);
    } elseif($type == "logged_in") {
        $user_id = $id;
        $sec_source = array("#ip#","#country#","#os#","#browser#","#useragent#","#time#");
        $sec_replace = array($ip, $data['country'],$data['os'], $data['browser'],$data['useragent'],$data['datetime']);
    } elseif($type == "forgot_password") {
        $user_id = $id;
        $sec_source = array("#password#");
        $sec_replace = array($data['pass']);
    } elseif($type == "email_code" || $type == "email_code_c") {
        if($type == "email_code_c")
            $temail = $_SESSION['email'];
        else
            $user_id = $id;
        $type = "email_code";
        $u = mysqli_query($DB_CONN, "SELECT * from users where id = '{$id}'");
        $user = mysqli_fetch_assoc($u);
        $sec_source = array("#action#", "#code#");
        $sec_replace = array($data['action'], $data['code']);
    } elseif($type == "change_account") {
        $user_id = $id;
        $str = "";
        foreach ($data as $key => $value) {
            $str .= strtoupper($key).": {$value}<br>";
        }
        $sec_source = array("#name#", "#username#", "#email#", "#site_name#", "#site_url#", "#data#", "#ip#");
        $sec_replace = array($user['fullname'], $user['username'], $user['email'], $preferences['title'], $preferences['domain'], $str, $ip);
    } elseif($type == "contact") {
        $text = "";
        foreach($data as $index=>$b) {
            $b = stripcslashes($b);
            $text .= ucfirst($index).": {$b}\n";
        }
    } else {
        $user_id = $id;
    }
    if($user_id || $temail) {
        if($temail)
            $to = $temail;
        else
            $to = $user['email'];
        $e = mysqli_query($DB_CONN, "select * from email where id = '{$type}' and status = 1");
        if(mysqli_num_rows($e) == 0)
            return false;
        $email = mysqli_fetch_assoc($e);
        $array_source = array_merge($array_source, $sec_source);
        $array_replace = array_merge($array_replace, $sec_replace);
        if (in_array($type, array('invest_user_notification', 'deposit_user_notification', 'withdraw_user_notification', 'withdraw_auto_user_notification'))) {
            if (strpos($type, 'withdraw') !== false) {
                $tt = "telegram_withdraw";
                $message_type = 'withdraw';
            } else {
                $tt = "telegram_deposit";
                $message_type = 'deposit';
            }
            $t_msg = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM email WHERE id = '{$tt}'"))['content'];
            $t_msg = str_replace($array_source, $array_replace, $t_msg);
            if ($message_type == 'withdraw') {
                $w_msg = $telegram_settings['withdraw'];
                $w_msg = str_replace($array_source, $array_replace, $w_msg);
                sendtelegram($t_msg, $id, null, $w_msg); 
            } else {
                $d_msg = $telegram_settings['invest'];
                $d_msg = str_replace($array_source, $array_replace, $d_msg);
                sendtelegram($t_msg, $id, $d_msg, null); 
            }
        }
        $email['content'] = str_replace($array_source, $array_replace, $email['content']);
        $email['subject'] = str_replace($array_source, $array_replace, $email['subject']);
        $email['alerty'] = str_replace($array_source, $array_replace, $email['alerty']);
        $body = email_text($email_settings['header']);
        $body .= '<p>'.$email['content'].'</p>';
        $body .= email_text($email_settings['footer']);
        if($user_id)
            mysqli_query($DB_CONN, "INSERT INTO `notifications`(`user_id`, `title`, `content`) VALUES ('{$user_id}', '{$email['type']}', '{$email['alerty']}')");
        sendphpmail($to, $email['subject'], $body);
        return; 
    } else {
        $email['subject'] = "Support Message";
        $to = $semail;
        $email['content'] = nl2br($text);
    }
}
function sendadminmail($type, $id, $user = array(), $data = array()) {
    global $preferences, $admin_settings, $email_settings, $DB_CONN, $pref, $t_alert, $ip, $mail;
    if($type == "withdraw_error_admin_notification")
        $id = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT user_id from transactions where id = '{$id}'"))[0];
    if(count($user) == 0)
        $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$id}'"));
    $adminemail = $admin_settings['email'];
    $array_source = array("#name#", "#username#", "#email#", "#site_name#", "#site_url#", "#currency#","#symbol#");
    $array_replace = array($user['fullname'], $user['username'], $user['email'], $preferences['title'], $preferences['domain'], $preferences['currency'],$preferences['symbol']);
    if($type == "deposit_admin_notification") {
        $sec_source = array("#amount#", "#method#", "#batch#");
        $sec_replace = array(fiat($data['amount']), $data['account'], $data['txn_id']);
    } elseif($type == "invest_admin_notification") {
        $sec_source = array("#amount#", "#method#", "#plan#", "#date#", "#batch#", "#compound#");
        $sec_replace = array(fiat($data['amount']), $data['account'], $data['pack_name'], $data['datetime'],$data['txn_id'],$data['compound']);
    } elseif($type == "withdraw_admin_notification") {
        $sec_source = array("#amount#", "#batch#", "#method#", "#tx_url#" , "#wallet#");
        $sec_replace = array($data['amount'], $data['txn_id'], $data['account'], $data['tx_url'],$data['address']);
    }elseif($type == "withdraw_error_admin_notification") {
        $sec_source = array("#id#", "#amount#", "#error#", "#method#", "#wallet#");
        $sec_replace = array($data['id'] ,$data['amount'], $data['error'], $data['account'],$data['address']);
    }elseif($type == "withdraw_request_admin_notification") {
        $sec_source = array("#amount#", "#method#" , "#wallet#","#date#" ,"#ip#");
        $sec_replace = array($data['amount'], $data['account'], $data['address'], $data['created_at'], $data['ip'] ?? $ip);
    } elseif($type == "exchange_admin_notification") {
        $sec_source = array("#currency_from#","#currency_to#", "#amount_from#", "#amount_to#", "#date#" ,"#ip#");
        $sec_replace = array($data['currency_from'], $data['currency_to'], $data['amount_from'], $data['amount_to'], date("Y-m-d H:ia"), $ip);
    } elseif($type == "admin_ticket_message" || $type == "admin_ticket_reply") {
        $sec_source = array("#code#", "#message#", "#link#", "#title#", "#status#");
        $sec_replace = array($data['id'], $data['msg'], $preferences['domain']."/admin?page=support&id=".$data['id'], $data['subject'], $data['status']);
    } elseif($type == "admin_login_message") {
    //  $ipdat = @json_decode(file_get_contents("http://ip-api.com/json/".$ip), true);   
       $country = $ipdat['country'] ? : '';
       $city = $ipdat['city']? : '';
        $browser = getBrowser();
        $os = getOS();
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $sec_source = array("#ip#", "#country#", "#city#", "#browser#", "#os#", "#useragent#");
        $sec_replace = array($ip,$country,$city,$browser,$os,$useragent);
    }
    $to = $adminemail;
    if($type == "admin_login_code") {
        $email['subject'] = "2fa code";
        $email['text'] = "Your 2fa for login is '{$id}'";
    } elseif($id) {
        $e = mysqli_query($DB_CONN, "select * from email where id = '{$type}' and status = 1");
        if(mysqli_num_rows($e) == 0)
            return false;
        $email = mysqli_fetch_assoc($e);
        $array_source = array_merge($array_source, $sec_source);
        $array_replace = array_merge($array_replace, $sec_replace);
        $email['text'] = str_replace($array_source, $array_replace, $email['content']);
        $email['text'] = str_replace("\n", "<br />", $email['text']);
        $email['subject'] = str_replace($array_source, $array_replace, $email['subject']);
    }
    $body = email_text($email_settings['header']);
    $body .= '<p>'.$email['text'].'</p>';
    $body .= email_text($email_settings['footer']);
    sendphpmail($to, $email['subject'], $body);
}

function sendphpmail(string $to, string $subject, string $body): bool {
    global $email_settings, $preferences;
    
    // Ensure proper UTF-8 encoding
    $subject = mb_convert_encoding($subject, 'UTF-8', 'auto');
    $body = mb_convert_encoding($body, 'UTF-8', 'auto');
    
    if ($email_settings['smtp_host']) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = 0;  // Added back SMTPDebug
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            $mail->isSMTP();
            $mail->Host = $email_settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $email_settings['smtp_user'];
            $mail->Password = encrypt_decrypt('decrypt', $email_settings['smtp_pass']);
            $mail->SMTPSecure = $email_settings['smtp_sec'];
            $mail->Port = $email_settings['smtp_port'];
            
            // Base64 encode the title for the From field
            $fromName = html_entity_decode($preferences['title'], ENT_QUOTES, 'UTF-8');  // Added html_entity_decode
            $mail->setFrom($email_settings['from_email'], $fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($email_settings['from_email'], $fromName);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    try {
        $fromemail = $email_settings['from_email'];
        $from = html_entity_decode($preferences['title'], ENT_QUOTES, 'UTF-8');
        
        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=UTF-8",
            sprintf('From: =?UTF-8?B?%s?= <%s>', base64_encode($from), $fromemail)  // Fixed From header format
        ];
        
        return mail($to, 
            '=?UTF-8?B?' . base64_encode($subject) . '?=',  // Added base64 encoding for subject
            $body, 
            implode("\r\n", $headers)
        );
    } catch (Exception $e) {
        error_log("PHP mail() Error: " . $e->getMessage());
        return false;
    }
}

function sendnewsletter($id, $subject, $message) {
    global $preferences, $email_settings, $DB_CONN;
    
    try {
        $user_id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$user_id) {
            error_log("Invalid user ID provided");
            return false;
        }
        
        $stmt = mysqli_prepare($DB_CONN, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        if (!$user) {
            error_log("User not found: " . $user_id);
            return false;
        }
        
        // Prepare UTF-8 encoded replacements
        $replacements = [
            "#name#" => mb_convert_encoding($user['fullname'], 'UTF-8', 'auto'),
            "#username#" => mb_convert_encoding($user['username'], 'UTF-8', 'auto'),
            "#email#" => $user['email'],
            "#site_name#" => mb_convert_encoding($preferences['title'], 'UTF-8', 'auto'),
            "#site_url#" => $preferences['domain']
        ];
        
        $to = $user['email'];
        
        // Do replacements first
        $emailText = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $message
        );
        
        // Check if content is HTML
        $isHTML = strip_tags($emailText) !== $emailText;
        
        // If not HTML, convert line breaks
        if (!$isHTML) {
            $emailText = nl2br($emailText, false);
            // Wrap in div with pre-wrap for plain text
            $emailText = '<div style="white-space: pre-wrap;">' . $emailText . '</div>';
        }
        
        // Construct email body
        $body = email_text($email_settings['header']);
        $body .= $emailText;  // No extra wrapping for HTML content
        $body .= email_text($email_settings['footer']);
        
        return sendphpmail($to, $subject, $body);
    } catch (Exception $e) {
        error_log("Newsletter Error: " . $e->getMessage());
        return false;
    }
}
function get_contents($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function get_contents_4($url, $postdata) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-type: application/x-www-form-urlencoded'
    ));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$vars);  
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
       curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}




function amount_format($am) {
    global $preferences;
    $am = (float)$am;
    return number_format($am, $preferences['round']);
}
function fiat($am, $id=0, $ticker = '') {
    global $preferences, $DB_CONN, $site_settings;
if($preferences['currency'] == 'USD') {
    if(!$id)
        return $preferences['symbol'].amount_format($am);
    else {
        $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$id}'"));
        return  "<img src='images/icons/{$id}.".$site_settings['image_format']."' width='".$site_settings['image_witdth']."' class='".$site_settings['image_class']."' />&nbsp;".$preferences['symbol'].amount_format($am)." ".$pm['name'];
    }
} elseif($preferences['currency'] == 'SAME') {
    if($ticker)
        return $ticker." ".amount_format($am);
    elseif(!$id)
        return $preferences['symbol'].amount_format($am);
    else {
        $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$id}'"));
        return  "<img src='images/icons/{$id}.".$site_settings['image_format']."'width='".$site_settings['image_witdth']."' class='".$site_settings['image_class']."' />&nbsp;".$pm['symbol']."&nbsp;".amount_format($am)." ".$pm['name'];
    }
} else {
    if(!$id)
        return amount_format($am).' '.$preferences['symbol'];
    else {
        $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$id}'"));
        return  "<img src='images/icons/{$id}.".$site_settings['image_format']."' width='".$site_settings['image_witdth']."' class='".$site_settings['image_class']."' />&nbsp;".$preferences['symbol'].' '.amount_format($am)." ".$pm['name'];
    }
}
}


// elseif(!isset($_SESSION['user_id']))
//  {
//      $uid = db_filter($_COOKIE['user']);
//      $chk = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT ip from login_report where user_id = '{$uid}' order by id desc limit 1"))[0];
//      if($chk == $ip) {
//          $_SESSION['user_id']=$uid;
//          updateuserinfo();
//      } else {
//          setcookie("user", NULL, -1);
//          header("location: $login_link",  true,  301 );exit;
//      }
        
//  } 

function isDisposableEmail($email, $disposableEmailDomains) {
    $domain = substr(strrchr($email, "@"), 1);
    return in_array($domain, $disposableEmailDomains);
}
function login_redirect() {
    global $DB_CONN, $ip, $login_link, $user_id, $telegram_settings, $user_settings;
    if(isset($_SESSION['tfa_verify'])) {
        header("location: $login_link",  true,  301 ); exit;
    } elseif(!isset($user_id)) {
        header("location: $login_link",  true,  301 ); exit;
    } elseif($user_id) {
            if($user_settings['logout_on_ip_change']){
    $uip = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT ip FROM `login_report` WHERE user_id = '{$user_id}' order by id desc limit 1"))[0];
    if($uip != $ip) {
        session_destroy();
        header("location: $login_link",  true,  301 ); exit;
    }
    }
    if($user_settings['logout_on_browser_change']){
    $us = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT useragent FROM `login_report` WHERE user_id = '{$user_id}' order by id desc limit 1"))[0];
    if($us != $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        header("location: $login_link",  true,  301 ); exit;
    }
    }
    }
}
function encrypt_decrypt($action, $string) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = '22385524sh';
    $secret_iv = '22385524sh';
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}
function contains($string, array $search, $caseInsensitive = false) {
    $exp = '/'
        . implode('|', array_map('preg_quote', $search))
        . ($caseInsensitive ? '/i' : '/');
    return preg_match($exp, $string) ? true : false;
}
function check_active($user_id) {
    global $DB_CONN;
    $ac = mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` where user_id = '{$user_id}' and status = 1 LIMIT 1");
    return mysqli_num_rows($ac) > 0 ? true : false;
}
function refferal_commission($id, $earning = 0) {
    global $DB_CONN, $referral_settings;
    $tier = $referral_settings['tier'];
    $spc = false;
    $p = mysqli_query($DB_CONN,"select *, (SELECT name from currencies where id = package_deposits.payment_method_id) as method,(SELECT name from packages where id = package_deposits.package_id) as pack_name, (SELECT referral from packages where id = package_deposits.package_id) as referral, (SELECT referral_compound from packages where id = package_deposits.package_id) as referral_compound, (SELECT details from packages where id = package_deposits.package_id) as details from package_deposits where id='$id'");
    $pack = mysqli_fetch_assoc($p);
    $pack['details'] = json_decode($pack['details'], true);
    if($referral_settings['profit_share'] && !$earning && !$pack['details']['referralc'])
        return false;
    if($referral_settings['enable']) {
        if($pack['details']['referralc'] && !$earning)
            $tier = $pack['details']['referral']['tier'];
        if(($pack['details']['level'][1] || $pack['details']['level'][2]) && !$earning) {
            if($pack['details']['level'][1])
                $spc = false;
            $levels = $pack['details']['level'];
            $deposit_checks = $pack['details']['level_deposit'];
            $level_checks = $pack['details']['level_range'];
        }
        if($referral_settings['profit_share'] && $earning)
            $amount = $earning;
        else
            $amount = $pack['amount'];
        $user_id = $pack['user_id'];
        $u = mysqli_query($DB_CONN, "select * from users where id = '{$user_id}'");
        $user = mysqli_fetch_assoc($u);
        $s_id = $user['sponsor'];
        if($referral_settings['min_deposit'] && $amount < $referral_settings['min_deposit'])
            return false;
        $q = "";
        if($referral_settings['count_referral']) {
            $q = " and id in (SELECT DISTINCT user_id from package_deposits where status = 1)";
        }
        $count = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT count(*) from users where sponsor = '{$s_id}' {$q}"))[0];
        if($pack['referral_compound']) {
            $pack_id = $pack['package_id'];
            $percentage = $pack['referral_compound'];
            $comm = ($amount/100)*$percentage;
            $ac = mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` where user_id = '{$s_id}' and package_id = '{$pack_id}' and status = 1 order by id desc LIMIT 1");
            if(mysqli_num_rows($ac)) {
                $ac = mysqli_fetch_assoc($ac);
                $ac_id = $ac['id'];
                if($referral_settings['max_commission'] && $comm > $referral_settings['max_commission'])
                    $comm = $referral_settings['max_commission'];
                mysqli_query($DB_CONN, "UPDATE `package_deposits` set amount = amount + {$comm} WHERE id = '{$ac_id}'");
            }
        }
        if($pack['referral']) {
            $percentage = $pack['referral'];
            $comm = ($amount/100)*$percentage;
            $res = true;
            if($referral_settings['active_users'])
                $res = check_active($s_id);
            if($comm && $s_id && $res) {
                ledgerentry(1, $comm, 0, $s_id, $user_id, $pack['payment_method_id'], "Level 1");
                sendmail("referral_commision_notification", $s_id, array(), array('username'=>$user['username'],'fullname'=>$user['fullname'], 'amount'=>$comm,'method'=>$pack['method']));
            }
        }
        if($referral_settings['fixed_commission']) {
            $comm = $referral_settings['fixed_commission'];
            $res = true;
            if($referral_settings['active_users'])
                $res = check_active($s_id);
            if($res) {
                ledgerentry(1, $comm, 0, $s_id, $user_id, $pack['payment_method_id'], "Level 1");
                sendmail("referral_commision_notification", $s_id, array(), array('username'=>$user['username'],'fullname'=>$user['fullname'], 'amount'=>$comm,'method'=>$pack['method']));
            }
        }
        if($spc):
            //package wise referral commission
            $ref_main = $referral_settings['ref_main'];
            foreach ($ref_main['commission'] as $key => $percentage) {
                if($percentage) {
                    $chk = true;
                    if($ref_main['amount_to'][$key]) {
                        $chk = false;
                        if(!$ref_main['amount_from'][$key])
                            $ref_main['amount_from'][$key] = 0;
                        if($amount >= $ref_main['amount_from'][$key] && $amount <= $ref_main['amount_to'][$key])
                            $chk = true;
                    }
                    if($ref_main['range_to'][$key]) {
                        $chk = false;
                        if(!$ref_main['range_from'][$key])
                            $ref_main['range_from'][$key] = 0;
                        if($count >= $ref_main['range_from'][$key] && $count <= $ref_main['range_to'][$key])
                            $chk = true;
                    }
                    if($chk) {
                        $comm = ($amount/100)*$percentage;
                        $res = true;
                        if($referral_settings['active_users'])
                            $res = check_active($s_id);
                        if($res && $comm) {
                            ledgerentry(1, $comm, 0, $s_id, $user_id, $pack['payment_method_id'], "Level 1");
                            sendmail("referral_commision_notification", $s_id, array(), array('username'=>$user['username'],'fullname'=>$user['fullname'], 'amount'=>$comm,'method'=>$pack['method']));
                        }
                        break;
                    }
                }
            }
            $u1 = mysqli_query($DB_CONN, "select sponsor from users where id = '{$s_id}' and sponsor");
            if(mysqli_num_rows($u1)) {
                $user1 = mysqli_fetch_assoc($u1);
                $s_id = $user1['sponsor'];
            } else
            $s_id = 0;
        endif;
        if(is_array($levels)) {
        foreach($levels as $index=>$percentage) {
            $type=1;
            $comm = ($amount/100)*$percentage;
            $chk = true;
            if($deposit_checks[$index] && $deposit_checks[$index] > $amount)
                $chk = false;
            if($comm && $s_id && $chk) {
                $ld = $index;
                $res = true;
                if($referral_settings['active_users'])
                    $res = check_active($s_id);
                if($res && $level_checks[$index]) {
                    $range = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT count(*) from users where sponsor = '{$s_id}' {$q}"))[0];
                    if($range < $level_checks[$index]) {
                        $res = false;
                    }
                }
                if($res) {
                    ledgerentry($type, $comm, 0, $s_id, $user_id, $pack['payment_method_id'], "Level {$ld}");
                    sendmail("referral_commision_notification", $s_id, array(), array('username'=>$user['username'],'fullname'=>$user['fullname'],'amount'=>$comm,'method'=>$pack['method']));
                }
                $u1 = mysqli_query($DB_CONN, "select sponsor from users where id = '{$s_id}' and sponsor");
                if(mysqli_num_rows($u1)) {
                    $user1 = mysqli_fetch_assoc($u1);
                    $s_id = $user1['sponsor'];
                }
                else
                $s_id = 0;
            }
        }} else {
            $tier = array_reverse($tier, true);
            for ($i=1; $i <= $referral_settings['levels']; $i++) {
                $user1 = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT level, sponsor, tier, (SELECT sum(amount) from transactions where user_id = users.id and status = 1 and txn_type = 'invest') as investment FROM `users` where id = '{$s_id}'"));
                $j_id = $s_id;
                for ($j=0; $j < $referral_settings['levels']; $j++) {
                  $user_ids = array();
                  $ub = mysqli_query($DB_CONN, "SELECT id from users where sponsor in ({$j_id}) {$q}");
                  $user_levels[$j] = mysqli_num_rows($ub);
                  while ($sp = mysqli_fetch_assoc($ub)) {
                    $user_ids[] = $sp['id'];
                  }
                  $j_id = implode(",", $user_ids);
                }
                $invested = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT COALESCE(sum(amount), 0) from transactions where txn_type = 'invest' and user_id in (SELECT id from users where sponsor = '{$s_id}')"))[0];
                foreach ($tier as $key => $value) {
                    $chk = false;
                    if(!$user1['tier']) {
                        $inv = $ref = $inves = true;
                        if(isset($value['invested']) && $value['invested'] && $user1['investment'] < $value['invested'])
                            $inv = false;
                        if(isset($value['sponsors']) && $value['sponsors'] && $user_levels[0] < $value['sponsors'])
                            $sp = false;
                        if(isset($value['referrals']) && $value['referrals'] && array_sum($user_levels) < $value['referrals'])
                            $ref = false;
                        if(isset($value['indirect_referrals']) && $value['indirect_referrals'] && (array_sum($user_levels)-$user_levels[0]) < $value['indirect_referrals'])
                            $iref = false;
                        if(isset($value['investments']) && $value['investments'] && $invested < $value['investments'])
                            $inves = false;
                        if($referral_settings['trigger']) {
                            if($inves || $inv || $sp || $ref || $iref)
                                $chk = true;
                        } elseif($inves && $inv && $sp && $ref && $iref) {
                            $chk = true;
                        }
                    } elseif($user1['tier'] == $key)
                        $chk = true;
                    $res = true;
                    if($referral_settings['active_users'])
                        $res = check_active($s_id);
                    if($chk && $value['level'][$i] && $res) {
                        $comm = ($amount/100)*$value['level'][$i];
                        if($comm) {
                            ledgerentry(1, $comm, 0, $s_id, $user_id, $pack['payment_method_id'], $i, $value['name'], $value['level'][$i]);
                            sendmail("referral_commision_notification", $s_id, array(), array('username'=>$user1['username'],'fullname'=>$user['fullname'],'amount'=>$comm,'method'=>$pack['method']));
                        }
                        if($user1['level'] < $key)
                            mysqli_query($DB_CONN, "UPDATE users set level = '{$key}' where id = '{$s_id}'");
                        break;
                    }
                }
                $s_id = $user1['sponsor'];
                if(!$s_id)
                    break;
            }
        }
    }
}

function ledgerentry( $ttype, $in, $out, $user_id, $p_id = 0, $method_id, $ld = "", $tier = "", $com = "")
{
    global $DB_CONN, $referral_settings;
    if($ttype == 1 && $referral_settings['max_commission'] && $in > $referral_settings['max_commission'])
        $in = $referral_settings['max_commission'];
    switch($ttype) {
        case 1:
        $name = mysqli_query($DB_CONN, "select username from users where id = $p_id");
        $name = mysqli_fetch_assoc($name);
        $fullname = $name['fullname '];
        $name = $name['username'];
        $detail = str_replace(array("#ref_username#","#ref_fullname","#ref_percentage#", "#ref_level#", "#ref_tier#"), array($name, $fullname, $com, $ld, $tier), $referral_settings['memo']);
        $details = "Referral commission from $name on level $ld $tier";
        break;
        case 2:
        $name = mysqli_query($DB_CONN, "select username from users where id = $p_id");
        $name = mysqli_fetch_assoc($name);
        $name = $name['username'];
        $detail = "Representative Earning from $name";
        break;
        case 3:
        $name = mysqli_query($DB_CONN, "select username from users where id = $p_id");
        $name = mysqli_fetch_assoc($name);
        $name = $name['username'];
        $detail = "Partner Earning from $name";
        break;
        case 4:
        $detail = "Cash Back Bonus";
        break;
        }
    if($user_id > 1) {
        mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$detail}','{$user_id}','{$in}', 'referral', '{$method_id}', '{$p_id}')");
        $am = $in - $out;
        add_balance($user_id, $method_id, $am);
    }
    return true;
}
function add_balance($user_id, $payment_method_id, $amount) {
    global $DB_CONN;
    $ub = mysqli_query($DB_CONN, "select * from user_balances where user_id = '{$user_id}' and payment_method_id = '{$payment_method_id}'");
    $ub = mysqli_num_rows($ub);
    $amount = mysqli_real_escape_string($DB_CONN, $amount);
    if($ub) {
        $t = "";
        if($amount > 0)
            $t = "total = total + $amount, ";
        mysqli_query($DB_CONN, "update `user_balances` set balance = balance + $amount where user_id = '{$user_id}' and payment_method_id = '{$payment_method_id}' limit 1");
    } else {
        mysqli_query($DB_CONN, "INSERT INTO `user_balances` (`user_id`, `payment_method_id`, `total`, `balance`) VALUES ('{$user_id}', '{$payment_method_id}', '$amount', '$amount')");
    }
}
function add_faucet($user_id, $payment_method_id, $amount) {
    global $DB_CONN;
    $ub = mysqli_query($DB_CONN, "select * from user_balances where user_id = '{$user_id}' and payment_method_id = '{$payment_method_id}'");
    $ub = mysqli_num_rows($ub);
    $amount = mysqli_real_escape_string($DB_CONN, $amount);
    if($ub) {
        $t = "";
        if($amount > 0)
            $t = "total = total + $amount, ";
        mysqli_query($DB_CONN, "update `user_balances` set faucet = faucet + $amount where user_id = '{$user_id}' and payment_method_id = '{$payment_method_id}' limit 1");
    } else {
        mysqli_query($DB_CONN, "INSERT INTO `user_balances` (`user_id`, `payment_method_id`, `total`, `faucet`) VALUES ('{$user_id}', '{$payment_method_id}', '$amount', '$amount')");
    }
}
function logEvent($user_id, $chat_id, $message_id, $event_type, $reason, $details = null, $text = null) {
global $DB_CONN;
    $stmt = $DB_CONN->prepare("INSERT INTO telegram (user_id, chat_id, message_id, event_type, reason, details, text, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        return;
    }
    $bot = "BOT";
    $stmt->bind_param('ssssssss', $user_id, $chat_id, $message_id, $event_type, $reason, $details, $text, $bot);
    $stmt->execute();
    $stmt->close();
}

function checkDisallowedUrl($entities, $content, $allowedUrlsArray) {
    $disallowedUrls = [];
    
    foreach ($entities as $entity) {
        if ($entity['type'] === 'url' || $entity['type'] === 'text_link') {
            if ($entity['type'] === 'url') {
                $encoding = 'UTF-8';
                $contentSubstr = mb_substr($content, 0, $entity['offset'], $encoding);
                $byteOffset = strlen($contentSubstr);
                $urlPart = mb_substr($content, $entity['offset'], $entity['length'], $encoding);
                $url = $urlPart;
            } else {
                $url = $entity['url'];
            }
            
            $originalUrl = $url;
            $cleanUrl = strtolower(trim($url));
            $cleanUrl = preg_replace('#^(https?://)?(www\.)?#i', '', $cleanUrl);
            $cleanUrl = rtrim($cleanUrl, '!.,? ');
            
            $allowed = false;
            foreach ($allowedUrlsArray as $allowedUrl) {
                $cleanAllowedUrl = strtolower(trim($allowedUrl));
                $cleanAllowedUrl = preg_replace('#^(https?://)?(www\.)?#i', '', $cleanAllowedUrl);
                
                if (preg_match('/\b' . preg_quote($cleanAllowedUrl, '/') . '\b/i', $cleanUrl)) {
                    $allowed = true;
                    break;
                }
            }
            
            if (!$allowed) {
                $fullContext = mb_substr($content, 0, $entity['offset'] + $entity['length'] + 10, $encoding);
                if (preg_match('/\bBitders\.com\b/i', $fullContext)) {
                    $allowed = true;
                } else {
                    $disallowedUrls[] = trim($originalUrl);
                }
            }
        }
    }
    
    return !empty($disallowedUrls) ? $disallowedUrls : false;
}
function strposa($haystack, $needleArray, $offset = 0) {
    if (!is_array($needleArray)) $needleArray = array($needleArray);
    foreach($needleArray as $needle) {
        if(stripos($haystack, $needle, $offset) !== false) return true;
    }
    return false;
}
function get_username($user_id) {
    global $DB_CONN;
    $ub = mysqli_query($DB_CONN, "select username from users where id = '{$user_id}'");
    if(mysqli_num_rows($ub)) {
        $ub = mysqli_fetch_assoc($ub);
        return $ub['username'];
    } else
        return '';
}
function get_tier($user_id) {
    global $DB_CONN, $referral_settings;
    $ub = mysqli_query($DB_CONN, "select tier from users where id = '{$user_id}'");
    if(mysqli_num_rows($ub)) {
        $ub = mysqli_fetch_assoc($ub);
        $t = $ub['tier']; 
        $tier = $referral_settings['tier'][$t]['name'] ;
        return $tier;
    } else
        return '';
}
function get_level($user_id) {
    global $DB_CONN, $referral_settings;
    $ub = mysqli_query($DB_CONN, "select level from users where id = '{$user_id}'");
    if(mysqli_num_rows($ub)) {
        $ub = mysqli_fetch_assoc($ub);
        $t = $ub['level']; 
        $tier = $referral_settings['tier'][$t]['name'] ;
        return $tier;
    } else
        return '';
}
function genPinCode($len)
{
    $genStr = '';
    $str = 'MNBVCXZASDFGHJKLPOIUYTREWQ123456789';
    $strLen = strlen($str);
    for($i=0; $i<$len; $i++)
    {
        $genStr .= $str[rand(0, ($strLen-1))];
    }
    return $genStr;
}


function isbtc() {
    global $preferences;
    if($preferences['currency'] == 'BTC')
        return true;
    else
        return false;
}
function isown() {
    global $preferences;
    if($preferences['currency'] == 'SAME')
        return true;
    else
        return false;
}
function isusd() {
    global $preferences;
    if($preferences['currency'] == 'USD')
        return true;
    else
        return false;
}
function currencytousd($amount, $coin, $direction = true) {
    global $DB_CONN;
    $coin = strtoupper($coin);
    $ch = mysqli_fetch_assoc(mysqli_query($DB_CONN,"SELECT rate FROM `currencies` where symbol = '{$coin}' limit 1"));
    if($ch['rate']) {
        $usd = $ch['rate'];
    }
    if($direction)
        $amount = round($amount * $usd,2);
    else
        $amount = round($amount / $usd,8);
    return $amount;
}
function tocurrency($deposit, $cur) {
    global $preferences;
    if(isusd()) {
        if (strpos($cur, "USD") === false) {
            $deposit = currencytousd($deposit, $cur);
        }
    } elseif(isown()) {
        $deposit = $deposit;
    } else {
        if($cur != $preferences['currency']) {
            $deposit = currencytousd($deposit, $cur);
            if (strpos($cur, "USD") === false) {
                $deposit = currencytousd($deposit, $preferences['currency'], false);
            }
        }
    }
    return $deposit;
}
function fromcurrency($deposit, $cur) {
    global $preferences;
    if(isusd()) {
        if (strpos($cur, "USD") === false) {
            $deposit = currencytousd($deposit, $cur, false);
        }
    } elseif(isown()) {
        $deposit = $deposit;
    } else {
        if($cur != $preferences['currency']) {
            $deposit = currencytousd($deposit, $preferences['currency']);
            if (strpos($cur, "USD") === false) {
                $deposit = currencytousd($deposit, $cur, false);
            }
        }
    }
    return $deposit;
}
function daily_earning() {
    global $DB_CONN, $deposit_settings, $dt, $referral_settings, $user_settings;
    $fp = fopen('lock.txt', 'w+');
    if(flock($fp, LOCK_EX | LOCK_NB)) {
    $plans= mysqli_query($DB_CONN,"SELECT package_deposits.id as package_deposit_id, packages.name as package_name, packages.details, package_deposits.package_id as package_id, package_deposits.payment_method_id, package_deposits.plan_id as plan_id, package_deposits.compound,package_deposits.avail,package_deposits.user_id,package_deposits.amount,package_deposits.last_earningDateTime,package_plans.actual_min,package_plans.actual_max,packages.id,packages.etype,packages.earning_delay,packages.frequency,packages.diff_in_seconds, packages.earnings_mon_fri, packages.earning_days from package_deposits INNER join package_plans ON package_deposits.plan_id=package_plans.id inner join 
    packages ON packages.id=package_plans.package_id where package_deposits.package_id=packages.id AND package_deposits.avail < packages.duration AND packages.status!=0 AND package_deposits.status=1 and packages.etype != 3 order by packages.id");

    while($plan=mysqli_fetch_assoc($plans))
    {
        $day = date('l');
        $lday = date('l', strtotime($plan['last_earningDateTime']));
        $diff = 0;
        $ahead = true;
        if($plan['earnings_mon_fri'] == 1) {
          if($day == "Sunday" || $day == "Saturday")
            $ahead = false;
        }
        $date = date("Y-m-d");
        if($ahead && $plan['earnings_mon_fri'] == 2) {
            $earning_days = json_decode($plan['earning_days'], true);
            if($earning_days[$day] != 'on')
                $ahead = false;
        }
        if($ahead) {
            $holiday=mysqli_query($DB_CONN, "SELECT * FROM `holidays` where date = '{$date}'");
            if(mysqli_num_rows($holiday) > 0)
                $ahead = false;
        }
        if($ahead && $user_settings['dailycheckin']) {
            $check_in = mysqli_query($DB_CONN, "SELECT * from users where id = '{$plan['user_id']}' and date(dailycheckin) = CURRENT_DATE");
            if(mysqli_num_rows($check_in) == 0)
                $ahead = false;
        }
        if($ahead && $day == "Monday" && $plan['earnings_mon_fri'] == 1) {
            if($lday == "Saturday")
                $diff += 86400;
            elseif($lday == "Sunday")
                $diff += 0;
            else
                $diff += (86400*2);
        }
        if($plan['earning_delay'])
            $diff += $plan['diff_in_seconds']*$plan['earning_delay'];
        $date_new=strtotime($plan['last_earningDateTime'])+$plan['diff_in_seconds']+$diff;      
        $date_now=time();
        if($date_now >= $date_new && $ahead)
        {
            $max=$plan['actual_max']*10000;
            $min=$plan['actual_min']*10000;
            $per=(mt_rand($min,$max)/10000);
            if($plan['etype'] == 2) {
                $l = mysqli_query($DB_CONN, "SELECT *, (SELECT amount from package_deposits where id = transactions.ref_id) as dep_amount FROM `transactions` WHERE txn_type = 'earning' and package_id = '{$plan['package_id']}' and plan_id = '{$plan['plan_id']}' and date(created_at) = CURRENT_DATE ");
                if(mysqli_num_rows($l) > 0) {
                    $l = mysqli_fetch_assoc($l);
                    $per = round(($l['amount']/$l['dep_amount'])*100, 4);
                }
            }
            $details = json_decode($plan['details'], true);
            if($details['increase'] && $details['increase_percentage'])
                $per += $plan['avail'] * $details['increase_percentage'];
            $amount = ($plan['amount']/100)*$per;
            $compound = 0;
            if($details['compound_enable'] && $plan['compound']) {
                $compound = ($amount/100)*$plan['compound'];
                if($compound)
                    $amount -= $compound;
            }
            $packname = $plan['package_name'];
            $deposit = $plan['amount'];
            $percentage = $per."%";
            $package_id = $plan['package_id'];
            $det = str_replace(array("#plan#","#invested_amount#","#percent#"), array($packname ,fiat($deposit), $percentage), $details['memo']);
            $earnings = mysqli_query($DB_CONN, "INSERT INTO `transactions`( `user_id`, `amount`, `txn_type`, `payment_method_id`, `package_id`, `plan_id`, `ref_id`,`detail` ) VALUES ('{$plan['user_id']}','{$amount}', 'earning', '{$plan['payment_method_id']}','{$plan['package_id']}','{$plan['plan_id']}','{$plan['package_deposit_id']}','{$det}')");
            if($earnings)
            {
                add_balance($plan['user_id'], $plan['payment_method_id'], $amount);
                mysqli_query($DB_CONN,"UPDATE users set earnings=earnings+$amount where id='{$plan['user_id']}'")or die(mysqli_error($DB_CONN));
                mysqli_query($DB_CONN,"UPDATE package_deposits set avail=avail+1, amount = amount + $compound, last_earningDateTime=CURRENT_TIMESTAMP where id='{$plan['package_deposit_id']}'");
                if($referral_settings['profit_share'])
                    refferal_commission($plan['package_deposit_id'], $amount);
            }
        }
    }
    //earning limit package 
    $st = mysqli_query($DB_CONN, "SELECT package_deposits.id, package_deposits.user_id, (SELECT sum(amount) from transactions WHERE ref_id = package_deposits.id and txn_type = 'earning') as given, package_deposits.amount, (SELECT name from currencies where id = package_deposits.payment_method_id) as account, packages.earning_limit, packages.name FROM `package_deposits` inner join packages on package_deposits.package_id = packages.id where package_deposits.status = 1 and packages.earning_limit > 0 ");
    if(mysqli_num_rows($st)) {
        while ($status=mysqli_fetch_assoc($st)) {
            $amount = $status['amount'];
            $limit = ($amount/100)*$status['earning_limit'];
            if($given >= $limit) {
                mysqli_query($DB_CONN,"UPDATE package_deposits set status = 0 WHERE id = '{$status['id']}'");
                sendmail("user_investment_expired", $status['user_id'], array(), array('amount' => $amount, 'datetime' => $dt, 'pack_name' => $status['name'], 'account' => $status['account']));
            }
        }
    }
    //principal return with package closing
    $st = mysqli_query($DB_CONN,"SELECT package_deposits.id, packages.name, packages.details, package_deposits.compound, package_deposits.auto_reinvest, packages.principal_hold, package_deposits.user_id, package_deposits.payment_method_id, package_deposits.amount, package_deposits.payment_method_id, package_deposits.package_id, package_deposits.plan_id, (SELECT name from currencies where id = package_deposits.payment_method_id) as account FROM `package_deposits`
    inner join packages on package_deposits.package_id = packages.id
    where package_deposits.status = 1 and package_deposits.avail = packages.duration and packages.principal_return = 1");
    if(mysqli_num_rows($st)) {
        while ($status=mysqli_fetch_assoc($st)) {
            $details = json_decode($status['details'], true);
            if($details['compound_enable'] && !$details['compound_end']) {
                $status['amount'] = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT amount FROM `transactions` WHERE ref_id = '{$status['package_id']}' and txn_type = 'invest' and status = 1 order by id desc limit 1"))[0];
            }
            if($status['principal_hold']) {
                $per = ($status['amount']/100)*$status['principal_hold'];
                $amount = $status['amount'] - $per;
            } else
                $amount = $status['amount'];
            if($status['auto_reinvest'] && $details['auto_reinvest']) {
                mysqli_query($DB_CONN,"UPDATE package_deposits set status = 0 WHERE id = '{$status['id']}'");
                $id = add_package($status['payment_method_id'], $amount, $status['user_id'], $status['package_id'], $status['plan_id'], 1);
                $detail = str_replace(array("#amount#","#plan#"), array(fiat($amount), $status['name']), $deposit_settings['auto_reinvest']);
                mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, fee, payment_method_id, txn_type, detail, ref_id) values('{$status['user_id']}', '{$amount}', '0', '{$status['payment_method_id']}', 'invest' , '{$detail}', '{$id}')");
                $mname = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT name from currencies where id = '{$status['payment_method_id']}'"))[0];
                sendmail("invest_user_notification", $user_id, array(), array('amount' => $amount, 'account' => $mname, 'pack_name' => $status['name'], 'txn_id' => 'Auto Reinvest', 'datetime' => $dt, 'compound' => false));
            } else {
                $detail = str_replace(array("#amount#","#hold_amount#","#hold_percentage#","#plan#"), array(fiat($amount), $per, $status['principal_hold'], $status['name']), $deposit_settings['investment_returned']);
                mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, fee, payment_method_id, txn_type, detail, ref_id) values('{$status['user_id']}', '{$amount}', '0', '{$status['payment_method_id']}', 'return' , '{$detail}', '{$status['id']}')");
                add_balance($status['user_id'], $status['payment_method_id'], $amount);
                mysqli_query($DB_CONN,"UPDATE package_deposits set status = 0 WHERE id = '{$status['id']}'");
            }
            sendmail("user_investment_expired", $status['user_id'], array(), array('amount' => $amount, 'datetime' => $dt, 'pack_name' => $status['name'], 'account' => $status['account']));
        }
    }
    //duration wise package ending
    $st = mysqli_query($DB_CONN,"SELECT package_deposits.id, package_deposits.user_id, package_deposits.amount, packages.name, (SELECT name from currencies where id = package_deposits.payment_method_id) as account FROM `package_deposits`
    inner join packages on package_deposits.package_id = packages.id
    where package_deposits.status = 1 and package_deposits.avail = packages.duration");
    if(mysqli_num_rows($st)) {
        while ($status=mysqli_fetch_assoc($st)) {
            mysqli_query($DB_CONN,"UPDATE package_deposits set status = 0 WHERE id = '{$status['id']}'");
            mysqli_query($DB_CONN,"UPDATE transactions set status = 0 WHERE id = '{$status['id']}'");
            sendmail("user_investment_expired", $status['user_id'], array(), array('amount' => $status['amount'], 'datetime' => $dt, 'pack_name' => $status['name'], 'account' => $status['account']));
        }
    }
    flock($fp, LOCK_UN);
    }
    fclose($fp);
    //unlink("lock.txt");
}
function add_package($payment_method_id, $deposit, $user_id, $package_id, $plan_id, $auto_reinvest = 0) {
    global $DB_CONN, $dt;
    $method = mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$payment_method_id}'");
    $method = mysqli_fetch_assoc($method);
    //fee calculation 
    if($method['dep_fee_per']) {
        $fee = $deposit/100*$method['dep_fee_per'];
    } elseif($method['dep_fee_amount']) {
        $fee = $method['dep_fee_amount'];
    }
    if($fee) {
        if($fee < $method['dep_fee_min'] && $method['dep_fee_min']) {
            $fee = $method['dep_fee_min'];
        } elseif($fee > $method['dep_fee_max'] && $method['dep_fee_max']) {
            $fee = $method['dep_fee_max'];
        }
    } else {
        $fee = 0;
    }
    $detail = $method['name'].' Account Balance';
    $DEPOSIT1= mysqli_query($DB_CONN,"INSERT INTO package_deposits (user_id,package_id,plan_id,amount,fee,status,payment_method_id,datetime, last_earningDateTime, txn_id, auto_reinvest) VALUES ('{$user_id}','{$package_id}','{$plan_id}','{$deposit}','{$fee}',1,'{$payment_method_id}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP, '{$detail}', '{$auto_reinvest}')");
    $id = mysqli_insert_id($DB_CONN);
    return $ID;
}
function coinpayments_api_call($cmd, $req = array(), $login = array()) {
    $public_key = $login['public_key'];
    $private_key = $login['private_key'];
    
    $req['version'] = 1;
    $req['cmd'] = $cmd;
    $req['key'] = $public_key;
    $req['format'] = 'json';
    $post_data = http_build_query($req, '', '&');
    $hmac = hash_hmac('sha512', $post_data, $private_key);
    static $ch = NULL;
    if ($ch === NULL) {
        $ch = curl_init('https://www.coinpayments.net/api.php');
        curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: '.$hmac));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $data = curl_exec($ch);             
    if ($data !== FALSE) {
        if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0)
            $dec = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
        else
            $dec = json_decode($data, TRUE);
        if ($dec !== NULL && count($dec))
            return $dec;
        else
            return array('error' => 'Unable to parse JSON result ('.json_last_error().')');
        
    } else {
        return array('error' => 'cURL error: '.curl_error($ch));
    }
}
function secondsToWords($seconds)
{
    $ret = "";
    $days = intval(intval($seconds) / (3600*24));
    if($days> 0)
    {
        $ret .= " days ";
    }
    $hours = (intval($seconds) / 3600) % 24;
    if($hours > 0)
    {
        $ret .= " hours ";
    }
    $minutes = (intval($seconds) / 60) % 60;
    if($minutes > 0)
    {
        //$ret .= "$minutes minutes ";
    }
    return $ret;
}
function getIpInfo($ip) {
    $data = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
    
    if (!$data || empty($data['country'])) {
        $data = @json_decode(file_get_contents("https://ipapi.co/{$ip}/json/"), true);
        if ($data) {
            $data['country'] = $data['country_name'] ?? '';
            $data['city'] = $data['city'] ?? '';
            $data['timezone'] = $data['timezone'] ?? '';
        }
    }
    
    return $data;
}

function login_report($DB_CONN, $limit = 15) {
    $query = "
    (SELECT 'login' as type, id, ip, user_id
    FROM login_report 
    WHERE country = ''
    AND ip != '0.0.0.0'
    AND ip != '')
    UNION ALL
    (SELECT 'user' as type, NULL as id, ip, user_id
    FROM login_report lr
    INNER JOIN users u ON lr.user_id = u.id
    WHERE u.timezone = '' 
    AND u.is_admin = 0
    AND lr.ip != '0.0.0.0'
    AND lr.ip != ''
    AND lr.id = (
        SELECT MAX(id) 
        FROM login_report 
        WHERE user_id = u.id
        AND ip != '0.0.0.0'
        AND ip != ''
    ))
    LIMIT " . (int)$limit;

    $result = mysqli_query($DB_CONN, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $existing = mysqli_query($DB_CONN, 
            "SELECT country, city, timezone FROM login_report 
             WHERE country != '' AND ip = '" . mysqli_real_escape_string($DB_CONN, $row['ip']) . "'
             AND ip != '0.0.0.0'
             LIMIT 1");
        if (mysqli_num_rows($existing)) {
            $ipdat = mysqli_fetch_assoc($existing);
        } else {
            $ipdat = getIpInfo($row['ip']);
            if (!$ipdat) continue;
        }

        if ($row['type'] === 'login' && !empty($ipdat['country'])) {
            mysqli_query($DB_CONN, "UPDATE login_report SET 
                country = '" . mysqli_real_escape_string($DB_CONN, $ipdat['country']) . "',
                city = '" . mysqli_real_escape_string($DB_CONN, $ipdat['city']) . "'
                WHERE id = '{$row['id']}'");
        }
        
        if ($row['type'] === 'user' && !empty($ipdat['timezone'])) {
            mysqli_query($DB_CONN, "UPDATE users SET 
                timezone = '" . mysqli_real_escape_string($DB_CONN, $ipdat['timezone']) . "'
                WHERE id = '{$row['user_id']}'");
        }
    }
}

function getUserIP() {
    if (!empty($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        return $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR' 
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipArray = explode(',', $_SERVER[$header]);
            foreach ($ipArray as $ip) {
                $ip = trim($ip); 
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    return 'Unknown IP';
}
function getOS() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform  = "Unknown OS Platform";
    $os_array    = array(
        '/windows nt 10/i'    =>  'Windows 10',
        '/windows nt 6.3/i'  =>  'Windows 8.1',
        '/windows nt 6.2/i'  =>  'Windows 8',
        '/windows nt 6.1/i'  =>  'Windows 7',
        '/windows nt 6.0/i'  =>  'Windows Vista',
        '/windows nt 5.2/i'  =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'  =>  'Windows XP',
        '/windows xp/i'      =>  'Windows XP',
        '/windows nt 5.0/i'  =>  'Windows 2000',
        '/windows me/i'      =>  'Windows ME',
        '/win98/i'            =>  'Windows 98',
        '/win95/i'            =>  'Windows 95',
        '/win16/i'            =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'            =>  'Linux',
        '/ubuntu/i'          =>  'Ubuntu',
        '/iphone/i'          =>  'iPhone',
        '/ipod/i'              =>  'iPod',
        '/ipad/i'              =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'      =>  'BlackBerry',
        '/webos/i'            =>  'Mobile'
    );
    foreach ($os_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $os_platform = $value;
    return $os_platform;
}

function getBrowser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser        = "Unknown Browser";
    $browser_array = array(
        '/msie/i'     => 'Internet Explorer',
        '/firefox/i'   => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/edge/i'     => 'Edge',
        '/opera/i'   => 'Opera',
        '/netscape/i'  => 'Netscape',
        '/maxthon/i'   => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i' => 'Mobile Browser'
    );
    foreach ($browser_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $browser = $value;
    return $browser;
}
function auto_withdraw() {
    global $DB_CONN, $preferences, $pref, $withdraw_settings, $userinfo, $timestamp, $cronTimes;
    $check = $check_rates = false;
    $datetime = date("Y-m-d H:i:s");
    $time = time();
    $diff = 60*1;
    $diff1 = 60*15;
    $c = mysqli_query($DB_CONN, "SELECT * from cron where type = 1 order by id desc limit 1");
    if(mysqli_num_rows($c) == 0) {
        $check = true;
    } else {
        $cron = mysqli_fetch_assoc($c);
        $dt = strtotime($cron['datetime']);
        if($dt+$diff <= $time)
            $check = true;
    }
    $c = mysqli_query($DB_CONN, "SELECT * from cron where type = 2 order by id desc limit 1");
    if(mysqli_num_rows($c) == 0) {
        $check_rates = true;
    } else {
        $cron = mysqli_fetch_assoc($c);
        $dt = strtotime($cron['datetime']);
        if($dt+$diff1 <= $time)
            $check_rates = true;
    }
    $fp = fopen('lock.txt', 'w+');
    if(flock($fp, LOCK_EX | LOCK_NB)) {
        $a = true;
        if($withdraw_settings['instantwithdraw_weekdays'] && ($day == "Sunday" || $day == "Saturday"))
            $a = false;
        if(($withdraw_settings['delay_instant_withdraw'] || $withdraw_settings['delay_auto_withdraw']) && $a) {
            $w = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE txn_type = 'withdraw' and status = 0 and ref_id and created_at < NOW() - INTERVAL ref_id MINUTE");
            while ($with = mysqli_fetch_assoc($w)) {
                $pp = mysqli_query($DB_CONN, "SELECT * from users where id = {$with['user_id']}");
                $user = mysqli_fetch_assoc($pp);
                $cur = mysqli_query($DB_CONN, "SELECT c.*, pm.* FROM currencies c 
        LEFT JOIN payment_methods pm ON pm.id = c.wi_pm_id
        WHERE c.id = '{$with['payment_method_id']}'");
                $currency = mysqli_fetch_assoc($cur);
                if($user['wi_pm_id'] && !in_array($currency['wi_pm_id'], array(2, 6, 17)))
                  $currency['wi_pm_id'] = $user['wi_pm_id'];
                   $system = json_decode($currency['currencies'], true);
                   $processor = new PaymentProcessor(
        $currency['wi_pm_id'], 
        $system, 
        $DB_CONN,
        $preferences, 
        $siteURL,
        $g_alert, 
        $main_link
        );
        $tx_id = $processor->processWithdrawal(
            $with['address'], 
            $with['amount'], 
            $with['id'], 
            $currency['symbol']
        );
            //  $tx_id = sendwithdraw($currency['wi_pm_id'], $with['address'], $with['amount'], $with['id'], $currency['symbol']);
            if($tx_id) {
                if($with['plan_id'] == 0) {
                    $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($with['address'] ,$currency['name'], $tx_id), $withdraw_settings['instant_memo']);
                    if($currency['wi_pm_id'] != 10 && $currency['wi_pm_id'] != 11)
                        sendmail("withdraw_user_notification", $user['id'], $user, array('amount' => $with['amount'], 'txn_id' => $tx_id, 'account' => $currency['name'], 'tx_url' => $with['tx_url'], 'address' => $with['address']));
                } elseif($with['plan_id'] == 1) {
                    $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($with['address'] ,$currency['name'], $tx_id), $withdraw_settings['auto_memo']);
                    if($currency['wi_pm_id'] != 10 && $currency['wi_pm_id'] != 11)
                        sendmail("withdraw_auto_user_notification", $user['id'], $user, array('amount' => $with['amount'], 'txn_id' => $tx_id, 'account' => $currency['name'], 'tx_url' => $with['tx_url'], 'address' => $with['address']));
                }
                mysqli_query($DB_CONN, "UPDATE `transactions` set status = 1, txn_id = '{$tx_id}', detail = '{$memo}', package_id = '{$currency['wi_pm_id']}' where id = {$with['id']}");
            }
        }
    }
    if($check) {
        mysqli_query($DB_CONN, "INSERT into cron(type, datetime) values(1, CURRENT_TIMESTAMP)");
        //bybit txnids  
        $system = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '10'"));
        $system = json_decode($system['currencies'], true);
        $api_key = encrypt_decrypt('decrypt', $system['api_key']);
        $secret_key = encrypt_decrypt('decrypt', $system['secret_key']);
        $t = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE length(txn_id) <= 14 and status = 1 and txn_id and package_id = '10'");
        while ($tx = mysqli_fetch_assoc($t)) {
            $c = mysqli_query($DB_CONN, "SELECT * FROM `currencies` WHERE id = '{$tx['payment_method_id']}'");
            $currency = mysqli_fetch_assoc($c);
            $timestamp = time() * 1000;
            $response = http_req($api_key, $secret_key, "/v5/asset/withdraw/query-record","GET","withdrawID=".$tx['txn_id']);
            $res = mysqli_real_escape_string($DB_CONN, json_encode($response));
            mysqli_query($DB_CONN, "INSERT INTO `payment_log`(`payment_method_id`, `with_id`, `detail`, `status`) VALUES ('10', '{$tx['id']}', '{$res}', '1')");
            $txn_id = $response['result']['rows'][0]['txID'];
            if($txn_id) {
                $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$tx['user_id']}'"));
                if($tx['plan_id'] == 0) {
                    $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($tx['address'] ,$currency['name'], $txn_id), $withdraw_settings['instant_memo']);
                    sendmail("withdraw_user_notification", $user['id'], $user, array('amount' => $tx['amount'], 'txn_id' => $txn_id, 'account' => $currency['name'], 'tx_url' => $tx['tx_url'], 'address' => $tx['address']));
                } elseif($tx['plan_id'] == 1) {
                    $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($tx['address'] ,$currency['name'], $txn_id), $withdraw_settings['auto_memo']);
                    sendmail("withdraw_auto_user_notification", $user['id'], $user, array('amount' => $tx['amount'], 'txn_id' => $txn_id, 'account' => $currency['name'], 'tx_url' => $tx['tx_url'], 'address' => $tx['address']));
                }
                mysqli_query($DB_CONN, "UPDATE transactions set detail = '{$memo}', txn_id = '{$txn_id}' where id = '{$tx['id']}'");
            }
        }
        //binance ids
        $system = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '11'"));
        $system = json_decode($system['currencies'], true);
        $api_key = encrypt_decrypt('decrypt', $system['api_key']);
        $secret_key = encrypt_decrypt('decrypt', $system['secret_key']);
        $t = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE length(txn_id) <= 32 and status = 1 and package_id = 11 AND user_id NOT IN (SELECT id from users WHERE auto_withdraw = 4)");
        while ($tx = mysqli_fetch_assoc($t)) {
            $c = mysqli_query($DB_CONN, "SELECT * FROM `currencies` WHERE id = '{$tx['payment_method_id']}'");
            $currency = mysqli_fetch_assoc($c);
            $master_id = $tx['txn_id'];
            $params = array();
            $params['limit'] = 10; 
            $params['timestamp'] = number_format(microtime(true)*1000,0,'.','');
            $query = http_build_query($params, '', '&');
            $signature = hash_hmac('sha256', $query, $secret_key);
            $params['signature'] = $signature; 
            $query = http_build_query($params, '', '&');
            $url = "https://api.binance.com/sapi/v1/capital/withdraw/history?{$query}";
            $curl_handle = curl_init($url);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)");
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                'X-MBX-APIKEY: ' . $api_key,
            ));
            $response = curl_exec($curl_handle);
            $res = json_decode($response, true);
            foreach ($res as $key => $value) {
                if($value['id'] == $master_id) {
                    if($value['txId']) {
                        $txn_id = $value['txId'];
                        $res1 = mysqli_real_escape_string($DB_CONN, json_encode($value));
                        mysqli_query($DB_CONN, "INSERT INTO `payment_log`(`payment_method_id`, `with_id`, `detail`, `status`) VALUES ('11', '{$tx['id']}', '{$res1}', '1')");
                        $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$tx['user_id']}'"));
                        if($tx['plan_id'] == 0) {
                            $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($tx['address'] ,$currency['name'], $txn_id), $withdraw_settings['instant_memo']);
                            sendmail("withdraw_user_notification", $user['id'], $user, array('amount' => $tx['amount'], 'txn_id' => $txn_id, 'account' => $currency['name'], 'tx_url' => $tx['tx_url'], 'address' => $tx['address']));
                        } elseif($tx['plan_id'] == 1) {
                            $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($tx['address'] ,$currency['name'], $txn_id), $withdraw_settings['auto_memo']);
                            sendmail("withdraw_auto_user_notification", $user['id'], $user, array('amount' => $tx['amount'], 'txn_id' => $txn_id, 'account' => $currency['name'], 'tx_url' => $tx['tx_url'], 'address' => $tx['address']));
                        }
                        mysqli_query($DB_CONN, "UPDATE transactions set detail = '{$memo}', txn_id = '{$txn_id}' where id = '{$tx['id']}'");
                    }
                }
            }
        }
    }
    if($check_rates) {
        if($infobox_settings['statistics'])
            updateSiteData($siteURL);
        mysqli_query($DB_CONN, "INSERT into cron(type, datetime) values(2, CURRENT_TIMESTAMP)");
        update_rates();
        if ($versions['auto_updates']) {
            if (!isset($cronTimes[4]) || strtotime($cronTimes[4]) + 43200 <= time()) {
                file_get_contents($siteURL.'/bitder?check_app_update=1');
                file_get_contents($siteURL.'/bitder?check_script_update=1');
                mysqli_query($DB_CONN, "INSERT INTO cron (type, datetime) VALUES (4, NOW())");
            }
        }
    if(($withdraw_settings['autowithdraw'] || $withdraw_settings['autowithdraw_request'])) {
        //balances loop
        $cur = mysqli_query($DB_CONN, "SELECT * FROM `currencies` where wi_pm_id");
        while($currency = mysqli_fetch_assoc($cur)) {
            $u = mysqli_query($DB_CONN, "SELECT * from user_balances where payment_method_id = '{$currency['id']}' and balance >= '{$currency['with_min']}' and balance > 0");
            while($ub = mysqli_fetch_assoc($u)) {
                $user_id = $ub['user_id'];
                $bal = $ub['balance'];
                $wf = "";
                if($withdraw_settings['autowithdraw_2fa'])
                    $wf = " and 2fa !=''";
                $userinfo = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = {$user_id} {$wf}"));
                $userinfo['wallets'] = json_decode($userinfo['wallets'], true);
                $data = check_withdraw($bal, $currency['id'], 'auto');
                if(!$data[0]):
                    $amount = $data[1];
                    $fee = $data[2];
                    $address = $data[3];
                    $detail = str_replace(array("#address#","#method#"), array($address ,$currency['name']), $withdraw_settings['auto_req_memo']);
                    $w = mysqli_query($DB_CONN,"INSERT into transactions (user_id, address, amount, fee, txn_type, am_type, payment_method_id, status,detail) values('{$user_id}', '{$address}', '{$bal}', '{$fee}', 'withdraw', 'out', '{$currency['id']}', '0', '{$detail}')");
                    $wid = mysqli_insert_id($DB_CONN);
                    add_balance($user_id, $currency['id'], -$amount);
                    $day = date('l');
                    $a = true;
                    if($withdraw_settings['autowithdraw_weekdays'] && ($day == "Sunday" || $day == "Saturday"))
                        $a = false;
                    if(!$withdraw_settings['max_daily_auto_withdraw_limit'])
                        $withdraw_settings['max_daily_auto_withdraw_limit'] = 100000000000000000000;
                    if($a && $withdraw_settings['autowithdraw_autouser'] && $userinfo['auto_withdraw'] != 1)
                        $a = false;
                    if($wid && $userinfo['auto_withdraw'] != 3 && $userinfo['auto_withdraw'] != 2 && !$withdraw_settings['autowithdraw_request'] && !$withdraw_settings['delay_auto_withdraw'] && $amount <= $withdraw_settings['max_daily_auto_withdraw_limit'] && $a) {
                        if($userinfo['wi_pm_id'] && !in_array($currency['wi_pm_id'], array(2, 6, 17)))
                          $currency['wi_pm_id'] = $userinfo['wi_pm_id'];
                        if($userinfo['auto_withdraw'] == 4)
                            $tx_id = mt_rand(1000000000000,9999999999999);
                        else
                            $tx_id = sendwithdraw($currency['wi_pm_id'], $address, $amount, $wid, $currency['symbol']);
                        if($tx_id) {
                            $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($address ,$currency['name'], $tx_id), $withdraw_settings['auto_memo']);
                            mysqli_query($DB_CONN, "UPDATE `transactions` set status = 1, txn_id = '{$tx_id}', detail = '{$memo}', package_id = '{$currency['wi_pm_id']}'  where id = {$wid}");
                            $tx_url = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT tx_url from transactions WHERE id = '{$wid}'"))[0];
                            if($currency['wi_pm_id'] != 10 && $currency['wi_pm_id'] != 11 && $userinfo['auto_withdraw'] != 4)
                                sendmail("withdraw_auto_user_notification", $userinfo['id'], $userinfo, array('amount' => $amount, 'txn_id' => $tx_id, 'account' => $currency['name'], 'tx_url' => $tx_url, 'address' => $address));
                        } else {
                             // mysqli_query($DB_CONN, "DELETE from transactions where id = '{$wid}'");
                             // add_balance($user_id, $currency['id'], $amount);
                        }
                        if($userinfo['auto_withdraw'] != 4) {
                            $delay = $withdraw_settings['delay_auto_withdraw_sleep'] ?: 10;
                            sleep($delay);
                        }
                    } elseif($wid && $user['auto_withdraw'] != 3 && $user['auto_withdraw'] != 2 && !$withdraw_settings['autowithdraw_request'] && $amount <= $withdraw_settings['max_daily_auto_withdraw_limit'] && $a && $withdraw_settings['delay_auto_withdraw']) {
                        mysqli_query($DB_CONN, "UPDATE `transactions` set ref_id = '{$withdraw_settings['delay_auto_withdraw']}', plan_id = 1 where id = {$wid}");
                    }
                endif;
            }
        }
    }
    }
    flock($fp, LOCK_UN);
    }
    fclose($fp);
    //unlink("lock.txt");
}
function sendwithdraw($wid, $address, $amount, $id = 0, $wallet='BTC', $log = false) {
    switch ($wid) {
        case '7':
            $tx_id = sendycpay($address, $amount, $id, $wallet);
            break;
    }
    return $tx_id;
}

function sendycpay($account, $amount, $id, $wallet = 'USDT') {
    global $DB_CONN;
    $system = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '7'"));
    $ma = $system;
    $system = json_decode($system['currencies'], true);
    $system['priv_key'] = md5(encrypt_decrypt('decrypt', $system['secret_key']));
    $amount = fromcurrency($amount, $wallet);
    $amount = number_format($amount,8,'.', '');
    $memo = urlencode("Withdraw # {$id}");
    $data = get_contents("https://coinments.com/payment.php?api_key={$system['priv_key']}&type=payment&symbol={$wallet}&amount={$amount}&address={$account}&order_no={$id}&memo={$memo}");
    $data = json_decode($data, true);
    if (!$data['tx_id']) {
        mysqli_query($DB_CONN, "INSERT INTO `payment_log`(`payment_method_id`, `with_id`, `detail`, `status`) VALUES ('{$ma['id']}', '{$id}', '{$data['message']}', '0')");
        sendadminmail("withdraw_error_admin_notification", $id, array(), array("amount" => $amount, 'id' => $id, "error" => $data['message']));
        return false;
    } else {
        mysqli_query($DB_CONN, "UPDATE `transactions` set tx_url = '{$data['tx_url']}', txn_id = '{$data['tx_id']}' WHERE id = '{$id}'");
        mysqli_query($DB_CONN, "INSERT INTO `payment_log`(`payment_method_id`, `with_id`, `detail`, `status`) VALUES ('{$ma['id']}', '{$id}', '{$data['tx_id']}', '1')");
        return $data['tx_id'];
    }
}

function code_check_exists($page) {
    global $withdraw_settings, $transfer_settings, $register_settings, $exchange_settings, $user_settings, $login_settings;
    $cc = false;
    switch ($page) {
        case 'withdraw':
            $settings = $withdraw_settings;
            break;
        case 'transfer':
            $settings = $transfer_settings;
            break;
        case 'exchange':
            $settings = $exchange_settings;
            break;
        case 'profile':
            $settings = $user_settings;
            break;
        case 'login':
            $settings = $login_settings;
            break;
        case 'register':
            $settings = $register_settings;
            break;
        case 'forgot':  

            $settings = [
                '2fa_code' => false, // Typically not used for password reset
                'pin_code' => false, // Typically not used for password reset
                'email_code' => isset($user_settings['forgot_email_code']) ? $user_settings['forgot_email_code'] : false,
                 'email_link' => isset($user_settings['forgot_email_link']) ? $user_settings['forgot_email_link'] : false
            ];
         
            break;    

    }
    if($settings['2fa_code'] )
        $cc = true;
    if($settings['pin_code'])
        $cc = true;
    if($settings['email_code'])
        $cc = true;
    if($settings['email_link'])
        $cc = true;
    return $cc;
}
function code_check_new($type, $settings) {
    global $data, $userinfo, $DB_CONN;
    
    // If no verification methods exist at all, return true immediately
    if (!code_check_exists($type)) {
        return true;
    }
    
    // Check if verification codes are provided in the request
    if (isset($_POST['2fa_code']) || isset($_POST['pin_code']) || isset($_POST['email_code'])) {
        // User has provided verification codes, validate them
        return code_check($type, $type);
    } else {

        // If confirmation is disabled but verification is required,
        // we need verification codes with the initial request
        if (!$settings['confirmation']) {
            // Frontend should have sent verification codes with initial request
            // since confirmation is disabled, but none were provided
            $data['result'] = false;
            $data['verify'] = true;
            // Set up the verification requirements
            $data['verification'] = [
                '2fa_code' => [
                    'required' => isset($settings['2fa_code']) && $settings['2fa_code'] && 
                                  (!empty($userinfo) && isset($userinfo['2fa']) && $userinfo['2fa'] !== ''),
                    'verified' => is_method_verified('2fa_code')
                ],
                'pin_code' => [
                    'required' => isset($settings['pin_code']) ? $settings['pin_code'] : false,
                    'verified' => is_method_verified('pin_code')
                ],
                'email_code' => [
                    'required' => isset($settings['email_code']) ? $settings['email_code'] : false,
                    'verified' => is_method_verified('email_code')
                ]
            ];
            
            // Send email OTP if required and not already verified
            if (isset($settings['email_code']) && $settings['email_code'] && !is_method_verified('email_code')) {
                sendotp($type);
            }
            
            return false;
        }
        
        // Set up verification requirements for standard flow
        $data['result'] = false;
        $data['verify'] = true;
        
        // Store user ID as temp_id for verification
        if (isset($userinfo['id'])) {
            $data['temp_id'] = sha1($userinfo['id']);
            $_SESSION['temp_id'] = $userinfo['id'];
        } else if (isset($_SESSION['user_id'])) {
            $data['temp_id'] = sha1($_SESSION['user_id']);
            $_SESSION['temp_id'] = $_SESSION['user_id'];
        }
        
        // Set required verification methods with verification status
        $data['verification'] = [
            '2fa_code' => [
                'required' => isset($settings['2fa_code']) && $settings['2fa_code'] && 
                                  (!empty($userinfo) && isset($userinfo['2fa']) && $userinfo['2fa'] !== ''),
                'verified' => is_method_verified('2fa_code')
            ],
            'pin_code' => [
                'required' => isset($settings['pin_code']) ? $settings['pin_code'] : false,
                'verified' => is_method_verified('pin_code')
            ],
            'email_code' => [
                'required' => isset($settings['email_code']) ? $settings['email_code'] : false,
                'verified' => is_method_verified('email_code')
            ]
        ];
        
        // Send email OTP if required and not already verified
        if (isset($settings['email_code']) && $settings['email_code'] && !is_method_verified('email_code')) {
            sendotp($type);
        }
        
        return false;
    }
}


// Improved sendotp function to handle all cases consistently
function sendotp($action, $user_id = null) {
    global $userinfo, $DB_CONN, $data;
    
    // Determine user ID
    if ($user_id) {
        $u_id = $user_id;
    } else if (!empty($userinfo['id'])) {
        $u_id = $userinfo['id'];
    } else if (!empty($_SESSION['temp_id'])) {
        $u_id = $_SESSION['temp_id'];
    } else if (!empty($_SESSION['email'])) {
        $u_id = $_SESSION['email'];
        $type = 'email';
    } else {
        call_alert(304); // No user identifier found
        return false;
    }
    
    // Store action in session
    $_SESSION['otp_action'] = $action;
    
    // Check for rate limiting (prevent spam)
    $check_recent = mysqli_query($DB_CONN, "SELECT * FROM `otp` WHERE user_id = '{$u_id}' AND action = '{$action}' AND timestamp >= NOW() - INTERVAL 1 MINUTE");
    if (mysqli_num_rows($check_recent)) {
        call_alert(302); // Please wait 60 seconds before requesting another code
        return false;
    }
    
    // Invalidate previous OTPs for this user and action
    mysqli_query($DB_CONN, "UPDATE `otp` SET status = 0 WHERE user_id = '{$u_id}' AND action = '{$action}'");
    
    // Generate new OTP
    $code = mt_rand(100000, 999999);
    
    // Store OTP in session for verification
    $_SESSION['email_code'] = $code;
    
    // Insert new OTP record
    mysqli_query($DB_CONN, "INSERT INTO `otp`(`user_id`, `code`, `action`, `status`) VALUES ('{$u_id}', '{$code}', '{$action}', 1)");
    
    // Send email with OTP
    if (isset($type) && $type == 'email') {
        sendmail("email_code_c", $u_id, "", array("action" => $action, "code" => $code));
    } else {
        sendmail("email_code", $u_id, "", array("action" => $action, "code" => $code));
    }
    
    call_alert(301); // Verification code sent to your email
    return true;
}

// Improved code_check function
function update_verification_status($method, $status) {
    if (!isset($_SESSION['verification_status'])) {
        $_SESSION['verification_status'] = [];
    }
    $_SESSION['verification_status'][$method] = $status;
}

// Add this function to check if a method is already verified
function is_method_verified($method) {
    return isset($_SESSION['verification_status'][$method]) && $_SESSION['verification_status'][$method] === true;
}

// Modify the code_check function to track each verification method separately
function code_check($page = '', $action = 'default') {
    global $userinfo, $withdraw_settings, $transfer_settings, $exchange_settings, $register_settings, $user_settings, $userinfo, $login_settings, $DB_CONN, $data;
    
    $verification_results = [
        'overall' => true,
        '2fa_code' => null,
        'pin_code' => null,
        'email_code' => null
    ];

    // Determine which settings to use
    switch ($page) {
        case 'register':
            $settings = $register_settings;
            break;
        case 'withdraw':
            $settings = $withdraw_settings;
            break;
        case 'transfer':
            $settings = $transfer_settings;
            break;
        case 'exchange':
            $settings = $exchange_settings;
            break;
        case 'profile':
            $settings = $user_settings;
            break;
        case 'login':
            $settings = $login_settings;
            break;
        case 'forgot':
            $settings = [
                '2fa_code' => false,
                'pin_code' => false,
                'email_code' => isset($user_settings['forgot_email_code']) ? $user_settings['forgot_email_code'] : false,
                'email_link' => isset($user_settings['forgot_email_link']) ? $user_settings['forgot_email_link'] : false
            ];
            break;
    }

    // Track if any verification method has been attempted in this request
    $verification_attempted = false;
    
    // Verify 2FA code if required
if (isset($settings['2fa_code']) && $settings['2fa_code']) {
    // Check if user has 2FA enabled
    $is_2fa_required = isset($userinfo['2fa']) && $userinfo['2fa'] !=='' ;
    
    // Only proceed with 2FA verification if user has it enabled
    if ($is_2fa_required) {
        // Skip if already verified
        if (is_method_verified('2fa_code')) {
            $verification_results['2fa_code'] = true;
        }
        // Check if provided in current request
        else if (isset($_POST['2fa_code']) && !empty($_POST['2fa_code'])) {
            $verification_attempted = true;
            if (TokenAuth6238::verify($userinfo['2fa'], $_POST['2fa_code'])) {
                $verification_results['2fa_code'] = true;
                update_verification_status('2fa_code', true);
            } else {
                call_alert(28); // Invalid 2FA Code
                $verification_results['2fa_code'] = false;
                $verification_results['overall'] = false;
            }
        } else {
            // 2FA required but not provided
            $verification_results['2fa_code'] = false;
            $verification_results['overall'] = false;
            
            // Only show the error if 2FA code was actually attempted to be submitted
            // or if no other verification method was attempted
            if (!$verification_attempted && !isset($_POST['pin_code']) && !isset($_POST['email_code'])) {
                call_alert(28); // Invalid 2FA Code
            }
        }
    } else {
        // User doesn't have 2FA enabled, so mark it as verified
        $verification_results['2fa_code'] = true;
    }
}
    
    // Verify PIN code if required
    if (isset($settings['pin_code']) && $settings['pin_code']) {
        // Skip if already verified
        if (is_method_verified('pin_code')) {
            $verification_results['pin_code'] = true;
        }
        // Check if provided in current request
        else if (isset($_POST['pin_code']) && !empty($_POST['pin_code'])) {
            $verification_attempted = true;
            if ($_POST['pin_code'] == $userinfo['pin_code']) {
                $verification_results['pin_code'] = true;
                update_verification_status('pin_code', true);
            } else {
                call_alert(30); // Pin code is not correct
                $verification_results['pin_code'] = false;
                $verification_results['overall'] = false;
            }
        } else {
            // PIN required but not provided
            $verification_results['pin_code'] = false;
            $verification_results['overall'] = false;
            
            // Only show the error if PIN code was actually attempted to be submitted
            // or if no other verification method was attempted yet
            if (!$verification_attempted && !isset($_POST['email_code'])) {
                call_alert(30); // Pin code is not correct
            }
        }
    }

    // Verify email code if required
    if (isset($settings['email_code']) && $settings['email_code']) {
        // Skip if already verified
        if (is_method_verified('email_code')) {
            $verification_results['email_code'] = true;
        }
        // Check if provided in current request
        else if (isset($_POST['email_code']) && !empty($_POST['email_code'])) {
            $verification_attempted = true;
            // Determine user ID
            $verify_user_id = null;
            if (!empty($userinfo['id'])) {
                $verify_user_id = $userinfo['id'];
            } else if (!empty($_SESSION['temp_id'])) {
                $verify_user_id = $_SESSION['temp_id'];
            }
            
            if (empty($verify_user_id)) {
                call_alert(303); // Unable to verify email code
                $verification_results['email_code'] = false;
                $verification_results['overall'] = false;
            } else {
                // Check if valid code exists
                $sql = "SELECT * FROM `otp` WHERE 
                    user_id = '{$verify_user_id}' AND 
                    code = '{$_POST['email_code']}' AND 
                    action = '{$action}' AND
                    status = 1";
                    
                $c = mysqli_query($DB_CONN, $sql);
                
                if (mysqli_num_rows($c) == 0) {
                    call_alert(303); // Email code is not correct
                    $verification_results['email_code'] = false;
                    $verification_results['overall'] = false;
                } else {
                    // Check if code is expired
                    $row = mysqli_fetch_assoc($c);
                    $sql_expired = "SELECT * FROM `otp` WHERE 
                        id = '{$row['id']}' AND
                        `timestamp` >= NOW() - INTERVAL 10 MINUTE";
                        
                    $c_expired = mysqli_query($DB_CONN, $sql_expired);
                    
                    if (mysqli_num_rows($c_expired) == 0) {
                        call_alert(300); // Email code is expired
                        $verification_results['email_code'] = false;
                        $verification_results['overall'] = false;
                    } else {
                        // Valid email code
                        $verification_results['email_code'] = true;
                        update_verification_status('email_code', true);
                        
                        // Invalidate the used OTP
                        $invalidate_sql = "UPDATE `otp` SET status = 0 
                            WHERE id = '{$row['id']}'";
                        mysqli_query($DB_CONN, $invalidate_sql);
                    }
                }
            }
        } else {
            // Email code required but not provided
            $verification_results['email_code'] = false;
            $verification_results['overall'] = false;
            
            // Only show the error if no other verification was attempted
            if (!$verification_attempted) {
                call_alert(303); // Email code is not correct
            }
        }
    }
    
    // Update the data with verification status
    $data['verification_status'] = [
    'verified' => $verification_results['overall'],
    'methods' => [
        '2fa_code' => [
            'required' => ($settings['2fa_code'] ?? false) && isset($userinfo['2fa']) && $userinfo['2fa'] == 1,
            'verified' => $verification_results['2fa_code']
        ],
        'pin_code' => [
            'required' => $settings['pin_code'] ?? false,
            'verified' => $verification_results['pin_code']
        ],
        'email_code' => [
            'required' => $settings['email_code'] ?? false,
            'verified' => $verification_results['email_code']
        ]
    ]
];

// Find the next required verification method
$data['verification_status']['next_required'] = null;
foreach (['2fa_code', 'pin_code', 'email_code'] as $method) {
    if (
        isset($settings[$method]) && 
        $settings[$method] && 
        ($method != '2fa_code' || (isset($userinfo['2fa']) && $userinfo['2fa'] == 1)) &&
        (!isset($verification_results[$method]) || $verification_results[$method] !== true)
    ) {
        $data['verification_status']['next_required'] = $method;
        break;
    }
}
    
    // Reset verification status if all methods are verified
    if ($verification_results['overall']) {
        $_SESSION['verification_status'] = [];
    }
    
    return $verification_results['overall'];
}

function captcha_check($page = '') {
    global $captcha_settings, $ip;
    $cc = true;
    if($page) {
        if(!$captcha_settings['page'][$page])
            return $cc;
    }
    switch ($captcha_settings['captcha_id']) {
    case 1:
    if(isset($_POST['g-recaptcha-response']))
        $captcha=$_POST['g-recaptcha-response'];
    if(!$captcha){
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    $response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$captcha_settings['google']['gserverkey']}&response=".$captcha."&remoteip=".$ip), true);
    if($response['success'] == false)
    {
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    break;
    case 2:
    if(isset($_POST['h-recaptcha-response']))
        $captcha=$_POST['h-recaptcha-response'];
    if(!$captcha){
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    $data = array(
        'secret' => $captcha_settings['hcaptcha']['hsecretkey'],
        'ip' => $ip,
        'response' => $captcha
    );
    $verify = curl_init();
    curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
    curl_setopt($verify, CURLOPT_POST, true);
    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($verify);
    $response = json_decode($response, true);
    if($response['success'] == false)
    {
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    break;
    case 3:
    if(isset($_POST['cf-turnstile-response']))
        $captcha=$_POST['cf-turnstile-response'];
    if(!$captcha){
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    $url_path = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = array('secret' => $captcha_settings['cloudflare']['csecretkey'], 'response' => $captcha, 'remoteip' => $ip);
    $options = array(
        'http' => array(
        'method' => 'POST',
        'content' => http_build_query($data))
    );
    $stream = stream_context_create($options);
    $result = file_get_contents(
            $url_path, false, $stream);
    $response =  json_decode($result,true);

    if(intval($response["success"]) !== 1)
    {
        call_alert(25); //Please Check Captcha
        $cc = false;
    }
    break;
    case 4:
    if($_POST['captcha']) {
        if(strtoupper($_SESSION['captcha_string']) == strtoupper($_POST['captcha'])) {
            $cc = true;
        } else {
            $cc = false;
            call_alert(27); //Invalid Captcha
        }
    } else {
        $cc = false;
        call_alert(26); //Please fill Captcha
    }
    break;
    }
    return $cc;
}
function check_license() {
    return true;
}
function add_hidden_fields_in_forms($form, $hidden_fields) {
    $forms = preg_split('/(<form.*?<\/form>)/s', $form, -1, PREG_SPLIT_DELIM_CAPTURE);
    $updated_forms = '';
    foreach ($forms as $individual_form) {
        if (strpos($individual_form, '<form') !== false) {
            $updated_form = add_hidden_fields_in_form($individual_form, $hidden_fields);
            $updated_forms .= $updated_form;
        } else {
            $updated_forms .= $individual_form;
        }
    }
    return $updated_forms;
}
function add_hidden_fields_in_form($form, $hidden_fields) {
    $form_open_end_position = strpos($form, '>', strpos($form, '<form'));
    $form_start = substr($form, 0, $form_open_end_position + 1);
    $form_end_position = strpos($form, '</form>');
    $form_middle = substr($form, $form_open_end_position + 1, $form_end_position - $form_open_end_position - 1);
    $form_with_hidden_fields = '';
    $form_with_hidden_fields .= $hidden_fields;
    $form_with_hidden_fields = $form_start . $form_with_hidden_fields . $form_middle . '</form>';
    return $form_with_hidden_fields;
}
function check_telegram() {
    return true;
}
function check_mobileapp() {
    return true;
}
function check_graph() {
    return true;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
function getMailbox($folder = 'INBOX', $page = 1, $per_page = 20) {
    global $email_settings;
    
    try {
        $imap_string = "{" . $email_settings['imap_host'] . ":" . 
                      $email_settings['imap_port'] . "/imap/" . 
                      $email_settings['imap_secure'] . "/novalidate-cert}" . $folder;
        
        $decrypted_password = encrypt_decrypt('decrypt', $email_settings['imap_pass']);
        $inbox = @imap_open($imap_string, $email_settings['imap_user'], $decrypted_password);
        
        if (!$inbox) {
            throw new Exception("Failed to connect to mailbox: " . imap_last_error());
        }

        // Get total emails and calculate pagination
        $total_emails = imap_num_msg($inbox);
        $total_pages = ceil($total_emails / $per_page);
        $start = ($page - 1) * $per_page + 1;
        $end = min($start + $per_page - 1, $total_emails);

        $emails = [];
        // Fetch emails in reverse order (newest first)
        for ($i = $total_emails - $start + 1; $i >= $total_emails - $end + 1; $i--) {
            $header = imap_headerinfo($inbox, $i);
            if (!$header) continue;

            $structure = imap_fetchstructure($inbox, $i);
            $has_attachments = isset($structure->parts) && count($structure->parts);

            // Get message body
            $body = imap_fetchbody($inbox, $i, 1);
            if (isset($structure->encoding)) {
                switch($structure->encoding) {
                    case 3: $body = base64_decode($body); break;
                    case 4: $body = quoted_printable_decode($body); break;
                }
            }

            // Clean body text
            $body = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $body);
            $body = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $body);
            $body = strip_tags($body);
            $body = str_replace('&nbsp;', ' ', $body);
            $body = preg_replace('/\s+/', ' ', $body);
            $body = trim($body);

            // Get attachments info
            $attachments = [];
            if ($has_attachments) {
                foreach ($structure->parts as $part_num => $part) {
                    if (isset($part->disposition) && $part->disposition === "ATTACHMENT") {
                        $filename = isset($part->dparameters[0]->value) 
                            ? $part->dparameters[0]->value 
                            : "attachment-" . ($part_num + 1);
                        $attachments[] = [
                            'filename' => $filename,
                            'part_num' => $part_num + 1
                        ];
                    }
                }
            }

            $sender = $header->from[0]->mailbox . '@' . $header->from[0]->host;
            $sender_name = isset($header->from[0]->personal) ? 
                          imap_utf8($header->from[0]->personal) : 
                          $sender;

            $emails[] = [
                'id' => $i,
                'uid' => imap_uid($inbox, $i),
                'message_id' => trim($header->message_id),
                'subject' => isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)',
                'from' => $sender,
                'from_name' => $sender_name,
                'date' => date('Y-m-d H:i:s', strtotime($header->date)),
                'timestamp' => strtotime($header->date),
                'seen' => trim($header->Unseen) != 'U',
                'flagged' => trim($header->Flagged) == 'F',
                'answered' => trim($header->Answered) == 'A',
                'preview' => mb_substr($body, 0, 200) . '...',
                'full_body' => $body,
                'has_attachments' => $has_attachments,
                'attachments' => $attachments
            ];
        }

        // Get all available folders
        $folders = array_map(function($folder) {
            return [
                'name' => str_replace($email_settings['imap_host'], '', $folder),
                'full_name' => $folder
            ];
        }, imap_list($inbox, $imap_string, '*'));

        imap_close($inbox);

        return [
            'success' => true,
            'data' => [
                'emails' => $emails,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_pages' => $total_pages,
                    'total_emails' => $total_emails
                ],
                'folders' => $folders
            ]
        ];

    } catch (Exception $e) {
        if (isset($inbox) && is_resource($inbox)) {
            imap_close($inbox);
        }
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Function to get a single email with full content
function getEmail($email_id, $folder = 'INBOX') {
    global $email_settings;
    
    try {
        $imap_string = "{" . $email_settings['imap_host'] . ":" . 
                      $email_settings['imap_port'] . "/imap/" . 
                      $email_settings['imap_secure'] . "/novalidate-cert}" . $folder;
        
        $decrypted_password = encrypt_decrypt('decrypt', $email_settings['imap_pass']);
        $inbox = @imap_open($imap_string, $email_settings['imap_user'], $decrypted_password);
        
        if (!$inbox) {
            throw new Exception("Failed to connect to mailbox");
        }

        $header = imap_headerinfo($inbox, $email_id);
        if (!$header) {
            throw new Exception("Email not found");
        }

        $structure = imap_fetchstructure($inbox, $email_id);
        
        // Function to get part of the email body
        function getPart($inbox, $email_id, $part_number, $encoding) {
            $data = imap_fetchbody($inbox, $email_id, $part_number);
            switch($encoding) {
                case 3: return base64_decode($data);
                case 4: return quoted_printable_decode($data);
                default: return $data;
            }
        }

        // Get email parts
        $body = [
            'html' => '',
            'plain' => ''
        ];
        
        if (!isset($structure->parts)) {
            // Single part message
            $body['plain'] = getPart($inbox, $email_id, 1, $structure->encoding);
        } else {
            // Multi-part message
            foreach($structure->parts as $part_num => $part) {
                $part_number = $part_num + 1;
                
                if ($part->subtype == 'PLAIN') {
                    $body['plain'] .= getPart($inbox, $email_id, $part_number, $part->encoding);
                }
                elseif ($part->subtype == 'HTML') {
                    $body['html'] .= getPart($inbox, $email_id, $part_number, $part->encoding);
                }
            }
        }

        // Get attachments
        $attachments = [];
        if (isset($structure->parts)) {
            foreach ($structure->parts as $part_num => $part) {
                if (isset($part->disposition) && $part->disposition == "ATTACHMENT") {
                    $filename = isset($part->dparameters[0]->value) 
                        ? $part->dparameters[0]->value 
                        : "attachment-" . ($part_num + 1);
                        
                    $attachments[] = [
                        'filename' => $filename,
                        'part_num' => $part_num + 1,
                        'size' => $part->bytes,
                        'type' => $part->subtype
                    ];
                }
            }
        }

        $sender = $header->from[0]->mailbox . '@' . $header->from[0]->host;
        $sender_name = isset($header->from[0]->personal) ? 
                      imap_utf8($header->from[0]->personal) : 
                      $sender;

        $email = [
            'id' => $email_id,
            'uid' => imap_uid($inbox, $email_id),
            'message_id' => trim($header->message_id),
            'subject' => isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)',
            'from' => $sender,
            'from_name' => $sender_name,
            'to' => array_map(function($to) {
                return $to->mailbox . '@' . $to->host;
            }, $header->to),
            'cc' => isset($header->cc) ? array_map(function($cc) {
                return $cc->mailbox . '@' . $cc->host;
            }, $header->cc) : [],
            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'timestamp' => strtotime($header->date),
            'seen' => trim($header->Unseen) != 'U',
            'flagged' => trim($header->Flagged) == 'F',
            'answered' => trim($header->Answered) == 'A',
            'body' => [
                'html' => $body['html'],
                'plain' => $body['plain']
            ],
            'attachments' => $attachments
        ];

        imap_close($inbox);

        return [
            'success' => true,
            'data' => $email
        ];

    } catch (Exception $e) {
        if (isset($inbox) && is_resource($inbox)) {
            imap_close($inbox);
        }
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getMetronicMailbox($folder = 'INBOX', $page = 1, $per_page = 20) {
    $mailbox = getMailbox($folder, $page, $per_page);
    if (!$mailbox['success']) {
        return '<div class="alert alert-danger">Failed to load mailbox: ' . $mailbox['error'] . '</div>';
    }
    
    $html = '
    <!--begin::Content-->
    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
        <!--begin::Container-->
        <div class="container-xxl" id="kt_content_container">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <!--begin::Actions-->
                    <div class="d-flex flex-wrap gap-2">
                        <!--begin::Checkbox-->
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_inbox_listing .form-check-input" value="1" />
                        </div>
                        <!--end::Checkbox-->
                        
                        <!--begin::Reload-->
                        <a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-bs-toggle="tooltip" data-bs-dismiss="click" data-bs-trigger="hover" title="Reload">
                            <i class="ki-duotone ki-arrows-circle fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </a>
                        <!--end::Reload-->

                        <!--begin::Delete-->
                        <a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Delete">
                            <i class="ki-duotone ki-trash fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                        </a>
                        <!--end::Delete-->

                        <!--begin::Mark as read-->
                        <a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Mark as read">
                            <i class="ki-duotone ki-notification-status fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </a>
                        <!--end::Mark as read-->

                        <!--begin::Move-->
                        <a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Move">
                            <i class="ki-duotone ki-folder fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </a>
                        <!--end::Move-->
                    </div>
                    <!--end::Actions-->

                    <!--begin::Search-->
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                        <div class="position-relative w-md-400px me-md-2">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle ms-6">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" class="form-control form-control-solid ps-10" name="search" value="" placeholder="Search inbox" />
                        </div>
                    </div>
                    <!--end::Search-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body p-0">
                    <!--begin::Table-->
                    <table class="table table-hover table-row-dashed fs-6 gy-5 my-0" id="kt_inbox_listing">
                        <thead class="d-none"><tr><th></th></tr></thead>
                        <tbody>';

                        foreach ($mailbox['data']['emails'] as $email) {
                            $unread_class = $email['seen'] ? '' : 'fw-bold';
                            $html .= '
                            <tr>
                                <td class="ps-9">
                                    <!--begin::Checkbox-->
                                    <div class="form-check form-check-sm form-check-custom form-check-solid mt-3">
                                        <input class="form-check-input" type="checkbox" value="1" />
                                    </div>
                                    <!--end::Checkbox-->
                                </td>
                                <td class="min-w-35px">
                                    <!--begin::Star-->
                                    <a href="#" class="btn btn-icon btn-color-gray-400 btn-active-color-primary w-35px h-35px" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Star">
                                        <i class="ki-duotone ki-star fs-3 ' . ($email['flagged'] ? 'text-warning' : '') . '"></i>
                                    </a>
                                    <!--end::Star-->
                                </td>
                                <td>
                                    <a href="#" class="d-flex align-items-center text-dark">
                                        <!--begin::Avatar-->
                                        <div class="symbol symbol-35px me-3">
                                            <span class="symbol-label" style="background-color: #' . substr(md5($email['from']), 0, 6) . '">
                                                ' . strtoupper(substr($email['from_name'], 0, 1)) . '
                                            </span>
                                        </div>
                                        <!--end::Avatar-->

                                        <!--begin::Name-->
                                        <span class="' . $unread_class . '">' . htmlspecialchars($email['from_name']) . '</span>
                                        <!--end::Name-->
                                    </a>
                                </td>
                                <td>
                                    <div class="text-gray-800 text-hover-primary">
                                        <span class="' . $unread_class . '">' . htmlspecialchars($email['subject']) . '</span>
                                        <span class="text-muted">-</span>
                                        <span class="text-muted fw-normal">' . htmlspecialchars(mb_substr($email['preview'], 0, 100)) . '...</span>
                                    </div>
                                </td>
                                <td class="w-100px text-end fs-7 pe-9">
                                    <span class="fw-semibold">' . date('M d', $email['timestamp']) . '</span>
                                </td>
                            </tr>';
                        }

                        $html .= '
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->

            <!--begin::Pagination-->
            <div class="d-flex flex-center py-3">
                <ul class="pagination">';
                
                for ($i = 1; $i <= $mailbox['data']['pagination']['total_pages']; $i++) {
                    $active = $i == $mailbox['data']['pagination']['current_page'] ? 'active' : '';
                    $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                }
                
                $html .= '
                </ul>
            </div>
            <!--end::Pagination-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::Content-->';

    return $html;
}
function findAndProcessTransaction($DB_CONN, $message, $telegram_settings) {
    $cleanMessage = trim(preg_replace('/[^\w\s\-:\.0x]/u', ' ', $message));
    $txn_id = null;
    $txn_type = null;

    if (preg_match('/\b(0x[a-f0-9]{64}|[a-f0-9]{64})\b/i', $cleanMessage, $matches)) {
        $txn_id = $matches[1];
        $txn_type = 'blockchain';
    }
    else if (preg_match('/\b([1-9A-HJ-NP-Za-km-z]{87,88})\b/', $cleanMessage, $matches)) {
        $txn_id = $matches[1];
        $txn_type = 'blockchain';
    }
    else if (preg_match('/\b(\d{7,12})\b/', $cleanMessage, $matches)) {
        $txn_id = $matches[1];
        $length = strlen($txn_id);
        
        if ($length >= 10) {
            $txn_type = 'payment_system_long'; // Payeer, Perfect Money
        } else {
            $txn_type = 'payment_system_short'; // ePayCore
        }
    }
    
    if (!$txn_id) {
        return ['status' => 'not_found'];
    }
    
    mysqli_begin_transaction($DB_CONN);
    
    try {
        $stmt = mysqli_prepare($DB_CONN, 
            "SELECT id FROM transactions WHERE txn_type = 'bonus' AND ref_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $txn_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result === false) {
            return ['status' => 'query_failed'];
        }

        $rowCount = mysqli_num_rows($result);

        if ($rowCount > 0) {
            $stmt = mysqli_prepare($DB_CONN, 
                "SELECT t.txn_id, t.user_id, t.amount FROM transactions t 
                 WHERE t.txn_type = 'bonus' AND t.ref_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $txn_id);
            mysqli_stmt_execute($stmt);
            $bonus_txn = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            
            mysqli_rollback($DB_CONN);
            return [
                'status' => 'already_rewarded',
                'user_id' => $bonus_txn['user_id'],
                'amount' => $bonus_txn['amount'],
                'txn_id' => $bonus_txn['txn_id']
            ];
        }
        
        if ($txn_type === 'blockchain' || $txn_type === 'batch') {
            $baseQuery = "SELECT t.txn_id, t.user_id, t.payment_method_id, t.amount, t.created_at 
                         FROM transactions t 
                         WHERE t.status = '1' 
                         AND t.txn_type = 'withdraw'
                         AND t.txn_id = ?";
            $params = [$txn_id];
            $types = "s";
        } else {
            $baseQuery = "SELECT t.txn_id, t.user_id, t.payment_method_id, t.amount, t.created_at 
                         FROM transactions t 
                         WHERE t.status = '1' 
                         AND t.txn_type = 'withdraw'
                         AND t.txn_id IN (?, ?, ?)";
            $params = [
                $txn_id,
                (string)(intval($txn_id) + 1),
                (string)(intval($txn_id) - 1)
            ];
            $types = "sss";
        }
        
        if ($telegram_settings['withdrawal_check'] === 'today') {
            $baseQuery .= " AND DATE(t.created_at) = CURRENT_DATE";
        }
        
        $stmt = mysqli_prepare($DB_CONN, $baseQuery);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_rollback($DB_CONN);
            return ['status' => 'no_matching_withdrawal'];
        }
        
        $transaction = mysqli_fetch_assoc($result);
        
        if ($telegram_settings['min_withdrawal_amount'] > 0 && 
            $transaction['amount'] < $telegram_settings['min_withdrawal_amount']) {
            mysqli_rollback($DB_CONN);
            return ['status' => 'amount_too_low'];
        }
        
        if ($telegram_settings['posting_daily_limit'] > 0) {
            $stmt = mysqli_prepare($DB_CONN, 
                "SELECT COUNT(*) as count FROM transactions 
                 WHERE user_id = ? AND txn_type = 'bonus' 
                 AND DATE(created_at) = CURRENT_DATE");
            mysqli_stmt_bind_param($stmt, "s", $transaction['user_id']);
            mysqli_stmt_execute($stmt);
            $count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
            
            if ($count >= $telegram_settings['posting_daily_limit']) {
                mysqli_rollback($DB_CONN);
                return ['status' => 'daily_limit_reached'];
            }
        }
        
        $reward_amount = $telegram_settings['random_bonus'] == 'true'
            ? round(mt_rand(
                ($telegram_settings['random_bonus_min'] * 100),
                ($telegram_settings['random_bonus_max'] * 100)
              ) / 100, 2)
            : (float)$telegram_settings['posting_txn_amount'];
        
        $payment_method = $telegram_settings['use_different_currency'] == 'true'
            ? $telegram_settings['posting_currency']
            : $transaction['payment_method_id'];
            
        add_balance($transaction['user_id'], $payment_method, $reward_amount);
        
        $detail = str_replace(
            ['#amount#', '#txn#'],
            [fiat($reward_amount), $transaction['txn_id']],
            $telegram_settings['posting_txn_memo']
        );
        
        $stmt = mysqli_prepare($DB_CONN,
            "INSERT INTO transactions (detail, user_id, amount, txn_type, 
             payment_method_id, ref_id, status, created_at)
             VALUES (?, ?, ?, 'bonus', ?, ?, '1', NOW())");
        mysqli_stmt_bind_param($stmt, "ssdss", 
            $detail, 
            $transaction['user_id'], 
            $reward_amount,
            $payment_method,
            $transaction['txn_id']
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to record bonus transaction");
        }
        
        mysqli_commit($DB_CONN);
        
        return [
            'status' => 'success',
            'user_id' => $transaction['user_id'],
            'amount' => $reward_amount,
            'txn_id' => $transaction['txn_id']
        ];
        
    } catch (Exception $e) {
        mysqli_rollback($DB_CONN);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
function processEmails() {
    global $DB_CONN, $tgtoken, $email_settings;
    
    if (!isset($email_settings['imap']) || $email_settings['imap'] !== "true") {
        return;
    }

    try {
        // Create IMAP connection string once
        $imap_string = sprintf("{%s:%s/imap/%s/novalidate-cert}INBOX",
            $email_settings['imap_host'],
            $email_settings['imap_port'],
            $email_settings['imap_secure']
        );
        
        $inbox = @imap_open(
            $imap_string,
            $email_settings['imap_user'],
            encrypt_decrypt('decrypt', $email_settings['imap_pass']),
            OP_READONLY | OP_SILENT  // Added OP_SILENT to reduce error checking overhead
        );

        if (!$inbox) return;

        // Fetch all processed IDs in one query and store in memory
        $processed_ids = [];
        $stmt = $DB_CONN->prepare("SELECT email_message_id FROM telegram WHERE email_message_id IS NOT NULL AND email_message_id != ''");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $processed_ids[$row['email_message_id']] = true;
        }

        // Prepare the insert statement once
        $insert_stmt = $DB_CONN->prepare("INSERT IGNORE INTO telegram (email_message_id) VALUES (?)");
        
        // Get all unread emails at once
        $emails = imap_search($inbox, 'UNSEEN', SE_UID);  // Added SE_UID for better performance
        if (!$emails) {
            imap_close($inbox);
            return;
        }

        // Compile regex patterns once
        $cleanup_patterns = [
            '/\s+/' => ' ',
            '/<style\b[^>]*>(.*?)<\/style>/is' => '',
            '/<script\b[^>]*>(.*?)<\/script>/is' => '',
            '/<br\s*\/?>/i' => "\n",
            '/<\/p>/i' => "\n\n",
            '/<\/div>/i' => "\n",
            '/<\/tr>/i' => "\n",
            '/<\/td>/i' => " ",
            '/<li>/i' => "Ã¢â‚¬Â¢ ",
            '/<\/li>/i' => "\n",
            '/\n\s*\n\s*\n/' => "\n\n"
        ];

        // Process emails in batches
        $batch_size = 10;
        foreach (array_chunk($emails, $batch_size) as $email_batch) {
            foreach ($email_batch as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, FT_UID);
                if (empty($overview)) continue;
                $header = $overview[0];
                
                if (empty($header->message_id)) continue;
                $message_id = trim($header->message_id);
                if (isset($processed_ids[$message_id])) continue;

                // Parse sender information
                $sender_parts = explode('@', $header->from);
                $sender = $header->from;
                $sender_name = !empty($header->from_personal) ? 
                              imap_utf8($header->from_personal) : 
                              $sender;

                // Get message body
                $structure = imap_fetchstructure($inbox, $email_number, FT_UID);
                $message = imap_fetchbody($inbox, $email_number, 1, FT_UID);
                
                // Decode message based on encoding
                if (isset($structure->encoding)) {
                    switch($structure->encoding) {
                        case 3: $message = base64_decode($message); break;
                        case 4: $message = quoted_printable_decode($message); break;
                    }
                }

                // Apply all cleanup patterns at once
                $message = preg_replace(
                    array_keys($cleanup_patterns),
                    array_values($cleanup_patterns),
                    $message
                );
                
                $message = strip_tags($message);
                $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $message = str_replace('&nbsp;', ' ', $message);
                $message = trim($message);

                // Extract links
                $links = '';
                if (preg_match_all('/https?:\/\/[^\s<>"]+/', $message, $matches)) {
                    $links = "\n\n*Links:*\n" . implode("\n", array_unique($matches[0]));
                }

                $telegram_text = sprintf(
                    "📧 *New Email*\n\n*From:* %s\n*Subject:* %s\n*Date:* %s\n\n*Message:*\n%s%s",
                    escapeMarkdownV2($sender_name),
                    escapeMarkdownV2($header->subject ?? '(No Subject)'),
                    escapeMarkdownV2(date('Y-m-d H:i:s', strtotime($header->date))),
                    escapeMarkdownV2($message),
                    $links ? "\n" . escapeMarkdownV2($links) : ""
                );

                $telegram_msg_id = sendToTelegram(
                    $telegram_text,
                    $email_number,
                    $message_id,
                    $header->subject ?? '(No Subject)',
                    $sender
                );

                if ($telegram_msg_id) {
                    $processed_ids[$message_id] = true;
                    $insert_stmt->bind_param("s", $message_id);
                    $insert_stmt->execute();
                }
            }
        }

        imap_close($inbox);

    } catch (Exception $e) {
        if (isset($inbox) && is_resource($inbox)) {
            imap_close($inbox);
        }
    }
}
 function escapeMarkdown($text) {
        // Escape characters that have special meaning in Markdown
        $special_chars = ['_', '*', '`', '['];
        $escaped_text = $text;
        foreach ($special_chars as $char) {
            $escaped_text = str_replace($char, '\\' . $char, $escaped_text);
        }
        return $escaped_text;
    }
function escapeMarkdownV2($text) {
    $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    return str_replace($chars, array_map(function($char) { return "\\$char"; }, $chars), $text);
}
function escapeMarkdownV2char($text) {
    // First, preserve intentional strikethrough formatting (paired tildes)
    $text = preg_replace_callback('/~~(.*?)~~/', function($matches) {
        return 'STRIKETHROUGH_' . base64_encode($matches[1]) . '_MARKER';
    }, $text);
    
    // Preserve links BEFORE doing any other replacements
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function($matches) {
        return 'LINKSTART_' . base64_encode($matches[1]) . '_LINKMIDDLE_' . base64_encode($matches[2]) . '_LINKEND';
    }, $text);
    
    // Mark all remaining single tildes for escaping
    $text = str_replace('~', 'SINGLETILDE_MARKER', $text);
    
    // Basic formatting markers
    $text = str_replace('*', 'ASTERISKMARKER', $text);
    $text = str_replace('_', 'UNDERSCOREMARKER', $text);
    $text = str_replace('`', 'BACKTICKMARKER', $text);
    
    // Additional formatting markers
    $text = str_replace('__', 'DOUBLEUNDERSCOREMARKER', $text);
    $text = str_replace('||', 'SPOILERMARKER', $text); 
    $text = str_replace('```', 'CODEBLOCKMARKER', $text);
    
    // Escape special characters
    $text = escapeMarkdownV2($text);
    
    // Restore basic formatting markers
    $text = str_replace('ASTERISKMARKER', '*', $text);
    $text = str_replace('UNDERSCOREMARKER', '_', $text);
    $text = str_replace('SINGLETILDE_MARKER', '\\~', $text);
    $text = str_replace('BACKTICKMARKER', '`', $text);
    
    // Restore additional formatting markers
    $text = str_replace('DOUBLEUNDERSCOREMARKER', '__', $text);
    $text = str_replace('SPOILERMARKER', '||', $text); 
    $text = str_replace('CODEBLOCKMARKER', '```', $text);
    
    // Restore strikethrough formatting
    $text = preg_replace_callback('/STRIKETHROUGH_(.*?)_MARKER/', function($matches) {
        return '~~' . base64_decode($matches[1]) . '~~';
    }, $text);
    
    // Restore links - careful not to escape characters in the URL
    $text = preg_replace_callback('/LINKSTART_(.*?)_LINKMIDDLE_(.*?)_LINKEND/', function($matches) {
        $linkText = base64_decode($matches[1]);
        $url = base64_decode($matches[2]);
        
        // Only escape special characters in the link text
        $charactersToEscape = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($charactersToEscape as $char) {
            $linkText = str_replace($char, "\\$char", $linkText);
        }
        
        // Don't escape characters in the URL that are part of the URL syntax
        return '[' . $linkText . '](' . $url . ')';
    }, $text);
    
    return $text;
}

function sendToTelegram($message, $email_number, $messageId, $subject, $sender) {
    global $DB_CONN, $tgtoken, $telegram_settings;
    
    // Create very simple callback data using just numbers and letters
    $safe_message_id = substr(md5($messageId), 0, 10); 
    $callback_data = "read_{$email_number}_{$safe_message_id}";
    $read_delete_callback = "readdelete_{$email_number}_{$safe_message_id}";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📫 Mark as Read', 'callback_data' => $callback_data],
                ['text' => '📫🗑 Read & Delete', 'callback_data' => $read_delete_callback],
                ['text' => '🗑 Dismiss', 'callback_data' => 'dismiss_message']
            ]
        ]
    ];
    
    $data = [
        'chat_id' => $telegram_settings['personal_id'],
        'text' => $message,
        'parse_mode' => 'MarkdownV2',
        'reply_markup' => json_encode($keyboard)
    ];
    
    $ch = curl_init("https://api.telegram.org/bot{$tgtoken}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']
    ]);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    $telegramMessageId = $responseData['result']['message_id'] ?? null;
    
    if ($telegramMessageId) {
        try {
            $stmt = $DB_CONN->prepare("INSERT INTO telegram (
                message_id, chat_id, text, message, type, date,
                email_message_id, email_number, email_subject, email_sender,
                username, first_name, last_name
            ) VALUES (?, ?, ?, ?, 'email', UNIX_TIMESTAMP(), ?, ?, ?, ?, '', '', '')");
            
            $stmt->bind_param("iissssss",
                $telegramMessageId,
                $telegram_settings['personal_id'],
                $message,
                $message,
                $messageId,
                $email_number,
                $subject,
                $sender
            );
            
            $stmt->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    return $telegramMessageId;
}

function markEmailAsRead($emailId, $messageId) {
    global $DB_CONN, $email_settings;
    
    try {
        if (!isset($email_settings['imap']) || $email_settings['imap'] !== "true") {
            return false;
        }

        $imap_string = "{" . $email_settings['imap_host'] . ":" . 
                      $email_settings['imap_port'] . "/imap/" . 
                      $email_settings['imap_secure'] . "/novalidate-cert}INBOX";
        
        $decrypted_password = encrypt_decrypt('decrypt', $email_settings['imap_pass']);
        $inbox = @imap_open($imap_string, $email_settings['imap_user'], $decrypted_password);
        
        if (!$inbox) {
            return false;
        }

        $flag_result = imap_setflag_full($inbox, $emailId, "\\Seen");
        if (!$flag_result) {
            imap_close($inbox);
            return false;
        }

        imap_close($inbox);

        $stmt = $DB_CONN->prepare("UPDATE telegram SET is_read = 1 WHERE email_message_id = ?");
        $stmt->bind_param("s", $messageId);
        $result = $stmt->execute();
        
        if (!$result) {
            return false;
        }

        return true;

    } catch (Exception $e) {
        if (isset($inbox) && is_resource($inbox)) {
            imap_close($inbox);
        }
        return false;
    }
}
function displayUserDetails($user_id, $DB_CONN) {
    // Get comprehensive user details
    $query = "SELECT u1.*, u2.username as sponsor_username,
              (SELECT COALESCE(sum(amount), 0) FROM package_deposits 
               WHERE user_id = u1.id AND status = 1) as active_investments,
              (SELECT COALESCE(sum(balance), 0) FROM user_balances 
               WHERE user_id = u1.id) as total_balance
              FROM users u1 
              LEFT JOIN users u2 ON u1.sponsor = u2.id 
              WHERE u1.id = '$user_id'";
              
    $result = mysqli_query($DB_CONN, $query);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Build the core user profile section
        $message_text = "👤 *User Details*\n" .
                       "Username: @{$user['username']}\n" .
                       "Email: {$user['email']}\n" .
                       "Full Name: {$user['fullname']}\n" .
                       "Joined: " . date('Y-m-d H:i', strtotime($user['created_at'])) . "\n" .
                       "Sponsor: " . ($user['sponsor_username'] ? "@{$user['sponsor_username']}" : "No Sponsor") . "\n" .
                       "Account Balance: $" . number_format($user['total_balance'], 2) . "\n" .
                       "Active Investments: $" . number_format($user['active_investments'], 2) . "\n\n";
        
        $message_text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        
        // Wallet balances and addresses section
        $wallets = json_decode($user['wallets'], true);
        $message_text .= "💰 *Wallet Balances & Addresses*\n";
        
        $p = mysqli_query($DB_CONN, "SELECT * FROM `currencies` WHERE de_pm_id or wi_pm_id group by name order by id");
        while($pm = mysqli_fetch_assoc($p)) {
            $bal = mysqli_fetch_assoc(mysqli_query($DB_CONN, 
                "SELECT * FROM `user_balances` 
                 WHERE payment_method_id ='{$pm['id']}' and user_id = '{$user['id']}'"));
            
            $str = strtolower(str_replace(' ','',$pm['name']));
            if($bal || isset($wallets[$str])) {
               
                $message_text .= "💳 *{$pm['name']}*\n";
                if($bal) {
                    $message_text .= "Balance: " . number_format($bal['balance'], 8) . " {$pm['symbol']}\n";
                }
                if(isset($wallets[$str])) {
                    $message_text .= "Address: `{$wallets[$str]}`\n";
                }
                $message_text .= "\n";
            }
        }
        
        $message_text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        
        // Active investments section
        $invest_query = mysqli_query($DB_CONN, 
            "SELECT pd.*, pkg.name as package_name, pkg.duration, 
             pkg.diff_in_seconds, pl.percent_max, c.name as currency_name,
             (SELECT MAX(created_at) FROM transactions 
              WHERE ref_id = pd.id AND txn_type = 'earning') as last_earningDateTime
             FROM package_deposits pd 
             JOIN packages pkg ON pd.package_id = pkg.id
             JOIN package_plans pl ON pd.plan_id = pl.id
             JOIN currencies c ON pd.payment_method_id = c.id
             WHERE pd.user_id = '{$user['id']}' AND pd.status = 1
             ORDER BY pd.datetime DESC LIMIT 5");
        
        $message_text .= "💎 *Active Investments*\n";
        if (mysqli_num_rows($invest_query) > 0) {
            while ($inv = mysqli_fetch_assoc($invest_query)) {
                $profit_per_cycle = ($inv['percent_max'] / 100) * $inv['amount'];
                $startTime = $inv['last_earningDateTime'] 
                    ? strtotime($inv['last_earningDateTime']) 
                    : strtotime($inv['datetime']);
                $next = $startTime + intval($inv['diff_in_seconds']);
                $expiry_date = strtotime($inv['datetime']) + ($inv['duration'] * $inv['diff_in_seconds']);
                
                $message_text .= "🔸 *#{$inv['id']}* - {$inv['package_name']}\n" .
                               "Amount: $" . number_format($inv['amount'], 2) . " {$inv['currency_name']}\n" .
                               "Daily Profit: $" . number_format($profit_per_cycle, 2) . "\n" .
                               "Next Profit: " . date('Y-m-d H:i', $next) . "\n" .
                               "Expires: " . date('Y-m-d H:i', $expiry_date) . "\n\n";
            }
        } else {
            $message_text .= "No active investments found.\n\n";
        }
        
        $message_text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        
        // Recent transactions section
        $trans_query = mysqli_query($DB_CONN,
            "SELECT t.*, c.name as currency_name, c.symbol 
             FROM transactions t
             LEFT JOIN currencies c ON t.payment_method_id = c.id
             WHERE t.user_id = '{$user['id']}'
             ORDER BY t.created_at DESC LIMIT 5");
        
        $message_text .= "📊 *Recent Transactions*\n";
        if (mysqli_num_rows($trans_query) > 0) {
            while ($trans = mysqli_fetch_assoc($trans_query)) {
                $message_text .= "🔹 *#{$trans['id']}* - {$trans['txn_type']}\n" .
                               "Amount: " . number_format($trans['amount'], 8) . " {$trans['symbol']}\n" .
                               "Status: " . ($trans['status'] ? '✅' : '⏳') . "\n";
                if (!empty($trans['detail'])) {
                    $message_text .= "Detail: {$trans['detail']}\n";
                }
                if (!empty($trans['txn_id']) && !empty($trans['tx_url'])) {
                    $message_text .= "{$trans['tx_url']}\n";
                } else if (!empty($trans['txn_id'])) {
                    $message_text .= "Transaction: {$trans['txn_id']}\n";
                }
                $message_text .= "Date: " . date('Y-m-d H:i', strtotime($trans['created_at'])) . "\n\n";
            }
        } else {
            $message_text .= "No recent transactions found.\n\n";
        }
        
        $message_text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        
        // Recent logins section
        $message_text .= "🔐 *Recent Logins*\n";
        
        $login_query = mysqli_query($DB_CONN, 
            "SELECT * FROM login_report 
             WHERE user_id = '{$user['id']}' 
             ORDER BY id DESC LIMIT 3");
        
        if (mysqli_num_rows($login_query) > 0) {     
            while ($login = mysqli_fetch_assoc($login_query)) {
                $location = $login['country'] . '/' . $login['city'];
                $time_ago = time_elapsed_string($login['datetime'], true);
                
                $message_text .= "🌐 *Login Details*\n" .
                               "IP: `{$login['ip']}`\n" .
                               "Location: {$location}\n" .
                               "Browser: {$login['browser']}\n" .
                               "OS: {$login['os']}\n" .
                               "Time: {$time_ago}\n" .
                               "User Agent: `{$login['useragent']}`\n\n";
            }
        } else {
            $message_text .= "No login history found.\n\n";
        }
        
        return [
            'success' => true,
            'message' => $message_text,
            'user' => $user
        ];
    }
    
    return [
        'success' => false,
        'message' => "❌ *User Not Found*\n\nThe requested user account does not exist or has been deleted."
    ];
}
function getAdminStats($DB_CONN, $preferences) {
    $pending_query = "SELECT 
        (SELECT COUNT(id) FROM transactions WHERE txn_type = 'withdraw' AND status = 0) as pending_withdrawals,
        (SELECT COUNT(id) FROM package_deposits WHERE txn_id = '' AND status = 0) as pending_investments,
        (SELECT COUNT(*) FROM package_deposits p 
         JOIN packages pkg ON p.package_id = pkg.id 
         WHERE p.status = 1 AND p.avail > 0 
         AND DATE(DATE_ADD(p.datetime, INTERVAL (pkg.duration * pkg.diff_in_seconds) SECOND)) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ) as expiring_deposits,
        (SELECT COUNT(id) FROM tickets WHERE status = 0) as new_tickets,
        (SELECT COUNT(id) FROM tickets WHERE status = 1) as pending_tickets,
        (SELECT COUNT(id) FROM tickets) as total_tickets";
        
    $pending_result = mysqli_fetch_assoc(mysqli_query($DB_CONN, $pending_query));
    
    return array_merge($pending_result, [
        'total_users' => $preferences['total_users'],
        'active_users' => $preferences['active_users'],
        'today_users' => $preferences['today_users'],
        'newest_member' => $preferences['newest_member'],
        'total_deposit' => $preferences['total_deposit'],
        'today_deposit' => $preferences['today_deposit'],
        'total_withdraw' => $preferences['total_withdraw'],
        'today_withdraw' => $preferences['today_withdraw'],
        'support' => $preferences['support']
    ]);
}
function formatAdminMessage($stats) {
    return "✨ *Admin Dashboard*\n\n" .
           "📊 *Statistics Overview*\n" .
           "├ Total Users: " . number_format($stats['total_users']) . "\n" .
           "├ Active Users: " . number_format($stats['active_users']) . "\n" .
           "├ New Users Today: " . number_format($stats['today_users']) . "\n" .
           "├ Total Deposits: " . fiat($stats['total_deposit']) . "\n" .
           "├ Today's Deposits: " . fiat($stats['today_deposit']) . "\n" .
           "├ Total Withdrawals: " . fiat($stats['total_withdraw']) . "\n" .
           "└ Today's Withdrawals: " . fiat($stats['today_withdraw']) . "\n\n" .
           "⚡ *Pending Actions*\n" .
           "├ Withdrawals: " . $stats['pending_withdrawals'] . "\n" .
           "├ Investments: " . $stats['pending_investments'] . "\n" .
           "├ Support Tickets: " . ($stats['new_tickets'] + $stats['pending_tickets']) . "\n" .
           "└ Expiring Deposits: " . $stats['expiring_deposits'] . "\n\n" .
           "Select an option below:";
}
function getAdminKeyboard($stats) {
    return [
        'inline_keyboard' => [
            [
                ['text' => "✨ Processor Balances", 'callback_data' => 'get_balances']
            ],
            [
                ['text' => "📨 Pending Withdrawals (" . $stats['pending_withdrawals'] . ")", 'callback_data' => 'pending_withdrawals'],
                ['text' => "🌟 Withdrawals: " . fiat($stats['total_withdraw']), 'callback_data' => 'admin_withdrawals']
            ],
            [
                ['text' => "💫 Pending Investments (" . $stats['pending_investments'] . ")", 'callback_data' => 'admin_investments'],
                ['text' => "✨Investments: " . fiat($stats['total_deposit']), 'callback_data' => 'admin_deposits']
            ],
            [
                ['text' => "⭐ Expiring Deposits (" . $stats['expiring_deposits'] . ")", 'callback_data' => 'expiring_deposits']
            ],
            [
                ['text' => "🎫 Support Tickets (" . ($stats['new_tickets'] + $stats['pending_tickets']) . "/" . $stats['total_tickets'] . ")", 'callback_data' => 'admin_support']
            ],
            [
                ['text' => "👥 Users | Active: " . number_format($stats['active_users']) . " | Total: " . number_format($stats['total_users']), 'callback_data' => 'admin_users']
            ],
            [
                ['text' => "📊 Performance", 'callback_data' => 'admin_performance'],
                ['text' => "💎 Currencies", 'callback_data' => 'admin_currency_performance']
            ],
            [
                ['text' => "📜 Transactions", 'callback_data' => 'admin_transactions']
            ],
            [
                ['text' => "♢ Refresh", 'callback_data' => 'refresh_data'],
                ['text' => "✕ Close", 'callback_data' => 'dismiss_message']
            ]
        ]
    ];
}

function getUserState($user_id) {
    global $DB_CONN;
    $result = mysqli_query($DB_CONN, "SELECT current_state, state_data FROM telegram_users WHERE user_id = '$user_id'");
    if ($row = mysqli_fetch_assoc($result)) {
        return [
            'state' => $row['current_state'],
            'data' => json_decode($row['state_data'], true) ?: []
        ];
    }
    return ['state' => null, 'data' => []];
}

// Function to update user state
function updateUserState($user_id, $state, $data = []) {
    global $DB_CONN;
    $state = mysqli_real_escape_string($DB_CONN, $state);
    $json_data = json_encode($data);
    $json_data = mysqli_real_escape_string($DB_CONN, $json_data);
    
    mysqli_query($DB_CONN, "UPDATE telegram_users SET 
        current_state = '$state', 
        state_data = '$json_data',
        last_seen = NOW()
        WHERE user_id = '$user_id'");
}

function getLangId($callback_user_id) {
    global $DB_CONN; // Assuming you have a database connection established
    
    $stmt = $DB_CONN->prepare("SELECT lang_id FROM telegram_users WHERE user_id = ?");
    $stmt->bind_param("s", $callback_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['lang_id'];
    }
    
    return null; // Return null if no user found
}
function getUserId($callback_chat_id) {
    global $DB_CONN; // Assuming you have a database connection established
    
    $stmt = $DB_CONN->prepare("SELECT id FROM users WHERE oauth_uid = ?");
    $stmt->bind_param("s", $callback_chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return null; // Return null if no user found
}
// Helper function to get flag emoji from language code
function getLanguageFlag($code) {
    $flags = [
        'en' => '🇬🇧',
        'es' => '🇪🇸',
        'fr' => '🇫🇷',
        'de' => '🇩🇪',
        'it' => '🇮🇹',
        'ru' => '🇷🇺',
        'zh' => '🇨🇳',
        'ja' => '🇯🇵',
        'ar' => '🇸🇦',
        'hi' => '🇮🇳',
        'pt' => '🇵🇹',
        'id' => '🇮🇩',
        'nl' => '🇳🇱',
        'tr' => '🇹🇷',
        'ko' => '🇰🇷',
        'vi' => '🇻🇳',
        'pl' => '🇵🇱',
        'th' => '🇹🇭'
    ];
    
    return $flags[strtolower($code)] ?? '🌐';
}
function getUserStats($userinfo) {
    return [
        'username' => $userinfo['username'],
        'fullname' => $userinfo['fullname'],
        'account_balance' => $userinfo['accountbalance'],
        'faucet_balance' => $userinfo['faucetbalance'],
        'total_earnings' => $userinfo['earnings'],
        'today_earnings' => $userinfo['earnings_today'],
        'active_investments' => $userinfo['investments_active'],
        'total_investments' => $userinfo['investments_total'],
        'total_withdrawals' => $userinfo['withdrawals_total'],
        'pending_withdrawals' => $userinfo['withdrawals_pending'],
        'referral_earnings' => $userinfo['affiliates_total'],
        'total_affiliates' => $userinfo['affiliates'],
        'affiliate_investments' => $userinfo['affiliates_investment'],
        'bonus_points' => $userinfo['taps'],
        'level' => $userinfo['level'],
        'tier' => $userinfo['tier']
    ];
}

function formatUserMessage($stats, $page_content) {
    // Format values without the asterisks
    $formatted_values = [
        '#username#' => escapeMarkdownV2char($stats['username']),
        '#fullname#' => escapeMarkdownV2char($stats['fullname']),
        '#balance#' => escapeMarkdownV2char(fiat($stats['account_balance'])),
        '#faucet_balance#' => escapeMarkdownV2char(fiat($stats['faucet_balance'])),
        '#earnings#' => escapeMarkdownV2char(fiat($stats['total_earnings'])),
        '#today_earnings#' => escapeMarkdownV2char(fiat($stats['today_earnings'])),
        '#active_investments#' => escapeMarkdownV2char(fiat($stats['active_investments'])),
        '#total_investments#' => escapeMarkdownV2char(fiat($stats['total_investments'])),
        '#total_withdrawals#' => escapeMarkdownV2char(fiat($stats['total_withdrawals'])),
        '#pending_withdrawals#' => escapeMarkdownV2char(fiat($stats['pending_withdrawals'])),
        '#referral_earnings#' => escapeMarkdownV2char(fiat($stats['referral_earnings'])),
        '#total_affiliates#' => escapeMarkdownV2char(number_format($stats['total_affiliates'])),
        '#affiliate_investments#' => escapeMarkdownV2char(fiat($stats['affiliate_investments'])),
        '#bonus_points#' => escapeMarkdownV2char(number_format($stats['bonus_points'])),
        '#level#' => escapeMarkdownV2char($stats['level']),
        '#tier#' => escapeMarkdownV2char($stats['tier'])
    ];
    
    // Get the message template
    $message_template = $page_content['details'];
    
    // Split the template by placeholders to preserve them
    $placeholders = array_keys($formatted_values);
    $template_parts = preg_split('/(#[a-z_]+#)/', $message_template, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    // Escape each part unless it's a placeholder
    $escaped_template = '';
    foreach ($template_parts as $part) {
        if (in_array($part, $placeholders)) {
            $escaped_template .= $part; // Keep placeholders as is
        } else {
            $escaped_template .= escapeMarkdownV2char($part); // Escape regular text
        }
    }
    
    // Replace placeholders with formatted values
    $message = str_replace(
        array_keys($formatted_values),
        array_values($formatted_values),
        $escaped_template
    );
    
    return $message;
}
// Function to get a sequential display number for an investment
function getInvestmentDisplayNumber($user_id, $investment_id) {
    global $DB_CONN;
    
    // Get all active investments for this user ordered by datetime
    $query = "SELECT id FROM package_deposits 
              WHERE user_id = ? AND (status = 1 OR avail > 0) 
              ORDER BY datetime DESC";
    
    $stmt = mysqli_prepare($DB_CONN, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Find position of the current investment
    $position = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['id'] == $investment_id) {
            return $position;
        }
        $position++;
    }
    
    // Fallback if investment isn't found
    return "INV";
}

function getPageContent($page_key, $lang_id = 1) {
    global $DB_CONN;
    
    // Query to get content for the specific page and language, now including default_templates
    $query = "SELECT content_data, image_url, allow_loading, default_templates FROM telegram_bot WHERE page = ? AND lang_id = ?";
    
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param("si", $page_key, $lang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Return decoded JSON content plus additional fields
        $content = json_decode($row['content_data'], true) ?: [];
        $content['allow_loading'] = $row['allow_loading'];
        $content['image_url'] = $row['image_url'];
        
        // Add default templates as a separate property
        $content['_default_templates'] = !empty($row['default_templates']) ? 
            json_decode($row['default_templates'], true) : [];
        
        return $content;
    }
    
    // If requested language not found, try fallback to default language (assuming 1 is English)
    if ($lang_id != 1) {
        return getPageContent($page_key, 1);
    }
    
    // Return empty array if no content found
    return [];
}
function getContentField($page_content, $field_name, $add_newlines = true, $escape = true) {
    // Check if the field explicitly exists in custom content, even if empty
    if (array_key_exists($field_name, $page_content)) {
        // If it exists but is empty, return empty string to indicate it was explicitly disabled
        if (empty($page_content[$field_name])) {
            return '';
        }
        $value = $page_content[$field_name];
    }
    // Then check default templates if not explicitly set in custom content
    else if (isset($page_content['_default_templates'][$field_name]) && 
             !empty($page_content['_default_templates'][$field_name])) {
        $value = $page_content['_default_templates'][$field_name];
    }
    // If neither is available, return empty string
    else {
        return '';
    }
    
    // Apply Markdown escaping if requested
    if ($escape) {
        $value = escapeMarkdownV2char($value);
    }
    
    // Add newlines if requested
    if ($add_newlines) {
        $value .= "\n\n";
    }
    
    return $value;
}
function getTemplateWithReplacements($page_content, $field_name, $replacements, $escape = true) {
    // Get template from content or default
    if (!empty($page_content[$field_name])) {
        $template = $page_content[$field_name];
    } else if (!empty($page_content['_default_templates'][$field_name])) {
        $template = $page_content['_default_templates'][$field_name];
    } else {
        return '';
    }
    
    // Use the existing renderTemplate function
    return renderTemplate($template, $replacements, null, $escape);
}
function renderTemplate($template, $replacements, $defaultTemplate = null, $escapeMarkdown = true) {
    // If template is empty but we have a default template, use it
    if (empty($template) && !empty($defaultTemplate)) {
        $template = $defaultTemplate;
    }
    
    // Replace all placeholders with their values
    $search = [];
    $replace = [];
    
    foreach ($replacements as $key => $value) {
        $search[] = "#{$key}#";
        $search[] = "{{$key}}";
        $replace[] = $value;
        $replace[] = $value;
    }
    
    $result = str_replace($search, $replace, $template);
    return $escapeMarkdown ? escapeMarkdownV2char($result) : $result;
}
function sendTelegramMessageWithImage($chat_id, $message_id, $text, $reply_markup, $page_content) {
    // If we have an image URL
    if (!empty($page_content['image_url'])) {
        // If we have a message_id, try to delete the previous message first
        if ($message_id) {
            sendTelegramRequest('deleteMessage', [
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);
        }
        // Send new message with photo
        return sendTelegramRequest('sendPhoto', [
            'chat_id' => $chat_id,
            'photo' => $page_content['image_url'],
            'caption' => $text,
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $reply_markup
        ]);
    } else {
        // No image to send
        // If message_id is 0 or empty, we should send a new message instead of editing
        if (!$message_id) {
            return sendTelegramRequest('sendMessage', [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'MarkdownV2',
                'disable_web_page_preview' => true, // Add this line
                'reply_markup' => $reply_markup
            ]);
        }
        // Otherwise, try to update the existing message
        $response = sendTelegramRequest('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => true, // Add this line
            'reply_markup' => $reply_markup
        ]); 
        // Rest of the function remains the same...
        $response_data = json_decode($response, true);
        if (!$response_data['ok'] && isset($response_data['description'])) {
            // Check for any of the three error conditions
            if (strpos($response_data['description'], 'no text in the message') !== false ||
                strpos($response_data['description'], 'message to edit not found') !== false ||
                strpos($response_data['description'], 'message is not modified') !== false) {
                
                // Delete the original message
                sendTelegramRequest('deleteMessage', [
                    'chat_id' => $chat_id,
                    'message_id' => $message_id
                ]);
                
                // Send a new message with the content
                return sendTelegramRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => $text,
                    'parse_mode' => 'MarkdownV2',
                    'disable_web_page_preview' => true, // Add this line
                    'reply_markup' => $reply_markup
                ]);
            }
        }
        return $response;
    }
}

function getPageNavigationButtons($page, $lang_id = 1) {
    global $DB_CONN;
    
    // Get this page information including previous_page and content_data
    $query = "SELECT buttons, previous_page, content_data FROM telegram_bot WHERE page = ? AND lang_id = ?";
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param("si", $page, $lang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $page_data = $result->fetch_assoc();
    
    // Decode the current page's content_data
    $current_page_content = json_decode($page_data['content_data'] ?? '{}', true);
    
    $buttons_array = [];
    
    if ($page_data && !empty($page_data['buttons'])) {
        // Process each button
        $button_pages = explode(',', $page_data['buttons']);
        
        foreach ($button_pages as $button_page) {
            $button_page = trim($button_page);
            
            // Get button content
            $btn_stmt = $DB_CONN->prepare("SELECT content_data FROM telegram_bot WHERE page = ? AND lang_id = ?");
            $btn_stmt->bind_param("si", $button_page, $lang_id);
            $btn_stmt->execute();
            $btn_result = $btn_stmt->get_result();
            
            if ($btn_row = $btn_result->fetch_assoc()) {
                $content = json_decode($btn_row['content_data'], true);
                
                // For back button, use previous_page if available
                if ($button_page == 'back' && !empty($page_data['previous_page'])) {
                    // Use back_text from current page if available, otherwise use back button's text
                    $button = [
                        'text' => !empty($current_page_content['back_text']) 
                               ? $content['icon'] . ' '. $current_page_content['back_text'] 
                               : ($content['icon'] . ' ' . $content['menu']),
                        'callback_data' => $page_data['previous_page']
                    ];
                } 
                // NEW CODE: Handle external URLs
                elseif (isset($content['is_external']) && 
                       ($content['is_external'] === true || $content['is_external'] === 1 || $content['is_external'] === "1")) {
                    $button = [
                        'text' => $content['icon'] . ' ' . $content['menu'],
                        'url' => $content['external_url']  // Telegram uses 'url' for external links
                    ];
                }
                else {
                    // Normal button or fallback behavior
                    $callback = ($button_page == 'back') ? 'back' :
                               (($button_page == 'refresh') ? 'refresh' : 
                               (($button_page == 'dismiss') ? 'dismiss_message' : $button_page));
                    
                    $button = [
                        'text' => $content['icon'] . ' ' . $content['menu'],
                        'callback_data' => $callback
                    ];
                }
                
                $buttons_array[] = $button;
            }
        }
    }
    
    return $buttons_array;
}

function addPageNavigationButtons($keyboard, $page, $lang_id = 1) {
global $callback_user_id, $u_id;
    // Get all buttons for this page
    $nav_buttons = getPageNavigationButtons($page, $lang_id);
        if ($page == 'invest_amount') {
        $user_id = $callback_user_id ?? $u_id; 
        $user_state = getUserState($user_id);
        
        if (!empty($user_state['data']) && 
            isset($user_state['data']['package_id']) && 
            isset($user_state['data']['plan_id'])) {
            
            // Find the back button in nav_buttons
            foreach ($nav_buttons as $key => $button) {
                if (strpos($button['callback_data'], 'invest_payment') === 0) {
                    // Replace with complete callback including parameters
                    $nav_buttons[$key]['callback_data'] = 
                        "invest_payment_{$user_state['data']['package_id']}_{$user_state['data']['plan_id']}";
                    break;
                }
            }
        }
    }
    
    // If we have navigation buttons, add them to the keyboard
    if (!empty($nav_buttons)) {
        // Put each button on its own line for cleaner appearance
        foreach ($nav_buttons as $button) {
            $keyboard['inline_keyboard'][] = [$button];
        }
    }
    
    return $keyboard;
}


function getUserKeyboard($lang_id = 1) {
    global $DB_CONN, $telegram_settings;
    
    // Get default column count from settings
    $defaultColumns = isset($telegram_settings['user_key_col']) ? (int)$telegram_settings['user_key_col'] : 2;
    if ($defaultColumns < 1) $defaultColumns = 2;
    
    $keyboard = [];
    $currentRow = [];
    $sameLineCount = 0;
    
    // Get menu items from database, ordered by menu_order
    $query = "SELECT page, content_data, menu_line, menu_order, image_url 
              FROM telegram_bot 
              WHERE lang_id = ? AND JSON_EXTRACT(content_data, '$.menu') != '' 
              AND show_in_menu = '1'
              ORDER BY menu_order ASC";
    
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param("i", $lang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($page = $result->fetch_assoc()) {
        $content = json_decode($page['content_data'], true);
        
        if (!isset($content['menu']) || empty($content['menu'])) {
            continue; // Skip items without menu text
        }
        
        $icon = isset($content['icon']) ? $content['icon'] . ' ' : '';
        $buttonText = $icon . $content['menu'];
        
        $nextButton = [
            'text' => $buttonText,
            'callback_data' => $page['page']
        ];
        
        if ($page['menu_line'] === 'separate') {
            // Add any pending items in current row
            if (!empty($currentRow)) {
                $keyboard[] = $currentRow;
                $currentRow = [];
                $sameLineCount = 0;
            }
            // Add this item as a separate row
            $keyboard[] = [$nextButton];
        } else {
            // 'same' menu_line - add to current row with column limit
            $currentRow[] = $nextButton;
            $sameLineCount++;
            
            // Check if we've reached the column limit
            if ($sameLineCount >= $defaultColumns) {
                $keyboard[] = $currentRow;
                $currentRow = [];
                $sameLineCount = 0;
            }
        }
    }
    
    // Add any remaining items in the current row
    if (!empty($currentRow)) {
        $keyboard[] = $currentRow;
    }
    
    return [
        'inline_keyboard' => $keyboard
    ];
}
function showLoadingMessage($callback_query, $action_type = null, $lang_id = 1) {
    global $tgtoken, $DB_CONN;
    
    // Check if loading should be shown at all
    if ($action_type) {
        // Get page content to check allow_loading flag
        $page_content = getPageContent($action_type, $lang_id);

      if (isset($page_content['allow_loading'])) {
            // Convert value to boolean (0, false, empty string, null will become false)
            $show_loading = (bool)$page_content['allow_loading'];
        }
        
        if (!$show_loading) {
            // Just acknowledge the callback query without showing text
            $answer_data = http_build_query([
                'callback_query_id' => $callback_query['id'],
                'show_alert' => false
            ]);
            file_get_contents("https://api.telegram.org/bot$tgtoken/answerCallbackQuery?$answer_data");
            return true;
        }
        
    }
    
    // Default text based on action type if provided
    $default_text = 'Loading...';
    if ($action_type) {
        // Map action types to default loading messages
        $action_messages = [
            'invest' => 'Loading investment plans ✅',
            'withdraw' => 'Loading withdrawal options ✅',
            'wallets' => 'Loading wallets ✅',
            'transactions' => 'Loading transactions ✅',
            'plans' => 'Loading investment plans ✅',
            'payment' => 'Loading payment methods ✅',
            'languages' => 'Loading languages ✅',
            'affiliates' => 'Loading affiliate details ✅',
            'settings' => 'Loading settings ✅',
            'dashboard' => 'Loading dashboard ✅'
        ];
        
        $default_text = $action_messages[$action_type] ?? "Loading $action_type ✅";
    }
    
    // Try to get custom loading text from page content
    $loading_text = $default_text;
    if ($action_type && isset($page_content)) {
        if (!empty($page_content['loading_text'])) {
            $loading_text = $page_content['loading_text'];
        }
    }
    
    $answer_data = http_build_query([
        'callback_query_id' => $callback_query['id'],
        'text' => $loading_text,
        'show_alert' => false
    ]);
    
    // Make the API call
    $response = file_get_contents("https://api.telegram.org/bot$tgtoken/answerCallbackQuery?$answer_data");
    
    // Return true if successful, false otherwise
    return $response !== false && strpos($response, '"ok":true') !== false;
}
function handleTelegramUser($message, $u_id, $DB_CONN) {
    $user_info = $message['from'];
    $u_id = $u_id;
    $username = $user_info['username'] ?? '';
    $first_name = $user_info['first_name'] ?? '';
    $last_name = $user_info['last_name'] ?? '';
    $is_bot = isset($user_info['is_bot']) ? intval($user_info['is_bot']) : 0;
    $is_premium = isset($user_info['is_premium']) ? intval($user_info['is_premium']) : 0;
    $language_code = $user_info['language_code'] ?? '';
    
    // Check if user already exists and get their current language setting
    $existing_user = null;
    $current_lang_id = null;
    
    $check_stmt = $DB_CONN->prepare("SELECT lang_id FROM telegram_users WHERE user_id = ? LIMIT 1");
    $check_stmt->bind_param('s', $u_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $existing_user = $check_result->fetch_assoc();
        $current_lang_id = $existing_user['lang_id'];
    }
    $check_stmt->close();
    
    // Get language ID from languages table
    $telegram_lang_id = 1; // Default language ID
    if (!empty($language_code)) {
        $lang_stmt = $DB_CONN->prepare("SELECT id FROM languagues WHERE code = ?");
        $lang_stmt->bind_param('s', $language_code);
        $lang_stmt->execute();
        $lang_result = $lang_stmt->get_result();
        if ($lang_result->num_rows > 0) {
            $lang_row = $lang_result->fetch_assoc();
            $telegram_lang_id = $lang_row['id'];
        }
        $lang_stmt->close();
    }
    
    // Determine which language ID to use
    $lang_id = $telegram_lang_id;
    if ($existing_user && $current_lang_id) {
        // Keep the existing language if the user is already in the database
        $lang_id = $current_lang_id;
    }
    
    // Now update user with or without changing the language
    if ($existing_user) {
        // User exists - update user data but preserve their language setting
        $stmt = $DB_CONN->prepare("UPDATE telegram_users SET 
            username = ?, first_name = ?, last_name = ?, 
            is_bot = ?, is_premium = ?, language_code = ?
            WHERE user_id = ?");
        $stmt->bind_param('sssiiss', $username, $first_name, $last_name, 
                          $is_bot, $is_premium, $language_code, $u_id);
    } else {
        // New user - insert with all data including language
        $stmt = $DB_CONN->prepare("INSERT INTO telegram_users (
            user_id, username, first_name, last_name, is_bot, is_premium, language_code, lang_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssiisi', $u_id, $username, $first_name, $last_name, 
                          $is_bot, $is_premium, $language_code, $lang_id);
    }
    
    $stmt->execute();
    $stmt->close();
    
    // Check if user exists and get their website user ID
    $stmt = $DB_CONN->prepare("SELECT id FROM users WHERE oauth_uid = ? AND oauth_provider = 'telegram'");
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_row = $result->fetch_assoc();
    $exists = ($result->num_rows > 0) ? 1 : 0;
    $website_user_id = $exists ? $user_row['id'] : null;
    $stmt->close();
    
    return [
        'telegram_id' => $u_id,
        'exists' => $exists,
        'user_info' => $user_info,
        'website_user_id' => $website_user_id,
        'lang_id' => $lang_id
    ];
}

function handleWebAppUser($user_data, $u_id, $DB_CONN) {
    // Format the user data to match the structure we need
    $user_info = [
        'id' => $u_id,
        'username' => $user_data['username'] ?? '',
        'first_name' => $user_data['first_name'] ?? '',
        'last_name' => $user_data['last_name'] ?? '',
        'is_bot' => 0,
        'is_premium' => $user_data['is_premium'] ?? 0,
        'language_code' => $user_data['language_code'] ?? ''
    ];
    
    // Use the same database logic as the original function
    $username = $user_info['username'];
    $first_name = $user_info['first_name'];
    $last_name = $user_info['last_name'];
    $is_bot = $user_info['is_bot'];
    $is_premium = $user_info['is_premium'];
    $language_code = $user_info['language_code'];
    
    $stmt = $DB_CONN->prepare("INSERT INTO telegram_users (
        user_id, username, first_name, last_name, is_bot, is_premium, language_code
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        username = VALUES(username),
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        is_bot = VALUES(is_bot),
        is_premium = VALUES(is_premium),
        language_code = VALUES(language_code)");
    
    $stmt->bind_param('ssssiis', $u_id, $username, $first_name, $last_name, $is_bot, $is_premium, $language_code);
    $stmt->execute();
    $stmt->close();

    // Check if user exists and get their website user ID
    $stmt = $DB_CONN->prepare("SELECT id FROM users WHERE oauth_uid = ? AND oauth_provider = 'telegram'");
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_row = $result->fetch_assoc();
    $exists = $result->num_rows > 0;
    $website_user_id = $exists ? $user_row['id'] : null;
    $stmt->close();
    
    return [
        'telegram_id' => $u_id,
        'exists' => $exists,
        'user_info' => $user_info,
        'website_user_id' => $website_user_id
    ];
}
function registerWebsiteUser($user_info, $DB_CONN, $tgtoken) {
    try {
        $c_id = $user_info['id'];
        if (empty($c_id)) {
            throw new Exception('User ID is required');
        }
        
        $username = $user_info['username'] ?? $user_info['first_name'] ?? '';
        $first_name = $user_info['first_name'] ?? '';
        $last_name = $user_info['last_name'] ?? '';
        $full_name = trim($first_name . ' ' . $last_name);
        $photo_url = $user_info['photo_url'] ?? 'images/bitx11.svg';
        $provider = 'telegram';

        // Get sponsor information
        $query = "SELECT start_param FROM telegram WHERE user_id = ? AND start_param IS NOT NULL AND start_param != '' ORDER BY id ASC LIMIT 1";
        $stmt = $DB_CONN->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare sponsor query: ' . $DB_CONN->error);
        }
        
        $stmt->bind_param("s", $c_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute sponsor query: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $sponsor = $row['start_param'] ?? '';
        $stmt->close();

        if ($sponsor) {
            $stmt = $DB_CONN->prepare("SELECT id FROM users WHERE oauth_uid = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Failed to prepare sponsor lookup query: ' . $DB_CONN->error);
            }
            
            $stmt->bind_param("s", $sponsor);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute sponsor lookup: ' . $stmt->error);
            }
            
            $stmt->bind_result($sponsor_id);
            $stmt->fetch();
            $stmt->close();
            $sponsor = $sponsor_id ?: 0;
        } else {
            $sponsor = 0;
        }

        // Get location and system info
        $ip = getUserIP();
        $tz = 'UTC';
        $country = 'Unknown';
        $city = 'Unknown';
        $browser = getBrowser();
        $os = getOS();
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $refer = $_SERVER['HTTP_REFERER'] ?? '';

        // Insert new user
        $stmt = $DB_CONN->prepare("INSERT INTO users (oauth_uid, oauth_provider, fullname, username, photo, sponsor, status, timezone) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare user insert: ' . $DB_CONN->error);
        }
        
        $stmt->bind_param("sssssis", $c_id, $provider, $full_name, $username, $photo_url, $sponsor, $tz);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert user: ' . $stmt->error);
        }
        
        $user_id = $stmt->insert_id;
        $stmt->close();

        if ($user_id) {
            // Log the login
            $stmt = $DB_CONN->prepare("INSERT INTO login_report (ip, useragent, refer, os, country, city, browser, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssssi", $ip, $useragent, $refer, $os, $country, $city, $browser, $user_id);
                $stmt->execute();
                $stmt->close();
            }

           if ($sponsor) {
                    $sponsor_query = $DB_CONN->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                    if ($sponsor_query) {
                        $sponsor_query->bind_param("i", $sponsor);
                        $sponsor_query->execute();
                        $sponsor_result = $sponsor_query->get_result();
                        $s = $sponsor_result->fetch_assoc();
                        $sponsor_query->close();
                        
                        sendmail("direct_signup_notification", $sponsor, $s, array(
                            'username' => $username,
                            'name' => $full_name,
                            'email' => ''
                        ));
                    }
                }

            return $user_id;
        } else {
            throw new Exception('User ID not generated after insert');
        }
        
    } catch (Exception $e) {
        throw $e; // Re-throw the exception to be handled by the calling function
    }
}
function sendtgalert($error_prefix, $error_message) {
    global $tgtoken, $telegram_settings;  
  
    $error_msg = urlencode($error_prefix . " " . $error_message);
    $urlalert = "https://api.telegram.org/bot{$tgtoken}/sendMessage?chat_id={$telegram_settings['personal_id']}&text={$error_msg}";
    return file_get_contents($urlalert);
}

function sendTelegramRequest($method, $data) {
    global $tgtoken;
    
    $ch = curl_init("https://api.telegram.org/bot$tgtoken/$method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
function sendTelegramMessage($chat_id, $text, $keyboard = null, $parse_mode = 'Markdown') {
    // Create the base parameters array
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode,
        'disable_web_page_preview' => true
    ];
    
    // Add keyboard if provided
    if ($keyboard !== null) {
        if (is_array($keyboard)) {
            $params['reply_markup'] = json_encode($keyboard);
        } else {
            $params['reply_markup'] = $keyboard;
        }
    }
    
    // Send the request using the existing function
    $response = sendTelegramRequest('sendMessage', $params);
    
    // Parse the JSON response if it's a string
    if (is_string($response)) {
        $response = json_decode($response, true);
    }
    
    return $response;
}
function validateAdminAuth($admin_settings, $passphrase, $page_key = null, $check_page = true) {
    $is_authorized = true;
    if ($check_page && $page_key !== null) {
        if (!empty($admin_settings['page'][$page_key])) {
            $is_authorized = checkPassphrase($admin_settings, $passphrase);
        }
    }
    else if (!empty($admin_settings['anpass'])) {
        $is_authorized = checkPassphrase($admin_settings, $passphrase);
    }
    if (!$is_authorized) {
        echo "<div class='alert alert-danger'><strong>Error - </strong> Invalid Passphrase  </div>";
    }
    return $is_authorized;
}
function checkPassphrase($admin_settings, $passphrase) {
    return $passphrase === encrypt_decrypt('decrypt', $admin_settings['anpass']);
}
function sendTelegram($message = null, $user_id = null, $invest_msg = null, $withdraw_msg = null, $full_msg = null, $array_source = [], $array_replace = [], $message_type = null) {
    global $telegram_settings, $DB_CONN, $tgtoken;
    
    if (empty($tgtoken)) {
        return false;
    }
    
    // Determine message type if not explicitly provided
    if ($message_type === null) {
        if ($withdraw_msg !== null || (isset($array_source) && isset($array_replace) && !empty($telegram_settings['withdraw_admin']))) {
            $message_type = 'withdraw';
        } else {
            $message_type = 'invest';
        }
    }
    
    // Prepare user notification message
    $final_message = "";
    if ($telegram_settings['notify_user_notification'] && $message) {
        $final_message .= $message . "\n";
    }
    
    if ($telegram_settings['notify_user_full'] && !empty($full_msg)) {
        $full_msg = str_replace("<br>", "\n", $full_msg); 
        $full_msg = strip_tags($full_msg, '<b><i><a>'); 
        $final_message .= $full_msg;
    }
    
    // User notification preparation
    $chat_id = null;
    if ($user_id) {
        $query = mysqli_query($DB_CONN, "SELECT oauth_uid FROM users WHERE id = '{$user_id}'");
        if ($query) {
            $user = mysqli_fetch_assoc($query);
            if (!empty($user['oauth_uid'])) {
                $chat_id = $user['oauth_uid'];
            }
        }
    }
    
    // Send to user if enabled
    if ($telegram_settings['notify_user'] === 'true' && $chat_id && !empty($final_message)) {
        $keyboard_rows = [];
        
        // Add WebApp button as the first row if enabled
        if (!empty($telegram_settings['show_webapp_button_user']) && $telegram_settings['show_webapp_button_user'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
            $button_text = !empty($telegram_settings['webapp_button_text_user']) ? $telegram_settings['webapp_button_text_user'] : 'WebApp';
            $keyboard_rows[] = [['text' => $button_text, 'web_app' => ['url' => $telegram_settings['menu_button_link']]]];
        }
        
        // Add Dismiss and Share buttons as a separate row
        $action_buttons = [];
        if (!empty($telegram_settings['notify_user_dismiss'])) {
            $action_buttons[] = ['text' => 'Dismiss', 'callback_data' => 'dismiss_message'];
        }
        if (!empty($telegram_settings['notify_user_share'])) {
            $action_buttons[] = ['text' => 'Share', 'switch_inline_query' => $final_message];
        }
        
        if (!empty($action_buttons)) {
            $keyboard_rows[] = $action_buttons;
        }
        
        $data = [
            'chat_id' => $chat_id,
            'text' => $final_message,
            'parse_mode' => 'HTML'
        ];
        
        // Only add reply_markup if there are buttons to display
        if (!empty($keyboard_rows)) {
            $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard_rows]);
        }
        
        sendTelegramRequest('sendMessage', $data);
       
    }

    // ADMIN NOTIFICATIONS
    if ($telegram_settings['notify_admin'] == 'true' && !empty($telegram_settings['personal_id'])) {
        if ($message_type == 'withdraw') {
            $admin_text = $withdraw_msg;
            if (empty($admin_text) && !empty($telegram_settings['withdraw_admin']) && !empty($array_source) && !empty($array_replace)) {
                $admin_text = str_replace($array_source, $array_replace, $telegram_settings['withdraw_admin']);
            }
            
            if (!empty($admin_text)) {
                $data = [
                    'chat_id' => $telegram_settings['personal_id'],
                    'text' => $admin_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for admin if enabled
                if (!empty($telegram_settings['show_webapp_button_admin']) && $telegram_settings['show_webapp_button_admin'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
                    $button_text = !empty($telegram_settings['webapp_button_text_admin']) ? $telegram_settings['webapp_button_text_admin'] : 'WebApp';
                    $keyboard = [[['text' => $button_text, 'web_app' => ['url' => $telegram_settings['menu_button_link']]]]];
                    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
                }
                
                sendTelegramRequest('sendMessage', $data);
            }
        } else {
            $admin_text = $invest_msg;
            if (empty($admin_text) && !empty($telegram_settings['invest_admin']) && !empty($array_source) && !empty($array_replace)) {
                $admin_text = str_replace($array_source, $array_replace, $telegram_settings['invest_admin']);
            }
            
            if (!empty($admin_text)) {
                $data = [
                    'chat_id' => $telegram_settings['personal_id'],
                    'text' => $admin_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for admin if enabled
                if (!empty($telegram_settings['show_webapp_button_admin']) && $telegram_settings['show_webapp_button_admin'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
                    $button_text = !empty($telegram_settings['webapp_button_text_admin']) ? $telegram_settings['webapp_button_text_admin'] : 'WebApp';
                    $keyboard = [[['text' => $button_text, 'web_app' => ['url' => $telegram_settings['menu_button_link']]]]];
                    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
                }
                
                sendTelegramRequest('sendMessage', $data);
            }
        }
    }

    // CHANNEL NOTIFICATIONS
    if ($telegram_settings['notify_channel'] == 'true' && !empty($telegram_settings['channel_id'])) {
        if ($message_type == 'withdraw' && $telegram_settings['withdraw_channel']) {
            $channel_text = $withdraw_msg;
            if (empty($channel_text) && !empty($array_source) && !empty($array_replace)) {
                $channel_text = str_replace($array_source, $array_replace, $telegram_settings['withdraw_channel']);
            }
            
            if (!empty($channel_text)) {
                $data = [
                    'chat_id' => $telegram_settings['channel_id'],
                    'text' => $channel_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for channel if enabled
                if (!empty($telegram_settings['show_webapp_button_channel']) && $telegram_settings['show_webapp_button_channel'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
    $button_text = !empty($telegram_settings['webapp_button_text_channel']) ? $telegram_settings['webapp_button_text_channel'] : 'WebApp';
    // Use URL button for channels instead of web_app
    $keyboard = [[['text' => $button_text, 'url' => $telegram_settings['mini_app_link'] . '?' . $telegram_settings['mini_app_link_start']]]];
    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
}
                
                sendTelegramRequest('sendMessage', $data);
                
            }
        } elseif ($message_type == 'invest' && $telegram_settings['invest_channel']) {
            $channel_text = $invest_msg;
            if (empty($channel_text) && !empty($array_source) && !empty($array_replace)) {
                $channel_text = str_replace($array_source, $array_replace, $telegram_settings['invest_channel']);
            }
            
            if (!empty($channel_text)) {
                $data = [
                    'chat_id' => $telegram_settings['channel_id'],
                    'text' => $channel_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for channel if enabled
                if (!empty($telegram_settings['show_webapp_button_channel']) && $telegram_settings['show_webapp_button_channel'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
    $button_text = !empty($telegram_settings['webapp_button_text_channel']) ? $telegram_settings['webapp_button_text_channel'] : 'WebApp';
    // Use URL button for channels instead of web_app
    $keyboard = [[['text' => $button_text, 'url' => $telegram_settings['mini_app_link'] . '?' . $telegram_settings['mini_app_link_start']]]];
    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
}
                
                sendTelegramRequest('sendMessage', $data);
              
            }
        }
    }

    // GROUP NOTIFICATIONS
    if ($telegram_settings['notify_group'] == 'true' && !empty($telegram_settings['group_id'])) {
        if ($message_type == 'withdraw' && $telegram_settings['withdraw_channel']) {
            $group_text = $withdraw_msg;
            if (empty($group_text) && !empty($array_source) && !empty($array_replace)) {
                $group_text = str_replace($array_source, $array_replace, $telegram_settings['withdraw_channel']);
            }
            
            if (!empty($group_text)) {
                $data = [
                    'chat_id' => $telegram_settings['group_id'],
                    'text' => $group_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for group if enabled
                if (!empty($telegram_settings['show_webapp_button_channel']) && $telegram_settings['show_webapp_button_channel'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
    $button_text = !empty($telegram_settings['webapp_button_text_channel']) ? $telegram_settings['webapp_button_text_channel'] : 'WebApp';
    // Use URL button for channels instead of web_app
    $keyboard = [[['text' => $button_text, 'url' => $telegram_settings['mini_app_link'] . '?' . $telegram_settings['mini_app_link_start']]]];
    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
}
                
                sendTelegramRequest('sendMessage', $data);
            }
        } elseif ($message_type == 'invest' && $telegram_settings['invest_channel']) {
            $group_text = $invest_msg;
            if (empty($group_text) && !empty($array_source) && !empty($array_replace)) {
                $group_text = str_replace($array_source, $array_replace, $telegram_settings['invest_channel']);
            }
            
            if (!empty($group_text)) {
                $data = [
                    'chat_id' => $telegram_settings['group_id'],
                    'text' => $group_text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ];
                
                // Add WebApp button for group if enabled
                if (!empty($telegram_settings['show_webapp_button_channel']) && $telegram_settings['show_webapp_button_channel'] === 'true' && !empty($telegram_settings['menu_button_link'])) {
    $button_text = !empty($telegram_settings['webapp_button_text_channel']) ? $telegram_settings['webapp_button_text_channel'] : 'WebApp';
    // Use URL button for channels instead of web_app
    $keyboard = [[['text' => $button_text, 'url' => $telegram_settings['mini_app_link'] . '?' . $telegram_settings['mini_app_link_start']]]];
    $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
}
                sendTelegramRequest('sendMessage', $data);
               
            }
        }
    }

    return true;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
function convertTitleToURL($str) { 
    $str = strtolower($str); 
    $str = str_replace(' ', '-', $str);
    $str = preg_replace('/[^a-z0-9\-]/', '', $str); 
    $str = preg_replace('/-+/', '-', $str); 
    $str = trim($str, '-'); 
    return $str; 
} 

function cleanContent($content) {
    if (is_array($content)) {
        return array_map('cleanContent', $content);
    }
     // Preserve shortcodes by temporarily replacing them
    $content = str_replace(
        ['#username#', '#first_name#', '#last_name#'],
        ['{{USERNAME}}', '{{FIRSTNAME}}', '{{LASTNAME}}'],
        $content
    );
    
    // Normalize line endings first
      $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\r", "\n", $content);
    $content = preg_replace('/\\\\r\\\\n/', "\n", $content);
    
   
    
    // Decode HTML entities
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    
    // Remove backslashes
    $content = stripslashes($content);
    
    // Fix links
    $content = preg_replace('/href="&quot;(.*?)&quot;"/', 'href="$1"', $content);
    
    // Remove empty paragraphs, spans, and breaks
    $content = preg_replace([
        '/<p>\s*<\/p>/',
        '/<span>\s*<\/span>/',
        '/(<br \/>)+/',
        '/<p><a[^>]*><\/a><\/p>/'
    ], '', $content);
    
    // Strip potentially harmful attributes
    $content = preg_replace('/(<[^>]+) on\w+=".*?"/i', '$1', $content);
    
    // Remove class and style attributes except for card classes
    $content = preg_replace('/\s(class|style)="(?!card)[^"]*"/i', '', $content);
    
    // Balance single tags
    $single_tags = ['br', 'hr', 'img', 'input'];
    foreach ($single_tags as $tag) {
        $content = preg_replace("/<$tag([^>]*)>/i", "<$tag$1 />", $content);
    }
    
    // Clean list items and table cells
    $content = preg_replace('/<(li|td)([^>]*)>\s*(&nbsp;)*\s*<\/(li|td)>/i', '<$1$2></$4>', $content);
    
    // Remove empty elements
    $content = preg_replace('/<(p|span|h\d|td)[^>]*>(\s|&nbsp;)*<\/\1>/i', '', $content);
    
    // Normalize inline whitespace (but preserve line breaks)
    $content = preg_replace('/[ \t]+/', ' ', $content);
    

   // $content = str_replace("rn", "\n", $content);
    // Convert special characters to HTML entities
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    // Restore shortcodes
    $content = str_replace(
        ['{{USERNAME}}', '{{FIRSTNAME}}', '{{LASTNAME}}'],
        ['#username#', '#first_name#', '#last_name#'],
        $content
    );
    return trim($content);
}


function update_rates() {
    global $DB_CONN;
    $c = mysqli_query($DB_CONN, "SELECT * FROM `currencies` where de_pm_id or wi_pm_id");
    while ($currency = mysqli_fetch_assoc($c)) {
        if (strpos($currency['symbol'], "USD") !== false)
            mysqli_query($DB_CONN, "UPDATE `currencies` set rate = 1 where id = '{$currency['id']}'");
        else {
            $pair = "{$currency['symbol']}USDT";
            $url = "https://data-api.binance.vision/api/v3/ticker/price?symbol={$pair}";
            $r = file_get_contents($url);
            $rates = json_decode($r, true);
            $usd = $rates['price'];
            if($usd > 0)
                mysqli_query($DB_CONN, "UPDATE `currencies` set rate = '{$usd}' where id = '{$currency['id']}'");
            sleep(1);
        }
    }
    $c = mysqli_query($DB_CONN, "SELECT * FROM `trading_pairs` where status = 1");
    while ($currency = mysqli_fetch_assoc($c)) {
        $pair = $currency['pair'];
        $url = "https://data-api.binance.vision/api/v3/ticker/price?symbol={$pair}";
        $r = file_get_contents($url);
        $rates = json_decode($r, true);
        $usd = $rates['price'];
        if($usd > 0)
            mysqli_query($DB_CONN, "UPDATE `trading_pairs` set rate = '{$usd}' where id = '{$currency['id']}'");
        sleep(1);
    }
}

function call_alert($id, $concat = '', $status = '', $keyboard = null) {
    global $alert, $alert_class, $alert_message, $g_alert, $smarty, $data, $is_api, $is_bot, $c_id, $keyboard, $telegram_settings;
    
    $alert = true;
    
    if (isset($g_alert[$id])) {
        $alert_class = $g_alert[$id]['class'];
        $base_message = $g_alert[$id]['content'];
        
        
        // Check if $concat is an array for placeholder replacement
        if (is_array($concat) && !empty($concat)) {
            foreach ($concat as $key => $value) {
                $base_message = str_replace("#{$key}#", $value, $base_message);
            }
            $alert_message = $base_message;
        } elseif ($concat !== '') {
            $ends_with_space = substr($base_message, -1) === ' ';
            $alert_message = $base_message . ($ends_with_space ? '' : ' ') . $concat;
        } else {
            $alert_message = $base_message;
        }
    } else {
        $alert_class = 'alert';
        $alert_message = 'Message';
    }
    
    // Handle API response
    if ($is_api) {
        $status1 = true;
        $data['alert_class'] = $alert_class;
        if (strpos($alert_class, "danger") !== FALSE || strpos($alert_class, "warning") !== FALSE) {
            $status1 = false;
        }
        $data['status'] = $status1;
        $data['message'] = $alert_message;
        
        switch ($status) {
            case 'unauthorized':
                http_response_code(401);
                break;
            case 'forbidden':
                http_response_code(403);
                break;
            default:
                http_response_code(200);
                break;
        }
    }
    
    // For Smarty template
    if ($alert && $smarty) {
        $smarty->assign('alert', $alert);
        $smarty->assign('alert_class', $alert_class);
        $smarty->assign('alert_message', $alert_message);
    }
    if (isset($is_bot) && $is_bot && isset($c_id)) {
        // Add emoji based on alert class
if (strpos($alert_class, "danger") !== FALSE) {
            $formatted_message = ($telegram_settings['danger_call_alert'] ?? "❌ ") . $alert_message;
        } elseif (strpos($alert_class, "warning") !== FALSE) {
            $formatted_message = ($telegram_settings['warning_call_alert'] ?? "⚠️ ") . $alert_message;
        } elseif (strpos($alert_class, "success") !== FALSE) {
            $formatted_message = ($telegram_settings['success_call_alert'] ?? "✅ ") . $alert_message;
        } elseif (strpos($alert_class, "info") !== FALSE) {
            $formatted_message = ($telegram_settings['info_call_alert'] ?? "ℹ️ ") . $alert_message;
        } else {
            $formatted_message = $alert_message;
        }
        
        // Prepare message parameters
        $params = [
            'chat_id' => $c_id,
            'text' => $formatted_message,
            'parse_mode' => 'Markdown'
        ];
        
        // Add any additional options
        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        
        // Send the message
        return sendTelegramRequest('sendMessage', $params);
    }
    
    // If not in bot mode or no chat_id, just return the message
    return $alert_message;
}

function timeToMinutes($timeStr) {
    list($hours, $minutes) = explode(':', $timeStr);
    return (int)$hours * 60 + (int)$minutes;
}

function check_withdraw($withdraw, $payment_method_id, $custom_address = null, $type = 'instant') {
    global $DB_CONN, $userinfo, $withdraw_settings, $ps;

    // Get payment method and system details with a single query
    $query = "SELECT 
        c.*,
        COALESCE(
            (SELECT currencies FROM payment_methods WHERE id = ? AND ? NOT IN (2, 6, 17)),
            (SELECT currencies FROM payment_methods WHERE id = c.wi_pm_id)
        ) as payment_system
        FROM currencies c 
        WHERE c.id = ?";
        
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param('iii', $userinfo['wi_pm_id'], $userinfo['wi_pm_id'], $payment_method_id);
    $stmt->execute();
    $method = $stmt->get_result()->fetch_assoc();
    
    // Handle wallet address based on settings and input
    $field = strtolower(str_replace(' ', '', $method['name']));
    $saved_address = $userinfo['wallets'][$field] ?? null;
    
    if ($withdraw_settings['address']) {
        if (!empty($custom_address)) {
            $validator = new CryptoValidator();
            $validation = $validator->validate($payment_method_id, $custom_address);
            if (!$validation['valid']) {
                call_alert(39, $method['name']); // invalid  address
                return array(true);
            }
            $withdrawal_address = $custom_address;
        }
        else if ($saved_address) {
            $withdrawal_address = $saved_address;
        }
        else {
            call_alert(166); // Please provide a withdrawal address
            return array(true);
        }
    } else {
        if (!$saved_address) {
            call_alert(66); // Please fill wallet details in profile
            return array(true);
        }
        $withdrawal_address = $saved_address;
    }

    if ($withdraw_settings['spec_time']) {
        $allowed_days = explode(',', $withdraw_settings['allowed_days']);
        $current_day = date('l');
        
        if (!in_array($current_day, $allowed_days)) {
            call_alert(1487, $current_day);
            return array(true);
        }

        if (isset($withdraw_settings['time_from']) && isset($withdraw_settings['time_to'])) {
            $time_from = $withdraw_settings['time_from'];
            $time_to = $withdraw_settings['time_to'];
            $current_minutes = timeToMinutes(date('H:i'));
            $from_minutes = timeToMinutes($time_from);
            $to_minutes = timeToMinutes($time_to);
            if ($current_minutes < $from_minutes || $current_minutes > $to_minutes) {
                call_alert(1488, "{$time_from} and {$time_to}");
                return array(true);
            }
        }
    }
    
    // Check withdrawal limits
    $min = $userinfo['min_withdraw'] ?: $method['with_min'];
    if ($min && $withdraw < $min) {
        call_alert(69, $method['name']); //Please check Min amount to withdraw in 
        return array(true);
    }
    
    $max = $userinfo['max_withdraw'] ?: $method['with_max'];
    if ($max && $withdraw > $max) {
        call_alert(70, $method['name']); //Please check Max amount to withdraw in 
        return array(true);
    }
    
    // KYC check
    if ($withdraw_settings['kyc'] && $userinfo['kyc'] != 1) {
        call_alert(90); // KYC required
        return array(true);
    }
    
    // Check withdrawal frequency limits
    if ($type == 'auto' && $withdraw_settings['auto_withdraw_limit'] && $withdraw_settings['auto_withdraw_limit_duration']) {
        $cond = getcond($withdraw_settings['auto_withdraw_limit_duration']);
        $stmt = $DB_CONN->prepare("SELECT COUNT(*) as count FROM transactions WHERE txn_type = 'withdraw' AND user_id = ? AND status = '1' AND {$cond}");
        $stmt->bind_param('i', $userinfo['id']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['count'] >= $withdraw_settings['auto_withdraw_limit']) {
            call_alert(71); // Withdraw Limit Reached
            return array(true);
        }
    } elseif($withdraw_settings['withdraw_limit'] && $withdraw_settings['withdraw_limit_duration']) {
        $cond = getcond($withdraw_settings['withdraw_limit_duration']);
        $stmt = $DB_CONN->prepare("SELECT COUNT(*) as count FROM transactions WHERE txn_type = 'withdraw' AND user_id = ? AND status = '1' AND {$cond}");
        $stmt->bind_param('i', $userinfo['id']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['count'] >= $withdraw_settings['withdraw_limit']) {
            call_alert(71); // Withdraw Limit Reached
            return array(true);
        }
    }
    
    // Check pending withdrawals
    if ($withdraw_settings['withdraw_pending_limit'] && $withdraw_settings['withdraw_pending_limit_duration']) {
        $cond = getcond($withdraw_settings['withdraw_pending_limit_duration']);
        $stmt = $DB_CONN->prepare("SELECT COUNT(*) as count FROM transactions WHERE txn_type = 'withdraw' AND user_id = ? AND status = '0' AND {$cond}");
        $stmt->bind_param('i', $userinfo['id']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()['count'] >= $withdraw_settings['withdraw_pending_limit']) {
            call_alert(62); // Pending withdrawal limit reached
            return array(true);
        }
    }
    
    // Check balance and calculate fee
    $stmt = $DB_CONN->prepare("SELECT balance FROM user_balances WHERE user_id = ? AND payment_method_id = ?");
    $stmt->bind_param('ii', $userinfo['id'], $payment_method_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $balance = $result ? $result['balance'] : 0;
    
    if ($balance >= $withdraw) {
        $fee = 0;
        if ($method['with_fee_per']) {
            $fee = $withdraw * $method['with_fee_per'] / 100;
        } elseif ($method['with_fee_amount']) {
            $fee = $method['with_fee_amount'];
        }
        
        if ($fee) {
            if ($method['with_fee_min'] && $fee < $method['with_fee_min']) {
                $fee = $method['with_fee_min'];
            } elseif ($method['with_fee_max'] && $fee > $method['with_fee_max']) {
                $fee = $method['with_fee_max'];
            }
        }
        
        $system = json_decode($method['payment_system'], true);
        $method['system'] = $system;
        
        return array(false, $withdraw, $fee, $withdrawal_address, $method);
    } else
        call_alert(64);  // Insufficient Balance
    return array(true);
}

function sendcashback($details, $amount, $user_id, $payment_method_id, $package_id)
{
  global $DB_CONN;
  $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$package_id}'"));
  $packname = $package['name'];
  if($details['cashback_bonus']) {
    $com = 0;
    if($details['cashback_bonus_amount']){
      $com += $details['cashback_bonus_amount'];
      if($com) {
        $memo = str_replace(array("#plan#","#bonus_amount#"), array($packname ,fiat($com)), $details['cashback_bonus_amount_memo']);
        mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$memo}','{$user_id}','{$com}', 'bonus', '{$payment_method_id}', '{$package_id}')");
        add_balance($user_id, $payment_method_id, $com);
      }
    }
    if($details['cashback_bonus_percentage']){
        $percetbonus = $details['cashback_bonus_percentage'];
      $com += ($amount/100)*$percetbonus;
        if($com) {
        $memo = str_replace(array("#plan#","#bonus_amount#", "#bonus_percentage#"), array($packname ,fiat($com), $percetbonus), $details['cashback_bonus_percentage_memo']);
        mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$memo}','{$user_id}','{$com}', 'bonus', '{$payment_method_id}', '{$package_id}')");
        add_balance($user_id, $payment_method_id, $com);
      }
    }    
  }
}

function getcondcode($cond, $column = "datetime") {
    switch ($cond) {
    case 'hours':
      $cond = "HOUR($column) = HOUR(NOW()) and DATE($column) = CURRENT_DATE";
      break;
    case 'days':
      $cond = "DATE($column) = CURRENT_DATE";
      break;
    case 'weeks':
      $cond = "WEEK($column) = WEEK(CURRENT_DATE) and YEAR($column) = YEAR(CURRENT_DATE)";
      break;
    case 'bi-weeks':
      $cond = "(WEEK($column) DIV 2) = (WEEK(CURRENT_DATE) DIV 2) and YEAR($column) = YEAR(CURRENT_DATE)";
      break;
    case 'months':
      $cond = "MONTH($column) = MONTH(CURRENT_DATE) and YEAR($column) = YEAR(CURRENT_DATE)";
      break;
    case 'years':
      $cond = "YEAR($column) = YEAR(CURRENT_DATE)";
      break;
    default:
      $cond = "1";
      break;
    }
    return $cond;
}

function getcondadd($cond) {
    switch ($cond) {
    case 'hours':
      $cond = 3600;
      break;
    case 'days':
      $cond = 86400;
      break;
    case 'weeks':
      $cond = 86400*7;
      break;
    case 'bi-weeks':
      $cond = 86400*14;
      break;
    case 'months':
      $cond = strtotime("+1 month")-time();
      break;
    case 'years':
      $cond = strtotime("+1 year")-time();
      break;
    default:
      $cond = 0;
      break;
    }
    return $cond;
}


function deposit_fee($method, $deposit, $reverse = false) {
    $eligible1 = true;
    if ($deposit <= 0) {
      $eligible1 = false;
      call_alert(42); // Try higher amount. Minimum Deposit is
    }
    if ($eligible1 && $method['dep_min'] && !($deposit >= $method['dep_min'])) {
      $eligible1 = false;
      call_alert(48, $currency); //Please check Min amount to deposit in 
    }
    if ($eligible1 && $method['dep_max'] && !($deposit <= $method['dep_max'])) {
      $eligible1 = false;
      call_alert(49, $currency); //Please check Max amount to deposit in 
    }
    if ($eligible1) {
    $fee = 0;
    if($method['dep_fee_per']) {
        if($reverse)
            $deposit = (($deposit/(100+$method['dep_fee_per']))*100);
        $fee += $deposit/100*$method['dep_fee_per'];
    }
    if($method['dep_fee_amount'])
        $fee += $method['dep_fee_amount'];
    if($fee) {
        if($method['dep_fee_min'] && $fee < $method['dep_fee_min'])
          $fee = $method['dep_fee_min'];
        elseif($method['dep_fee_max'] && $fee > $method['dep_fee_max'])
          $fee = $method['dep_fee_max'];
        }
    }
    return array('eligible1' => $eligible1, 'fee' => $fee);
}

function check_deposit($deposit, $package, $plan, $user_id, $method, $acc_id, $compound = 0, $fee = false) {
    global $DB_CONN, $userinfo;
    $currency = $method['id'];
    $plan_name = $package['name'];
    $details = json_decode($package['details'], true);
    $eligible1 = true;
    if($deposit < $plan['min'] && $eligible1)
    {
        $eligible1=false;
        call_alert(42, fiat($plan['min'])); // Try higher amount. Minimum Deposit is
    }
    if($deposit > $plan['max'] && $eligible1)
    {
        $eligible1=false;
        call_alert(43, fiat($plan['max'])); // Try smaller amount. Maximum Deposit is
    }
    if($eligible1 && $details['compound_enable'] && $compound)
    {
        if($details['compound_min'] && $deposit < $details['compound_min']) {
            $eligible1=false;
            call_alert(43, fiat($details['compound_min'])); // Amount must be higher than for componding
        }
        if($eligible1 && $details['compound_max'] && $deposit > $details['compound_max']) {
            $eligible1=false;
            call_alert(43, fiat($details['compound_min'])); // Amount must be less than for componding
        }
        if($eligible1 && $details['compound_percent_min'] && $compound < $details['compound_percent_min']) {
            $eligible1=false;
            call_alert(43, $details['compound_percent_min']); // Percet must be higher than for componding
        }
        if($eligible1 && $details['compound_percent_max'] && $compound > $details['compound_percent_max']) {
            $eligible1=false;
            call_alert(43, $details['compound_percent_max']); // Percet must be less than for componding
        }
    }
    if($eligible1) {
        extract(deposit_fee($method, $deposit, $fee));
    }
    if($package['limit_currency'] && $eligible1 && $acc_id != $package['limit_currency']) {
        $eligible1=false;
        call_alert(53, $currency); //Deposit only allowed with
    }
    if($package['limit_tier'] && $eligible1 && $userinfo['tier'] >= $package['limit_tier']) {
        $eligible1=false;
        call_alert(1822); //Not Eligible for this package
    }
    if($package['parent_plan'] && $eligible1) {
        if($package['parent_plan_type'])
            $ch = mysqli_num_rows(mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` WHERE user_id = $user_id and package_id = '{$package['parent_plan']}' and (status = 1 or last_earningDateTime)"));
        else
            $ch = mysqli_num_rows(mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` WHERE user_id = $user_id and package_id = '{$package['parent_plan']}' and status = 1"));
        if($ch == 0) {
            $pname = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT name from packages where id = '{$package['parent_plan']}'"))[0];
            $eligible1=false;
            call_alert(54, $pname); //Must have deposit in
        }
    }
    if($details['limit_user_deposits_count'] && $eligible1) {
    $cond = getcondcode($details['limit_user_deposits_count_per']);
    $ch = mysqli_num_rows(mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` WHERE user_id = $user_id and package_id = '{$package['id']}' and (status = 1 or last_earningDateTime) and {$cond}"));
    if($details['limit_user_deposits_count'] >= $ch) {
    $eligible1=false;
    call_alert(55, $plan_name); //You have reached maximum no of Investments allowed for this Plan.
    }
    }
    if($details['limit_user_deposits_amount'] && $eligible1) {
    $cond = getcondcode($details['limit_user_deposits_amount_per']);
    $ch = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT sum(amount) FROM `package_deposits` WHERE user_id = $user_id and package_id = '{$package['id']}' and (status = 1 or last_earningDateTime) and {$cond}"))[0];
    if($details['limit_user_deposits_amount'] >= $ch) {
    $eligible1=false;
    call_alert(82, $plan_name); //You have reached maximum investment allowed for this Plan.
    }
    }
    return array($eligible1, $fee);
}
function activate_package($ID, $deposit, $fee, $txn_id, $user_id, $payment_method_id, $package_id, $plan_id, $currency, $hash = '', $txn_type = 'invest', $ref = true) {
    global $DB_CONN, $deposit_settings;
    $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$package_id}'"));
    $plan = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from package_plans where id = '{$plan_id}'"));
    $details = json_decode($package['details'], true);
    if($currency['dep_bonus_per'] || $currency['dep_bonus_amount']) {
        $add = 0;
        if($currency['dep_bonus_per'])
            $add += $deposit/100*$currency['dep_bonus_per'];
        if($currency['dep_bonus_amount'])
            $add += $currency['dep_bonus_amount'];
        if($add)
            $deposit += $add;
    }
    //auto pool
    if($package['etype'] == 5) {
        $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$user_id}'"));
        $c = mysqli_query($DB_CONN, "SELECT users.id, package_deposits.id FROM `users` INNER join package_deposits on users.id = package_deposits.user_id WHERE users.sponsor = '{$user_id}' and package_deposits.package_id = '{$package['id']}'");
        if($user['parent'] == 0 && $c >= 3) {
            // $user['sponsor'] ??= '2';
            $parent = get_parent(2);
            $details = "Earning from Auto Pool 1";
            $am = 5;
            mysqli_query($DB_CONN, "UPDATE users set parent = '{$parent}' where id = '{$user_id}'");
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `plan_id`) VALUES ('{$detail}','{$user_id}','{$am}', 'autopool', '{$payment_method_id}', '1')");
            add_balance($user_id, $payment_method_id, $am);
            $s_id = $parent;
            for ($i=2; $i < 15; $i++) {
                $chk_txn = mysqli_query($DB_CONN, "SELECT * FROM transactions where user_id = '{$user_id}' and txn_type = 'autopool' and plan_id = '{$i}'");
                if(mysqli_num_rows($chk_txn) == 0) {
                    // $coupon = 100;
                    // if($i == 2) {
                    //     $cu = mysqli_query($DB_CONN, "SELECT id from users where parent = '{$s_id}'");
                    // } else {
                        $k = $i - 1;
                        $cu = mysqli_query($DB_CONN, "SELECT * FROM transactions where user_id in (SELECT id from users where parent = '{$s_id}') and txn_type = 'autopool' and plan_id = '{$k}'");
                    // }
                    if($cu >= 3) {
                        $am = 5 * pow(2, $i-1);
                        // add_faucet($s_id, 1, $coupon);
                        $details = "Earning from Auto Pool {$i}";
                        mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `plan_id`) VALUES ('{$detail}','{$s_id}','{$am}', 'autopool', '{$payment_method_id}', '{$i}')");
                        add_balance($s_id, $payment_method_id, $am);
                        $u1 = mysqli_query($DB_CONN, "SELECT parent from users where id = '{$s_id}' and parent");
                        if(mysqli_num_rows($u1)) {
                            $user1 = mysqli_fetch_assoc($u1);
                            $s_id = $user1['parent'];
                        } else
                            $s_id = 0;
                        if(!$s_id)
                            break;
                    } else
                        break;
                } else
                    break;
            }
            $p = power_leg($user['id']);
        }
    }
    $a = mysqli_query($DB_CONN, "UPDATE package_deposits set status = 1, amount = '{$deposit}', fee = '{$fee}', last_earningDateTime = CURRENT_TIMESTAMP, txn_id = '{$txn_id}' where id = '{$ID}'");
    $data = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from package_deposits where id = '{$ID}'"));
    $packname = $package['name'];
    if($package['etype'] == 0)
    $percentage = $plan['percent_max']."%";
    else
    $percentage = $plan['percent_min']."% ~ ".$plan['percent_max']."%";
    $det = str_replace(array("#plan#","#invested_amount#","#percent#"), array($packname ,fiat($deposit), $percentage), $deposit_settings['memo_invest']);
    if($a) {
        mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, fee, payment_method_id, txn_id, txn_type, ref_id, detail, rawdata) values('{$user_id}', '{$deposit}', '{$fee}', '{$payment_method_id}', '{$txn_id}', '{$txn_type}', '{$ID}', '{$det}', '{$hash}')");
        sendmail("invest_user_notification", $user_id, array(), array('amount' => $deposit, 'account' => $currency['name'], 'pack_name' => $package['name'], 'txn_id' => $txn_id, 'datetime' => $data['datetime'], 'compound' => $data['compound']));
        if($ref)
            refferal_commission($ID);
        sendcashback($details, $deposit, $user_id, $payment_method_id, $package_id);
        return $a;
    } else
    return false;
}

function calc_release($dep, $camount = 0) {
  global $DB_CONN;
  $a = true;
  $fee = 0;
  $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$dep['package_id']}'"));
  $details = json_decode($package['details'], true);
  if($package['allowprincipal']) {
    if(!$details['allowprincipalfull'] && $camount)
        $amount = $camount;
    else
        $amount = $dep['amount'];
    $a = false;
    if($details['principal_release_period']) {
      $dt = strtotime($dep['datetime'])+($details['principal_release_period']*86400);
      if($dt > time())
        $a = false;
    }
    if(!$a) {
      foreach ($details['depositduration'] as $key => $duration) {
        $duration = (int)$duration;
        if($duration > 0) {
          if((strtotime($dep['datetime'])+($duration*86400)) <= time()) {
            $fee = ($amount/100)*$details['withdrawalfee'][$key];
            break;
    }}}}}
  return array('a'=>$a, 'fee'=>$fee, 'amount'=>$amount, 'package' => $package);
}

function getcond($var) {
    $cond = "1";
    switch ($var) {
        case 'day':
            $cond = "DATE(created_at) = CURRENT_DATE";
            break;
        case 'week':
            $cond = "WEEK(created_at) = WEEK(CURRENT_DATE) and YEAR(created_at) = YEAR(CURRENT_DATE)";
            break;
        case 'month':
            $cond = "MONTH(created_at) = MONTH(CURRENT_DATE) and YEAR(created_at) = YEAR(CURRENT_DATE)";
            break;
        case 'year':
            $cond = "YEAR(created_at) = YEAR(CURRENT_DATE)";
            break;
    }
    return $cond;
}

function gettransfer($amount, $username, $payment_method_id)
{
    global $DB_CONN, $userinfo, $transfer_settings, $method;
    $a1 = true;
    $method = mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$payment_method_id}'");
    $method = mysqli_fetch_assoc($method);
    $fee = 0;
    if ($amount <= 0) {
      $a1 = false;
      call_alert(51); // 0 not allowed
    }
    if ($a1 && $method['transfer_min'] && !($amount >= $method['transfer_min'])) {
      $a1 = false;
      call_alert(74);  // Please check Min amount to Transfer in 
    }
    if ($a1 && $method['transfer_max'] && !($amount <= $method['transfer_max'])) {
      $a1 = false;
      call_alert(75); // Please check Max amount to Transfer in 
    }
    if($a1 && $transfer_settings['internal_transfer_limit'] && $transfer_settings['limit_period']) {
        $cond = getcond($transfer_settings['limit_period']);
        $c = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE txn_type = 'transfer' and user_id = '{$userinfo['id']}' and am_type = 'out' and {$cond}");
        if(mysqli_num_rows($c) >= $transfer_settings['internal_transfer_limit']) {
            $a1 = false;
            call_alert(78); //Transfer Limit Reached.
        }
    }
    if($method['transfer_fee_per'])
        $fee = $amount/100*$method['transfer_fee_per'];
    elseif($method['transfer_fee_amount'])
        $fee = $method['transfer_fee_amount'];
    if($fee) {
        if($fee < $method['transfer_fee_min'] && $method['transfer_fee_min'])
            $fee = $method['transfer_fee_min'];
        elseif($fee > $method['transfer_fee_max'] && $method['transfer_fee_max'])
            $fee = $method['transfer_fee_max'];
    }
    if($a1) {
        $abc = mysqli_query($DB_CONN, "select * from users where username = '{$_POST['transferto']}'");
        if(mysqli_num_rows($abc) == 0) {
            call_alert(76); //Invalid Username!
            $a1 = false;
        } else {
            $touser = mysqli_fetch_assoc($abc);
            $userid = $touser['id'];
        }
    }
    if($fee && $transfer_settings['internal_transfer_fee_payer'] == 'payer')
        $amount += $fee;
    if($a1 && $userinfo['balances'][$payment_method_id] < $amount) {
        call_alert(59); //Insufficient Balance!
        $a1 = false;
    }
    return array('a1'=>$a1, 'fee'=>$fee, 'userid' => $userid, 'touser' => $touser);
}

function confirm_deposit($ID, $deposit, $symbol, $txn_id, $hash = '', $address = '', $confs = 0) {
    global $DB_CONN, $dt, $deposit_settings;
    if($hash)
        $hash = encrypt_decrypt('encrypt', $hash);
    $deposit = tocurrency($deposit, $symbol);
    $currency = mysqli_query($DB_CONN, "SELECT * from currencies where symbol = '{$symbol}'");
    $currency = mysqli_fetch_assoc($currency);
    $bal_id = substr($ID, 0, 1);
    $table_name = "package_deposits";
    if($bal_id == 'b')
        $table_name = "transactions";
    if($address)
        mysqli_query($DB_CONN, "UPDATE `$table_name` set txn_id = '{$txn_id}', confs = '{$confs}' where address = '{$address}'");
    if($bal_id == 'b') {
        $ID = ltrim($ID, $bal_id);
        $query = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE id = '{$ID}' and status IN (0, 2)");
        if(mysqli_num_rows($query) > 0) {
            $plan=mysqli_fetch_assoc($query);
            $user_id = $plan['user_id'];
            mysqli_query($DB_CONN, "UPDATE `transactions` set status = 1, amount = '{$deposit}', rawdata = '{$hash}', txn_id = '{$txn_id}' where id = '{$ID}'");
            add_balance($user_id, $currency['id'], $deposit);
            sendmail("deposit_user_notification", $user_id, array(), array('amount' => $deposit, 'account' => $currency['name'], 'txn_id' => $txn_id, 'datetime' => $dt));
            return true;
        }
        return false;
    } 
    $query = mysqli_query($DB_CONN,"SELECT * from package_deposits where id='$ID' AND status IN (0, 2)");
    if(mysqli_num_rows($query) > 0) {
      $plan=mysqli_fetch_assoc($query);
      $user_id = $plan['user_id'];
      $pp = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `package_plans` where id = '{$plan['plan_id']}'"));
      $pp['min'] = $pp['range_min'];
      $pp['max'] = $pp['range_max'];
      $pa = check_deposit($deposit, $plan, $pp, $user_id, $currency, $plan['payment_method_id'], true);
      $eligible = $pa[0];
      $fee = $pa[2];
      $deposit = $deposit-$fee;
      if($deposit >= $pp['range_min'] && $deposit <= $pp['range_max'] && $eligible) {
        activate_package($ID, $deposit, $fee, $txn_id, $plan['user_id'], $plan['payment_method_id'], $plan['package_id'],$plan['plan_id'], $currency, $hash);
        return true;
      } else {
        if($deposit_settings['partial_payments_balance']){
            add_balance($user_id, $currency['id'], $deposit);
            $query3 = mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, fee, payment_method_id, txn_id, txn_type, ref_id, rawdata)
                values('{$user_id}', '{$deposit}', '{$fee}', '{$currency['id']}', '{$txn_id}', 'deposit', '{$ID}', '{$hash}')");
            sendmail("deposit_user_notification", $user_id, array(), array('amount' => $deposit, 'account' => $currency['name'], 'txn_id' => $txn_id, 'datetime' => $dt));
            return true;
        }
      }
    }
    return false;
}

function generateSecureToken($identifier, $is_telegram = true) { 
    global $telegram_settings, $tgtoken, $access_key, $ip, $security_settings;
    $timestamp = time();
    $user_ip = $ip;
    $session_timeout = isset($security_settings['admin_session']) ? 
                      (intval($security_settings['admin_session']) * 60) : 60;
    $payload = [
        'timestamp' => $timestamp,
        'nonce' => bin2hex(random_bytes(16)),
        'ip' => $user_ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'valid_until' => time() + $session_timeout,
        'auth_type' => $is_telegram ? 'telegram' : 'admin'
    ];

    if ($is_telegram) {
        $payload['user_id'] = $telegram_settings['personal_id'];
        $payload['telegram_id'] = $telegram_settings['personal_id'];
        $payload['message_id'] = $identifier;
    } else {
        $payload['user_id'] = $identifier;
    }

    $encoded_payload = base64_encode(json_encode($payload));
    $signature_base = $encoded_payload;
    
    if ($is_telegram) {
        $signature_base .= $tgtoken . $telegram_settings['personal_id'];
    } else {
        $signature_base .= $identifier;
    }
    
    $signature = hash_hmac('sha256', $signature_base, $access_key);
    $verification_key = substr($signature, 0, 8);
    
    return [
        'token' => $encoded_payload . '.' . $signature,
        'verification_key' => $verification_key
    ];
}

function validateSecureAccess($token, $verification_key) {
   global $DB_CONN, $telegram_settings, $tgtoken, $ip, $access_key, $admin_settings;
   
   $parts = explode('.', $token);
   if (count($parts) !== 2) {
       return false;
   }
   
   list($encoded_payload, $signature) = $parts;
   $payload = json_decode(base64_decode($encoded_payload), true);
   
   if (!$payload) {
       return false;
   }

   $is_telegram = ($payload['auth_type'] === 'telegram');
   $signature_base = $encoded_payload;
   
   if ($is_telegram) {
       $signature_base .= $tgtoken . $telegram_settings['personal_id'];
   } else {
       $signature_base .= $payload['user_id'];
   }
   
   $expected_signature = hash_hmac('sha256', $signature_base, $access_key);
   
   if (!hash_equals($signature, $expected_signature)) {
       return false;
   }
   
   if (!hash_equals(substr($signature, 0, 8), $verification_key)) {
       return false;
   }
   
   if (time() > $payload['valid_until']) {
       return false;
   }
   
   if ($payload['ip'] !== $_SERVER['REMOTE_ADDR']) {
       if($admin_settings['logout_on_ip_change']) {
           return false;
       }
   }
   
   if ($payload['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
       if($admin_settings['logout_on_browser_change']) {
           return false;
       }
   }

   if ($is_telegram) {
    // First verify this is from the authorized Telegram account
    if ($payload['telegram_id'] !== $telegram_settings['personal_id']) {
        return false;
    }
    
    // Map the Telegram user to an admin user account
    $admin = mysqli_query($DB_CONN, "SELECT * FROM users WHERE is_admin = 1 LIMIT 1");
    if (!$admin || mysqli_num_rows($admin) === 0) {
        return false;
    }
    
    // Use the actual admin user's ID instead of the Telegram ID
    $admin_data = mysqli_fetch_assoc($admin);
    $user_id = $admin_data['id']; // This will be a valid user ID from your database
} else {
    $admin = mysqli_query($DB_CONN, "SELECT * FROM users WHERE id = " . intval($payload['user_id']) . " AND is_admin = 1");
    if (!$admin || mysqli_num_rows($admin) === 0) {
        return false;
    }
    $user_id = intval($payload['user_id']);
}
   
  
   $useragent = $_SERVER['HTTP_USER_AGENT'];
   $refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ($is_telegram ? 'TELEGRAM' : 'ADMIN');
   $browser = getBrowser($useragent);
   $os = getOS($useragent);
   $country = '';
   $city = '';
   
   $stmt = mysqli_prepare($DB_CONN, "INSERT INTO login_report (ip, useragent, refer, os, country, city, browser, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
   mysqli_stmt_bind_param($stmt, "sssssssi", $ip, $useragent, $refer, $os, $country, $city, $browser, $user_id);
   mysqli_stmt_execute($stmt);
   mysqli_stmt_close($stmt);
   
   session_regenerate_id(true);
   $_SESSION['admin_chk'] = $user_id;
   $_SESSION['last_activity'] = time();

   if ($is_telegram) {
       $update_data = [
            'chat_id' => $telegram_settings['personal_id'],
            'message_id' => $payload['message_id'],
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Successfully Logged In ✓',
                            'callback_data' => 'logged_in'
                        ],
                        [
                            'text' => '🗑 Dismiss',
                            'callback_data' => 'dismiss_message'
                        ]
                    ]
                ]
            ])
        ];
       
       file_get_contents("https://api.telegram.org/bot$tgtoken/editMessageReplyMarkup?" . http_build_query($update_data));
   }
   
   return true;
}
function is_image($path)
{
    $a = getimagesize($path);
    $image_type = $a[2];
    if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP)) && $a[0])
        return true;
    return false;
}
function upload_image($string) {
    global $data, $userinfo;
    $tmp_file = $_FILES[$string]['tmp_name'];
    if(is_image($tmp_file)) {
      $name = "kyc_data/".$userinfo['user_id']."_".mt_rand(10,10000)."_".$_FILES[$string]["name"];
      if(move_uploaded_file($tmp_file, $name))
        $data[$string]=$name;
      else
        call_alert(65); //Some Error. Please try again
    } else
      call_alert(86); //Not a valid image. please use Jpg or png format ony.
}
function upload_a_image($string) {
    global $data;
    $tmp_file = $_FILES[$string]['tmp_name'];
    if(is_image($tmp_file)) {
        $file_name = $_FILES[$string]['name'];
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $target_file = 'images/' . $unique_filename;
        move_uploaded_file($tmp_file, $target_file);
        return array('file_path' => $target_file, 'file_name' => $unique_filename);
    }
    return false; // or some error message
}
function getFileFromFileId($file_id, $file_type, $file_metadata = null) {
    global $tgtoken;
    
    try {
        $get_file_url = "https://api.telegram.org/bot{$tgtoken}/getFile?file_id={$file_id}";
        $file_data = json_decode(file_get_contents($get_file_url), true);
        
        if(isset($file_data['result']['file_path'])) {
            $file_url = "https://api.telegram.org/file/bot{$tgtoken}/" . $file_data['result']['file_path'];
            $metadata = is_string($file_metadata) ? json_decode($file_metadata, true) : $file_metadata;
            
            return [
                'url' => $file_url,
                'type' => $file_type,
                'metadata' => $metadata,
                'path' => $file_data['result']['file_path'],
                'size' => $file_data['result']['file_size'] ?? null
            ];
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}
function getUserProfilePhotos($user_id, $limit = 1) {
    global $tgtoken;
    
    // Use the main token from the array
    $token = $tgtoken['main_token'];
    
    $url = "https://api.telegram.org/bot{$token}/getUserProfilePhotos?user_id={$user_id}&limit={$limit}";
    
    // Use curl instead of file_get_contents for better error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $response = json_decode($response, true);
        
        if(isset($response['result']['photos']) && !empty($response['result']['photos'])) {
            // Get the first (most recent) profile photo
            $photo = $response['result']['photos'][0]; // This is an array of different sizes
            $file_id = $photo[count($photo)-1]['file_id']; // Get largest size
            
            // Get the actual photo URL
            return getPhotoFromFileId($file_id);
        }
    }
    
    return false;
}

function getPhotoFromFileId($file_id) {
    global $tgtoken;
    
    // Use the main token from the array
    $token = $tgtoken['main_token'];
    
    // First, get the file path
    $url = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $response = json_decode($response, true);
        
        if(isset($response['result']['file_path'])) {
            // Return the full URL to the photo
            return "https://api.telegram.org/file/bot{$token}/" . $response['result']['file_path'];
        }
    }
    
    return false;
}
function getTelegramUserInfo($id){
    global $DB_CONN;
    $stmt = $DB_CONN->prepare("SELECT username, first_name, last_name, bio, photo_path, language_code, is_premium, first_seen, last_seen FROM telegram_users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the data as an associative array
    $user_info = $result->fetch_assoc();
    $stmt->close();

    return $user_info;
}
function getUserProfileInfo($userId) {
    // Use the existing sendTelegramRequest function for consistency
    $response = sendTelegramRequest('getChat', [
        'chat_id' => $userId
    ]);
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData['result'])) {
        $user = $responseData['result'];
        $photoId = isset($user['photo']) ? ($user['photo']['small_file_id'] ?? '') : '';
        
        if ($photoId) {
            $localPath = downloadAndSavePhoto($photoId);
        }
        
        return [
            'bio' => $user['bio'] ?? '',
            'photo_id' => $photoId,
            'photo_path' => $localPath ?? '',
        ];
    }
    
    return [
        'bio' => '', 
        'photo_id' => '', 
        'photo_path' => ''
    ];
}

function downloadAndSavePhoto($fileId) {
    global $tgtoken;
    
    // Get file path from Telegram
    $fileInfo = file_get_contents("https://api.telegram.org/bot{$tgtoken}/getFile?file_id={$fileId}");
    $fileInfo = json_decode($fileInfo, true);
    
    if (!isset($fileInfo['result']['file_path'])) {
        return false;
    }
    
    // Generate local file path
    $uploadDir = 'images/telegram/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($fileInfo['result']['file_path']);
    $localPath = $uploadDir . $fileName;
    
    // Download and save file
    $photoUrl = "https://api.telegram.org/file/bot{$tgtoken}/" . $fileInfo['result']['file_path'];
    $photoContent = file_get_contents($photoUrl);
    
    if (file_put_contents($localPath, $photoContent)) {
        return $localPath;
    }
    
    return false;
}

function handleImageUpload($fieldName, $uploadDir, $currentImage = null, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    $result = [
        'status' => 'unchanged',
        'message' => '',
        'filename' => $currentImage
    ];

    // Check if a file was uploaded
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] == 0) {
        $file = $_FILES[$fieldName];
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check if file type is allowed
        if (!in_array($fileType, $allowedTypes)) {
            $result['status'] = 'error';
            $result['message'] = "Sorry, only " . implode(", ", $allowedTypes) . " files are allowed.";
            return $result;
        }

        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($file['name']));
        $targetPath = $uploadDir . $filename;

        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Upload the file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // If there was a previous image, delete it
            if ($currentImage && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentImage)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $currentImage);
            }
            $result['status'] = 'updated';
            $result['message'] = "The file " . $filename . " has been uploaded.";
            $result['filename'] = '/images/' . $filename;
        } else {
            $result['status'] = 'error';
            $result['message'] = "Sorry, there was an error uploading your file.";
        }
    } elseif (isset($_POST[$fieldName . '_remove']) && $_POST[$fieldName . '_remove'] == '1') {
        // Image removal requested
        if ($currentImage && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentImage)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $currentImage);
            $result['status'] = 'removed';
            $result['message'] = "The file has been removed.";
            $result['filename'] = null;
        }
    }

    return $result;
}
function checkTelegramMembership($type, $userinfo, $telegram_settings, $context = 'check') {
    global $DB_CONN;
    
    // For task verification (handleTasks('check')), use Telegram API
    if ($context === 'check') {
        $chat_id = ($type === 'group') ? $telegram_settings['group_id'] : $telegram_settings['channel_id'];
        $response = json_decode(sendTelegramRequest('getChatMember', [
            'chat_id' => $chat_id,
            'user_id' => $userinfo['oauth_uid']
        ]), true);
        
        if ($response['ok']) {
            $is_member = in_array($response['result']['status'], ['member', 'administrator', 'creator']);
            
            // Update the membership status in database
            $membership_field = ($type === 'group') ? 'group_member' : 'channel_member';
            $status_value = $is_member ? 1 : 0;

            mysqli_query($DB_CONN,
                "INSERT INTO telegram_users (user_id, {$membership_field}) 
                VALUES ('{$userinfo['oauth_uid']}', {$status_value})
                ON DUPLICATE KEY UPDATE {$membership_field} = {$status_value}"
            );
            
            return $is_member;
        }
        return false;
    } 
    // For list/get_taps context, check database
    else {
        $membership_field = ($type === 'group') ? 'group_member' : 'channel_member';
        
        $query = mysqli_query($DB_CONN,
            "SELECT {$membership_field} FROM telegram_users 
            WHERE user_id = '{$userinfo['oauth_uid']}' 
            LIMIT 1"
        );
        
        if ($query && $row = mysqli_fetch_assoc($query)) {
            return $row[$membership_field] == 1;
        }
        return false;
    }
}


function SystemTasks($action = 'list', $specific_task = null, $task_settings = null, $context = 'list') {
    global $DB_CONN, $userinfo, $telegram_settings;
   
    $available_tasks = [
        'group' => [
        'name' => 'Join Telegram Group',
        'description' => 'User will need to join your Telegram Group you entered in your Telegram Manager',
        'duration_supported' => false,
        'check' => function() use ($userinfo, $telegram_settings, $context) {
            return checkTelegramMembership('group', $userinfo, $telegram_settings, $context);
        }
    ],
    'channel' => [
        'name' => 'Join Telegram Channel',
        'description' => 'User will need to join your Telegram Channel you entered in your Telegram Manager',
        'duration_supported' => false,
        'check' => function() use ($userinfo, $telegram_settings, $context) {
            return checkTelegramMembership('channel', $userinfo, $telegram_settings, $context);
        }
    ],
        'post' => [
            'name' => 'Group Post Activity',
            'description' => 'Post message in your Telegram Group you entered in your Telegram Manager',
            'duration_supported' => true,
            'check' => function($settings) use ($DB_CONN, $userinfo, $telegram_settings) {
                $duration_clause = "";
                if (!empty($settings['limit_duration'])) {
                    switch($settings['limit_duration']) {
                        case 'day':
                            $duration_clause = "AND DATE(datetime) = CURRENT_DATE";
                            break;
                        case 'week':
                            $duration_clause = "AND YEARWEEK(datetime) = YEARWEEK(CURRENT_DATE)";
                            break;
                        case 'month':
                            $duration_clause = "AND YEAR(datetime) = YEAR(CURRENT_DATE) AND MONTH(datetime) = MONTH(CURRENT_DATE)";
                            break;
                        case 'year':
                            $duration_clause = "AND YEAR(datetime) = YEAR(CURRENT_DATE)";
                            break;
                    }
                }
                
                $query = mysqli_query($DB_CONN, 
                    "SELECT COUNT(*) as count FROM `telegram` 
                    WHERE user_id = '{$userinfo['oauth_uid']}' 
                    AND chat_id = '{$telegram_settings['group_id']}' 
                    AND type = 'supergroup' 
                    {$duration_clause}"
                );
                return ($query && mysqli_fetch_assoc($query)['count'] > 0);
            }
        ],
        'comment' => [
            'name' => 'Channel Comment Activity',
            'description' => 'Comment on channel posts of your channel you entered in your Telegram Manager',
            'duration_supported' => true,
            'check' => function($settings) use ($DB_CONN, $userinfo, $telegram_settings) {
                $duration_clause = "";
                if (!empty($settings['limit_duration'])) {
                    switch($settings['limit_duration']) {
                        case 'day':
                            $duration_clause = "AND DATE(datetime) = CURRENT_DATE";
                            break;
                        case 'week':
                            $duration_clause = "AND YEARWEEK(datetime) = YEARWEEK(CURRENT_DATE)";
                            break;
                        case 'month':
                            $duration_clause = "AND YEAR(datetime) = YEAR(CURRENT_DATE) AND MONTH(datetime) = MONTH(CURRENT_DATE)";
                            break;
                        case 'year':
                            $duration_clause = "AND YEAR(datetime) = YEAR(CURRENT_DATE)";
                            break;
                    }
                }

                $query = mysqli_query($DB_CONN, 
                    "SELECT COUNT(*) as count FROM `telegram` 
                    WHERE user_id = '{$userinfo['oauth_uid']}' 
                    AND reply = '{$telegram_settings['channel_id']}' 
                    {$duration_clause}"
                );
                return ($query && mysqli_fetch_assoc($query)['count'] > 0);
            }
        ], 
        'investment' => [
                'name' => 'Investment Check',
                'description' => 'Check if user has made investments meeting specified criteria',
                'duration_supported' => true,
                'check' => function($settings) use ($DB_CONN, $userinfo) {
                    $duration_clause = "";
                    if (!empty($settings['limit_duration'])) {
                        switch($settings['limit_duration']) {
                            case 'day':
                                $duration_clause = "AND DATE(created_at) = CURRENT_DATE";
                                break;
                            case 'week':
                                $duration_clause = "AND YEARWEEK(created_at) = YEARWEEK(CURRENT_DATE)";
                                break;
                            case 'month':
                                $duration_clause = "AND YEAR(created_at) = YEAR(CURRENT_DATE) AND MONTH(created_at) = MONTH(CURRENT_DATE)";
                                break;
                        }
                    }
                    $amount_clause = !empty($settings['min_amount']) ? 
                        "AND amount >= '{$settings['min_amount']}'" : "";
                    $currency_clause = !empty($settings['currency_id']) ? 
                        "AND payment_method_id = '{$settings['currency_id']}'" : "";
                    $package_clause = !empty($settings['package_id']) ? 
                    "AND package_id = '{$settings['package_id']}'" : "";
                    $query = mysqli_query($DB_CONN, 
                        "SELECT COUNT(*) as count 
                        FROM package_deposits 
                        WHERE user_id = '{$userinfo['id']}' 
                        AND status = 1
                        {$duration_clause}
                        {$amount_clause}
                        {$currency_clause}
                        {$package_clause}"
                    );
                    if (!$query) {
                        error_log("Database error in task investment: " . mysqli_error($DB_CONN));
                        return false;
                    }
                    return ($query && mysqli_fetch_assoc($query)['count'] > 0);
                }
            ],
            
            'withdrawal' => [
                'name' => 'Withdrawal Check',
                'description' => 'Check if user has made withdrawals meeting specified criteria',
                'duration_supported' => true,
                'check' => function($settings) use ($DB_CONN, $userinfo) {
                    $duration_clause = "";
                    if (!empty($settings['limit_duration'])) {
                        switch($settings['limit_duration']) {
                            case 'day':
                                $duration_clause = "AND DATE(timestamp) = CURRENT_DATE";
                                break;
                            case 'week':
                                $duration_clause = "AND YEARWEEK(timestamp) = YEARWEEK(CURRENT_DATE)";
                                break;
                            case 'month':
                                $duration_clause = "AND YEAR(timestamp) = YEAR(CURRENT_DATE) AND MONTH(timestamp) = MONTH(CURRENT_DATE)";
                                break;
                        }
                    }
            
                    $amount_clause = !empty($settings['min_amount']) ? 
                        "AND amount >= '{$settings['min_amount']}'" : "";
            
                    $query = mysqli_query($DB_CONN, 
                        "SELECT COUNT(*) as count FROM transactions 
                        WHERE user_id = '{$userinfo['id']}' 
                        AND txn_type = 'withdraw'
                        AND status = 1
                        {$duration_clause}
                        {$amount_clause}"
                    );
                    
                    return ($query && mysqli_fetch_assoc($query)['count'] > 0);
                }
            ],
            
           'referral' => [
    'name' => 'Referral Check',
    'description' => 'Check if user has referred others meeting specified criteria',
    'duration_supported' => true,
    'check' => function($settings) use ($DB_CONN, $userinfo) {
        if (!empty($settings['min_referrals'])) {
            $ref_query = mysqli_query($DB_CONN, 
                "SELECT COUNT(*) as count FROM users 
                WHERE sponsor = '{$userinfo['id']}'"
            );
            $ref_count = mysqli_fetch_assoc($ref_query)['count'];
            if ($ref_count < $settings['min_referrals']) {
                return false;
            }
        }

        $duration_clause = "";
        if (!empty($settings['limit_duration'])) {
            switch($settings['limit_duration']) {
                case 'day':
                    $duration_clause = "AND DATE(timestamp) = CURRENT_DATE";
                    break;
                case 'week':
                    $duration_clause = "AND YEARWEEK(timestamp) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $duration_clause = "AND YEAR(timestamp) = YEAR(CURRENT_DATE) AND MONTH(timestamp) = MONTH(CURRENT_DATE)";
                    break;
            }
        }

        $amount_clause = !empty($settings['min_amount']) ? 
            "HAVING total_amount >= '{$settings['min_amount']}'" : "";

        $query = mysqli_query($DB_CONN, 
            "SELECT SUM(amount) as total_amount 
            FROM transactions 
            WHERE user_id = '{$userinfo['id']}' 
            AND txn_type = 'referral'
            AND status = 1
            {$duration_clause}
            {$amount_clause}"
        );
        
        return ($query && mysqli_fetch_assoc($query)['total_amount'] > 0);
    }
],
           'daily_earnings' => [
    'name' => 'Daily Earnings Check',
    'description' => 'Check if user has earned a specified minimum amount today',
    'duration_supported' => true,
    'check' => function($settings) use ($DB_CONN, $userinfo) {
        $amount_clause = !empty($settings['min_amount']) ? 
            "HAVING total_amount >= '{$settings['min_amount']}'" : "";

        $query = mysqli_query($DB_CONN, 
            "SELECT SUM(amount) as total_amount 
            FROM transactions 
            WHERE user_id = '{$userinfo['id']}' 
            AND txn_type = 'earning' 
            AND DATE(timestamp) = CURDATE()
            {$amount_clause}"
        );
        
        return ($query && mysqli_fetch_assoc($query)['total_amount'] > 0);
    }
],
        
      'faucet_claims' => [
    'name' => 'Faucet Claims Check',
    'description' => 'Check if user has claimed specified amount from faucets',
    'duration_supported' => true,
    'check' => function($settings) use ($DB_CONN, $userinfo) {
        $duration_clause = "";
        if (!empty($settings['limit_duration'])) {
            switch($settings['limit_duration']) {
                case 'day':
                    $duration_clause = "AND DATE(timestamp) = CURRENT_DATE";
                    break;
                case 'week':
                    $duration_clause = "AND YEARWEEK(timestamp) = YEARWEEK(CURRENT_DATE)";
                    break;
                case 'month':
                    $duration_clause = "AND YEAR(timestamp) = YEAR(CURRENT_DATE) AND MONTH(timestamp) = MONTH(CURRENT_DATE)";
                    break;
            }
        }

        $amount_clause = !empty($settings['min_amount']) ? 
            "HAVING total_amount >= '{$settings['min_amount']}'" : "";

        $query = mysqli_query($DB_CONN, 
            "SELECT SUM(amount) as total_amount 
            FROM transactions 
            WHERE user_id = '{$userinfo['id']}' 
            AND txn_type = 'faucet'
            AND status = 1
            {$duration_clause}
            {$amount_clause}"
        );
        
        return ($query && mysqli_fetch_assoc($query)['total_amount'] > 0);
    }
],
'account_balance' => [
    'name' => 'Account Balance Check',
    'description' => 'Check if user maintains a minimum balance across all currencies',
    'duration_supported' => false,
    'check' => function($settings) use ($userinfo) {
        $min_balance = !empty($settings['min_amount']) ? $settings['min_amount'] : 0;
        return $userinfo['accountbalance'] >= $min_balance;
    }
],
    'kyc_verification' => [
        'name' => 'KYC Verification Check',
        'description' => 'Check if user has completed KYC verification',
        'duration_supported' => false,
        'check' => function($settings) use ($userinfo) {
            return isset($userinfo['kyc']) && $userinfo['kyc'] == 1;
        }
    ],
    
'active_investment' => [
    'name' => 'Active Investment Check',
    'description' => 'Check if user has active investments meeting the minimum amount',
    'duration_supported' => false,
    'check' => function($settings) use ($userinfo) {
        $min_investment = !empty($settings['min_amount']) ? $settings['min_amount'] : 0;
        return $userinfo['investments_active'] >= $min_investment;
    }
],
  'currency_specific_balance' => [
    'name' => 'Currency Balance Check',
    'description' => 'Check if user maintains minimum balance in specific currency',
    'duration_supported' => false,
    'check' => function($settings) use ($userinfo) {
    if (empty($settings['currency_id']) || empty($settings['min_amount'])) {
        return false;
    }
        
        $currency_id = $settings['currency_id'];
        $min_amount = $settings['min_amount'];
        
        return isset($userinfo['balances'][$currency_id]) && 
               $userinfo['balances'][$currency_id] >= $min_amount;
    }
],
    
    'daily_checkin' => [
        'name' => 'Daily Check-in',
        'description' => 'Check if user has performed daily check-in',
        'duration_supported' => false,
        'check' => function($settings) use ($userinfo) {
            return isset($userinfo['dailycheckin']) && 
                   date('Y-m-d', strtotime($userinfo['dailycheckin'])) === date('Y-m-d');
        }
    ],
    
'tier_level' => [
    'name' => 'Tier Level Check',
    'description' => 'Check if user has reached specific tier level',
    'duration_supported' => false,
    'check' => function($settings) use ($userinfo) {
        $required_tier = !empty($settings['min_tier']) ? $settings['min_tier'] : 1;
        return isset($userinfo['tier']) && $userinfo['tier'] >= $required_tier;
    }
]
    ];
  
     
    switch ($action) {
        case 'list':
            return array_map(function($task, $key) {
                return [
                    'id' => $key,
                    'name' => $task['name'],
                    'description' => $task['description'],
                    'duration_supported' => $task['duration_supported']
                ];
            }, $available_tasks, array_keys($available_tasks));

        case 'check':
            if ($specific_task) {
                if (isset($available_tasks[$specific_task])) {
                    return [
                        'task' => $specific_task,
                        'completed' => $available_tasks[$specific_task]['check']()
                    ];
                }
                return ['error' => 'Task not found'];
            }
            
            $results = [];
            foreach ($available_tasks as $task_id => $task) {
                $results[$task_id] = [
                    'completed' => $task['check']($task_settings[$task_id] ?? [])
                ];
            }
            return $results;
    }
}
function handleTasks($action = 'list', $task_id = null, $params = null) {
    global $DB_CONN, $userinfo;
    
    $checkDurationValidity = function($timestamp, $duration) {
        $current_timestamp = time();
        switch($duration) {
            case 'day':
                return date('Y-m-d', $timestamp) !== date('Y-m-d', $current_timestamp);
            case 'week':
                return date('oW', $timestamp) !== date('oW', $current_timestamp);
            case 'month':
                return date('Y-m', $timestamp) !== date('Y-m', $current_timestamp);
            case 'year':
                return date('Y', $timestamp) !== date('Y', $current_timestamp);
            default:
                return true;
        }
    };

    $processCompletedTasks = function() use ($DB_CONN, $userinfo, &$checkDurationValidity) {
        $validation_results = [];
        $valid_task_ids = [];
        $total_taps = 0;

        $handleReversal = function($task, $task_details, $current_validation) use ($DB_CONN, $userinfo) {
            $total_reversed_amount = 0;
            $reversed_currencies = [];
            
            // Get all transactions related to this application
            $transactions_query = mysqli_query($DB_CONN,
                "SELECT * FROM transactions 
                 WHERE ref_id = '{$task['id']}' 
                 AND (txn_type = 'bonus' OR txn_type = 'faucet')"
            );

            while ($transaction = mysqli_fetch_assoc($transactions_query)) {
                if ($transaction['txn_type'] === 'bonus' && 
                    isset($task_details['account_balance_reversal_enabled']) && 
                    $task_details['account_balance_reversal_enabled'] === 'true') {
                    
                    // Reverse account balance transaction
                    $amount = -$transaction['amount'];
                    add_balance($userinfo['id'], $transaction['payment_method_id'], $amount);
                    
                    $det = "Task reward reversal - conditions no longer met";
                    mysqli_query($DB_CONN, 
                        "INSERT INTO transactions (user_id, amount, payment_method_id, txn_type, ref_id, detail, am_type) 
                         VALUES ('{$userinfo['id']}', '{$amount}', '{$transaction['payment_method_id']}','bonus', '{$task['id']}', '{$det}', 'out')"
                    );
                    
                    $total_reversed_amount += abs($amount);
                    $reversed_currencies[$transaction['payment_method_id']] = isset($reversed_currencies[$transaction['payment_method_id']]) 
                        ? $reversed_currencies[$transaction['payment_method_id']] + abs($amount)
                        : abs($amount);
                }
                
                if ($transaction['txn_type'] === 'faucet' && 
                    isset($task_details['faucet_reversal_enabled']) && 
                    $task_details['faucet_reversal_enabled'] === 'true') {
                    
                    // Reverse faucet transaction
                    $amount = -$transaction['amount'];
                    add_faucet($userinfo['id'], $transaction['payment_method_id'], $amount);
                    
                    $det = "Faucet reward reversal - conditions no longer met";
                    mysqli_query($DB_CONN,
                        "INSERT INTO transactions (user_id, amount, payment_method_id, txn_type, ref_id, detail, am_type) 
                         VALUES ('{$userinfo['id']}', '{$amount}', '{$transaction['payment_method_id']}','faucet', '{$task['id']}', '{$det}' , 'out')"
                    );
                    
                    $total_reversed_amount += abs($amount);
                    $reversed_currencies[$transaction['payment_method_id']] = isset($reversed_currencies[$transaction['payment_method_id']]) 
                        ? $reversed_currencies[$transaction['payment_method_id']] + abs($amount)
                        : abs($amount);
                }
            }

            // Create reversal summary for comment
            $reversal_summary = [];
            foreach ($reversed_currencies as $currency => $amount) {
                $reversal_summary[] = fiat($amount) . " " . $currency;
            }
            $reversal_details = implode(", ", $reversal_summary);
            $comment = "Task conditions no longer met - rewards reversed (Total: " . $reversal_details . ")";
            
            // Use the current validation state that caused the reversal
            $result_details = [];
            foreach ($task_details['tasks'] as $task_type => $settings) {
                if (isset($settings['enabled']) && $settings['enabled'] === 'true') {
                    if (isset($current_validation[$task_type])) {
                        $result_details[$task_type] = $current_validation[$task_type]['completed'];
                    }
                }
            }
            $result_json = json_encode($result_details);
            
            mysqli_query($DB_CONN, 
                "UPDATE applications 
                 SET status = 0, 
                     result = '{$result_json}',
                     comment = '{$comment}'
                 WHERE id = '{$task['id']}'"
            );
        };

        $completed_tasks = mysqli_query($DB_CONN, 
            "SELECT a.*, t.details as task_details, t.id as task_id
             FROM applications a
             JOIN tasks t ON a.task_id = t.id
             WHERE a.user_id = '{$userinfo['id']}'
             AND a.status = 1"
        );

        while ($task = mysqli_fetch_assoc($completed_tasks)) {
            $task_details = json_decode($task['task_details'], true);
            $completion_result = json_decode($task['result'], true);
            
            if (!$task_details || !$completion_result) {
                continue;
            }

            $task_settings = [];
            if (isset($task_details['tasks'])) {
                foreach ($task_details['tasks'] as $task_type => $settings) {
                    if (isset($settings['enabled']) && $settings['enabled'] === 'true') {
                        $task_settings[$task_type] = $settings;
                    }
                }
            }

            $current_validation = SystemTasks('check', null, $task_settings, 'list');
            
            $task_still_valid = true;
            $duration_valid = true;

            // Check duration validity if specified
            if (isset($task_details['duration'])) {
                $duration_valid = $checkDurationValidity(strtotime($task['timestamp']), $task_details['duration']);
            }

            // Check task completion validity
            foreach ($completion_result as $task_type => $was_completed) {
                if ($was_completed && isset($current_validation[$task_type])) {
                    if (!$current_validation[$task_type]['completed']) {
                        $task_still_valid = false;
                        break;
                    }
                }
            }

            if (!$task_still_valid || !$duration_valid) {
                $handleReversal($task, $task_details, $current_validation);
            } else {
                $valid_task_ids[] = $task['task_id'];
                if (isset($task_details['taps_enabled']) && $task_details['taps_enabled'] == 'true') {
                    $total_taps += intval($task_details['taps_amount']);
                }
            }
        }

        return ['valid_tasks' => $valid_task_ids, 'total_taps' => $total_taps];
    };

    switch ($action) {
        case 'available':
            return SystemTasks('list', null, null, 'list');
            
       case 'get_data':
            // Combined processing for both tasks and taps
            $completedTasksResults = $processCompletedTasks();
            
            // Get all active tasks with completion status
            $task_query = mysqli_query($DB_CONN, 
                "SELECT t.*, a.timestamp as last_completion_time, a.status as completion_status
                 FROM tasks t 
                 LEFT JOIN (
                     SELECT task_id, timestamp, status
                     FROM applications 
                     WHERE user_id = '{$userinfo['id']}'
                     AND status = 1
                     AND timestamp = (
                         SELECT MAX(timestamp)
                         FROM applications a2
                         WHERE a2.task_id = applications.task_id
                         AND a2.user_id = '{$userinfo['id']}'
                         AND a2.status = 1
                     )
                 ) a ON t.id = a.task_id
                 WHERE t.status = '1' 
                 AND t.translation_of is null");

            $userTasks = [];
            while ($task = mysqli_fetch_assoc($task_query)) {
                $task_details = json_decode($task['details'], true);
                $completion_status = 0;
                
                if (isset($task['last_completion_time'])) {
                    if (isset($task_details['duration'])) {
                        if ($checkDurationValidity(strtotime($task['last_completion_time']), $task_details['duration'])) {
                            $completion_status = 0;
                        } else {
                            $completion_status = 1;
                        }
                    } else {
                        $completion_date = date('Y-m-d', strtotime($task['last_completion_time']));
                        $current_date = date('Y-m-d');
                        
                        $completion_status = ($completion_date === $current_date) ? 1 : 0;
                    }
                }
                
                $userTasks[] = [
                    'id' => $task['id'],
                    'title' => $task['name'],
                    'description' => $task['content'],
                    'type' => $task['type'],
                    'status' => $completion_status
                ];
            }

            return [
                'tasks' => $userTasks,
                'total_taps' => $completedTasksResults['total_taps']
            ];
            
        case 'check':
            if (!$task_id) {
                call_alert(1266);
                return ['status' => 'error'];
            }
    
            $task_query = mysqli_query($DB_CONN, "SELECT * FROM tasks WHERE id = '{$task_id}'");
            $task = mysqli_fetch_assoc($task_query);
            
            if (!$task) {
                call_alert(1266);
                return ['status' => 'error'];
            }
            
            if ($task['type'] == '2') {
                $task_details = json_decode($task['details'], true);
                $validation_results = SystemTasks('check', null, $task_details['tasks'], 'check');
                
                $all_completed = true;
                $result_details = [];
                foreach ($task_details['tasks'] as $task_type => $task_settings) {
                    if (isset($task_settings['enabled']) && $task_settings['enabled'] === 'true') {
                        if (isset($validation_results[$task_type])) {
                            $result_details[$task_type] = $validation_results[$task_type]['completed'];
                            if (!$validation_results[$task_type]['completed']) {
                                $all_completed = false;
                            }
                        }
                    }
                }
    
                $result_json = json_encode($result_details);
    
                if (!$all_completed) {
                    mysqli_query($DB_CONN, 
                        "INSERT INTO applications (user_id, task_id, result, type, status, comment) 
                         VALUES ('{$userinfo['id']}', '{$task_id}', '{$result_json}', 2, 0, 'Not all required tasks completed')"
                    );
                    call_alert(1266);
                    return ['status' => 'error'];
                }

                // Track rewards for completion message
                $total_rewards = [];
                
                // Initialize application with basic success message
                mysqli_query($DB_CONN, 
                    "INSERT INTO applications (user_id, task_id, result, type, status, comment) 
                     VALUES ('{$userinfo['id']}', '{$task_id}', '{$result_json}', 2, 1, 'Task completed successfully')"
                );
    
                $application_id = mysqli_insert_id($DB_CONN);
                
                if ($task_details['account_balance_enabled'] == 'true') {
                    $amount = $task_details['account_amount_min'];
                    if ($task_details['account_amount_type'] == '1') {
                        $min = floatval($task_details['account_amount_min']);
                        $max = floatval($task_details['account_amount_max']);
                        $amount = rand($min * 100, $max * 100) / 100;
                    }
                    $det = str_replace(["#task_reward#"], [fiat($amount)], $task_details['account_memo']);
                    add_balance($userinfo['id'], $task_details['account_currency'], $amount);
                    mysqli_query($DB_CONN, 
                        "INSERT INTO transactions (user_id, amount, payment_method_id, txn_type, ref_id, detail)
                         VALUES ('{$userinfo['id']}', '{$amount}', '{$task_details['account_currency']}',
                         'bonus', '{$application_id}', '{$det}')"
                    );
                    
                    $total_rewards[$task_details['account_currency']] = isset($total_rewards[$task_details['account_currency']]) 
                        ? $total_rewards[$task_details['account_currency']] + $amount
                        : $amount;
                }
            
                if ($task_details['faucet_balance_enabled'] == 'true') {
                    $amount = $task_details['faucet_amount_min'];
                    if ($task_details['faucet_amount_type'] == '1') {
                        $min = floatval($task_details['faucet_amount_min']);
                        $max = floatval($task_details['faucet_amount_max']);
                        $amount = rand($min * 100, $max * 100) / 100;
                    }
                    $det = str_replace(["#task_reward#"], [fiat($amount)], $task_details['faucet_memo']);
                    add_faucet($userinfo['id'], $task_details['faucet_currency'], $amount);
                    mysqli_query($DB_CONN, 
                        "INSERT INTO transactions (user_id, amount, payment_method_id, txn_type, ref_id, detail)
                         VALUES ('{$userinfo['id']}', '{$amount}', '{$task_details['faucet_currency']}',
                         'faucet', '{$application_id}', '{$det}')"
                    );
                    
                    $total_rewards[$task_details['faucet_currency']] = isset($total_rewards[$task_details['faucet_currency']]) 
                        ? $total_rewards[$task_details['faucet_currency']] + $amount
                        : $amount;
                }

                // Update application with reward details
                $reward_summary = [];
                foreach ($total_rewards as $currency => $amount) {
                    $reward_summary[] = fiat($amount) . " " . $currency;
                }
                $reward_details = implode(", ", $reward_summary);
                $success_message = "Task completed successfully (Rewards: " . $reward_details . ")";
                
                mysqli_query($DB_CONN, 
                    "UPDATE applications 
                     SET comment = '{$success_message}'
                     WHERE id = '{$application_id}'"
                );
    
                // Return updated task list status along with success
                $current_task_status = mysqli_query($DB_CONN, 
                    "SELECT timestamp FROM applications 
                     WHERE task_id = '{$task_id}' 
                     AND user_id = '{$userinfo['id']}' 
                     AND status = 1 
                     ORDER BY timestamp DESC 
                     LIMIT 1"
                );
                
                $status_data = mysqli_fetch_assoc($current_task_status);
                $completion_status = 1;
                
                if (isset($task_details['duration']) && isset($status_data['timestamp'])) {
                    $completion_status = $checkDurationValidity(strtotime($status_data['timestamp']), $task_details['duration']) ? 0 : 1;
                }
    
                call_alert(1265);
                return [
                    'status' => 'success',
                    'task_status' => $completion_status
                ];
            } else if ($task['type'] == '1') {
                if (!$params || !isset($params['result']) || !isset($params['comment'])) {
                    call_alert(1266);
                    return ['status' => 'error'];
                }
    
                mysqli_query($DB_CONN, 
                    "INSERT INTO applications (user_id, task_id, result, type, status, comment) 
                     VALUES ('{$userinfo['id']}', '{$task_id}', '{$params['result']}', 1, 2, '{$params['comment']}')"
                );
                
                call_alert(1267);
                return ['status' => 'pending'];
            }
            break;
    }
    
    call_alert(1266);
    return ['status' => 'error'];
}
function makeRequest($type, $filename, $siteURL) {
    $ch = curl_init($siteURL . '/api.php');
    $payload = json_encode(['type' => $type]);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Updated to use dirname(__DIR__) to go up one level to the root directory
    file_put_contents(dirname(__DIR__) . '/' . $filename, $response);
    return $response;
}

function updateSiteData($siteURL) {
    global $site_settings;
    if($site_settings['webapp']) {
        $sitedata = makeRequest('sitedata', 'sitedata.json', $siteURL);
       // makeRequest('seo', 'metadata.json', $siteURL);
        
        // Parse and update manifest
        $sitedata = json_decode($sitedata, true);
        if (!$sitedata) {
            return false;
        }
        
        $seo = $sitedata['seo_settings'];
        $manifest = [
            'name' => $seo['sitename'],
            'short_name' => $seo['alternateName'] ?: $seo['sitename'],
            'description' => $seo['description'],
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => $seo['background_color'],
            'theme_color' => $seo['theme_color'],
            'icons' => [
                [
                    'src' => $seo['favicon'],
                    'sizes' => '48x48',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => $seo['favicon192'],
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => $seo['favicon512'],
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable'
                ]
            ],
            'lang' => $seo['primaryLanguage']
        ];
        
        // Updated to use dirname(__DIR__) to go up one level to the root directory
        return file_put_contents(
            dirname(__DIR__) . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
function calculateDailyInvestmentProfit($userID) {
    global $DB_CONN;
    $totalDailyProfit = 0;
    
    // Get all active investments
    $query = mysqli_query($DB_CONN, "SELECT pd.*, pp.percent_max, 
                                     p.duration, p.diff_in_seconds, p.earnings_mon_fri, p.earning_days, p.details 
                          FROM package_deposits pd 
                          JOIN packages p ON pd.package_id = p.id
                          JOIN package_plans pp ON pd.plan_id = pp.id
                          WHERE pd.user_id = '$userID' AND pd.status = 1");
    
    while($investment = mysqli_fetch_assoc($query)) {
        // Calculate base profit per earning
        $rate = $investment['percent_max'];
        $amount = $investment['amount'];
        $profitPerEarning = ($rate / 100) * $amount;
        
        // Determine earnings frequency factor
        $earningsPerDay = 1; // Default: daily
        
      if($investment['diff_in_seconds'] > 86400) {
    // If earning interval is more than a day
    $earningsPerDay = 86400 / $investment['diff_in_seconds'];
} else if($investment['diff_in_seconds'] < 86400) {
    // If earning interval is less than a day (hourly plans)
    $earningsPerDay = 86400 / $investment['diff_in_seconds'];
}
        
        // Adjust for earnings_mon_fri setting
        if($investment['earnings_mon_fri'] == 1) {
            // Only Mon-Fri (5 out of 7 days)
            $earningsPerDay *= (5/7);
        } elseif($investment['earnings_mon_fri'] == 2) {
            // Custom days
            $earningDays = json_decode($investment['earning_days'], true);
            $daysCount = 0;
            foreach($earningDays as $day => $status) {
                if($status == 'on') $daysCount++;
            }
            $earningsPerDay *= ($daysCount/7);
        }
        
        // Calculate daily profit
        $dailyProfit = $profitPerEarning * $earningsPerDay;
        $totalDailyProfit += $dailyProfit;
    }
    
    return $totalDailyProfit;
}
function updateuserinfo() {
    global $DB_CONN, $smarty, $userinfo, $login_link, $ps, $user_id, $tgtoken, $telegram_settings, $siteURL, $is_api, $data;
    if ($user_id === null) {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    $query = "SELECT u.*,
        (SELECT count(*) FROM users a WHERE a.sponsor = u.id) as affiliates,
        (SELECT u2.username FROM users u2 WHERE u2.id = u.sponsor) AS sponsor
    FROM users u 
    WHERE u.id = ?";

    $stmt = mysqli_prepare($DB_CONN, $query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userinfo = mysqli_fetch_assoc($result);

    if($userinfo['status'] != 1){
        call_alert(14);
        echo json_encode($data);
        exit;
    }
    if($userinfo['status'] != 1 || !$userinfo['id']) {
        session_destroy();
        header("location: $login_link",  true,  301 );
        exit;
    }
    $is_login = true;
    $userinfo['logged'] = "1";
    $userinfo['tier'] = get_tier($userinfo['id']);
    $userinfo['level'] = get_level($userinfo['id']);
    $login = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from login_report WHERE user_id = '{$userinfo['id']}' ORDER BY id DESC LIMIT 1 "));
    $userinfo['last_access_ip'] = $login['ip'];
    $userinfo['last_access'] = $login['datetime'];
    $ac = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT COALESCE(sum(amount), 0) as a FROM `package_deposits` WHERE user_id = '{$userinfo['id']}' and status = 1 "));
    $userinfo['active'] = $ac['a'];
    $ref = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT count(DISTINCT user_id) as c, COALESCE(sum(amount), 0) as a FROM `package_deposits` WHERE user_id in (SELECT id from users where sponsor = '{$userinfo['id']}') and (status = 1 or last_earningDateTime) "));
    $userinfo['affiliates'] = mysqli_num_rows(mysqli_query($DB_CONN, "SELECT * from users where sponsor = '{$userinfo['id']}'"));
    $userinfo['affiliates_investment'] = $ref['a'];
    $t = mysqli_query($DB_CONN, "SELECT txn_type, sum(amount) as amount FROM `transactions` WHERE user_id = '{$userinfo['id']}' and status = 1 GROUP by txn_type ");
    $options = array();
    while($txn = mysqli_fetch_assoc($t)) {
        switch ($txn['txn_type']) {
            case 'invest':
            $options[$txn['txn_type']]['name'] = "Investments";
            $options[$txn['txn_type']]['link'] = "investments";
            $userinfo['investments_total'] = $txn['amount'];
            break;
            case 'deposit':
            $options[$txn['txn_type']]['name'] = "Deposits";
            $options[$txn['txn_type']]['link'] = "deposits";
            $userinfo['deposits_total'] = $txn['amount'];
            break;
            case 'earning':
            $options[$txn['txn_type']]['name'] = "Earnings";
            $options[$txn['txn_type']]['link'] = "earnings";
            $userinfo['earnings_total'] = $txn['amount'];
            break;
            case 'withdraw':
            $options[$txn['txn_type']]['name'] = "Withdrawals";
            $options[$txn['txn_type']]['link'] = "withdrawals";
            $userinfo['withdrawals_total'] = $txn['amount'];
            break;
            case 'bonus':
            $userinfo['bonuses_total'] = $txn['amount'];
            break;
            case 'faucet':
            $options[$txn['txn_type']]['name'] = "Faucets";
            $options[$txn['txn_type']]['link'] = "faucets";
            $userinfo['faucets_total'] = $txn['amount'];
            break;
            case 'penalty':
            $options[$txn['txn_type']]['name'] = "Penalties";
            $options[$txn['txn_type']]['link'] = "penalties";
            $userinfo['penalties_total'] = $txn['amount'];
            break;
            case 'referral':
            $options[$txn['txn_type']]['name'] = "Referral Commissions";
            $options[$txn['txn_type']]['link'] = "commissions";
            $userinfo['affiliates_total'] = $txn['amount'];
            break;
            case 'exchange':
            $options[$txn['txn_type']]['name'] = "Exhanges";
            $options[$txn['txn_type']]['link'] = "exchanges";
            $userinfo['exchanges_total'] = $txn['amount'];
            break;
            case 'balance':
            $options[$txn['txn_type']]['name'] = "Investment Returned";
            $options[$txn['txn_type']]['link'] = "returned";
            $userinfo['investment_returned_total'] = $txn['amount'];
            break;
            case 'release':
            $options[$txn['txn_type']]['name'] = "Investment Release";
            $options[$txn['txn_type']]['link'] = "released";
            $userinfo['investments_release_total'] = $txn['amount'];
            break;
            case 'return':
            $options[$txn['txn_type']]['name'] = "Investment Returned";
            $options[$txn['txn_type']]['link'] = "returned";
            $userinfo['investments_release_total'] = $txn['amount'];
            break;
            case 'transfer':
            $options[$txn['txn_type']]['name'] = "Internal Transfers";
            $options[$txn['txn_type']]['link'] = "transfers";
            $userinfo['transfers_total'] = $txn['amount'];
            break;
        }       
    }
    $ps = array();
    $psq = mysqli_query($DB_CONN, "SELECT currencies.*, REPLACE(lower(name), ' ', '') as field, 1 as status, (SELECT sum(balance) from user_balances where user_id = '{$userinfo['id']}' and payment_method_id = currencies.id) as balance,(SELECT sum(faucet) from user_balances where user_id = '{$userinfo['id']}' and payment_method_id = currencies.id) as faucet, (SELECT SUM(amount) as c FROM transactions where user_id = '{$userinfo['id']}' and txn_type = 'earning' and payment_method_id = currencies.id) as earning, (SELECT sum(amount) from package_deposits where user_id = '{$userinfo['id']}' and payment_method_id = currencies.id and status = 1) as investment, (SELECT COALESCE(sum(amount), 0) as a FROM transactions where user_id = '{$userinfo['id']}' and payment_method_id = currencies.id and status = 0 and txn_type = 'withdraw') as pwithdraw FROM currencies where de_pm_id order by id ASC");
    while($psqu = mysqli_fetch_assoc($psq)) {
        $userinfo['balances'][$psqu['id']] = $psqu['balance'];
        $userinfo['faucets'][$psqu['id']] = $psqu['faucet'];
        if(isown())
            $rate = $psqu['rate'];
        else
            $rate = 1;
        $psqu['usdbalance'] = $userinfo['usdbalances'][$psqu['id']] = $psqu['balance'] * $rate;
        $psqu['usdfaucet'] = $userinfo['usdfaucets'][$psqu['id']] = $psqu['faucet'] * $rate;
        $userinfo['earning'][$psqu['id']] = $psqu['earning'] * $rate;
        $userinfo['investment'][$psqu['id']] = $psqu['investment'] * $rate;
        $userinfo['pwithdraw'][$psqu['id']] = $psqu['pwithdraw'] * $rate;
        // $psqu['rate'] = tocurrency(1, $psqu['symbol']); // line to remember
        $ps[] = $psqu;
    }
$profile = [
    'fullname' => $userinfo['fullname'] ?? null,
    'email' => $userinfo['email'] ?? null,
    'username' => $userinfo['username'] ?? null,
    'phone' => $userinfo['phone'] ?? null,
    'address' => $userinfo['address'] ?? null,
    'city' => $userinfo['city'] ?? null,
    'zip' => $userinfo['zip'] ?? null,
    'country' => $userinfo['country'] ?? null,
    'question' => $userinfo['question'] ?? null,
    'answer' => $userinfo['answer'] ?? null,
    'timezone' => $userinfo['timezone'] ?? null,
    'tfa' => $userinfo['2fa'] ?? null,
    'created_at' => $userinfo['created_at'] ?? null,
    'level' => $userinfo['level'] ?? null,
    'kyc' => $userinfo['kyc'] ?? null,
    'kyc_data' => $userinfo['kyc_data'] ?? null,
    'oauth_uid' => $userinfo['oauth_uid'] ?? null,
    'dailycheckin' => $userinfo['dailycheckin'] ?? null,
    'oauth_provider' => $userinfo['oauth_provider'] ?? null,
    'photo' => $userinfo['photo'] ?? null,
    'photo_id' => $userinfo['photo_id'] ?? null,
    'sponsor' => $userinfo['sponsor'] ?? null,
    'lastAccess' => $userinfo['last_access'] ?? null,
    'lastAccessIp' => $userinfo['last_access_ip'] ?? null,
    'tier' => $userinfo['tier'] ?? null,
    'treferrallink' => $telegram_settings['mini_app_link'] . '?' . $telegram_settings['mini_app_link_start'] . '=' . ($telegram_settings['mini_app_link_type'] == 'username' ? $userinfo['username'] : $userinfo['oauth_uid']),
    'referrallink' => $siteURL . '/?ref=' . $userinfo['username']
];

$userinfo['profile'] = $profile;
    $userinfo['wallets'] = json_decode($userinfo['wallets'], true);
    $userinfo['data'] = array();
    $userinfo['accountbalance'] = 0;
    $userinfo['faucetbalance'] = 0;
    foreach ($ps as $psqu) {
        $id = $psqu['id'];
        $name = $psqu['name'];
        $symbol = $psqu['symbol'];
        $ticker = $psqu['ticker']? : $psqu['symbol'];
        $balance = $psqu['balance'] ?? "0";
        $with_min = $psqu['with_min'];
        $rate = $psqu['rate'] ?? 1;
        $earning = $psqu['earning'] ?? "0";
        $investment = $psqu['investment'] ?? "0";
        $pending_withdraw = $psqu['pwithdraw'];
        $faucet = $psqu['facuet'];
        $field = $psqu['field'];
        if(!$psqu['is_p']) {
            $userinfo['accountbalance'] += $psqu['usdbalance'];
            $userinfo['faucetbalance'] += $psqu['usdfaucet'];
        }
        $address = isset($userinfo['wallets'][$field]) ? $userinfo['wallets'][$field] : '';         
        $userdata[] = array(
            "id" => $id,
            "name" => $name,
            "symbol" => $symbol,
            "ticker" => $ticker,
            "icon" => $siteURL . '/images/icons/' . $id . '.svg',
            "wallets" => $field,
            "address" => $address,
            "balance" => fiat($balance, 0, $ticker),
            "balance_numeric" => number_format($balance, 2, ".", ""),
            "with_min" => fiat($with_min, 0, $ticker),
            "wi_pm_id" => $psqu['wi_pm_id'],
            "de_pm_id" => $psqu['de_pm_id'],
            "is_p" =>  $psqu['is_p'],
            "rate" => $rate,
            "earning" => $earning,
            "investment" => $investment,
            "pending_withdraw" => $pending_withdraw,
            // Added new fields
            "transfer_fee_per" => $psqu['transfer_fee_per'],
            "transfer_fee_amount" => $psqu['transfer_fee_amount'],
            "transfer_min" => $psqu['transfer_min'],
            "transfer_max" => $psqu['transfer_max'],
            "dep_fee_per" => $psqu['dep_fee_per'],
            "dep_fee_amount" => $psqu['dep_fee_amount'],
            "dep_min" => $psqu['dep_min'],
            "dep_max" => $psqu['dep_max'],
            "dep_bonus_per" => $psqu['dep_bonus_per'],
            "dep_bonus_amount" => $psqu['dep_bonus_amount'],
            "with_fee_per" => $psqu['with_fee_per'],
            "with_fee_amount" => $psqu['with_fee_amount'],
            "with_min" => $psqu['with_min'],
            "with_max" => $psqu['with_max']
        );       
    }
    $userinfo['pm_wallets'] = $userdata;
    //print_r($userdata);
    $userinfo['earnings'] = array_sum($userinfo['earning']);
    $userinfo['investments_active'] = array_sum($userinfo['investment']);
    $userinfo['withdrawals_pending'] = array_sum($userinfo['pwithdraw']);
    $earntoday = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(amount) as c FROM `transactions` where user_id = '{$userinfo['id']}' and txn_type = 'earning' AND date(timestamp) = CURDATE() "));
    $userinfo['earnings_today'] = $earntoday['c'];
    $afftoday = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(amount) as c FROM `transactions` where user_id = '{$userinfo['id']}' and txn_type = 'referral' AND date(timestamp) = CURDATE() "));
    $userinfo['affiliates_today'] = $afftoday['c'];
    $ftoday = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(amount) as c FROM `transactions` where user_id = '{$userinfo['id']}' and txn_type = 'faucet' AND date(timestamp) = CURDATE() "));
    $userinfo['faucets_today'] = $ftoday['c'];
    $availtabs = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(ref_id) as c FROM `transactions` where user_id = '{$userinfo['id']}' and txn_type = 'tap' AND date(timestamp) = CURDATE()"));
    $userinfo['available_taps'] = $availtabs['c'];
    $activeTrades = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT COUNT(*) as c FROM `trades` WHERE user_id = '{$userinfo['id']}' AND status = 0"));
$userinfo['activeTrades'] = $activeTrades['c'];

$totalTrades = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT COUNT(*) as c FROM `trades` WHERE user_id = '{$userinfo['id']}'"));
$userinfo['totalTrades'] = $totalTrades['c'];

$totalPnl = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(pnl) as c FROM `trades` WHERE user_id = '{$userinfo['id']}'"));
$userinfo['totalPnl'] = $totalPnl['c'];

$todayPnl = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT SUM(pnl) as c FROM `trades` WHERE user_id = '{$userinfo['id']}' AND DATE(timestamp) = CURDATE()"));
$userinfo['todayPnl'] = $todayPnl['c'];
    
if (isset($smarty) && is_object($smarty)) {
        $smarty->assign('is_login', $is_login);
        $smarty->assign('options', $options);
        $smarty->assign('userinfo', $userinfo);
        $smarty->assign('ps', $ps);
    }
}
function getCommonMetadata($message, $content, $all_entities, $is_edit, $edit_date) {
    return [
        'original_text' => $message['text'] ?? null,
        'original_caption' => $message['caption'] ?? null,
        'combined_content' => $content,
        'entities' => $message['entities'] ?? null,
        'caption_entities' => $message['caption_entities'] ?? null,
        'all_entities' => $all_entities,
        'title' => $message['chat']['title'] ?? null,
        'chat_type' => $message['chat']['type'] ?? null,
        'is_edited' => $is_edit,
        'edit_date' => $edit_date
    ];
} 
function improvedWordCount($content) {
    $text = html_entity_decode(strip_tags($content));
    $text = preg_replace('/[^\p{L}\p{M}\s]/u', '', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    return count($words);
}

function calculateReadingTime($wordCount, $wordsPerMinute = 200) {
    $minutes = ceil($wordCount / $wordsPerMinute);
    return max(1, $minutes); // Ensure at least 1 minute reading time
}
function getImageDimensions($image) {
    if ($image) {
        $imageSize = getimagesize($image);
        if ($imageSize) {
            return [
                'width' => $imageSize[0],
                'height' => $imageSize[1]
            ];
        }
    }
    return [
        'width' => 1200,
        'height' => 630
    ];
}
function convertHtmlToPlainText($html) {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    return $text;
}
function getAuthors() {
    global $DB_CONN, $main_link, $siteURL;
    
    $query = "SELECT * FROM users WHERE author = 1";
    $result = $DB_CONN->query($query);
    
    $author_base = $main_link['authors'] ?: 'authors';
    $authors = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['url'] = $siteURL .'/'. $author_base . '/' . $row['slug'];
            $authors[] = $row;
        }
    }
    
    return $authors;
}
function manageFAQs($item_id, $item_type) {
 global $DB_CONN, $lang_id;

    $existing_faqs_stmt = $DB_CONN->prepare("SELECT id FROM faqs WHERE {$item_type}_id = ?");
    $existing_faqs_stmt->bind_param("i", $item_id);
    $existing_faqs_stmt->execute();
    $existing_faqs_result = $existing_faqs_stmt->get_result();
    $existing_faq_ids = [];
    while ($row = $existing_faqs_result->fetch_assoc()) {
        $existing_faq_ids[] = $row['id'];
    }

    // Prepare statements for insert and update
    // $insert_faq_stmt = $DB_CONN->prepare();
    // $update_faq_stmt = $DB_CONN->prepare();

    $faq_questions = $_POST['faq_question'] ?? [];
    $faq_answers = $_POST['faq_answer'] ?? [];
    $faq_ids = $_POST['faq_id'] ?? [];

    $processed_faq_ids = [];

    foreach ($faq_questions as $key => $question) {
        $answer = $faq_answers[$key] ?? '';
        $faq_id = $faq_ids[$key] ?? null;

        $question = trim($question);
        $answer = trim($answer);

        // Only process non-empty FAQs
        if (!empty($question) || !empty($answer)) {
            if ($faq_id && in_array($faq_id, $existing_faq_ids)) {
                // Update existing FAQ
                // $update_faq_stmt->bind_param("ssi", $question, $answer, $faq_id);
                // $update_faq_stmt->execute();
                mysqli_query($DB_CONN, "UPDATE faqs SET question = '{$question}', answer = '{$answer}' WHERE id = '{$faq_id}'");
                $processed_faq_ids[] = $faq_id;
            } else {
                mysqli_query($DB_CONN, "INSERT INTO faqs ({$item_type}_id, question, answer, lang_id) VALUES ('{$item_id}', '{$question}', '{$answer}', '{$lang_id}')");
                // Insert new FAQ
                // $insert_faq_stmt->bind_param("issi", $item_id, $question, $answer, $lang_id);
                // $insert_faq_stmt->execute();
                $processed_faq_ids[] = $DB_CONN->insert_id;
            }
        }
    }

    // Delete FAQs that were removed from the form or are empty
    $faqs_to_delete = array_diff($existing_faq_ids, $processed_faq_ids);
    if (!empty($faqs_to_delete)) {
        $delete_faq_stmt = $DB_CONN->prepare("DELETE FROM faqs WHERE id = ?");
        foreach ($faqs_to_delete as $faq_id) {
            $delete_faq_stmt->bind_param("i", $faq_id);
            $delete_faq_stmt->execute();
        }
    }
}
function fetchReviewsForProduct($productId) {
global $DB_CONN;
    $reviews = [];

    // Prepare the SQL query
    $query = "
        SELECT 
            r.id,
            r.user_id,
            r.product_id,
            r.review AS review_body,
            r.rating,
            r.datetime,
            u.fullname AS author_name
        FROM 
            reviews r
        JOIN 
            users u ON r.user_id = u.id
        WHERE 
            r.product_id = ?
            AND r.status = 1  -- Assuming status 1 means approved or active
        ORDER BY 
            r.datetime DESC
        LIMIT 5  -- Fetch the last 5 reviews
    ";

    if ($stmt = $DB_CONN->prepare($query)) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->bind_result($id, $user_id, $product_id, $review_body, $rating, $datetime, $author_name);

        while ($stmt->fetch()) {
            $reviews[] = [
                'id' => $id,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'review_body' => $review_body,
                'rating' => $rating,
                'datetime' => $datetime,
                'author_name' => $author_name
            ];
        }

        // Close the statement
        $stmt->close();
    } else {
    }

    return $reviews;
}
function generateUnifiedSchema($pageSpecificData, $product = null, $faqs = null) {
    global $seo_settings, $siteURL, $products, $products_base, $changelogs, $pfaqs, $bfaqs, $main_link;
    $pageType = $pageSpecificData['pagetype'] ?: 'WebPage';
    $currentURL = $pageSpecificData['currentURL'];
    $pageName = $pageSpecificData['pageName'];
    $description = $pageSpecificData['description'];
   
    $schema = [
        "@context" => "https://schema.org",
        "@type" => WebPage,
        "url" => $currentURL,
        "name" => $pageName,
        "description" => $description,
        "isPartOf" => [
            "@type" => "WebSite",
            "url" => $siteURL,
            "name" => $seo_settings['sitename'],
            "description" => $seo_settings['description'],
            "publisher" => [
                "@type" => $seo_settings['orgType'],
                "name" => $seo_settings['sitename'],
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $seo_settings['logo']
                ],
                "sameAs" => array_values(array_filter([
                    $seo_settings['facebook'],
                    $seo_settings['twitter'],
                    $seo_settings['linkedin'],
                    $seo_settings['telegram'],
                    $seo_settings['github']
                ]))
            ]
        ],
        "datePublished" => date('c', strtotime($pageSpecificData['datePublished'] ?? 'now')),
        "dateModified" => date('c', strtotime($pageSpecificData['dateModified'] ?? 'now'))
    ];

 
    // Contact point
    $contactPoint = [
        "@type" => "ContactPoint",
        "telephone" => $seo_settings['phone'],
        "contactType" => "Customer Service",
        "areaServed" => "WorldWide",
        "availableLanguage" => $seo_settings['primaryLanguage']
    ];

    if (!empty($contactPoint['telephone'])) {
        $schema['isPartOf']['publisher']['contactPoint'] = $contactPoint;
    }

     $schemas = [$schema];
switch ($pageType) {
  case 'Article':
    case 'BlogPosting':
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $pageType,
            'headline' => substr($pageSpecificData['headline'], 0, 110),
            'articleBody' => $pageSpecificData['content'],
            'datePublished' => date('c', strtotime($pageSpecificData['datePublished'])),
            'dateModified' => date('c', strtotime($pageSpecificData['dateModified'] ?? $pageSpecificData['datePublished'])),
            'author' => $pageSpecificData['author'],
            'image' => $pageSpecificData['image'],
            'publisher' => $pageSpecificData['publisher'],
            'articleSection' => implode(', ', $pageSpecificData['categories'] ?? []),
            'keywords' => implode(', ', $pageSpecificData['tags'] ?? []),
            'wordCount' => $pageSpecificData['wordCount'] ?? null,
            'timeRequired' => !empty($pageSpecificData['readingTime']) ? "PT{$pageSpecificData['readingTime']}M" : null,
            'mainEntityOfPage' => $pageSpecificData['mainEntityOfPage'] ?? [
                '@type' => 'WebPage',
                '@id' => $pageSpecificData['currentURL']
            ]
        ];

        // Add FAQ schema if applicable
        if (!empty($pageSpecificData['bfaqs'])) {
            $schema['mainEntity'] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(function($faq) {
                    return [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer']
                        ]
                    ];
                }, $pageSpecificData['bfaqs'])
            ];
        }

        // Append the schema to the schemas array
        $schemas[] = $schema;
        break;
case 'Blog':
            $schema['@type'] = 'CollectionPage';
            $schema['mainEntity'] = [
                "@type" => "ItemList",
                "itemListElement" => []
            ];
            foreach ($pageSpecificData['blogPosts'] as $index => $post) {
                $schema['mainEntity']['itemListElement'][] = [
                    "@type" => "ListItem",
                    "position" => $index + 1,
                    "item" => [
                        "@type" => "BlogPosting",
                        "headline" => $post['title'],
                        "datePublished" => $post['date_published'],
                        "dateModified" => $post['date_modified'] ?? $post['date_published'],
                        "author" => [
                            "@type" => "Person",
                            "name" => $post['author']
                        ],
                        "url" => $post['url'],
                        "description" => $post['excerpt'] ?? null,
                        "image" => $post['image'] ?? null
                    ]
                ];
            }
            // Add the blog page schema to the schemas array
            $schemas[] = $schema;
            break;
            
case 'ProductsPage':
    $schema['@type'] = ['CollectionPage', 'ItemList'];
    $schema['mainEntity'] = [
        "@type" => "ItemList",
        "itemListElement" => []
    ];
    
    foreach ($products as $index => $product) {
        // Fetch reviews for the product
        $reviews = fetchReviewsForProduct($product['id']);
      
        $productSchema = [
            "@type" => "Product",
            "name" => $product['name'],
            "description" => $product['short_description'],
            "url" => $siteURL.'/'.$products_base.'/'.$product['slug'],
            "image" => $siteURL.'/'.$product['image_url'],
            "offers" => [
                "@type" => "Offer",
                "price" => $product['price'],
                "priceCurrency" => $product['currency'],
                 "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
                "availability" => "https://schema.org/InStock"
            ]
        ];

        if (!empty($reviews)) {
            // Add individual reviews
            $productSchema["review"] = array_map(function($review) {
                return [
                    "@type" => "Review",
                    "author" => [
                        "@type" => "Person",
                        "name" => $review['author_name']
                    ],
                    "reviewRating" => [
                        "@type" => "Rating",
                        "ratingValue" => $review['rating']
                    ],
                    "reviewBody" => $review['review_body']
                ];
            }, $reviews);

            // Calculate and add aggregate rating
            $totalRating = array_sum(array_column($reviews, 'rating'));
            $reviewCount = count($reviews);
            $averageRating = $reviewCount ? $totalRating / $reviewCount : 0;

            $productSchema["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $averageRating,
                "reviewCount" => $reviewCount,
                "ratingCount" => $reviewCount
            ];
        }

        $schema['mainEntity']['itemListElement'][] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "item" => $productSchema
        ];
    }
    
    $schemas[] = $schema;
    break;
case 'Categories':
            $schema['@type'] = ['CollectionPage', 'ItemList'];
            $schema['name'] = $pageSpecificData['name'];
            $schema['description'] = $pageSpecificData['description'];
            $schema['url'] = $pageSpecificData['url'];
            $schema['mainEntity'] = [
                "@type" => "ItemList",
                "itemListElement" => []
            ];
            $position = 1;
            foreach ($pageSpecificData['category']['products'] as $product) {
                // Fetch reviews for the product
                $reviews = fetchReviewsForProduct($product['id']);
                
                $productSchema = [
                    "@type" => "Product",
                    "name" => $product['name'],
                    "url" => $product['slug'],
                    "description" => $product['short_description'] ?? '',
                    "image" => $product['image_url'] ?? '',
                    "offers" => [
                        "@type" => "Offer",
                        "price" => $product['price'] ?? '',
                        "priceCurrency" => $product['currency'],
                         "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
                        "availability" => "https://schema.org/InStock",
                    ]
                ];
            
                if (!empty($reviews)) {
                    // Add individual reviews
                    $productSchema["review"] = array_map(function($review) {
                        return [
                            "@type" => "Review",
                            "author" => [
                                "@type" => "Person",
                                "name" => $review['author_name']
                            ],
                            "reviewRating" => [
                                "@type" => "Rating",
                                "ratingValue" => $review['rating']
                            ],
                            "reviewBody" => $review['review_body']
                        ];
                    }, $reviews);
            
                    // Calculate and add aggregate rating
                    $totalRating = array_sum(array_column($reviews, 'rating'));
                    $reviewCount = count($reviews);
                    $averageRating = $reviewCount ? $totalRating / $reviewCount : 0;
            
                    $productSchema["aggregateRating"] = [
                        "@type" => "AggregateRating",
                        "ratingValue" => $averageRating,
                        "reviewCount" => $reviewCount
                    ];
                }
            
                $schema['mainEntity']['itemListElement'][] = [
                    "@type" => "ListItem",
                    "position" => $position++,
                    "item" => $productSchema
                ];
            }
            foreach ($pageSpecificData['category']['news'] as $newsItem) {
                $schema['mainEntity']['itemListElement'][] = [
                    "@type" => "ListItem",
                    "position" => $position++,
                    "item" => [
                        "@type" => "BlogPosting",
                        "headline" => $newsItem['title'],
                        "url" => $currentURL,
                        "image" => $newsItem['image_url'],
                        "author" => [
                            "@type" => "Person",
                            "name" => $newsItem['author_name'],
                            "url" => $siteURL .'/'. 'author/' . $newsItem['author_slug']
                        ],
                        "description" => $newsItem['short_description'] ?? '',
                        "datePublished" => ($newsItem['datetime'] ?? '') ? date('c', strtotime($newsItem['datetime'])) : ''
                    ]
                ];
            }
            // Add the categories page schema to the schemas array
            $schemas[] = $schema;
            break;
        case 'ProductPage':
            if ($product) {
                // Create separate schemas for Product and SoftwareApplication
                $productSchema = [
                    "@context" => "https://schema.org",
                    "@type" => "Product",
                    "name" => $product['name'],
                    "description" => strip_tags($product['short_description'] ?? $product['description']),
                    "offers" => [
                        "@type" => "Offer",
                        "price" => $product['price'],
                        "priceCurrency" => $product['currency'] ?? "USD",
                        "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
                        "availability" => "https://schema.org/InStock",
                        "seller" => [
                            "@type" => "Organization",
                            "name" => $seo_settings['sitename'],
                            "url" => $siteURL
                        ],
                    ],
                    "image" => [
                        "@type" => "ImageObject",
                        "url" => $product['image_url'] ?? $seo_settings['image'],
                        "width" => $product['image_width'] ?? "1200",
                        "height" => $product['image_height'] ?? "630"
                    ],
                    "brand" => [
                        "@type" => "Brand",
                        "name" => $seo_settings['sitename']
                    ]
                ];

                $softwareSchema = [
                    "@context" => "https://schema.org",
                    "@type" => "SoftwareApplication",
                    "name" => $product['name'],
                    "description" => strip_tags($product['short_description'] ?? $product['description']),
                    "applicationCategory" => "BusinessApplication",
                    "operatingSystem" => "Cross-platform",
                    "softwareVersion" => $product['version'] ?? "1.0",
                    "offers" => [
                        "@type" => "Offer",
                        "price" => $product['price'],
                        "priceCurrency" => $product['currency'] ?? "USD",
                        "priceValidUntil" => date('Y-m-d', strtotime('+1 year')),
                        "availability" => "https://schema.org/InStock",
                        "seller" => [
                            "@type" => "Organization",
                            "name" => $seo_settings['sitename'],
                            "url" => $siteURL
                        ],
                    ],
                    "image" => [
                        "@type" => "ImageObject",
                        "url" => $product['image_url'] ?? $seo_settings['image'],
                        "width" => $product['image_width'] ?? "1200",
                        "height" => $product['image_height'] ?? "630"
                    ],
                    "screenshot" => !empty($product['screenshots']) ? array_map(function($screenshot) {
                        return [
                            "@type" => "ImageObject",
                            "url" => $screenshot['url'],
                            "caption" => $screenshot['caption'] ?? null
                        ];
                    }, json_decode($product['screenshots'], true)) : null,
                    "softwareHelp" => isset($product['documentation_url']) ? [
                        "@type" => "CreativeWork",
                        "url" => $product['documentation_url']
                    ] : null,
                    "award" => $product['award'] ?? null
                ];
                // Add features if they exist
if (!empty($product['features'])) {
    $features = is_string($product['features']) ? json_decode($product['features'], true) : $product['features'];
    
    if (is_array($features)) {
        $softwareSchema["featureList"] = $features;
    }
}

                // Add reviews and ratings if available
                $reviews = fetchReviewsForProduct($product['id']); // Assume this function fetches reviews
                if (!empty($reviews)) {
                    $productSchema["review"] = array_map(function($review) {
                        return [
                            "@type" => "Review",
                            "author" => [
                                "@type" => "Person",
                                "name" => $review['author_name']
                            ],
                            "reviewRating" => [
                                "@type" => "Rating",
                                "ratingValue" => $review['rating']
                            ],
                            "reviewBody" => $review['review_body']
                        ];
                    }, $reviews);

                    // Calculate aggregate rating
                    $totalRating = array_sum(array_column($reviews, 'rating'));
                    $reviewCount = count($reviews);
                    $averageRating = $reviewCount ? $totalRating / $reviewCount : 0;

                    $productSchema["aggregateRating"] = [
                        "@type" => "AggregateRating",
                        "ratingValue" => $averageRating,
                        "reviewCount" => $reviewCount
                    ];
                     $softwareSchema["aggregateRating"] = [
                        "@type" => "AggregateRating",
                        "ratingValue" => $averageRating ?? null,
                        "ratingCount" => $reviewCount ?? null,
                        "reviewCount" => $reviewCount ?? null,
                                      
                    ];
                }
        
                // Add FAQs
                if (!empty($pfaqs)) {
                    $softwareSchema["hasPart"] = [
                        "@type" => "FAQPage",
                        "mainEntity" => array_map(function($faq) {
                            return [
                                "@type" => "Question",
                                "name" => $faq['question'],
                                "acceptedAnswer" => [
                                    "@type" => "Answer",
                                    "text" => $faq['answer']
                                ]
                            ];
                        }, $pfaqs)
                    ];
                }

                // Add changelogs as release notes
                if (isset($changelogs) && is_array($changelogs) && !empty($changelogs)) {
                    $releaseNotesText = "";
                    foreach ($changelogs as $changelog) {
                        $releaseNotesText .= "Version " . $changelog['version'] . " - " . $changelog['detail'] . " (" . $changelog['date'] . ")\n";
                    }
                    $releaseNotesText = rtrim($releaseNotesText);
                    $softwareSchema["releaseNotes"] = $releaseNotesText;
                }

                // Add both schemas to the schemas array
                $schemas[] = $productSchema;
                $schemas[] = $softwareSchema;
            }
            break;
  case 'Article':
            if (!empty($pageSpecificData['articleType']) && $pageSpecificData['articleType'] == 'TechnicalArticle') {
                $schema['@type'] = 'TechnicalArticle';
                $schema['dependencies'] = $pageSpecificData['dependencies'] ?? null;
                $schema['proficiencyLevel'] = $pageSpecificData['proficiencyLevel'] ?? null;
            }
            
            if (!empty($pageSpecificData['codeSnippets'])) {
                $schema['hasPart'] = array_map(function($snippet) {
                    return [
                        "@type" => "SoftwareSourceCode",
                        "programmingLanguage" => $snippet['language'],
                        "codeRepository" => $snippet['repository'] ?? null,
                        "codeSampleType" => $snippet['type'] ?? "code snippet",
                        "sourceCode" => $snippet['code']
                    ];
                }, $pageSpecificData['codeSnippets']);
            }
            
            if (!empty($pageSpecificData['relatedArticles'])) {
                $schema['relatedLink'] = array_map(function($article) use ($siteURL) {
                    return $siteURL . '/' . $article['slug'];
                }, $pageSpecificData['relatedArticles']);
            }
            
            break;
case 'BlogPosting':
            $schema['isFamilyFriendly'] = $pageSpecificData['isFamilyFriendly'] ?? true;
            $schema['commentCount'] = $pageSpecificData['commentCount'] ?? null;
            
            if (!empty($pageSpecificData['speakable'])) {
                $schema['speakable'] = [
                    "@type" => "SpeakableSpecification",
                    "cssSelector" => $pageSpecificData['speakable']
                ];
            }
            
            // Add specific BlogPosting properties
            $schema['discussionUrl'] = $pageSpecificData['discussionUrl'] ?? null;
            $schema['sharedContent'] = $pageSpecificData['sharedContent'] ?? null;
            
            break;
            
case 'AuthorProfile':
            $schema['@type'] = 'ProfilePage';
            $schema['url'] = $pageSpecificData['currentURL'] ?? null;
            $schema['name'] = $pageSpecificData['pageName'] ?? null;

            if (!empty($pageSpecificData['content'])) {
                $schema['description'] = $pageSpecificData['content'];
            }

            $schema['mainEntity'] = [
                "@type" => "Person",
                "name" => $pageSpecificData['pageName'] ?? null,
                "description" => $pageSpecificData['content'] ?? null,
                "url" => $pageSpecificData['currentURL'] ?? null,
                "mainEntityOfPage" => [
                    "@type" => "WebPage",
                    "@id" => $pageSpecificData['canonicalURL'] ?? $pageSpecificData['currentURL']
                ]
            ];

            if (!empty($pageSpecificData['photo'])) {
                $schema['mainEntity']['image'] = [
                    "@type" => "ImageObject",
                    "url" => $pageSpecificData['photo']
                ];
            }

            if (!empty($pageSpecificData['email'])) {
                $schema['mainEntity']['email'] = $pageSpecificData['email'];
            }

            if (!empty($pageSpecificData['username'])) {
                $schema['mainEntity']['alternateName'] = $pageSpecificData['username'];
            }

            if (!empty($pageSpecificData['socialProfiles'])) {
                $schema['mainEntity']['sameAs'] = array_values(array_filter($pageSpecificData['socialProfiles']));
            }

            if (!empty($pageSpecificData['organization'])) {
                $schema['mainEntity']['worksFor'] = [
                    "@type" => "Organization",
                    "name" => $pageSpecificData['organization']
                ];
            }

            if (!empty($pageSpecificData['jobTitle'])) {
                $schema['mainEntity']['jobTitle'] = $pageSpecificData['jobTitle'];
            }

            if (!empty($pageSpecificData['blogs'])) {
                $schema['mainEntity']['knowsAbout'] = array_map(function($blog) use ($pageSpecificData) {
                    return [
                        "@type" => "Article",
                        "headline" => $blog['title'],
                        "datePublished" => date('c', strtotime($blog['date_published'] ?? 'now')),
                        "dateModified" => date('c', strtotime($blog['date_modified'] ?? $blog['date_published'] ?? 'now')),
                        "url" => $blog['url'],
                        "author" => [
                            "@type" => "Person",
                            "name" => $pageSpecificData['pageName']
                        ]
                    ];
                }, $pageSpecificData['blogs']);
            }

            // Clean up null values
            $schema = array_filter($schema, function($value) {
                return !is_null($value) && $value !== '';
            });
            $schema['mainEntity'] = array_filter($schema['mainEntity'], function($value) {
                return !is_null($value) && $value !== '';
            });

            // Add the author profile schema to the schemas array
            $schemas[] = $schema;
            break;

case 'FAQPage':
            $schema['@type'] = 'FAQPage';
            if (!empty($pageSpecificData['faqs'])) {
                $schema['mainEntity'] = array_map(function($faq) {
                    return [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer']
                        ]
                    ];
                }, $pageSpecificData['faqs']);
            }
            // Add the FAQ page schema to the schemas array
            $schemas[] = $schema;
            break;

        case 'ContactPage':
    $schema['@type'] = 'ContactPage';
    $schema['mainContentOfPage'] = [
        "@type" => "WebPageElement",
        "description" => $description
    ];

    // Add contact information if available
    if (!empty($pageSpecificData['contact'])) {
        $schema['contactPoint'] = array_map(function($contact) {
            return [
                "@type" => "ContactPoint",
                "telephone" => $contact['telephone'] ?? null,
                "email" => $contact['email'] ?? null,
                "contactType" => $contact['contactType'] ?? 'customer service',
                "areaServed" => $contact['areaServed'] ?? null,
                "availableLanguage" => $contact['availableLanguage'] ?? 'English'
            ];
        }, $pageSpecificData['contact']);
    }

    // Add physical address if available
    if (!empty($pageSpecificData['address'])) {
        $schema['address'] = [
            "@type" => "PostalAddress",
            "streetAddress" => $pageSpecificData['address']['streetAddress'] ?? null,
            "addressLocality" => $pageSpecificData['address']['addressLocality'] ?? null,
            "addressRegion" => $pageSpecificData['address']['addressRegion'] ?? null,
            "postalCode" => $pageSpecificData['address']['postalCode'] ?? null,
            "addressCountry" => $pageSpecificData['address']['addressCountry'] ?? null
        ];
    }

    // Add opening hours if available
    if (!empty($pageSpecificData['openingHours'])) {
        $schema['openingHours'] = $pageSpecificData['openingHours'];
    }

    // Add social media links if available
    if (!empty($pageSpecificData['socialProfiles'])) {
        $schema['sameAs'] = array_values(array_filter($pageSpecificData['socialProfiles']));
    }

    // Add the contact page schema to the schemas array
    $schemas[] = $schema;
    break;

}

    if (!empty($pageSpecificData['breadcrumbs'])) {
        $breadcrumbSchema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => []
        ];

        foreach ($pageSpecificData['breadcrumbs'] as $index => $crumb) {
            $breadcrumbSchema["itemListElement"][] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "item" => [
                    "@id" => $crumb['url'],
                    "name" => $crumb['name']
                ]
            ];
        }

        // Add the breadcrumb schema as a separate item in the array
        $schemas[] = $breadcrumbSchema;
    }
  
    return $schemas; // Return the array of schemas
}
function getCategories($item_id, $item_type) {
    global $DB_CONN, $lang_id;
    $table = $item_type . '_categories';
    
    $query = "
        SELECT COALESCE(ct.name, c.name) AS name, 
               COALESCE(ct.slug, c.slug) AS slug, 
               c.id
        FROM categories c 
        LEFT JOIN categories ct ON c.id = ct.translation_of AND ct.lang_id = ?
        JOIN $table ic ON c.id = ic.category_id 
        WHERE ic.{$item_type}_id = ?";
    
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param('ii', $lang_id, $item_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addCategoriesToItem($item_id, $item_type, $category_ids) {
    global $DB_CONN;
    $table = $item_type . '_categories';
    $query = "INSERT INTO $table ({$item_type}_id, category_id) VALUES (?, ?)";
    $stmt = $DB_CONN->prepare($query);
    foreach ($category_ids as $category_id) {
        $stmt->bind_param('ii', $item_id, $category_id);
        $stmt->execute();
    }
}
function getCategoryItems($category_slug) {
    global $DB_CONN, $lang_id;
    
   $category_query = "
    SELECT COALESCE(ct.id, c.id) AS id,
           COALESCE(ct.name, c.name) AS name,
           COALESCE(ct.slug, c.slug) AS slug,
           COALESCE(ct.description, c.description) AS description,
           COALESCE(ct.seo_robots, c.seo_robots) AS seo_robots
    FROM categories c 
    LEFT JOIN categories ct ON c.id = ct.translation_of AND ct.lang_id = ?
    WHERE c.slug = ?
";

$stmt = $DB_CONN->prepare($category_query);
$stmt->bind_param('is', $lang_id, $category_slug);
$stmt->execute();
$category_result = $stmt->get_result();
$category = $category_result->fetch_assoc();
    
    if (!$category) {
        return null;
    }
    
    $category_id = $category['id'];
    $products = [];
    
    // Try to get products if the tables exist
    try {
        // Check if products table exists
        $table_check = $DB_CONN->query("SHOW TABLES LIKE 'products'");
        $products_table_exists = $table_check->num_rows > 0;
        
        // Check if product_categories table exists
        $table_check = $DB_CONN->query("SHOW TABLES LIKE 'product_categories'");
        $product_categories_table_exists = $table_check->num_rows > 0;
        
        if ($products_table_exists && $product_categories_table_exists) {
            $products_query = "SELECT p.id, p.name, p.slug, p.short_description, p.image_url, p.price, p.currency
                             FROM products p
                             JOIN product_categories pc ON p.id = pc.product_id
                             WHERE pc.category_id = ?";
            
            $stmt_products = $DB_CONN->prepare($products_query);
            $stmt_products->bind_param('i', $category_id);
            $stmt_products->execute();
            $products_result = $stmt_products->get_result();
            $products = $products_result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {

    }
    
    // Get news
    $news = [];
    try {
        $news_query = "SELECT 
            n.id, 
            n.title, 
            u.fullname AS author_name,
            u.slug AS author_slug,
            n.slug, 
            n.short_description, 
            n.image_url, 
            n.datetime 
        FROM 
            news n 
        JOIN 
            news_categories nc ON n.id = nc.news_id 
        JOIN 
            users u ON n.author = u.id 
        WHERE 
            nc.category_id = ?";
        
        $stmt_news = $DB_CONN->prepare($news_query);
        $stmt_news->bind_param('i', $category_id);
        $stmt_news->execute();
        $news_result = $stmt_news->get_result();
        $news = $news_result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Log the error if needed
        error_log("Error fetching news: " . $e->getMessage());
    }
    
    return [
        'category' => $category,
        'products' => $products,
        'news' => $news
    ];
}
function getAllCategories() {
    global $DB_CONN, $lang_id;
    $query = "
        SELECT COALESCE(ct.id, c.id) AS id,
               COALESCE(ct.name, c.name) AS name,
               COALESCE(ct.slug, c.slug) AS slug,
               COALESCE(ct.seo_robots, c.seo_robots) AS seo_robots
        FROM categories c 
        LEFT JOIN categories ct ON c.id = ct.translation_of AND ct.lang_id = ?
        WHERE c.parent_id IS NOT NULL AND c.parent_id != 1 
        ORDER BY COALESCE(ct.name, c.name)";
    
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param('i', $lang_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function addCategory($name) {
    global $DB_CONN;
    $query = "INSERT INTO categories (name) VALUES (?)";
    $stmt = $DB_CONN->prepare($query);
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        return $DB_CONN->insert_id;
    }
    return false;
}

function getCurrentStreak() {
    global $DB_CONN, $userinfo, $user_settings;
    $user_id = $userinfo['id'];
    
    // Get the current streak information
    $query = "SELECT streak_day, DATE(claimed_at) as claim_date,
              DATEDIFF(CURRENT_DATE, DATE(claimed_at)) as days_since_claim
              FROM daily_bonuses 
              WHERE user_id = ? 
              ORDER BY claimed_at DESC 
              LIMIT 1";
    
    $stmt = mysqli_prepare($DB_CONN, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $last_claim = mysqli_fetch_assoc($result);
    
    // Initialize completed_days
    $completed_days = [];
    
    // If no claims exist or the streak is broken (more than 1 day gap)
    if (!$last_claim || $last_claim['days_since_claim'] > 1) {
        return [
            'next_day' => 1,
            'completed_days' => []
        ];
    }
    
    // Calculate next_day
    $next_day = $last_claim['streak_day'] + 1;
    
    // Reset streak if completed
    if ($next_day > $user_settings['streak_days']) {
        $next_day = 1;
        return [
            'next_day' => $next_day,
            'completed_days' => []
        ];
    }
    
    // Fetch the current streak days
    $streak_query = "SELECT streak_day, DATE(claimed_at) as claim_date 
                    FROM daily_bonuses 
                    WHERE user_id = ? 
                    AND claimed_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
                    AND streak_day < ?
                    ORDER BY streak_day ASC";
    
    $stmt = mysqli_prepare($DB_CONN, $streak_query);
    $days_to_check = $next_day;
    mysqli_stmt_bind_param($stmt, "iii", $user_id, $days_to_check, $next_day);
    mysqli_stmt_execute($stmt);
    $streak_result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($streak_result)) {
        $completed_days[] = [
            'day' => (int)$row['streak_day'],
            'date' => $row['claim_date']
        ];
    }
    
    return [
        'next_day' => $next_day,
        'completed_days' => $completed_days
    ];
}

function claimDailyBonus() {
    global $DB_CONN, $userinfo, $user_settings, $g_alert, $data;
    $user_id = $userinfo['id'];
    
    // Check if daily streak is enabled
    if ($user_settings['dailystreak'] !== 'true') {
        return;
    }
    
    // Check if today's bonus is already claimed
    $query = "SELECT id FROM daily_bonuses 
              WHERE user_id = ? AND DATE(claimed_at) = CURRENT_DATE";
    $stmt = mysqli_prepare($DB_CONN, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Alert for already claimed
        call_alert(1270); // You've already claimed today's bonus!
        return $data;
    }
    
    // Get streak information
    $streak_info = getCurrentStreak($user_id, $DB_CONN);
    $streak_day = $streak_info['next_day'];
    $bonus_amount = $user_settings["streak_day{$streak_day}_bonus"] ?? 0;
    $payment_method_id = $user_settings["streak_day{$streak_day}_payment_id"] ?? null;
    
    // Check if a payment method is configured for this day
    if (!$payment_method_id) {
        return;
    }
    
    // Prepare replacements for placeholders
    $replacements = [
        'streak_day' => $streak_day,
        'bonus_amount' => fiat($bonus_amount)
    ];
    
    // Insert into daily bonuses
    $query = "INSERT INTO daily_bonuses (user_id, streak_day, bonus_amount) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($DB_CONN, $query);
    mysqli_stmt_bind_param($stmt, "iid", $user_id, $streak_day, $bonus_amount);
    
    if (mysqli_stmt_execute($stmt)) {
        // If alert message is defined, replace placeholders
        if (isset($g_alert[1269])) {
            $message_template = $g_alert[1269]['content']; 
            
            // Replace placeholders with actual values
            foreach ($replacements as $key => $value) {
                $message_template = str_replace("#{$key}#", $value, $message_template);
            }
            
            $detail = $message_template;
        } else {
            $detail = "Bonus claimed successfully!";
        }
        
        // Add balance to user
        add_balance($user_id, $payment_method_id, $bonus_amount);
        
        // Record the transaction
        mysqli_query($DB_CONN, 
            "INSERT INTO transactions (user_id, amount, txn_type, payment_method_id, detail)  
             VALUES ('$user_id', '$bonus_amount', 'bonus', '$payment_method_id', '$detail')");
        
        // Show success alert with dynamic placeholders
        call_alert(1269, $replacements);
        
        return $data;
    }
    
    // If the insert failed
    return ["success" => false, "message" => "Error claiming bonus: " . mysqli_error($DB_CONN)];
}

function assign_title($title_key, $return_data = false) {
    global $pref, $smarty, $main_link, $siteURL, $siteName, $siteLogo, $DB_CONN, $lang_id, $seo_settings;
    
    $base_key = $title_key;
    $assign_name = $main_link[$base_key];
    $page = $base_key;
    $pagetitle = ucfirst($base_key);
    $page_title = $main_link["{$base_key}_title"] ?: $pagetitle;
    $full_title = "$page_title - $siteName";

    if ($base_key === 'home') {
        $canonical_url = rtrim($siteURL, '/');
    } else {
        $canonical_path = $main_link[$base_key] ?: $base_key;
        $canonical_url = rtrim($siteURL, '/') . '/' . ltrim($canonical_path, '/');
    }

    $description = $main_link["{$base_key}_description"] ?: $seo_settings["description"];
    $description = substr($description, 0, 160);
    $keywords = isset($main_link["{$base_key}_keywords"]) ? $main_link["{$base_key}_keywords"] : $seo_settings["keywords"];
    $robots = isset($main_link["{$base_key}_robots"]) ? $main_link["{$base_key}_robots"] : 'no index, no follow';
    
    $seo_data = [
        'type' => 'website',
        'page_title' => $full_title,
        'pagename' => $page_title,
        'description' => $description,
        'keywords' => $keywords,
        'robots' => $robots,
        'image_url' => $main_link["{$base_key}_image"] ?: $seo_settings["image"],
    ];

    $product = null;
    $faqs = null;
    $page_type = $main_link["{$base_key}_schema_type"] ?? 'WebPage';
    $page_Published = $pref['datetime'] ?? date('c');
    $page_Modified = $main_link["{$base_key}_modified"] ?? date('c');

    $pageSpecificData = [
        'pagetype' => $page_type,
        'description' => $description,
        'currentURL' => $canonical_url,
        'pageName' => $page_title,
        'datePublished' => $page_Published,
        'dateModified' => $page_Modified,
        'breadcrumbs' => []
    ];

    switch ($page_type) {
        case 'AboutPage':
            break;
        case 'Blog':
            $blog_query = mysqli_query($DB_CONN, "SELECT * ,(select fullname from users where id = news.author) as author FROM news WHERE lang_id = '{$lang_id}'");
            if ($blog_query) {
                $blogPosts = [];
                while ($row = mysqli_fetch_assoc($blog_query)) {
                    $blogPosts[] = [
                        'title' => htmlspecialchars_decode($row['title']),
                        'date_published' => $row['datetime'],
                        'date_modified' => $row['timestamp'] ?? $row['datetime'],
                        'author' => htmlspecialchars_decode($row['author']),
                        'slug' => htmlspecialchars_decode($row['url']),
                        'image' => htmlspecialchars_decode($row['image_url'] ?? '')
                    ];
                }
                $pageSpecificData['blogPosts'] = $blogPosts;
            }
            break;
        case 'FAQPage':
            $faqs_query = mysqli_query($DB_CONN, "SELECT * FROM faqs WHERE lang_id = '{$lang_id}'");
            if ($faqs_query) {
                $faqs = [];
                while ($row = mysqli_fetch_assoc($faqs_query)) {
                    $faqs[] = [
                        'question' => htmlspecialchars_decode($row['question']),
                        'answer' => htmlspecialchars_decode($row['answer'])
                    ];
                }
                $pageSpecificData['faqs'] = $faqs;
            }
            break;
        case 'ProductPage':
            break;
        case 'ProductsPage':
            break;
        case 'ContactPage':
            break;
    }

    if ($base_key === "home") {
        $pageSpecificData['breadcrumbs'] = [
            ['url' => $siteURL, 'name' => 'Home']
        ];
    } else {
        $pageSpecificData['breadcrumbs'] = [
            ['url' => $siteURL, 'name' => $siteName],
            ['url' => $canonical_url, 'name' => ucfirst($base_key)]
        ];
    }

    $schema = generateUnifiedSchema($pageSpecificData, $product);
    $minifiedSchema = preg_replace('/\s+/', ' ', json_encode($schema, JSON_UNESCAPED_SLASHES));
    
    if (!$return_data) {
        // Existing Smarty assignments
        $smarty->assign('pagename', $page_title);
        $smarty->assign('page', $page);
        $smarty->assign('page_title', $full_title);
        assignSEOMetadata($smarty, $seo_data, $canonical_url);
        $smarty->assign('seo_schema', $minifiedSchema);
        $breadcrumb = [
            ['url' => $siteURL, 'name' => $siteName],
            ['url' => $canonical_url, 'name' => ucfirst($assign_name ?: $base_key)]
        ];
        $smarty->assign('breadcrumb', $breadcrumb);
        $smarty->assign('site_name', $siteName);
        $smarty->assign('site_url', $siteURL);
        $smarty->assign('site_logo', $siteLogo);
    } else {
        // Return data for API
        return [
            'metadata' => assignSEOMetadata(null, $seo_data, $canonical_url, true),
            'schema' => $schema
        ];
    }
}
function assignSEOMetadata($smarty, $data, $current_url, $return_data = false) {
    global $seo_settings, $siteName;
    
    $clean_description = substr(strip_tags($data['description'] ?? ''), 0, 160);
    $og_description = substr(strip_tags($data['description'] ?? ''), 0, 200);
    $image_url = $data['image_url'] ?? $seo_settings["image"];
    
    $type_data = [
        'website' => ['og' => 'website', 'twitter' => 'summary'],
        'product' => ['og' => 'product', 'twitter' => 'summary_large_image'],
        'news' => ['og' => 'article', 'twitter' => 'summary_large_image'],
        'profile' => ['og' => 'profile', 'twitter' => 'summary_large_image'],
        'article' => ['og' => 'article', 'twitter' => 'summary_large_image']
    ];
    
    $type = $data['type'] ?? 'website';
    $type_info = $type_data[$type] ?? $type_data['website'];
    
    $assignments = [
        'page_title' => $data['page_title'],
        'pagename' => $data['pagename'],
        'seo_description' => $clean_description,
        'seo_keywords' => $data['keywords'] ?? '',
        'seo_canonical' => $current_url,
        'seo_robots' => $data['robots'] ?? 'index, follow',
        'seo_og_type' => $type_info['og'],
        'seo_og_title' => $data['page_title'],
        'seo_og_description' => $og_description,
        'seo_og_url' => $current_url,
        'seo_og_image' => $image_url,
        'site_name' => $siteName,
        'seo_twitter_card' => $type_info['twitter'],
        'seo_twitter_title' => $data['page_title'],
        'seo_twitter_description' => $og_description,
        'seo_twitter_image' => $image_url,
        'seo_twitter_site' => $seo_settings["twitter_username"],
        'seo_lang' => $seo_settings['primaryLanguage'],
        'seo_favicon' => $seo_settings['favicon'],
        'seo_theme_color' => $seo_settings['theme_color'],
        'seo_background_color' => $seo_settings['background_color'],
        'seo_manifest_url' => '/webmanifest'
    ];

    // Assign to Smarty if provided
    if ($smarty) {
        foreach ($assignments as $key => $value) {
            if (!empty($value)) {
                $smarty->assign($key, $value);
            }
        }
    }

    // Return structured data if requested
    if ($return_data) {
        return [
            'basic' => [
                'title' => $assignments['page_title'],
                'pagename' => $assignments['pagename'],
                'description' => $assignments['seo_description'],
                'keywords' => $assignments['seo_keywords'],
                'robots' => $assignments['seo_robots'],
                'lang' => $assignments['seo_lang'],
                'canonical' => $assignments['seo_canonical']
            ],
            'og' => [
                'type' => $assignments['seo_og_type'],
                'title' => $assignments['seo_og_title'],
                'description' => $assignments['seo_og_description'],
                'url' => $assignments['seo_og_url'],
                'image' => $assignments['seo_og_image'],
                'site_name' => $assignments['site_name']
            ],
            'twitter' => [
                'card' => $assignments['seo_twitter_card'],
                'title' => $assignments['seo_twitter_title'],
                'description' => $assignments['seo_twitter_description'],
                'image' => $assignments['seo_twitter_image'],
                'site' => $assignments['seo_twitter_site']
            ],
            'pwa' => [
                'favicon' => $assignments['seo_favicon'],
                'theme_color' => $assignments['seo_theme_color'],
                'background_color' => $assignments['seo_background_color'],
                'manifest_url' => $assignments['seo_manifest_url']
            ]
        ];
    }
}

function checkLicense($domain, $feature = 'translate') {
    $url = "https://api.bitders.com/check/" . urlencode($domain);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        return isset($result[$feature]) && $result[$feature] === true;
    }
    
    return false;
}
function performTranslationTest($texts, $targetLang, $domain) {
    // Static variable to track the last request time across function calls
    static $lastRequestTime = 0;
    
    // Calculate time since last request (in microseconds)
    $currentTime = microtime(true) * 1000000;
    $timeSinceLastRequest = $currentTime - $lastRequestTime;
    
    // If less than 200ms has passed since last request, delay execution
    if ($lastRequestTime > 0 && $timeSinceLastRequest < 200000) {
        $delayMicroseconds = 200000 - $timeSinceLastRequest;
        usleep($delayMicroseconds);
    }
    
    // Update the last request time
    $lastRequestTime = microtime(true) * 1000000;
    
    // Handle both single text and array input
    $textsArray = is_array($texts) ? $texts : [$texts];
    
    // Limit batch size to maximum 20 items per request
    if (count($textsArray) > 20) {
        // If more than 20 texts, split into chunks and process sequentially
        $results = ['http_code' => 200, 'response' => ['translations' => []]];
        $chunks = array_chunk($textsArray, 20);
        
        foreach ($chunks as $chunk) {
            $chunkResult = performTranslationTest($chunk, $targetLang, $domain);
            
            if ($chunkResult['http_code'] != 200) {
                return $chunkResult; // Return error if any chunk fails
            }
            
            $results['response']['translations'] = array_merge(
                $results['response']['translations'], 
                $chunkResult['response']['translations']
            );
        }
        
        // Return in original format for backward compatibility
        if (!is_array($texts)) {
            return [
                'http_code' => $results['http_code'],
                'response' => [
                    'translation' => $results['response']['translations'][0] ?? ''
                ]
            ];
        }
        
        return $results;
    }
    
    $postData = [
        'text' => json_encode($textsArray),
        'target_language' => $targetLang,
        'domain' => $domain,
    ];
    
    $url = "https://api.bitders.com/translate/" . urlencode($domain);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    
    // Add a simple retry mechanism for failed requests
    if ($httpCode != 200 || empty($result['translations'])) {
        // Wait 500ms and retry once
        usleep(500000);
        return performTranslationTest($texts, $targetLang, $domain);
    }
    
    // Return in original format for backward compatibility
    if (!is_array($texts)) {
        return [
            'http_code' => $httpCode,
            'response' => [
                'translation' => $result['translations'][0] ?? ''
            ]
        ];
    }
    
    return [
        'http_code' => $httpCode,
        'response' => [
            'translations' => $result['translations'] ?? []
        ]
    ];
}

function processContentTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = false;
    $result = mysqli_query($DB_CONN, "SELECT page, content_data FROM content WHERE lang_id = 1");
    
    while ($row = mysqli_fetch_assoc($result)) {
        $content_array = json_decode($row['content_data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($content_array)) {
            $texts_to_translate = [];
            $key_mapping = [];
            
            // Only collect texts for translation if we should translate
            if ($should_translate) {
                foreach ($content_array as $key => $value) {
                    if (is_string($value) && !empty($value)) {
                        $texts_to_translate[] = $value;
                        $key_mapping[] = $key;
                    }
                }
                
                if (!empty($texts_to_translate)) {
                    $translated = performTranslationTest($texts_to_translate, $code, $domain);
                    
                    if ($translated['http_code'] == 200) {
                        foreach ($translated['response']['translations'] as $index => $translation) {
                            $key = $key_mapping[$index];
                            $content_array[$key] = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
                        }
                    }
                }
            }
            
            $success = mysqli_query($DB_CONN, sprintf(
                "INSERT INTO content (page, content_data, lang_id) VALUES ('%s', '%s', '%s')",
                mysqli_real_escape_string($DB_CONN, $row['page']),
                mysqli_real_escape_string($DB_CONN, json_encode($content_array, JSON_UNESCAPED_UNICODE)),
                $new_lang_id
            ));
        }
    }
    return $success;
}
function processTelegramBotTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = false;
    $result = mysqli_query($DB_CONN, "SELECT 
        page, content_data, menu_line, menu_order, image_url, 
        allow_loading, buttons, default_templates, use_default, 
        show_in_menu, previous_page 
        FROM telegram_bot WHERE lang_id = 1");
    
    if (!$result) {
        error_log("SQL Error in telegram_bot query: " . mysqli_error($DB_CONN));
        return false;
    }

    $bot_entries = [];
    $texts_to_translate = [];
    $key_mapping = [];
    
    // Process each row to extract translatable content
    while ($row = mysqli_fetch_assoc($result)) {
        $bot_entries[] = $row;
        $content_array = json_decode($row['content_data'], true);
        
        // Skip invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($content_array)) {
            continue;
        }
        
        // Only collect texts for translation if we should translate
        if ($should_translate) {
            // Collect all translatable text fields from content_data
            foreach ($content_array as $key => $value) {
                if (is_string($value) && !empty($value)) {
                    $texts_to_translate[] = $value;
                    $key_mapping[] = [
                        'page' => $row['page'],
                        'type' => 'content',
                        'key' => $key
                    ];
                }
            }
            
            // Check for nested objects in default_templates
            $templates = null;
            if (isset($row['default_templates']) && !empty($row['default_templates'])) {
                $templates = json_decode($row['default_templates'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($templates)) {
                    foreach ($templates as $template_key => $template_value) {
                        if (is_string($template_value) && !empty($template_value)) {
                            $texts_to_translate[] = $template_value;
                            $key_mapping[] = [
                                'page' => $row['page'],
                                'type' => 'template',
                                'key' => $template_key
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // Translate all collected text fields if needed
    $translated_content = [];
    $translated_templates = [];
    
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);
        
        if ($translated['http_code'] == 200) {
            $translations = $translated['response']['translations'];
            
            // Process translations back into the data structure
            foreach ($translations as $index => $translation) {
                $mapping = $key_mapping[$index];
                $page = $mapping['page'];
                
                if ($mapping['type'] === 'content') {
                    if (!isset($translated_content[$page])) {
                        $translated_content[$page] = [];
                    }
                    $translated_content[$page][$mapping['key']] = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
                } else if ($mapping['type'] === 'template') {
                    if (!isset($translated_templates[$page])) {
                        $translated_templates[$page] = [];
                    }
                    $translated_templates[$page][$mapping['key']] = html_entity_decode($translation, ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }
    
    // Insert entries (translated or original)
    foreach ($bot_entries as $entry) {
        $page = $entry['page'];
        $content_array = json_decode($entry['content_data'], true);
        
        // Skip invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($content_array)) {
            continue;
        }
        
        // Update content fields with translations if available
        if ($should_translate && isset($translated_content[$page])) {
            foreach ($translated_content[$page] as $key => $value) {
                $content_array[$key] = $value;
            }
        }
        
        // Handle templates
        $templates_json = $entry['default_templates'];
        if ($should_translate && isset($translated_templates[$page]) && !empty($templates_json)) {
            $templates = json_decode($templates_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($templates)) {
                foreach ($translated_templates[$page] as $key => $value) {
                    $templates[$key] = $value;
                }
                $templates_json = json_encode($templates, JSON_UNESCAPED_UNICODE);
            }
        }
        
        // Insert the new entry
        $query = sprintf(
            "INSERT INTO telegram_bot (page, content_data, lang_id, menu_line, menu_order, image_url, allow_loading, buttons, default_templates, use_default, show_in_menu, previous_page) 
            VALUES ('%s', '%s', %d, '%s', %d, '%s', %d, '%s', '%s', %d, %d, '%s')",
            mysqli_real_escape_string($DB_CONN, $page),
            mysqli_real_escape_string($DB_CONN, json_encode($content_array, JSON_UNESCAPED_UNICODE)),
            $new_lang_id,
            mysqli_real_escape_string($DB_CONN, $entry['menu_line']),
            intval($entry['menu_order']),
            mysqli_real_escape_string($DB_CONN, $entry['image_url'] ?? ''),
            intval($entry['allow_loading']),
            mysqli_real_escape_string($DB_CONN, $entry['buttons'] ?? ''),
            mysqli_real_escape_string($DB_CONN, $templates_json ?? ''),
            intval($entry['use_default']),
            intval($entry['show_in_menu']),
            mysqli_real_escape_string($DB_CONN, $entry['previous_page'] ?? '')
        );
        
        $result = mysqli_query($DB_CONN, $query);
        if (!$result) {
            error_log("Error inserting telegram_bot entry: " . mysqli_error($DB_CONN));
            return false;
        }
        $success = true;
    }
    
    return $success;
}

function processAlertsTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = false;
    $result = mysqli_query($DB_CONN, "SELECT page, class, content, ref, status, id FROM alerts WHERE lang_id = 1");
    
    $alerts = [];
    $texts_to_translate = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $alerts[] = $row;
        if ($should_translate) {
            $texts_to_translate[] = $row['content'];
        }
    }
    
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);
        
        if ($translated['http_code'] == 200) {
            foreach ($alerts as $index => $alert) {
                $content = $should_translate ? 
                    ($translated['response']['translations'][$index] ?? $alert['content']) : 
                    $alert['content'];
                
                $success = mysqli_query($DB_CONN, sprintf(
                    "INSERT INTO alerts (page, class, content, ref, lang_id, status, parent_id) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                    mysqli_real_escape_string($DB_CONN, $alert['page']),
                    mysqli_real_escape_string($DB_CONN, $alert['class']),
                    mysqli_real_escape_string($DB_CONN, html_entity_decode($content, ENT_QUOTES, 'UTF-8')),
                    mysqli_real_escape_string($DB_CONN, $alert['ref']),
                    $new_lang_id,
                    mysqli_real_escape_string($DB_CONN, $alert['status']),
                    mysqli_real_escape_string($DB_CONN, $alert['id'])
                ));
            }
        }
    } else {
        // Just copy without translation
        foreach ($alerts as $alert) {
            $success = mysqli_query($DB_CONN, sprintf(
                "INSERT INTO alerts (page, class, content, ref, lang_id, status, parent_id) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                mysqli_real_escape_string($DB_CONN, $alert['page']),
                mysqli_real_escape_string($DB_CONN, $alert['class']),
                mysqli_real_escape_string($DB_CONN, $alert['content']),
                mysqli_real_escape_string($DB_CONN, $alert['ref']),
                $new_lang_id,
                mysqli_real_escape_string($DB_CONN, $alert['status']),
                mysqli_real_escape_string($DB_CONN, $alert['id'])
            ));
        }
    }
    return $success;
}

function processFaqsTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = false;
    $result = mysqli_query($DB_CONN, "SELECT question, answer, category_id, product_id, news_id FROM faqs WHERE lang_id = 1");
    
    $texts_to_translate = [];
    $faqs = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $faqs[] = $row;
        if ($should_translate) {
            $texts_to_translate[] = $row['question'];
            $texts_to_translate[] = $row['answer'];
        }
    }
    
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);
        
        if ($translated['http_code'] == 200) {
            $translations = $translated['response']['translations'];
            
            foreach ($faqs as $index => $faq) {
                $question_index = $index * 2;
                $answer_index = $question_index + 1;
                
                $translated_question = $translations[$question_index] ?? $faq['question'];
                $translated_answer = $translations[$answer_index] ?? $faq['answer'];
                
                $category_id = $faq['category_id'] ?? 'NULL';
                $product_id = $faq['product_id'] ?? 'NULL';
                $news_id = $faq['news_id'] ?? 'NULL';

                // Insert the translated FAQ
                $success = mysqli_query($DB_CONN, sprintf(
                    "INSERT INTO faqs (question, answer, lang_id, category_id, product_id, news_id) VALUES ('%s', '%s', '%s', %s, %s, %s)",
                    mysqli_real_escape_string($DB_CONN, html_entity_decode($translated_question, ENT_QUOTES, 'UTF-8')),
                    mysqli_real_escape_string($DB_CONN, html_entity_decode($translated_answer, ENT_QUOTES, 'UTF-8')),
                    $new_lang_id,
                    $category_id === 'NULL' ? $category_id : intval($category_id),
                    $product_id === 'NULL' ? $product_id : intval($product_id),
                    $news_id === 'NULL' ? $news_id : intval($news_id)
                ));
            }
        }
    } else {
        // Just copy without translation
        foreach ($faqs as $faq) {
            $category_id = $faq['category_id'] ?? 'NULL';
            $product_id = $faq['product_id'] ?? 'NULL';
            $news_id = $faq['news_id'] ?? 'NULL';
            
            $success = mysqli_query($DB_CONN, sprintf(
                "INSERT INTO faqs (question, answer, lang_id, category_id, product_id, news_id) VALUES ('%s', '%s', '%s', %s, %s, %s)",
                mysqli_real_escape_string($DB_CONN, $faq['question']),
                mysqli_real_escape_string($DB_CONN, $faq['answer']),
                $new_lang_id,
                $category_id === 'NULL' ? $category_id : intval($category_id),
                $product_id === 'NULL' ? $product_id : intval($product_id),
                $news_id === 'NULL' ? $news_id : intval($news_id)
            ));
        }
    }
    return $success;
}
function processTasksTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = true;
    $result = mysqli_query($DB_CONN, "SELECT 
        id, name, url, image_url, content, type, details, 
        instructions, instructions_image_url, status, 
        amount_min, amount_max 
        FROM tasks WHERE lang_id = 1");
    
    if (!$result) {
       return false;
    }

    $tasks = [];
    $texts_to_translate = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $tasks[] = $row;
        
        // Only collect texts if we should translate
        if ($should_translate) {
            if (!empty($row['name'])) {
                $texts_to_translate[] = $row['name'];
            }
            if (!empty($row['content'])) {
                $texts_to_translate[] = $row['content'];
            }
            if (!empty($row['instructions'])) {
                $texts_to_translate[] = $row['instructions'];
            }
        }
    }
    
    $translations = [];
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);
        
        if ($translated['http_code'] == 200) {
            $translations = $translated['response']['translations'];
        }
    }
    
    $current_index = 0;
    foreach ($tasks as $task) {
        // Default to original values
        $task_name = $task['name'];
        $task_content = $task['content'];
        $task_instructions = $task['instructions'];
        
        // Use translated content if available
        if ($should_translate && !empty($translations)) {
            if (!empty($task['name'])) {
                $task_name = $translations[$current_index++] ?? $task['name'];
            }
            if (!empty($task['content'])) {
                $task_content = $translations[$current_index++] ?? $task['content'];
            }
            if (!empty($task['instructions'])) {
                $task_instructions = $translations[$current_index++] ?? $task['instructions'];
            }
            
            // Apply entity decoding if translated
            $task_name = html_entity_decode($task_name, ENT_QUOTES, 'UTF-8');
            $task_content = html_entity_decode($task_content, ENT_QUOTES, 'UTF-8');
            $task_instructions = html_entity_decode($task_instructions, ENT_QUOTES, 'UTF-8');
        }
        
        // Insert task with either translated or original content
        $query = sprintf(
            "INSERT INTO tasks (
                name, url, image_url, content, type, details, 
                instructions, instructions_image_url, status, 
                amount_min, amount_max, lang_id, translation_of
            ) VALUES (
                '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', 
                %d, %f, %f, %d, %d
            )",
            mysqli_real_escape_string($DB_CONN, $task_name),
            mysqli_real_escape_string($DB_CONN, $task['url']),
            mysqli_real_escape_string($DB_CONN, $task['image_url']),
            mysqli_real_escape_string($DB_CONN, $task_content),
            intval($task['type']),
            mysqli_real_escape_string($DB_CONN, $task['details']),
            mysqli_real_escape_string($DB_CONN, $task_instructions),
            mysqli_real_escape_string($DB_CONN, $task['instructions_image_url']),
            intval($task['status']),
            floatval($task['amount_min']),
            floatval($task['amount_max']),
            intval($new_lang_id),
            intval($task['id'])
        );
        
        $success = mysqli_query($DB_CONN, $query);
        
        if (!$success) {
            $success = false;
        }
    }
    
    return $success;
}
function getUniqueSlug($DB_CONN, $base_slug, $lang_code, $table_name) {
    $slug = $base_slug . '-' . $lang_code;
    $counter = 1;

    while (true) {
        $query = "SELECT id FROM $table_name WHERE slug = '$slug'";
        $result = mysqli_query($DB_CONN, $query);
        
        if (mysqli_num_rows($result) == 0) {
            break;  
        }

        $slug = $base_slug . '-' . $lang_code . '-' . $counter;
        $counter++;
    }

    return $slug;
}
function processCategoriesTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = true;
    $result = mysqli_query($DB_CONN, "SELECT id, name, description, slug, parent_id, seo_robots FROM categories WHERE lang_id = 1");

    if (!$result) {
        error_log("SQL Error: " . mysqli_error($DB_CONN));
        return false;
    }

    $texts_to_translate = [];
    $categories = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
        
        // Only collect texts if we should translate
        if ($should_translate) {
            $texts_to_translate[] = $row['name'];
            if (!empty($row['description'])) {
                $texts_to_translate[] = $row['description'];
            }
        }
    }

    $translations = [];
    // Translate if needed and texts are available
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);

        if ($translated['http_code'] == 200) {
            $translations = $translated['response']['translations'];
        } else {
            error_log("Translation API Error: " . json_encode($translated));
            // Continue with original content if translation fails
        }
    }

    $current_translation_index = 0;
    foreach ($categories as $category) {
        // Use translated content if available, otherwise use original
        $name = $category['name'];
        $description = $category['description'];
        
        if ($should_translate && !empty($translations)) {
            $name = $translations[$current_translation_index++] ?? $category['name'];
            
            if (!empty($category['description'])) {
                $description = $translations[$current_translation_index++] ?? $category['description'];
            }
        }

        // Generate unique slug with language suffix
        $new_slug = getUniqueSlug($DB_CONN, $category['slug'], $code, 'categories');

        // Insert category with either translated or original content
        $insert_query = sprintf(
            "INSERT INTO categories (name, description, slug, parent_id, seo_robots, lang_id, translation_of) 
            VALUES ('%s', %s, '%s', %s, '%s', '%s', '%s')",
            mysqli_real_escape_string($DB_CONN, html_entity_decode($name, ENT_QUOTES, 'UTF-8')),
            $description ? "'" . mysqli_real_escape_string($DB_CONN, html_entity_decode($description, ENT_QUOTES, 'UTF-8')) . "'" : 'NULL',
            mysqli_real_escape_string($DB_CONN, $new_slug),
            $category['parent_id'] ? intval($category['parent_id']) : 'NULL',
            mysqli_real_escape_string($DB_CONN, $category['seo_robots']),
            $new_lang_id,
            $category['id']
        );

        if (!mysqli_query($DB_CONN, $insert_query)) {
            error_log("Insert Error: " . mysqli_error($DB_CONN));
            $success = false;
        }
    }

    return $success;
}
function processNewsTranslation($DB_CONN, $new_lang_id, $code, $domain, $should_translate = false) {
    $success = true;
    $result = mysqli_query($DB_CONN, "
        SELECT id, title, content, short_description, slug, keywords, 
               seo_robots, image_url, datetime, schema_type, 
               include_in_sitemap, status, author
        FROM news 
        WHERE lang_id = 1
    ");
    
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($DB_CONN));
        return false;
    }

    $texts_to_translate = [];
    $news_items = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $news_items[] = $row;
        
        // Only collect texts if we should translate
        if ($should_translate) {
            $texts_to_translate[] = $row['title'];
            $texts_to_translate[] = $row['short_description'];
            $texts_to_translate[] = $row['content'];
        }
    }

    $translations = [];
    if ($should_translate && !empty($texts_to_translate)) {
        $translated = performTranslationTest($texts_to_translate, $code, $domain);
        
        if ($translated['http_code'] == 200) {
            $translations = $translated['response']['translations'];
        } else {
            error_log("Translation API Error: " . json_encode($translated));
            // Continue with original content if translation fails
        }
    }

    $current_translation_index = 0;
    foreach ($news_items as $news) {
        // Default to original values
        $title = $news['title'];
        $short_description = $news['short_description'];
        $content = $news['content'];
        
        // Use translated content if available
        if ($should_translate && !empty($translations)) {
            $title = $translations[$current_translation_index++] ?? $news['title'];
            $short_description = $translations[$current_translation_index++] ?? $news['short_description'];
            $content = $translations[$current_translation_index++] ?? $news['content'];
            
            // Apply entity decoding if translated
            $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
            $short_description = html_entity_decode($short_description, ENT_QUOTES, 'UTF-8');
            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        }

        $new_slug = getUniqueSlug($DB_CONN, $news['slug'], $code, 'news');
        
        // Insert news with either translated or original content
        $insert_query = sprintf(
            "INSERT INTO news (title, content, short_description, slug, keywords, 
                seo_robots, lang_id, translation_of, image_url, datetime, 
                schema_type, include_in_sitemap, status, author
            ) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%s', '%s')",
            mysqli_real_escape_string($DB_CONN, $title),
            mysqli_real_escape_string($DB_CONN, $content),
            mysqli_real_escape_string($DB_CONN, $short_description),
            mysqli_real_escape_string($DB_CONN, $new_slug),
            mysqli_real_escape_string($DB_CONN, $news['keywords']),
            mysqli_real_escape_string($DB_CONN, $news['seo_robots']),
            $new_lang_id,
            $news['id'],
            mysqli_real_escape_string($DB_CONN, $news['image_url']),
            mysqli_real_escape_string($DB_CONN, $news['datetime']),
            mysqli_real_escape_string($DB_CONN, $news['schema_type']),
            mysqli_real_escape_string($DB_CONN, $news['include_in_sitemap']),
            mysqli_real_escape_string($DB_CONN, $news['status']),
            mysqli_real_escape_string($DB_CONN, $news['author'])
        );

        if (!mysqli_query($DB_CONN, $insert_query)) {
            error_log("Insert Error: " . mysqli_error($DB_CONN));
            $success = false;
        }
    }
    
    return $success;
}
function processTrade($trade_id, $current_price) {
    global $DB_CONN, $trading_settings, $data;
    $data = [
        'result' => false,
        'message' => 'Trade not found or already closed'
    ];

    $query = "SELECT * FROM trades WHERE id = '$trade_id' AND status = 0";
    $result = mysqli_query($DB_CONN, $query);

    if (!$result || mysqli_num_rows($result) == 0)
        return $data;

    $trade = mysqli_fetch_assoc($result);
    $isWin = false;
    $pnl = 0;
    $tradeResult = 'loss';
    switch ($trade['calc_type']) {
        case 'spot':
        if ($current_price == $trade['entry_price']) {
            $tradeResult = 'closed';
            $isWin = false;
            $pnl = 0;
        } else {
            $pnl = (($trade['qty']/$trade['entry_price'])*$current_price)-$trade['qty'];
            if($pnl > 0) {
                $isWin = true;
                $tradeResult = 'win';
            } else
                $tradeResult = 'loss';
        }
        $data = tradetxn($trade, $current_price, $pnl, $tradeResult, $isWin);
        break;
        case 'future':
        if ($current_price == $trade['entry_price']) {
            $tradeResult = 'closed';
            $isWin = false;
            $pnl = 0;
        } else {
            $leverage = isset($trade['leverage']) ? $trade['leverage'] : 1; // Default to 1 if not set
            
            if ($trade['trade_type'] === 'long') {
                $isWin = $current_price > $trade['entry_price'];
                if ($isWin) {
                    $priceDiff = $current_price - $trade['entry_price'];
                    $percentChange = ($priceDiff / $trade['entry_price']) * 100;
                    $pnl = $trade['qty'] * ($percentChange / 100) * $leverage;
                } else {
                    $priceDiff = $trade['entry_price'] - $current_price;
                    $percentChange = ($priceDiff / $trade['entry_price']) * 100;
                    $pnl = -($trade['qty'] * ($percentChange / 100) * $leverage);
                }
            } else if ($trade['trade_type'] === 'short') {
                $isWin = $current_price < $trade['entry_price'];
                if ($isWin) {
                    $priceDiff = $trade['entry_price'] - $current_price;
                    $percentChange = ($priceDiff / $trade['entry_price']) * 100;
                    $pnl = $trade['qty'] * ($percentChange / 100) * $leverage;
                } else {
                    $priceDiff = $current_price - $trade['entry_price'];
                    $percentChange = ($priceDiff / $trade['entry_price']) * 100;
                    $pnl = -($trade['qty'] * ($percentChange / 100) * $leverage);
                }
            }
            
            $tradeResult = $isWin ? 'win' : 'loss';
            
            // Check for liquidation (loss exceeds margin)
            if ($pnl < 0 && abs($pnl) >= $trade['qty']) {
                $pnl = -$trade['qty']; // Total loss of margin
                $tradeResult = 'loss';
            }
        }
        $fee = 0;
        if($trading_settings['taker_fee']) {
            $fee = $trading_settings['taker_fee'] * $leverage;
        }
        $data = tradetxn($trade, $current_price, $pnl, $tradeResult, $isWin, $fee);
        break;
        default:
        if ($current_price == $trade['entry_price']) {
            $tradeResult = 'closed';
            $isWin = false;
            $pnl = 0;
        } else {
            if ($trade['trade_type'] === 'long') {
                $isWin = $current_price > $trade['entry_price'];
            } else if ($trade['trade_type'] === 'short') {
                $isWin = $current_price < $trade['entry_price'];
            }

            if ($isWin) {
                $pnl = ($trade['qty'] * ($trading_settings['win_rate']/100));
                $tradeResult = 'win';
            } else {
                $pnl = -($trade['qty'] * ($trading_settings['loss_rate']/100));
                $tradeResult = 'loss';
            }
        }
        $data = tradetxn($trade, $current_price, $pnl, $tradeResult, $isWin);
        break;
    }
    return $data;
}

function tradetxn($trade, $current_price, $pnl, $tradeResult, $isWin, $fee = 0) {
    global $DB_CONN;
    mysqli_query($DB_CONN, "START TRANSACTION");
    try {
        // Update trade status
        $updateTrade = "UPDATE trades 
                       SET status = 1,
                           exit_price = '$current_price',
                           pnl = '$pnl',
                           trade_result = '$tradeResult',
                           closed_by = 'user',
                           timestamp = CURRENT_TIMESTAMP
                       WHERE id = '{$trade['id']}'";
        if (!mysqli_query($DB_CONN, $updateTrade)) {
            throw new Exception("Error updating trade");
        }

        // Update user balance
        $totalBalanceChange = $trade['qty'] + $pnl;
        if($fee) {
            $totalBalanceChange = $totalBalanceChange-(($totalBalanceChange/100)*$fee);
        }
        add_balance($trade['user_id'], $trade['payment_method_id'], $totalBalanceChange);

        // Process referral commissions for winning trades
        if ($isWin && $trade['payment_method_id'] == '19') {
            processReferralCommissions($trade, $pnl);
        }

        mysqli_query($DB_CONN, "COMMIT");
        call_alert(1003);
        $data['result'] = true;
        $data['trade_result'] = $tradeResult;
        $data['pnl'] = $pnl;
    } catch (Exception $e) {
        mysqli_query($DB_CONN, "ROLLBACK");
        $data['result'] = false;
        $data['message'] = 'Error processing trade: ' . $e->getMessage();
    }
    return $data;
}

function processReferralCommissions($trade, $pnl) {
    // Get user details
    global $DB_CONN, $referral_settings;
    $commissionLevels = $referral_settings['tier'][2]['level'];
    if(!$commissionLevels)
        return false;
    $query = "SELECT * FROM users WHERE id = '{$trade['user_id']}'";
    $result = mysqli_query($DB_CONN, $query);

    $user = mysqli_fetch_assoc($result);
    $sponsorId = $user['sponsor'];
    foreach ($commissionLevels as $key => $value) {
        if (!$sponsorId)
            break;
        $commissionAmount = ($pnl / 100) * $value;
        $detail = "Trade sponsor commission from " . $user['username']." from level {$key}";

        // Insert commission transaction
        $insertTxn = "INSERT INTO transactions 
                      (detail, user_id, amount, txn_type, payment_method_id, ref_id)
                      VALUES ('$detail', '$sponsorId', '$commissionAmount', 'referral', '{$trade['payment_method_id']}', '{$trade['id']}')";
        
        if (!mysqli_query($DB_CONN, $insertTxn)) {
            throw new Exception("Error inserting commission transaction");
        }

        add_balance($sponsorId, $trade['payment_method_id'], $commissionAmount);

        $result = mysqli_query($DB_CONN, "SELECT sponsor FROM users WHERE id = '$sponsorId'");
        
        if (!$result || mysqli_num_rows($result) == 0) {
            break;
        }
        
        $sponsorRow = mysqli_fetch_assoc($result);
        if (!$sponsorRow['sponsor']) {
            break;
        }
        
        $sponsorId = $sponsorRow['sponsor'];
    }
}
function addDays($startdate, $days, $duration, $exclude_days) {
  $d = new DateTime( $startdate );
  $t = $d->getTimestamp();
  for($i=0; $i<$days; $i++){
    $addDay = $duration;
    $nextDay = date('l', ($t+$addDay));
    if(in_array($nextDay, $exclude_days))
        $i--;
    $t = $t+$addDay;
  }
  return $t;
}
function withdraw_redirect($wid, $method, $tx_id = '') {
    global $withdraw_settings, $DB_CONN, $smarty;
    if($withdraw_settings['redirect'] == 'true'){
    $d = mysqli_query($DB_CONN, "SELECT * FROM `transactions` where id = '{$wid}'");
    while($de = mysqli_fetch_assoc($d)) {
      $de['id'] = md5($de['id']);
      $de['type'] = ucfirst($de['txn_type']);
      $de['currency'] = $method['name'];
      $de['symbol'] = $method['symbol'];
      $de['cid'] = $de['payment_method_id'];
     if($de['txn_type'] == 'withdraw') {
        if($withdraw_settings['autowithdraw_weekdays'] && $de['plan_id'] == 1) {
          $day = date('l');
          if($day == "Sunday")
            $add = 86400;
          if($day == "Saturday")
            $add = 86400*2;
        } elseif($withdraw_settings['instantwithdraw_weekdays']) {
          $day = date('l');
          if($day == "Sunday")
            $add = 86400;
          if($day == "Saturday")
            $add = 86400*2;
        }
        $next = strtotime($de['created_at'])+($de['ref_id']*60+$add);
        $dtCurrent = DateTime::createFromFormat('U', time());
        $dtCreate = DateTime::createFromFormat('U', $next);
        // $diff = $dtCurrent->diff($dtCreate);
        // $interval = $diff->format("%d Days %h Hours %i Mins %s Sec");
        // $interval = preg_replace('/(^0| 0) (Days|Hours|Mins|Sec)/', '', $interval);
        // $de['delay_timer'] = max($interval, 0);
        $de['delay_time'] = date("F j, Y, g:i a", $next);
        $de['delay_timer'] = $next;
      }
      $rows[] = $de;
    }
    $smarty->assign('rows',$rows);
    if($tx_id){
    call_alert(61, $tx_id);
    }else{
    call_alert(63);
    }
    $smarty->display('user_withdraw_redirect.tpl');
    exit;
  }
}
function datef($dt, $format) {
    return date($format, strtotime($dt));
} 
function add_hidden($tpl_source, Smarty_Internal_Template $template)
{
global $token;
return add_hidden_fields_in_forms($tpl_source, $token);
}
function biasedCoinFlip($perce)
{
  $randomNumber = random_int(0, 99);
  return $randomNumber < $perce ? 1 : 0;
}
function user_withdraw_status($status) {
    switch ($status) {
        case '4':
            return "<span class='badge bg-warning badge-sm'>Fake</span>";
        case '3':
            return "<span class='badge bg-success badge-sm'>Instant Disabled</span>";
        case '2':
            return "<span class='badge bg-success badge-sm'>Auto Disabled</span>";
        case '1':
            return "<span class='badge bg-success badge-sm'>Auto Enabled</span>";
        default:
            return "<span class='badge badge-light-primary badge-sm'> Default</span>";
    }
}
function user_2fa_status($status) {
    return ($status != '') ? 
        "<span class='badge badge-light-success badge-sm'>Enabled</span>" : 
        "<span class='badge badge-light-danger badge-sm'>Disabled</span>";
}
function getBadge($txn_type) {
    $badgeClass = '';
    switch ($txn_type) {
        case 'deposit':
        case 'invest':
            $badgeClass = 'badge-light-success';
            break;
        case 'earning':
        case 'transfer':
        case 'exchange':
            $badgeClass = 'badge-light-info';
            break;
        case 'bonus':
            $badgeClass = 'badge-light-secondary';
            break;
        case 'penalty':
        case 'release':
        case 'return':
        case 'faucet':
            $badgeClass = 'badge-light-warning';
            break;
        case 'withdraw':
            $badgeClass = 'badge-light-danger';
            break;
        case 'referral':
            $badgeClass = 'badge-light-primary';
            break;
        default:
            $badgeClass = 'badge-light-default';
            break;
    }
    return "<span class='badge $badgeClass badge-sm'>" . ucfirst($txn_type) . "</span>";
}
function isPlatformAllowed($platform) {
    global $telegram_settings;
    
    
    // Normalize the platform string
    $platform = strtolower(trim($platform));
    
    // Define the mapping of platforms to their settings keys
    $platform_settings_map = [
        'ios' => 'allow_ios',
        'android' => 'allow_android',
        'tdesktop' => 'allow_tdesktop',
        'web' => 'allow_web',
        'webz' => 'allow_webz',
        'webk' => 'allow_webk',
        'macos' => 'allow_macos'
    ];
    

    
    // Get the corresponding settings key
    $settings_key = $platform_settings_map[$platform];
    

    // Log the result
    $is_allowed = (bool)$telegram_settings[$settings_key];
    
    return $is_allowed;
}
function incrementVersion($version) {
    $versionParts = explode('.', str_replace('V', '', $version));
    $versionParts[count($versionParts) - 1] += 1;
    $newVersion = 'V' . implode('.', $versionParts);
    return $newVersion;
}
function getContent($page, $field = null) {
    global $content;
    
    if (!isset($content[$page])) {
        return null;
    }
    
    if ($field === null) {
        return $content[$page];
    }
    
    return $content[$page][$field] ?? null;
}
function faucets_list() {
    global $DB_CONN, $userinfo;
    $package_ = mysqli_query($DB_CONN, "SELECT * FROM `packages` WHERE etype = 4 and status = 1");
if(mysqli_num_rows($package_)) {
$packages = array();
$i = 0;
$amounts = array();
while ($package=mysqli_fetch_assoc($package_))
{
  $details = json_decode($package['details'], true);
  if($details['faucet'] && count($details['faucet'])) {
    $faucet = array_reverse($details['faucet'], true);
    $am = 0;
    $type = $faucet[0]['type'];
    $base = $faucet[0]['amount'];
    foreach ($faucet as $key => $value) {
      if($key) {
        $p = mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` WHERE package_id = '{$key}' and user_id = '{$userinfo['id']}' and status = 1");
        if(mysqli_num_rows($p) && $value['amount']) {
          $am = $value['amount'];
          $type = $value['type'];
          if($value['do'] == 1)
            $am += $base;
        }
      } 
      elseif($am == 0) {
        if($base)
          $am = $base;
      }
      if($am) {
        $amounts[$i]['amount'] = $am;
        $amounts[$i]['type'] = $type;
        $amounts[$i]['limit_error'] = false;
        $amounts[$i]['id'] = $package['id'];
        $amounts[$i]['cid'] = $package['limit_currency'];
        $amounts[$i]['remain_time'] = $amounts[$i]['remain_limit'] = $amounts[$i]['total_limit'] = false;
        $cond = getcondcode($details['limit_user_deposits_count_per'], "created_at");
        $ch = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE user_id = {$userinfo['id']} and txn_type = 'faucet' and status = 1 and ref_id = '{$package['id']}' and {$cond} order by id desc");
        if($details['limit_user_deposits_count'] <= mysqli_num_rows($ch)) {
          $ch = mysqli_fetch_assoc($ch);
          $next = strtotime($ch['created_at'])+getcondadd($details['limit_user_deposits_count_per']);
          $amounts[$i]['remain_time'] = date("Y-m-d H:i:s", $next);
        }
        if(!$amounts[$i]['remain_time'] && $details['limit_faucet_amount_per']) {
          $cond = getcondcode($details['limit_faucet_amount_per'], "created_at");
          $q2 = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT sum(amount) as c FROM `transactions` where txn_type = 'faucet' and {$cond}"))[0];
          $q2 = $q2 ?: 0;
          $amounts[$i]['total_limit'] = $details['limit_faucet_amount'];
          $amounts[$i]['remain_limit'] = $details['limit_faucet_amount'] - $q2;
          if($details['limit_faucet_amount'] <= $q2) {
            $amounts[$i]['limit_error'] = true;
            $next = strtotime("+ ".getcondadd($details['limit_faucet_amount_per'])." seconds");
            $amounts[$i]['remain_time'] = date("Y-m-d 00:00:00", $next);
          }
        }
        $i++;
        break;
      }
    }
  }
}}
return $amounts;
}
function minify_html($tpl_output, Smarty_Internal_Template $template)
{
    // First, protect scripts from being minified
    $tpl_output = preg_replace_callback(
        '/<script\b[^>]*>(.*?)<\/script>/is',
        function($matches) {
            // Store scripts and replace with markers
            return '<!--SCRIPT' . base64_encode($matches[0]) . 'SCRIPT-->';
        },
        $tpl_output
    );

    // Perform minification
    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
    );
    $replace = array(
        '>',
        '<',
        '\\1'
    );
    
    $tpl_output = preg_replace($search, $replace, $tpl_output);

    // Restore scripts
    $tpl_output = preg_replace_callback(
        '/<!--SCRIPT(.*?)SCRIPT-->/s',
        function($matches) {
            // Restore original scripts
            return base64_decode($matches[1]);
        },
        $tpl_output
    );

    return $tpl_output;
}
function getTelegramBotInfo($encrypted_token) {
    $token = encrypt_decrypt('decrypt', $encrypted_token);
    $url = "https://api.telegram.org/bot{$token}/getMe";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $botInfo = json_decode($response, true);
        return $botInfo['ok'] ? $botInfo['result'] : null;
    }
    
    return null;
}
// connections
// --- connection -- 
$pref = mysqli_query($DB_CONN,"select * from preferences where id=1");
if($pref && mysqli_num_rows($pref)>0)
{
$pref= mysqli_fetch_assoc($pref);
$site_settings = json_decode($pref['site_settings'], true);
$referral_settings = json_decode($pref['referral_settings'], true);
$security_settings = json_decode($pref['security_settings'], true);
$main_link = json_decode($pref['seo_links'], true);
$seo_settings = json_decode($pref['seo_settings'], true);
$admin_link = json_decode($pref['admin_links'], true);
$email_settings = json_decode($pref['email_settings'], true);
$alerts_settings = json_decode($pref['alerts_settings'], true);
$user_settings = json_decode($pref['user_settings'], true);
$register_settings = json_decode($pref['register_settings'], true);
$login_settings = json_decode($pref['login_settings'], true);
$kyc_settings = json_decode($pref['kyc_settings'], true);
$admin_settings = json_decode($pref['admin_settings'], true);
$deposit_settings = json_decode($pref['deposit_settings'], true);
$withdraw_settings = json_decode($pref['withdraw_settings'], true);
$transfer_settings = json_decode($pref['transfer_settings'], true);
$exchange_settings = json_decode($pref['exchange_settings'], true);
$trading_settings = json_decode($pref['trading_settings'], true);
$captcha_settings = json_decode($pref['captcha_settings'], true);
$infobox_settings = json_decode($pref['infobox_settings'], true);
$extra_pages = json_decode($pref['extra_pages'], true);
$versions = json_decode($pref['version'], true);
$tgtoken_encrypted = $pref['tgtoken'];
$tgtoken = $chattgtoken = $grouptgtoken = '';
if (!empty($tgtoken_encrypted)) {
    $telegram_tokens_encrypted = json_decode($tgtoken_encrypted, true);
    
    if (is_array($telegram_tokens_encrypted)) {
        foreach (['main' => 'tgtoken', 'chat' => 'chattgtoken', 'group' => 'grouptgtoken'] as $type => $var) {
            $token_key = $type . '_token';
            if (!empty($telegram_tokens_encrypted[$token_key])) {
                ${$var} = encrypt_decrypt('decrypt', $telegram_tokens_encrypted[$token_key]);
            }
        }
    }
}
if(!isset($_SESSION))
{
    // Use the user_session value from security settings (in minutes)
    $session_minutes = isset($security_settings['user_session']) ? intval($security_settings['user_session']) : 120; // Default to 15 minutes if not set
    $session_expiration = time() + ($session_minutes * 60); // Convert minutes to seconds
    session_set_cookie_params($session_expiration);
    session_start();
}

if(isset($_COOKIE['user_id']) && $_COOKIE['user_id'])
    $user_id = $_COOKIE['user_id'];
elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'])
    $user_id = $_SESSION['user_id'];
    
$is_api = false;
$telegram_settings = json_decode($pref['telegram_settings'], true);
$disposableEmailDomains = [
    '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
    'temp-mail.org', 'yopmail.com','yopmail.com', 'keemail.me', 'trashmail.com',
    'getnada.com', 'emailondeck.com', 'burnermail.io',
    '33mail.com', 'tempmailaddress.com', 'moakt.com',
    'mytemp.email', 'spamgourmet.com', 'maildrop.cc',
    'fakeinbox.com', 'mailcatch.com', 'throwawaymail.com',
    'easytrashmail.com', 'instantemailaddress.com', 'fakemailgenerator.com',
    'dodgit.com', 'temp-mail.io', 'tempail.com', 'minuteinbox.com'
];
$preferences['currency'] = $site_settings['site_currency'];
if($preferences['currency'] == 'USD') {
    $preferences['symbol'] = '$';
    $preferences['round'] = $site_settings['site_round'];
} elseif($preferences['currency'] == 'SAME') {
    $preferences['symbol'] = '$';
    $preferences['round'] = $site_settings['site_round'];
} else {
    $preferences['symbol'] =$site_settings['site_symbol'];
    $preferences['round'] = $site_settings['site_round'];
}

    $dash_link = $main_link['dashboard'] ?: 'dashboard';
    $login_link = $main_link['login'] ?: 'login';

//  if($telegram_settings['mini_app'])
//      $login_link = 'telegram';

    $ip = getUserIP();

    if($pref['timezone']) {
        $tz = $pref['timezone'];
        date_default_timezone_set($tz);
        $timezone = $tz;
        $date = new DateTime(null, new DateTimeZone($tz));
        $mins = $date->getOffset()/60;
        $sgn = ($mins < 0 ? -1 : 1);
        $mins = abs($mins);
        $hrs = floor($mins / 60);
        $mins -= $hrs * 60;
        $offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
        mysqli_query($DB_CONN, "SET time_zone='$offset'");
    } else {
        date_default_timezone_set("GMT");
        $timezone = 'GMT';
        mysqli_query($DB_CONN, "SET time_zone='+00:00'");
    }
    $siteURL = $preferences['site_url'] = $pref['domain'];
    $siteName = $preferences['title'] = $pref['title'];
    $siteLogo = $preferences['site_logo'] = $pref['base_domain'];
    if($pref['base_domain']) {
        $base_domain = $pref['base_domain'];
    }
    $preferences['title'] = $pref['title'];
    $preferences['domain'] = $pref['domain'];
    $preferences['datetime'] = date("Y-m-d", strtotime($pref['datetime']));
    //$version = $pref['version'];
    $query = "SELECT COUNT(DISTINCT u.id) as total_users, (SELECT COUNT(DISTINCT user_id) FROM package_deposits WHERE status = 1) as active_users, SUM(CASE WHEN DATE(u.created_at) = CURDATE() THEN 1 ELSE 0 END) as today_users, (SELECT username FROM users ORDER BY id DESC LIMIT 1) as newest_member FROM users u";
    $result = mysqli_fetch_assoc(mysqli_query($DB_CONN, $query));
    $preferences['total_users'] = $result['total_users'];
    $preferences['active_users'] = $result['active_users'];
    $preferences['newest_member'] = $result['newest_member'];
    $preferences['today_users'] = $result['today_users'];
    $deposits_query = "SELECT SUM(amount) as total_deposit, SUM(CASE WHEN DATE(datetime) = CURDATE() THEN amount ELSE 0 END) as today_deposit, MAX(CAST(amount as UNSIGNED)) as largest_investment, MIN(CAST(amount as UNSIGNED)) as smallest_investment FROM package_deposits WHERE status = 1 OR avail > 0";
    $deposits_result = mysqli_fetch_assoc(mysqli_query($DB_CONN, $deposits_query));
    $preferences['total_deposit'] = $deposits_result['total_deposit'];
    $preferences['today_deposit'] = $deposits_result['today_deposit'];
    $preferences['largest_investment'] = $deposits_result['largest_investment'];
    $preferences['smallest_investment'] = $deposits_result['smallest_investment'];
    $withdrawals_query = "SELECT         SUM(amount) as total_withdraw,        SUM(CASE WHEN DATE(timestamp) = CURDATE() THEN amount ELSE 0 END) as today_withdraw,        MAX(amount) as largest_withdraw,        MIN(amount) as smallest_withdraw    FROM transactions     WHERE status = 1 AND txn_type = 'withdraw'";
    $withdrawals_result = mysqli_fetch_assoc(mysqli_query($DB_CONN, $withdrawals_query));
    $preferences['total_withdraw'] = $withdrawals_result['total_withdraw'];
    $preferences['today_withdraw'] = $withdrawals_result['today_withdraw'];
    $preferences['largest_withdraw'] = $withdrawals_result['largest_withdraw'];
    $preferences['smallest_withdraw'] = $withdrawals_result['smallest_withdraw'];
    $support_query = "SELECT         COUNT(*) as total_tickets,        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as new_tickets,        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as pending_tickets,        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as closed_tickets,        SUM(CASE WHEN DATE(datetime) = CURDATE() THEN 1 ELSE 0 END) as today_tickets    FROM tickets";
    $support_result = mysqli_fetch_assoc(mysqli_query($DB_CONN, $support_query));
    $preferences['total_tickets'] = $support_result['total_tickets'];
    $preferences['new_tickets'] = $support_result['new_tickets'];
    $preferences['pending_tickets'] = $support_result['pending_tickets'];
    $preferences['closed_tickets'] = $support_result['closed_tickets'];
    $preferences['today_tickets'] = $support_result['today_tickets'];
    $preferences['support'] = isset($user_settings['support']) ? $user_settings['support'] : false;
}
$lang = mysqli_query($DB_CONN,"select * from languagues where status='1'");
$languagues = array();
while($lan = mysqli_fetch_assoc($lang)) {
    $languagues[$lan['code']]['id'] = $lan['id'];
    $languagues[$lan['code']]['name'] = $lan['name'];
}
$lang_id = $pref['lang_id'];
$input = file_get_contents("php://input");
$input = json_decode($input, true);
if(isset($_GET['lang'])) {
    $l = db_filter_val($_GET['lang']);
    $l = mysqli_query($DB_CONN,"select * from languagues where status='1' and code = '{$l}'");
    if(mysqli_num_rows($l)) {
        $lang_id = mysqli_fetch_assoc($l)['id'];
        $_SESSION['lang_id'] = $lang_id;
    } else
        $lang_id = $pref['lang_id'];
} elseif(isset($_SESSION['lang_id']))
    $lang_id = $_SESSION['lang_id'];
elseif(count($input) > 0) {
    if($input['callback_query']['from']['id'])
        $callback_user_id = $input['callback_query']['from']['id'];
    else
        $callback_user_id = $input['message']['from']['id'];
    if($callback_user_id) {
        $s = mysqli_query($DB_CONN, "SELECT * FROM `telegram_users` WHERE user_id = '{$callback_user_id}'");
        if(mysqli_num_rows($s)) {
            $s = mysqli_fetch_assoc($s);
            $lang_id = $s['lang_id'];
        }
    }
}

$gen_alerts = mysqli_query($DB_CONN,"select * from alerts where status='1' and lang_id = '{$lang_id}'");
$g_alert = array();

while($g = mysqli_fetch_assoc($gen_alerts)) {
    $aid = $g['id'];
    if($lang_id != 1)
        $aid = $g['parent_id'];
    $g_alert[$aid]['icon'] = $g['icon'];
    $g_alert[$aid]['class'] = $g['class'];
    $g_alert[$aid]['content'] = $g['content'];
}


while($g = mysqli_fetch_assoc($gen_alerts)) {
    $aid = $g['id'];
    if($lang_id != 1)
        $aid = $g['parent_id'];
    $g_alert[$aid]['icon'] = $g['icon'];
    $g_alert[$aid]['class'] = $g['class'];
    $g_alert[$aid]['content'] = $g['content'];
}

$count = mysqli_query($DB_CONN,"select * from countries");
$countries = array();

while($cou = mysqli_fetch_assoc($count)) {
    $countries[$cou['id']]['name'] = $cou['name'];
}

$query = "
    SELECT t.*, c.name as currency_name, c.id as currency_id
    FROM tasks t
    LEFT JOIN currencies c ON JSON_UNQUOTE(JSON_EXTRACT(t.details, '$.curreny')) = c.id
    WHERE t.status='1' AND t.lang_id = '{$lang_id}'
";

$countt = mysqli_query($DB_CONN, $query);
$tasks = array();

while ($cou = mysqli_fetch_assoc($countt)) {
if (!empty($cou['amount_max']) && $cou['amount_max'] > $cou['amount_min']) {
        $reward = fiat($cou['amount_min']) . ' - ' . fiat($cou['amount_max']); // Show range
    } else {
        $reward = fiat($cou['amount_min']); // Show only min
    }
    $tasks[$cou['id']] = [
        'name' => $cou['name'],
        'image_url' => $cou['image_url'],
        'url' => $cou['url'],
        'reward' => $reward,
        'currency' => $cou['currency_id'],
        'currency_name' => $cou['currency_name'] 
    ];
}
$content = [];

// Fetch all content for current language
$content_query = mysqli_query($DB_CONN, "SELECT * FROM content WHERE lang_id = '{$lang_id}'");
while($item = mysqli_fetch_assoc($content_query)) {
    $content[$item['page']] = json_decode($item['content_data'], true);
}

$faqs_query = mysqli_query($DB_CONN,
    "SELECT f.*, c.name as category_name, COALESCE(c.sort_order, 999) as sort_order
    FROM faqs f 
    LEFT JOIN categories c ON f.category_id = c.id 
    WHERE f.lang_id = '{$lang_id}' 
    AND (f.product_id = 0 OR f.product_id IS NULL)
    AND (f.news_id = 0 OR f.news_id IS NULL)
    ORDER BY COALESCE(c.sort_order, 999), c.name, f.id");

$faqs = array();
$categorized_faqs = array();
while($faq = mysqli_fetch_assoc($faqs_query)) {
    // Maintain the old structure for backward compatibility
    $faqs[$faq['id']] = array(
    'id' => $faq['id'],
        'question' => $faq['question'],
        'answer' => $faq['answer'],
         'tags' => $faq['tags']
    );
    
    // Create new structured array with categories
    $category = !empty($faq['category_name']) ? $faq['category_name'] : 'Uncategorized';
    $categorized_faqs[$category][] = array(
        'id' => $faq['id'],
        'question' => $faq['question'],
        'answer' => $faq['answer'],
        'tags' => $faq['tags']
    );
}


//$dt = date("Y-m-d H:i:s");
//$post = print_r($_POST, true);
//$get = print_r($_GET, true);
//$ser = print_r($_SERVER, true);
//$ses = print_r($_SESSION, true);
//$uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
//mysqli_query($DB_CONN,"insert into syslog(user_id, ip, postr, getr, serverr, sessionr, datetime) values('{$uid}', '{$ip}', '{$post}', '{$get}', '{$ser}', '{$ses}', '{$dt}')");

$query = "SELECT news.*, users.fullname, users.bio, users.photo FROM news JOIN users ON users.id = news.author WHERE news.lang_id = '{$lang_id}' and news.status = 1";
$gen_alerts = mysqli_query($DB_CONN, $query);
$news_list = array();
$news_base = $main_link['news'] ?: 'news';
while ($g = mysqli_fetch_assoc($gen_alerts)) {
    $news_list[$g['id']]['title'] = $g['title'];
    $news_list[$g['id']]['image_url'] = $g['image_url'];
     $news_list[$g['id']]['slug'] =  $g['slug'];
    $news_list[$g['id']]['link'] = $news_base . '/' . $g['slug'];
    $news_list[$g['id']]['content'] = substr(nl2br($g['content']), 0, 200);
    $news_list[$g['id']]['datetime'] = $g['datetime'];
    $news_list[$g['id']]['author_name'] = $g['fullname'];
    $news_list[$g['id']]['author_bio'] = $g['bio'];
    $news_list[$g['id']]['author_photo'] = $g['photo'];
}


$stat_last_deposit = $stat_last_withdrawal = array();
$l1 = mysqli_query($DB_CONN, "SELECT amount, (select username from users where id = package_deposits.user_id) as username FROM `package_deposits` WHERE status = 1 order by id DESC limit 1");
if(mysqli_num_rows($l1) > 0) {
    $stat_last_deposit = mysqli_fetch_assoc($l1);
}
$w1 = mysqli_query($DB_CONN, "SELECT amount, (select username from users where id = transactions.user_id) as username FROM `transactions` WHERE status = 1 and txn_type = 'withdraw' order by id DESC limit 1");
if(mysqli_num_rows($w1) > 0) {
    $stat_last_withdrawal = mysqli_fetch_assoc($w1);
}

$currency_rate = currencytousd(1, $preferences['currency']);
$settings =  array('site_name' => $preferences['title'],
        'site_url' => $preferences['domain'],
        'captcha' => $captcha_settings,
        'currency' => $preferences['currency'],
        'symbol' => $preferences['symbol'],
        'timezone' => $timezone,
        'server_time' => date("Y-m-d H:i:s"),
        'round' => $preferences['round'],
        'site_start_month_str_generated' => date("M", strtotime($preferences['datetime'])),
        'site_start_day' => date("d", strtotime($preferences['datetime'])),
        'site_start_month' => date("m", strtotime($preferences['datetime'])),
        'site_start_year' => date("Y", strtotime($preferences['datetime'])),
        'site_days_online_generated' => floor((time()-strtotime($preferences['datetime']))/86400),
        'show_info_box_newest_member_generated' => $preferences['newest_member'],
        'info_box_total_accounts_generated' => $preferences['total_users'],
        'info_box_total_active_accounts_generated' => $preferences['active_users'],
        'info_box_invest_funds_generated' => $preferences['total_deposit'],
        'info_box_today_invest_funds_generated' => $preferences['today_deposit'],
        'info_box_withdraw_funds_generated' => $preferences['total_withdraw'],
        'info_box_smallest_withdraw' => $preferences['smallest_withdraw'],
        'info_box_largest_withdraw' => $preferences['largest_withdraw'],
        'info_box_largest_investment' => $preferences['largest_investment'],
        'site' => $site_settings,
        'user' => $user_settings,
        'withdraw' => $withdraw_settings,
        'deposit' => $deposit_settings,
        'transfer' => $transfer_settings,
        'exchange' => $exchange_settings,
        'register' => $register_settings,
        'login' => $login_settings,
        'referral' => $referral_settings,
        'kyc' => $kyc_settings,
        'infobox' => $infobox_settings,
        'trading' => $trading_settings,
        'security' => $security_settings,
        'link' => $main_link,
    );
    //packages
$package_= mysqli_query($DB_CONN,"select * from packages where status=1 and etype IN (0,1,2) order by sort desc");
$packages = array();
$i = 0;
while ($package=mysqli_fetch_assoc($package_))
{
    $details = json_decode($package['details'], true);
    $packages[$i] = $package;
    if($i == 0)
        $packages[$i]['a'] = 1;
    else
        $packages[$i]['a'] = 0;
        $packages[$i]['accurals'] = $package['frequency'];
        $packages[$i]['reinvest'] = isset($details['auto_reinvest']) ? $details['auto_reinvest'] : false;
        $packages[$i]['cashback_bonus_amount'] = isset($details['cashback_bonus_amount']) ? $details['cashback_bonus_amount'] : false;
        $packages[$i]['cashback_bonus_percentage'] = isset($details['cashback_bonus_percentage']) ? $details['cashback_bonus_percentage'] : false;
         $packages[$i]['accept_processings'] = isset($details['accept_processings']) ? $details['accept_processings'] : false;
          $packages[$i]['accept_account_balance'] = isset($details['accept_account_balance']) ? $details['accept_account_balance'] : false;
        
       if (isset($details['compound_enable']) && $details['compound_enable'] === "true") {
        $compoundDetails = [
            'compound_end' => $details['compound_end'] === "true" ? "Yes" : "No",
            'compound_min' => $details['compound_min'],
            'compound_max' => $details['compound_max'],
            'compound_percent_min' => $details['compound_percent_min'],
            'compound_percent_max' => $details['compound_percent_max']
        ];
        $packages[$i]['compound'] = $compoundDetails;
        }

            $full = '100';
            $principal_return = $full - $package['principal_hold'];
            if ($package['duration'] == 100000) {
                $p = 'Forever';
                $period = 'Unlimited';
            } elseif ($package['frequency'] == 'hours')
            {   
                $diff = ($package['diff_in_seconds'] / 3600);
                $period = $diff / 24;
                $p = 'After ' . $package['duration'] . ' Hour';
            }elseif ($package['frequency'] == 'hourly')
            {
                $p =  ucfirst($package['frequency']).' for '. $package['duration'] . ' Hour';
            
                    $period = ['duration'];
            }elseif ($package['frequency'] == 'daily')
            {
                $p =  ucfirst($package['frequency']).' for '. $package['duration'] . ' Day';
                $diff = ($package['diff_in_seconds'] / 3600);
                    $period = ['duration'];
            }elseif ($package['frequency'] == 'weekly')
            {
                $p =  ucfirst($package['frequency']).' for '. $package['duration'] . ' Week';
                $diff = ($package['diff_in_seconds'] / 3600);
                    $period = ['duration'];
            }elseif ($package['frequency'] == 'monthly')
            {
                $p =  ucfirst($package['frequency']).' for '. $package['duration'] . ' Month';
                $diff = ($package['diff_in_seconds'] / 3600);
                    $period = ['duration'];
            }
            elseif ($package['frequency'] == 'days')
            {
                $diff = ($package['diff_in_seconds'] / 3600);
                $period = $diff / 24;
                $p = 'After ' . $period . ' Day';
                
            }
            elseif ($package['frequency'] == 'weeks')
            {
                $diff = ($package['diff_in_seconds'] / 3600);
                $period = $diff / 24;
                $diff = ($package['diff_in_seconds'] / 3600);
            }
            elseif ($package['frequency'] == 'bi-weeks')
            {
               $diff = ($package['diff_in_seconds'] / 3600);
               $period = $diff / 24;
            $p = 'After ' . $period . ' Bi Week';
            }
            elseif ($package['frequency'] == 'months')
            {
                $diff = ($package['diff_in_seconds'] / 3600);
               $period = $diff / 24;
                $p = 'After ' . $period . ' Month';
            }
            else
            {
                $diff = ($package['diff_in_seconds'] / 3600);
                $period = $diff / 24;
                $p = ucfirst($package['frequency']);

            }
            if($package['principal_return']){
            $principal = '+ Return ' . $principal_return . '% Principal ';
            }else{
            $principal = 'Included in Profits';
            }
            $packages[$i]['principal'] = $principal;
            $packages[$i]['frequency'] = $p;
            $packages[$i]['period'] = $period;
        
      if($package['earnings_mon_fri'] == 0) {
          $days = "Mon-Sun";
        }
        if($package['earnings_mon_fri'] == 1) {
            $days = "Mon-Fri";
      }
      // show days like mon,tue,fri,sat
      $earning_days = array();
      if($package['earnings_mon_fri'] == 2) {
        $earning_days = json_decode($package['earning_days'], true);
        $days_loop = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
        foreach ($days_loop as $day) {
          if($earning_days[$day] == 'on') {
            $days = $earning_days[$day];
           
          }
        }
      }

            $packages[$i]['days'] = $days;
        
    $plan_= mysqli_query($DB_CONN,"SELECT * FROM `package_plans` where package_id = '{$package['id']}' ORDER BY ID ASC");
    $j = 0;
    while($plan = mysqli_fetch_assoc($plan_)) {
        if($plan['range_min']) {
            $packages[$i]['plans'][$j] = $plan;
            $packages[$i]['plans'][$j]['min_deposit'] = number_format($plan['range_min'],2);
            $packages[$i]['plans'][$j]['minimum_deposit'] = $plan['range_min'];
            $packages[$i]['plans'][$j]['max_deposit'] = number_format($plan['range_max'],2);
            $packages[$i]['plans'][$j]['maximum_deposit'] = $plan['range_max'];
            if($package['etype'] == (1 or 2)) {
                $packages[$i]['plans'][$j]['percent_min'] = number_format($plan['percent_min'],2)."%";
                $packages[$i]['plans'][$j]['percent_max'] = number_format($plan['percent_max'],2)."%";
                $packages[$i]['plans'][$j]['percentage_min'] = $plan['percent_min'];
                $packages[$i]['plans'][$j]['percentage_max'] = $plan['percent_max'];
            } else {
                $packages[$i]['plans'][$j]['percent'] = number_format($plan['percent_max'],2)."%";
                $packages[$i]['plans'][$j]['percentage'] = $plan['percent_max'];
            }
            $j++;
        }
    }
    $i++;
}
// Facuet game
$package_= mysqli_query($DB_CONN,"select * from packages where status=1 and etype = 4 order by sort desc");
$faucet_packages = array();
$i = 0;
while ($package=mysqli_fetch_assoc($package_))
{
    $details = json_decode($package['details'], true);
    $faucet_packages[$i] = $package;
    $faucet_packages[$i]['details'] = $details;
    
    $i++;
}
function maskUsername($username, $show_mask, $stars_count = 4) {
    if (!$show_mask) return $username;
    $visible_length = floor(strlen($username) / 2);
    return substr($username, 0, $visible_length) . str_repeat('*', $stars_count);
}

// Transactions
if($infobox_settings['transactions']) {
    $transactions = [];
    $i = 0;
    $limit = $infobox_settings['transactions'] ?: 10;
    $withdraw_ = mysqli_query($DB_CONN, "SELECT id, (SELECT users.username from users where id = transactions.user_id) as username,(SELECT currencies.name from currencies where id = transactions.payment_method_id) as currency, amount,payment_method_id,created_at as datetime, txn_id, 'Withdraw' as type from transactions where status = 1 and txn_type = 'withdraw' UNION SELECT id, (SELECT users.username from users where id = package_deposits.user_id) as username, (SELECT currencies.name from currencies where id = package_deposits.payment_method_id) as currency, amount,payment_method_id,datetime, txn_id, 'Invested' as type from package_deposits where status = 1 or avail > 0 order by datetime DESC LIMIT {$limit}");
    
    while($withdraw = mysqli_fetch_assoc($withdraw_)) {
        $transactions[$i] = $withdraw;
        $transactions[$i]['username'] = maskUsername(
            $withdraw['username'],
            isset($infobox_settings['transactions_mask']) && $infobox_settings['transactions_mask'],
            $infobox_settings['transactions_stars'] ?? 4
        );
        $transactions[$i]['amount'] = $withdraw['amount'];
        $transactions[$i]['date'] = $withdraw['datetime'];
        $transactions[$i]['cid'] = $withdraw['payment_method_id'];
        $transactions[$i]['currency'] = $withdraw['currency'];
        $transactions[$i]['icon'] = $siteURL . '/images/icons/' . $withdraw['payment_method_id'] . '.svg';
        $i++;
    }
}

// Last Deposits
if($infobox_settings['last_deposits']) {
    $deposits = [];
    $i = 0;
    $limit = $infobox_settings['last_deposits'] ?: 10;
    $deposit_ = mysqli_query($DB_CONN, "SELECT id, (SELECT name from packages where id = package_deposits.package_id) as package, (SELECT users.username from users where id = package_deposits.user_id) as username, (SELECT name from currencies where id = package_deposits.payment_method_id) as currency, datetime, payment_method_id , amount FROM `package_deposits` where (status = 1 or avail > 0) order by id desc limit {$limit}");
    
    while($deposit = mysqli_fetch_assoc($deposit_)) {
        $deposits[$i] = $deposit;
        $deposits[$i]['username'] = maskUsername(
            $deposit['username'],
            isset($infobox_settings['last_deposits_mask']) && $infobox_settings['last_deposits_mask'],
            $infobox_settings['last_deposits_stars'] ?? 4
        );
        $deposits[$i]['amount'] = $deposit['amount'];
        $deposits[$i]['date'] = date("M-d h:i ", strtotime($deposit['datetime']));
        $deposits[$i]['icon'] = $siteURL . '/images/icons/' . $deposit['payment_method_id'] . '.svg';
        $i++;
    }
} else {
    $deposits = NULL;
}

// Last Withdraws
if($infobox_settings['last_withdraws']) {
    $withdraws = [];
    $i = 0;
    $limit = $infobox_settings['last_withdraws'] ?: 10;
    $withdraw_ = mysqli_query($DB_CONN, "SELECT id, (SELECT users.username from users where id = transactions.user_id) as username, (SELECT name from currencies where id = transactions.payment_method_id) as currency, timestamp,created_at,payment_method_id, amount, txn_id, tx_url FROM `transactions` where status = 1 ORDER BY `transactions`.`timestamp` DESC limit {$limit}");
    
    while($withdraw = mysqli_fetch_assoc($withdraw_)) {
        $withdraws[$i] = $withdraw;
        $withdraws[$i]['username'] = maskUsername(
            $withdraw['username'],
            isset($infobox_settings['last_withdraws_mask']) && $infobox_settings['last_withdraws_mask'],
            $infobox_settings['last_withdraws_stars'] ?? 4
        );
        $withdraws[$i]['amount'] = $withdraw['amount'];
           $withdraws[$i]['payment_method_id'] = $withdraw['amount'];
        $withdraws[$i]['date'] = date("M-d h:i ", strtotime($withdraw['timestamp']));
        $withdraws[$i]['icon'] = $siteURL . '/images/icons/' . $withdraw['payment_method_id'] . '.svg';
        $i++; 
    }
} else {
    $withdraws = NULL;
}


// Top Depositors
if($infobox_settings['top_depositors']) {
    $investors = [];
    $i = 0;
    $withdraw_ = mysqli_query($DB_CONN, "SELECT SUM(amount) as amount, (SELECT username from users where id = package_deposits.user_id) as username FROM `package_deposits` where (status = 1 or avail > 0) GROUP by user_id limit 10");
    
    while($withdraw = mysqli_fetch_assoc($withdraw_)) {
        $investors[$i] = $withdraw;
        $investors[$i]['username'] = maskUsername(
            $withdraw['username'],
            isset($infobox_settings['top_depositors_mask']) && $infobox_settings['top_depositors_mask'],
            $infobox_settings['top_depositors_stars'] ?? 4
        );
        $investors[$i]['amount'] = $withdraw['amount'];
        $i++;
    }
} else {
    $investors = NULL;
}

// Reviews
if($infobox_settings['last_reviews']) {
    $reviews = [];
    $i = 0;
    $limit = $infobox_settings['last_reviews'] ?: 10;
    $reviews_ = mysqli_query($DB_CONN, "SELECT review, datetime ,(SELECT users.fullname from users WHERE id = reviews.user_id) as uname, (SELECT users.photo from users WHERE id = reviews.user_id) as photo FROM `reviews` where status = '1' ORDER BY `datetime` DESC limit {$limit}");
    
    while($review = mysqli_fetch_assoc($reviews_)) {
        $reviews[$i] = $review;
        $reviews[$i]['uname'] = maskUsername(
            $review['uname'],
            isset($infobox_settings['last_reviews_mask']) && $infobox_settings['last_reviews_mask'],
            $infobox_settings['last_reviews_stars'] ?? 4
        );
        $reviews[$i]['review'] = $review['review'];
        $reviews[$i]['photo'] = $review['photo'];
        $reviews[$i]['datetime'] = date("M-d h:i ", strtotime($review['datetime']));
        $i++; 
    }
} else {
    $reviews = NULL;
}

// Function for referral comparison
function cmp($a, $b) {
    if ($a["amount"] == $b["amount"]) {
        return 0;
    }
    return ($a["amount"] < $b["amount"]) ? -1 : 1;
}

// Top Referrals
if($infobox_settings['top_refferals']) {
    $referrals = [];
    $i = 0;
    $cUser = mysqli_query($DB_CONN,"SELECT COUNT(*) as users, sponsor FROM `users` where sponsor != 0 GROUP by sponsor ORDER BY `users` DESC ");
    
    while($row_User = mysqli_fetch_assoc($cUser)) { 
        $active_deposits = mysqli_query($DB_CONN,"SELECT SUM(amount) AS amount_sum from package_deposits WHERE user_id in (select id from users where sponsor = '{$row_User['sponsor']}') AND (status=1 or avail > 0)");
        $row_deposits = mysqli_fetch_assoc($active_deposits);
        
        $sum = $row_deposits['amount_sum'];
        if($sum) {  // Move the assignment before the condition
            $username = get_username($row_User['sponsor']);
            $referrals[$i]['username'] = maskUsername(
                $username,
                isset($infobox_settings['top_refferals_mask']) && $infobox_settings['top_refferals_mask'],
                $infobox_settings['top_refferals_stars'] ?? 4
            );
            $referrals[$i]['amount'] = (float)$sum;
            $i++; 
        }
    }
    usort($referrals, "cmp");
    $referrals = array_slice($referrals, 0, 10);
} else {
    $referrals = NULL;
}
//Payment Methods
if(true) {
    $payments = array();
    $withdraw_ = mysqli_query($DB_CONN, "SELECT id, name, symbol, transfer_fee_per, transfer_fee_amount, transfer_min, transfer_max,dep_fee_per, dep_fee_amount, dep_min, dep_max, dep_bonus_per, dep_bonus_amount,with_fee_per, with_fee_amount, with_min, with_max  FROM `currencies` where de_pm_id order by id DESC");
    while($withdraw = mysqli_fetch_assoc($withdraw_)) {
        $withdraw['icon'] = $siteURL . '/images/icons/' . $withdraw['id'] . '.svg';
        $payments[] = $withdraw;
    }
} else {
    $payments = NULL;
}
if (basename($_SERVER['SCRIPT_NAME']) === 'api.php') {
    // Exit the frontend routing completely for API requests
    return;
}

if(stripos($_SERVER['REQUEST_URI'], "admin") === FALSE):

        // Your existing Smarty initialization
        require_once('smarty/Smarty.class.php');
        $smarty = new Smarty();
        if($seo_settings['minify']){
            $smarty->registerFilter('output', 'minify_html');
        }
        $root = "";
        $smarty->setTemplateDir($root.'tmpl');
        $smarty->setCompileDir($root.'tmpl_c');
 
require_once('smarty/Smarty.class.php');
$smarty = new Smarty();

//$smarty->caching = true;
//$smarty->cache_lifetime = 3600;
//$smarty->compile_check = false;
//$smarty->force_compile = false;
if($seo_settings['minify']){
$smarty->registerFilter('output', 'minify_html');
}
$root = "";
$smarty->setTemplateDir($root.'tmpl');
$smarty->setCompileDir($root.'tmpl_c');
$smarty->assign('settings', $settings);
$smarty->assign('currency_sign',$preferences['symbol']);
$smarty->assign('stat_last_deposit',$stat_last_deposit);
$smarty->assign('stat_last_withdrawal',$stat_last_withdrawal);
$smarty->assign('content',$content);
$smarty->assign('faqs',$faqs);
$smarty->assign('categorized_faqs', $categorized_faqs); 
$smarty->assign('news',$news_list);
$smarty->assign('currency_rate',$currency_rate);
$smarty->assign('countries',$countries);
$smarty->registerPlugin("modifier","time_elapsed_string", "time_elapsed_string");
$smarty->registerPlugin("modifier","fiat", "fiat");
$smarty->registerPlugin("modifier","amount_format", "amount_format");
$smarty->registerPlugin("modifier","currencytousd", "currencytousd");
$smarty->registerPlugin("modifier","datef", "datef");
//products

$smarty->assign('index_plans',$packages);


$smarty->assign('faucet_plans',$faucet_packages);

$smarty->assign('last_reviews',$reviews);
$smarty->assign('last_deposits',$deposits);
$smarty->assign('last_withdrawals',$withdraws);
//Last 10 Transaxtion

$smarty->assign('last_transactions',$transactions);
//Top 10 investors

$smarty->assign('top_investors',$investors);

$smarty->assign('top_referrals',$referrals);

$smarty->assign('ps',$payments);
$smarty->assign('withdraw_systems',$payments);
if(isset($_POST['ec'])) {
    $_POST['payment_method_id'] = $_POST['ec'];
}
$is_login = false;
if(isset($user_id)) {
    $userinfo = array();
    updateuserinfo();
    if($userinfo['timezone']) {
        $tz = $userinfo['timezone'];
        date_default_timezone_set($tz);
        $timezone = $tz;
        $date = new DateTime(null, new DateTimeZone($tz));
        $mins = $date->getOffset()/60;
        $sgn = ($mins < 0 ? -1 : 1);
        $mins = abs($mins);
        $hrs = floor($mins / 60);
        $mins -= $hrs * 60;
        $offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
        mysqli_query($DB_CONN, "SET time_zone='$offset'");
    }
    $d = (mysqli_query($DB_CONN, "SELECT *,(SELECT name FROM currencies WHERE id = package_deposits.payment_method_id) as currency FROM `package_deposits` where user_id = '{$userinfo['id']}' and status = 1 order by id desc limit 1"));
    if(mysqli_num_rows($d) > 0) {
        $l = mysqli_fetch_assoc($d);
        $last_deposit = $l['amount'];
        $last_deposit_date = date("Y-m-d",strtotime($l['datetime']));
        $last_deposit_id = $l['package_id'];
        $last_deposit_cid = $l['payment_method_id'];
        $last_deposit_currency = $l['currency'];
        $smarty->assign('investment_last',$last_deposit_id);
        $smarty->assign('investment_last_id',$last_deposit_id);
        $smarty->assign('investment_last_cid',$last_deposit_cid);
        $smarty->assign('investment_last_currency',$last_deposit_currency);
        $smarty->assign('investment_last_date',$last_deposit_date);
    }

    $w = (mysqli_query($DB_CONN, "SELECT *,(SELECT name FROM currencies WHERE id = transactions.payment_method_id) as currency FROM `transactions` where user_id = '{$userinfo['id']}' and status = 1 and txn_type = 'withdraw' order by id desc limit 1"));
    if(mysqli_num_rows($w) > 0) {
        $w = mysqli_fetch_assoc($w);
        $last_withdrawal = $w['amount'];
        $last_withdrawal_cid = $w['payment_method_id'];
        $last_withdrawal_currency = $w['currency'];
        $last_withdrawal_date = date("Y-m-d",strtotime($w['timestamp']));
        $smarty->assign('withdrawal_last',$last_withdrawal);
        $smarty->assign('withdrawal_last_cid',$last_withdrawal_cid);
        $smarty->assign('withdrawal_last_currency',$last_withdrawal_currency);
        $smarty->assign('withdrawal_last_date',$last_withdrawal_date);
    }
    $e = (mysqli_query($DB_CONN, "SELECT *,(SELECT name FROM currencies WHERE id = transactions.payment_method_id) as currency FROM `transactions` where user_id = '{$userinfo['id']}' and txn_type = 'earning' order by id desc limit 1"));
    if(mysqli_num_rows($e) > 0) {
        $e = mysqli_fetch_assoc($e);
        $last_earning = $e['amount'];
        $last_earning_cid = $e['payment_method_id'];
        $last_earning_currency = $e['currency'];
        $last_earning_date = date("Y-m-d",strtotime($e['timestamp']));
        $smarty->assign('earning_last',$last_earning);
        $smarty->assign('earning_last_cid',$last_earning_cid);
        $smarty->assign('earning_last_currency',$last_earning_currency);
        $smarty->assign('earning_last_date',$last_earning_date);
    }

    // $pa = mysqli_query($DB_CONN, "SELECT *, (SELECT diff_in_seconds FROM `packages` WHERE id = package_deposits.package_id) as diff, (SELECT percent_min FROM `package_plans` where id = package_deposits.plan_id) as percent FROM `package_deposits` where user_id = '{$_SESSION['user_id']}' and status = 1");
    // $expected = 0;
    // while ($ea = mysqli_fetch_assoc($pa)) {
    //   $last = strtotime($ea['last_earningDateTime']);
    //   $next = $last+$ea['diff'];
    //   $lapsed = $next-time();
    //   $tearning = ($ea['amount']/100)*$ea['percent'];
    //   $earning = $tearning/$ea['diff'];
    //   $ex = $earning*$lapsed;
    //   $expected += $tearning-$ex;
    // }
    // $smarty->assign('expected',$expected);
    
    $limit = $infobox_settings['dpackages'] ?: 3;
    $mp = mysqli_query($DB_CONN, "SELECT *, (SELECT currencies.name from currencies where id = package_deposits.payment_method_id) as currency FROM `package_deposits` where user_id = '{$userinfo['id']}' and status = 1  order by id desc limit {$limit}");
    $des = array();
    while ($de = mysqli_fetch_assoc($mp)) {
      $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$de['package_id']}'"));
      $details = json_decode($package['details'], true);
      $plan = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from package_plans where id = '{$de['plan_id']}'"));
      $rem = strtotime($de['last_earningDateTime'])+(($package['duration'] - $package['avail'])*$package['diff_in_seconds'])-time();
      $next = strtotime($de['last_earningDateTime'])+$package['diff_in_seconds'];
      $last_day = date('l', strtotime($de['last_earningDateTime']));
      $exclude_days = array();
      if($package['earnings_mon_fri'] == 1) {
        if($last_day == 'Friday')
          $next += (86400*2);
        $exclude_days[] = 'Saturday';
        $exclude_days[] = 'Sunday';
      }
      $next_day = date('l', $next);
      if($package['earnings_mon_fri'] == 2) {
        $earning_days = json_decode($package['earning_days'], true);
        $days_loop = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
        foreach ($days_loop as $day) {
          if($earning_days[$day] != 'on') {
            $exclude_days[] = $day;
            if($day == $next_day)
              $next += 86400;
          }
        }
      }
      $exclude_days = array_unique($exclude_days);
      $current_time = time();
      $dtCurrent = DateTime::createFromFormat('U', $current_time);
      $dtCreate = DateTime::createFromFormat('U', $next);
      $diff = $dtCurrent->diff($dtCreate);
      $interval = $diff->format("%d days %h hours %i minutes");
      $interval = preg_replace('/(^0| 0) (days|hours|minutes)/', '', $interval);
      $de['expiry'] = date("F j, Y, g:i a", addDays($de['datetime'], $package['duration'], $package['diff_in_seconds'], $exclude_days));
      $de['last_earning_time'] = $de['avail'] > 0 ? date("F j, Y, g:i a", strtotime($de['last_earningDateTime'])) : 0;
      $de['next_earning_time'] =  date("F j, Y, g:i a", $next);
      $de['next_earning'] = max($interval, 0);
      $de['reinvest'] = $details['auto_reinvest'];
      $de['datetime'] = date("F j, Y, g:i a", strtotime($de['datetime']));
      $de['profit'] = ($plan['percent_max'] / 100)*$de['amount'];
      $de['total_profit'] = $de['profit']*$package['duration'];
      $de['percentage'] = $plan['percent_max'];
      $de['total_percentage'] = $plan['percent_max']*$package['duration'];
      $de['name'] = $package['name'];
      $de['duration'] = $package['duration'];
      $de['amount'] = $de['amount'];
      $earned = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT sum(amount) FROM `transactions` WHERE ref_id = '{$de['id']}' and txn_type = 'earning'"))[0];
      $de['earned'] = $earned;
      $e = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE ref_id = '{$de['id']}' and txn_type = 'earning' ");
      while ($earning = mysqli_fetch_assoc($e))
        $de['earning'][] = $earning;
      $de['allowprincipal'] = $package['allowprincipal'];
      $release = false;
      if($details['principal_release_period']) {
        $rt = strtotime($de['datetime'])+($details['principal_release_period']*86400);
        if($rt <= time())
          $release = true;
        else
          $de['release_time'] = date("F j, Y, g:i a", $rt);
      } else {
        foreach ($details['depositduration'] as $key => $duration) {
        $duration = (int)$duration;
        if($duration > 0) {
          if((strtotime($de['datetime'])+($duration*86400)) <= time()) {
          $release = true;
          break;
        }}}
        if($details['depositduration'][0]) {
          $rt = strtotime($de['datetime'])+($details['depositduration'][0]*86400);
          $de['release_time'] = date("F j, Y, g:i a", $rt);
        }
      }
      $de['release'] = $release;
      $des[] =$de;
    }
    $smarty->assign('activeplans',$des);
    $logs = array();
    $limit = $infobox_settings['dlogs'] ?: 5;
    $mpa = mysqli_query($DB_CONN, "SELECT title, content, timestamp as date FROM `notifications` WHERE user_id='{$userinfo['id']}' ORDER BY `date` DESC LIMIT {$limit}");
    while ($mpaa = mysqli_fetch_assoc($mpa)) {
        $logs[] = $mpaa;

    }
    $smarty->assign('logs',$logs);
    $trans = array();
    $limit = $infobox_settings['dtransactions'] ?: 5;
    $transa = mysqli_query($DB_CONN, "SELECT *,(SELECT symbol from currencies WHERE id = transactions.payment_method_id) as symbol FROM `transactions` WHERE user_id='{$userinfo['id']}' ORDER BY `created_at` DESC LIMIT {$limit}");
    while ($transaa = mysqli_fetch_assoc($transa)) {
        $transaa['date'] = date("F j, Y, g:i a" ,strtotime($transaa['created_at']));
        $transaa['cid'] = $transaa['payment_method_id'];
        $transaa['txn_type'] = ucfirst($transaa['txn_type']);
        $trans[] = $transaa;

    }
    $smarty->assign('transactions',$trans);
}
endif;