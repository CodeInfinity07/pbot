<?php
use Elliptic\EC;
use kornrunner\Keccak;
require_once('includes/connection.php');
if(isset($_GET['currency'])) {
  header('Location: '.$siteURL.$dash_link, true,301); exit;
}
if(!isset($_GET['plisio'])) 
  $_POST = db_filter($_POST);
$_GET = db_filter($_GET);
$alert = false;
$alert_class = '';
$alert_message = '';
$ref_param = $main_link['sys_ref'] ?: 'ref'; 
if(isset($_GET[$ref_param])) {
  $_SESSION['came_from'] = $_SERVER['HTTP_REFERER'];
  $_SESSION['ref'] =  $_GET[$ref_param];
}
if(isset($userinfo['id']) && $userinfo['id'])
  $user_id = $userinfo['id'];
//deposit ajax checking
if(isset($_GET['balance'])) {
  $bal = $_GET['balance'];
  $u = mysqli_query($DB_CONN,"SELECT balance FROM `user_balances` WHERE user_id = '{$user_id}' and payment_method_id = '{$bal}'") or die(mysqli_error($DB_CONN));
  $user = mysqli_fetch_assoc($u);
  echo json_encode(array("balance" => number_format($user['balance'],$preferences['round'])));
  die();
} elseif(isset($_GET['currency'])) {
  $cur = $_GET['currency'];
  $coin = strtoupper($cur);
  if($coin == 'BTC') {
      $value_in_btc = 1;
  }  else {
    $value_in_btc = cointobtc($coin, 1);
    }
  echo $value_in_btc;
  die();
} elseif(isset($_GET['check_exchange'])) {
  $cur = $_GET['check_exchange'];
  $e = mysqli_query($DB_CONN, "SELECT to_currency, rate, (SELECT name from currencies where id = exchange.to_currency) as name FROM `exchange` WHERE from_currency = '{$cur}'");
  while ($ex = mysqli_fetch_assoc($e)) {
    echo "<option value=\"{$ex['to_currency']}\" data-rate=\"{$ex['rate']}\">{$ex['name']}</option>";
  }
  die();
}
if(isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
  if ( $_GET[ 'h' ] == 'newmsg' ) {
    $c = mysqli_query($DB_CONN, "select * from chat where user_id != {$user_id} and timestamp > '{$_GET['time']}' order by timestamp asc");
    $numResults = mysqli_num_rows($c);
    if($numResults == 0) {
      $time = $_GET['time'];
    }
    $counter = 0;
    $msgs = array();
     $url = "https://ptetutorials.com/images/user-profile.png";
     while ($chat = mysqli_fetch_assoc($c)) {
      $name = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT username from `users` where id = '{$chat['user_id']}'"))['username'];
      $msgs[$counter]['msg'] = $chat['msg'];
      $msgs[$counter]['user_id'] = $chat['user_id'];
      $msgs[$counter]['image']=$url;
      $msgs[$counter]['name']=$name;
      $msgs[$counter]['msgtime']=date("H:i A");
      if (++$counter == $numResults) {
        $time = $chat['timestamp'];
     }
      }
    die( json_encode( array( 'success' => true, 'time' => $time, 'msgs' => $msgs)));
    }
if ( $_GET[ 'h' ] == 'sendmsg' ) {
  $bHasLink = strpos($_GET['msg'], 'http') !== false || strpos($_GET['msg'], 'www.') !== false || strpos($_GET['msg'], '.com') !== false || strpos($_GET['msg'], '.org') !== false || strpos($_GET['msg'], '.net') !== false || strpos($_GET['msg'], '.xyz') !== false;
  if(!$bHasLink) {
  if (preg_match('#^jQuery#',$_GET['msg']) === 1)
  {
    $msg = substr($_GET['msg'], 0,6);
    $msg = str_replace("jQuery", "?", $msg); 
  }
  else{
  $msg = $_GET['msg'];
  }  
    mysqli_query($DB_CONN, "INSERT INTO `chat` (msg, user_id) values('{$msg}','{$user_id}')");
    die( json_encode( array( 'success' => true)));
  } else {
    die( json_encode( array( 'success' => false)));
  }
  }
  die();
}
if(isset($_GET['send_otp']) && $_SESSION['otp_action']) {
  if(sendotp($_SESSION['otp_action'])) {
    echo "OTP Sent Successfully!";
  } else {
    echo "Please wait to receive previous otp atleast 1 minute!";
  }
  die();
}
if(isset($_GET['get_nonce'])) {
  $_SESSION['nonce'] = $nonce = uniqid();
  echo ("Sign this message to validate that you are the owner of the account. Random string: " . $nonce);
  exit;
}
if(isset($_GET['address'])) {
    login_redirect();
  if(strpos($_SERVER['HTTP_REFERER'], $siteURL) !== false) {
    $stmt = $DB_CONN->prepare("SELECT status FROM package_deposits WHERE address = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('s', $_GET['address']); 
      $stmt->execute();
      $stmt->bind_result($status); 
      $stmt->fetch();
      echo $status;
      $stmt->close();
    } else {
      echo "Error preparing statement";
    }
    $dep = mysqli_fetch_assoc($d);
    echo $dep['status'];
}
die();
}
if(isset($_GET['topup'])) {
    login_redirect();
  if(strpos($_SERVER['HTTP_REFERER'], $siteURL) !== false) {
    $stmt = $DB_CONN->prepare("SELECT status FROM transactions WHERE address = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('s', $_GET['topup']); 
      $stmt->execute();
      $stmt->bind_result($status); 
      $stmt->fetch();
      echo $status;
      $stmt->close();
    } else {
      echo "Error preparing statement";
    }
    $dep = mysqli_fetch_assoc($d);
    echo $dep['status'];
}
die();
}

if (isset($argv) && is_array($argv) && isset($argv[1]) && $argv[1] == 'cron') {
  try {
      login_report($DB_CONN);
      daily_earning();
      auto_withdraw();
      mysqli_query($DB_CONN, "UPDATE `package_deposits` SET status = 2 WHERE status = 0 AND txn_id = '' AND txn_type = 'deposit'  AND created_at < NOW() - INTERVAL 2 HOUR LIMIT 1000");  
      mysqli_query($DB_CONN, "UPDATE `package_deposits` SET status = 2 WHERE status = 0 AND txn_id = '' AND last_earningDateTime IS NULL           AND datetime < NOW() - INTERVAL 2 HOUR LIMIT 1000");
        if (date('i') % 1 == 0) {
            $cronCheck = mysqli_query($DB_CONN, "SELECT type, datetime FROM cron WHERE type IN (3, 4) GROUP BY type ORDER BY type, datetime DESC");
            $cronTimes = [];
            if (mysqli_num_rows($cronCheck) > 0) {
                while($row = mysqli_fetch_assoc($cronCheck)) {
                    $cronTimes[$row['type']] = $row['datetime'];
                }
            }
            if (!isset($cronTimes[3]) || strtotime($cronTimes[3]) + 300 <= time()) {
                // Process users who need profile updates
                $stmt = $DB_CONN->prepare("SELECT user_id FROM telegram_users  WHERE (bio IS NULL OR bio = '' OR photo_id IS NULL OR photo_id = '')  AND is_blocked = 0 AND status = 'active' LIMIT 15");
                
                if ($stmt->execute() && $result = $stmt->get_result()) {
                    while ($row = $result->fetch_assoc()) {
                        if ($tgInfo = getUserProfileInfo($row['user_id'])) {
                            $updateStmt = $DB_CONN->prepare("UPDATE telegram_users SET bio = ?, photo_id = ?, photo_path = ?, last_seen = NOW()  WHERE user_id = ?");
                            $updateStmt->bind_param('sssi',$tgInfo['bio'],$tgInfo['photo_id'], $tgInfo['photo_path'],$row['user_id']);
                            $updateStmt->execute();
                            usleep(100000);
                        }
                    }
                    mysqli_query($DB_CONN, "INSERT INTO cron (type, datetime) VALUES (3, NOW())");
                }
                $appStmt = $DB_CONN->prepare("SELECT applications.user_id, users.oauth_uid FROM applications  JOIN users ON applications.user_id = users.id  WHERE JSON_CONTAINS(applications.result, '{\"group\":true}')  GROUP BY applications.user_id ORDER BY MAX(applications.timestamp) DESC LIMIT 10");
                if ($appStmt->execute() && $appResult = $appStmt->get_result()) {
                      while ($appRow = $appResult->fetch_assoc()) {
                        $userinfo = ['oauth_uid' => $appRow['oauth_uid']];
                        // Check channel membership
                        checkTelegramMembership('channel', $userinfo, $telegram_settings, 'check');
                        // Check group membership
                        checkTelegramMembership('group', $userinfo, $telegram_settings, 'check');
                        usleep(100000);
                      }
                    }
                }
                
        }
      $eprocessor = new EmailProcessor($DB_CONN, $preferences, $email_settings, $telegram_settings, $ip, date('Y-m-d H:i:s'));
      $eprocessor->processQueue();
      processEmails();
      die("exit|200");
  } catch (Exception $e) {
      die("exit|500");
  }
}

$uri = rtrim($_SERVER['REQUEST_URI'], "/");
$uri = explode("/", $uri);
if (isset($uri[1]) && strpos($uri[1], "eb430691fe30d16070b5a144c3d3303c") !== false) {
  $generator = new QRCode($_GET['d'], $_GET);
  $generator->output_image();
  exit;
} else {
 $allowed_sections = [
        $main_link['news'] ?: 'news',
        $main_link['products'] ?: 'products',
        $main_link['category'] ?: 'category',
        $main_link['authors'] ?: 'authors'
    ];
    if (isset($uri[2]) && !empty($uri[2]) && !in_array($uri[1], $allowed_sections)) {
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
        exit;
    }
$uri = $uri[1];
$uri = strtok($uri,"?");
$uri = db_filter_val($uri);
switch($uri) {
    case '':
  // case $main_link['home'] ?: 'home':
    if ($site_settings['webapp'] && $site_settings['webapp_disable']) {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden status code
        echo json_encode([
            'status' => 'error',
            'message' => 'Access not allowed',
            'code' => 403
        ]);
        exit;
    }
    assign_title('home');
    $smarty->display('home.tpl');
    break;
    case $main_link['faqs'] ?: 'faqs':
    assign_title('faqs');
    $smarty->display('faq.tpl');
    break;
    case $main_link['contact'] ? $main_link['contact'] : 'contact':
    assign_title('contact');
    if(isset($_POST['name'])) {
        if(!Csrf::verifyToken('contact')) {
        header("location: /",  true,  301 );  exit;
        } else
        Csrf::removeToken('contact');
    $cc = captcha_check('contact');
        if($cc) {
        unset($_POST['csrftoken']);
        unset($_POST['captcha']);
        unset($_POST['g-recaptcha-response']);
        unset($_POST['cf-turnstile-response']);
        unset($_POST['h-recaptcha-response']); 
        $data = $_POST;
        unset($data['submit']);
        sendmail("contact", 0, "", $data);
        call_alert(21); //Message Sent Successfully
        }
    }
    $token = Csrf::getInputToken('contact');
    $smarty->registerFilter('output','add_hidden');
    $smarty->display('contact.tpl');
    break;
     case $main_link['reviews'] ?: 'reviews':
      if(!$user_settings['reviews']) {
       header("location: $dash_link",  true,  301 );  exit;
      }
      assign_title('reviews');
     if (isset($_POST['submit'])) {
     login_redirect();
     if(!Csrf::verifyToken('reviews')) {
     header("location: reviews",  true,  301 );  exit;
     } else
      Csrf::removeToken('reviews');
     $cc = captcha_check('review');
     if($cc) {
        $review = $_POST['review'];
        $banstring = $user_settings['ban_words'];
        $banArray = explode(',', $banstring);
        if($rHasLink || strposa($review, $banArray) !== false) {
         mysqli_query($DB_CONN, "INSERT into reviews(user_id, review, status) values( '{$userinfo['id']}','{$review}', '0')");
         call_alert(111); //Your review is under moderation by Support, Will be published after moderation.
        }else{
          mysqli_query($DB_CONN, "INSERT into reviews(user_id, review, status) values( '{$userinfo['id']}','{$review}', '1')");
          if($user_settings['review_faucet']){
            $am = $user_settings['review_faucet_amount'];
            $det = str_replace(array("#faucet_amount#"), array(fiat($am)), $user_settings['review_faucet_memo']); 
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$det}','{$userinfo['id']}','{$am}', 'faucet', '{$user_settings['review_faucet_curreny']}', '{$package['id']}')");
            if($user_settings['review_faucet_type']=='0')
              add_balance($userinfo['id'], $user_settings['review_faucet_curreny'], $am);
            else
              add_faucet($userinfo['id'], $user_settings['review_faucet_curreny'], $am);
          }      
             call_alert(110); //Thank you, Your Review has been published Successfully.
            }

      
            }
        }
        $reviews = array();
        $i =0;
        $t = mysqli_query($DB_CONN, "SELECT *, (select fullname from users where id = reviews.user_id) as uname from reviews where user_id = '{$userinfo['id']}' order by id desc");
        while($review = mysqli_fetch_assoc($t)) {
          $reviews[$i] = $review;
          $reviews[$i]['review'] =  $review['review'];
          $reviews[$i]['datetime'] = $review['datetime'];
          $i++;
          }
        $smarty->assign('reviews',$reviews);
        $token = Csrf::getInputToken('reviews');
        $smarty->registerFilter('output','add_hidden');
      $smarty->display('reviews.tpl');
       if(!$user_settings['reviews']) {
    header("location: $dash_link",  true,  301 );  exit;
    }          
    assign_title('reviews');
    $smarty->display('user_reviews.tpl');
    break;
   case 'captcha_image':
  header ('Content-Type: image/png');
  $width = $captcha_settings['image']['image_width'];
  $height = $captcha_settings['image']['image_height'];
  $font_size = $captcha_settings['image']['font_size'];
  $font = "bitders/assets/fonts/captcha.ttf";
  $font = realpath($font);
  $chars_length = $captcha_settings['image']['image_char'];
  if($captcha_settings['image']['numbers'])
    $captcha_characters = '1234567890';
  else
    $captcha_characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
  $image = imagecreatetruecolor($width, $height);
  $bg = $captcha_settings['image']['bgcolor'];
  list($r, $g, $b) = sscanf($bg, "#%02x%02x%02x");
  if($captcha_settings['image']['random_bg'])
   $bg_color = imagecolorallocate($image, rand(0,255), rand(0,255), rand(0,255));
  else
  $bg_color = imagecolorallocate($image, $r, $g, $b); 
  $textcolor = $captcha_settings['image']['textcolor'];
  list($r, $g, $b) = sscanf($textcolor, "#%02x%02x%02x");
  if($captcha_settings['random_text'])
   $font_color = imagecolorallocate($image, rand(0,255), rand(0,255), rand(0,255));
  else
  $font_color = imagecolorallocate($image, $r, $g, $b);
  imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
  $vert_line = round($width/5);
  $linecolor = $captcha_settings['image']['linecolor'];
  list($r, $g, $b) = sscanf($linecolor, "#%02x%02x%02x");
  if($captcha_settings['image']['random_line'])
  for($i = 0; $i < $vert_line; $i++) {
      $color = imagecolorallocate($image, rand(0,255), rand(0,255), rand(0,255));
      imageline($image, rand(0,$width), rand(0,$height), rand(0,$height), rand(0,$width), $color);
  }
  else
  $color = imagecolorallocate($image, $r, $g, $b);
  for($i = 0; $i < $vert_line; $i++) {
      imageline($image, rand(0,$width), rand(0,$height), rand(0,$height), rand(0,$width), $color);
  }
  $xw = ($width/$chars_length);
  $x = 0;
  $font_gap = $xw/2-$font_size/2;
  $token = '';
  for($i = 0; $i < $chars_length; $i++) {
      $letter = $captcha_characters[rand(0, strlen($captcha_characters)-1)];
      $token .= $letter;
      $x = ($i == 0 ? 0 : $xw * $i);
      imagettftext($image, $font_size, rand(-20,20), $x+$font_gap, rand(20, $height-5), $font_color, $font, $letter);
  }
  $_SESSION['captcha_string'] = strtolower($token);
  imagepng($image);
  imagedestroy($image);
        break;
    case $main_link['recover'] ?: 'recover':
  assign_title('recover');
if(isset($_POST['confirm']) && $_SESSION['temp_id'] && $_POST['email_code']) {
  $a = true;
  if ($_POST['password'] != $_POST['confirm_password']) {
    call_alert(10);
    $a = false;
  }
  if($a && strlen($_POST['password'])<8) {
    call_alert(6);
    $a = false;
  }
  if($a) {
    if($_POST['email_code'] == $_SESSION['email_code']) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    mysqli_query($DB_CONN,"UPDATE users set password = '{$password}' where id = '{$_SESSION['temp_id']}'");
    unset($_SESSION['temp_id']);
    unset($_SESSION['email_code']);
    unset($_SESSION['email']);
    call_alert(3);
    $smarty->display('forgot_password.tpl');
  } else {
    $a = false;
    call_alert(30); //invalid code
    }
  }
  if(!$a) {
    $smarty->assign('code',true);
    $smarty->display('forgot_password.tpl');
    exit;
  }
} elseif($_SESSION['temp_id'] && $_SESSION['email_code']) {
  $smarty->assign('code',true);
  $smarty->display('forgot_password.tpl');
  exit;
}
if(isset($_POST['change_password']) && $_SESSION['temp_id']) {
  $a = true;
  if ($_POST['password'] != $_POST['confirm_password']) {
    call_alert(10);
    $a = false;
  }
  if($a && strlen($_POST['password'])<8) {
    call_alert(6);
    $a = false;
  }
  if($a) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    mysqli_query($DB_CONN,"UPDATE users set password = '{$password}' where id = '{$_SESSION['temp_id']}'");
    unset($_SESSION['temp_id']);
    call_alert(3);
    $smarty->display('forgot_password.tpl');
  }
} elseif($_SESSION['temp_id'] && $_GET['c']) {
  $smarty->assign('pass',true);
  $smarty->display('forgot_password.tpl');
  exit;
}
if(isset($_GET['c'])) {
  $ui = mysqli_query($DB_CONN, "SELECT * from users where code = '{$_GET['c']}' and code_timestamp > NOW() - INTERVAL 15 MINUTE");
  if(mysqli_num_rows($ui)) {
    $user = mysqli_fetch_assoc($ui);
    $uid = $user['id'];
    $_SESSION['temp_id'] = $uid;
    $smarty->assign('pass', true);
  } else
  call_alert(1); //Invalid or expired link
}
  if(isset($_POST['forget_email'])) {
    if(!Csrf::verifyToken('forgot')) {
      header("location: /",  true,  301 );  exit;
    } else
      Csrf::removeToken('forgot');
    $cc = captcha_check('forgot');
    if($cc) {
      $ui = mysqli_query($DB_CONN, "SELECT * from users where email = '{$_POST['forget_email']}'");
      if(mysqli_num_rows($ui)) {
        $user = mysqli_fetch_assoc($ui);
        $uid = $user['id'];
        if($user_settings['forgot_email_code']) {
          $_SESSION['email'] = $user['email'];
          $_SESSION['temp_id'] = $uid;
          sendotp("Password Reset");
          call_alert(22); //Please check your email for OTP
          $smarty->assign('code', true);
          $smarty->display('forgot_password.tpl');
          die();
        } elseif($user_settings['forgot_email_link']) {
          $code = genPinCode(32);
          mysqli_query($DB_CONN,"UPDATE users set code = '{$code}', code_timestamp = CURRENT_TIMESTAMP where id = '{$uid}'");
          sendmail("forgot_password_confirm", $uid, $user, array('code' => $code));
          call_alert(22); //link has been sent to your email
          $smarty->display('forgot_password.tpl');
          die();
        } else {
          $pass = genPinCode(8);
          $code = password_hash($pass, PASSWORD_DEFAULT);
          mysqli_query($DB_CONN, "update users set password = '{$code}' where id = '{$uid}'");
          sendmail("forgot_password", $uid, $user, array('pass' => $pass));
        }
      }
      call_alert(22); //Password has been sent to your email if email exists in our database
    }
  }
  $token = Csrf::getInputToken('forgot');
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('forgot_password.tpl');
  break;
  case $main_link['register'] ?: 'register':
  assign_title('register');
  if(isset($_POST['confirm']) && $_SESSION['temp_id']) {
    $a = true;
    if($_POST['email_code']) {
      if($_POST['email_code'] == $_SESSION['email_code']) {
        $a = false;
        mysqli_query($DB_CONN,"UPDATE users set status = 1 where id = '{$_SESSION['temp_id']}'");
        unset($_SESSION['temp_id']);
        unset($_SESSION['email_code']);
        unset($_SESSION['email']);
        call_alert(3);
        $smarty->display('register_redirect.tpl');
      }
    }
    if($a) {
      call_alert(30);
      $smarty->assign('code',true);
      $smarty->display('register_redirect.tpl');
      exit;
    }
  } elseif($_SESSION['temp_id'] && $_SESSION['email_code']) {
    $smarty->assign('code',true);
    $smarty->display('register_redirect.tpl');
    exit;
  }
  if($_SESSION['ref'])
    $smarty->assign('ref',$_SESSION['ref']);
  else
    $smarty->assign('ref','');
  if (isset($_POST["submit"]))
  {
    $cc = captcha_check('register');
    if($register_settings['sponsor_must']) {
      $sc = mysqli_query($DB_CONN,"SELECT id from users where username='{$_POST['sponsor']}' limit 1");
      if(mysqli_num_rows($sc) == 0) {
        $cc = false;
        call_alert(8); //Registration without a sponsor is not allowed
      } else {
        $_SESSION['ref'] = $_POST['sponsor'];
      }
    }
    if($register_settings['pin_code']) {
      $sc = mysqli_query($DB_CONN,"select id from users where username='{$_POST['sponsor']}' limit 1");
      if($_POST['pin_code'] == '' || strlen($_POST['pin_code']) < 4)  {
        $cc = false;
        call_alert(9); //Registration without Pin Code is not allowed and Pin Code length must be greater than 4
      }
    }
    $_POST['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $e_domain = explode('@', $_POST['email'])[1];
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !checkdnsrr($e_domain, 'MX') || !checkdnsrr($e_domain, 'A')) {
      call_alert(7); //Email address is not valid
      $cc = false;
    }
     $email = $_POST['email'];
    if (isDisposableEmail($email, $disposableEmailDomains)) {
         call_alert(7); //Email address is not valid
      $cc = false;
    }
    if ($_POST['password'] != $_POST['confirm_password']) {
      call_alert(10); //Password and Confirm password does not match
      $cc = false;
    }
    $_POST['username'] = trim($_POST['username']);
       if (!ctype_alnum($_POST['username']) || empty($_POST['username'])) {
      call_alert(11); //Username can only contains letters and numbers
      $cc = false;
    }
  if($cc) {
   if(!empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password']) )
    {
      if(!Csrf::verifyToken('register')) {
        header("location: /",  true,  301 );  exit;
      } else
        Csrf::removeToken('register');
      $add_datetime = date("Y-m-d H:i:s");
      $username = $_POST['username'];
      $email = $_POST['email'];
      $phone = $_POST['phone'];
      $address = $_POST['address'];
      $city = $_POST['city'];
      $state = $_POST['state'];
      $zip = $_POST['zip'];
      $country = $_POST['country'];
      $us = mysqli_query($DB_CONN,"select * from users where email='{$email}' limit 1") or die(mysqli_error($DB_CONN));
      if(mysqli_num_rows($us)) {
        $us = mysqli_fetch_assoc($us);
        if($us['status'] == 0)
          mysqli_query($DB_CONN, "DELETE from users where id = '{$us['id']}'");
      }
      $cUser = mysqli_query($DB_CONN,"select * from users where username='{$username}' or email='{$email}' limit 1") or die(mysqli_error($DB_CONN));
      if($cUser && !mysqli_num_rows($cUser)>0)
      {
        if(strlen($_POST['password'])>=8)
        {
          $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
          $updateRecords = true;
          $fullname = $_POST['fullname'];
          $email = $_POST['email'];
          $username = $_POST['username'];
          if($_SESSION['ref']) {
            $sp = mysqli_query($DB_CONN,"select * from users where username='{$_SESSION['ref']}' limit 1") or die(mysqli_error($DB_CONN));
            if(mysqli_num_rows($sp)) {
              $s = mysqli_fetch_assoc($sp);
              $sponsor = $s['id'];
              sendmail("direct_signup_notification", $sponsor, $s, array('username'=>$username,'name'=>$fullname, 'email'=>$email));
              }
              else
                $sponsor = 0;
          } else
              $sponsor = 0;
          if($updateRecords)
          {
            $city = $ipdat['city'];
            $upd_query = "INSERT into users (username, email, password, fullname, sponsor, status, phone, address,city ,state ,zip ,country, pin_code,wallets,question,answer,came_from, timezone, `oauth_provider`, `oauth_uid`, `photo`) values('{$username}', '{$email}', '{$password}', '{$_POST['fullname']}', '{$sponsor}', '1', '{$phone}','{$address}','{$city}','{$state}','{$zip}', '{$country}', '{$_POST['pin_code']}','$co','{$_POST['question']}','{$_POST['answer']}','{$_SESSION['came_from']}', '', '{$_SESSION['post']['oauth_provider']}', '{$_SESSION['post']['oauth_id']}', '{$_SESSION['post']['photo']}')";
            unset($_SESSION['post']);
            if(mysqli_query($DB_CONN,$upd_query))
            {
                $id = mysqli_insert_id($DB_CONN);
                $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$id}'"));
                if($_SESSION['ref_id']) {
                  mysqli_query($DB_CONN, "UPDATE `refer_stats` SET `user_id`='{$id}' WHERE id = '{$_SESSION['ref_id']}'");
                }
                unset($_SESSION['ref']);
                unset($_SESSION['ref_id']);
                $co = mysqli_real_escape_string($DB_CONN, json_encode($_POST['payment']));
                $q = "UPDATE users set wallets = '{$co}' where `id` = '{$id}'";
                $update_user = mysqli_query($DB_CONN,$q);
              if($register_settings['double_optin_reg']) {
                $code = genPinCode(32);
                mysqli_query($DB_CONN,"UPDATE users set status = 0, code = '{$code}' where id = '{$id}'");
                sendmail("confirm_registration", $id, $user, array('code' => $code));
                call_alert(2); //Registered Successfully. Please Check Your Inbox/Spam folder to confirm your account.
              } elseif($register_settings['email_code']) {
                $_SESSION['email'] = $email;
                $_SESSION['temp_id'] = $id;
                mysqli_query($DB_CONN,"update users set status = 0 where id = '{$id}'");
                sendotp("User Registration");
                call_alert(2);
                $smarty->assign('code', true);
                $smarty->display('register_redirect.tpl');
                die();
              } else {
                sendmail("registration", $id, $user);
                $browser = getBrowser();
                $os = getOS();
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                $refer = $_SERVER['HTTP_REFERER'];
                if($register_settings['after_register'] == 'dashboard_page') {
                  mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$id}')");
                  $_SESSION['user_id']=$id;
                  header("location: {$dash_link}",  true,  301 );  exit;
                } elseif($register_settings['after_register'] == 'login_redirect_page') {
                  mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$id}')");
                  $_SESSION['user_id']=$id;
                  $smarty->display('login_redirect.tpl');
                  exit;
                } else {
                  call_alert(1); //Registered Successfully.
                  if($register_settings['after_register'] == 'same_page')
                    $smarty->display('register.tpl');
                  elseif($register_settings['after_register'] == 'after_registration_page')
                    $smarty->display('register_redirect.tpl');
                  elseif($register_settings['after_register'] == 'login_page')
                    $smarty->display('login.tpl');
                  die();
                } 
              }
            }
          }
        }
        else
          call_alert(6);  //Password length must be greater than 8!
      }
      else
       call_alert(4); //Username Or Email Address already exists.
    }
    else
      call_alert(5); //Invalid Data https://beta.bitders.com/logout
    }
  }
$payment = array();
$p = mysqli_query($DB_CONN, "SELECT * FROM `currencies` WHERE de_pm_id group by name order by id");
while($pm = mysqli_fetch_assoc($p)) {
  $payment[$pm['id']]['field'] = strtolower(str_replace(' ','',$pm['name']));
  $str = $payment[$pm['id']]['field'] ;
   $result = substr($str, strlen($str)-3);
  $payment[$pm['id']]['name'] = $pm['name'];
}
  $smarty->assign('payment',$payment);
  $token = Csrf::getInputToken('register');
  $smarty->registerFilter('output','add_hidden');
  $smarty->assign('country',$country);
  if(count($_POST))
    $smarty->assign('post',$_POST);
  elseif(count($_SESSION['post']))
    $smarty->assign('post',$_SESSION['post']);
  $smarty->display('register.tpl');
  break;
  case $main_link['login'] ?: 'login':
    if(isset($_GET['check'])) {
    echo json_encode(array("status" => true));
    die();
    }
  $reg = $main_link['register'] ?: 'register';
  if($site_settings['loginwithgoogle'] && file_exists('client.apps.googleusercontent.com.json')) {
    require_once 'includes/google/vendor/autoload.php';
    $client = new Google\Client();
    $client->setAuthConfig('client.apps.googleusercontent.com.json');
    if(isset($_GET['google'])) {
      $client->addScope("email");
      $client->addScope("profile");
      $authUrl = $client->createAuthUrl();
      header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    }
  }
  if($site_settings['loginwithtelegram'] && isset($_GET['telegram'])) {
    $bot_id = $telegram_settings['id'];
    $site = urlencode($siteURL);
    $return = urlencode($siteURL."/login");
    $url = "https://oauth.telegram.org/auth?bot_id={$bot_id}&origin={$site}&embed=1&request_access=write&return_to={$return}";
    header('Location: ' . filter_var($url, FILTER_SANITIZE_URL));
    exit;
  }
  if($site_settings['loginwithmetamask'] && isset($_GET["metamask"])){
  require_once "includes/Keccak/Keccak.php";
  require_once "includes/Elliptic/EC.php";
  require_once "includes/Elliptic/Curves.php";

  $data = json_decode(file_get_contents("php://input"));
  $address = $data->address;
  $signature = $data->signature;

  $message = "Sign this message to validate that you are the owner of the account. Random string: " . $_SESSION['nonce'];
  // Check if the message was signed with the same private key to which the public address belongs
  function pubKeyToAddress($pubkey) {
    return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
  }
  function verifySignature($message, $signature, $address) {
    $msglen = strlen($message);
    $hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
    $sign   = ["r" => substr($signature, 2, 64),
               "s" => substr($signature, 66, 64)];
    $recid  = ord(hex2bin(substr($signature, 130, 2))) - 27;
    if ($recid != ($recid & 1))
        return false;
    $ec = new EC('secp256k1');
    $pubkey = $ec->recoverPubKey($hash, $sign, $recid);
    return $address == pubKeyToAddress($pubkey);
  }
  // If verification passed, authenticate user
  if (verifySignature($message, $signature, $address)) {
    $c = mysqli_query($DB_CONN, "SELECT * FROM `users` WHERE oauth_uid = '{$address}' and oauth_provider = 'metamask'");
    if(mysqli_num_rows($c) > 0) {
      $browser = getBrowser();
      $os = getOS();
      $useragent = $_SERVER['HTTP_USER_AGENT'];
      $refer = $_SERVER['HTTP_REFERER'];
      $user = mysqli_fetch_assoc($c);
      mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$user['id']}')");
      $_SESSION['user_id']=$user['id'];
      sendmail("logged_in", $user['id'], $user, array('country' => $country,'city' => $city, 'os' => $os, 'browser' => $browser, 'useragent' => $useragent, 'datetime' => $dt));
      $link = $dash_link;
    } else {
      $_SESSION['post'] = array('oauth_id' => $address, 'oauth_provider' => 'metamask');
      $link = $reg;
    }
    echo(json_encode(["Success", $link]));
  } else {
    echo "Fail";
  }
  exit;
  }
  if(isset($_GET["username"])){
    try {
      $auth_data = checkTelegramAuthorization($_GET);
      if($auth_data) {
        $c = mysqli_query($DB_CONN, "SELECT * FROM `users` WHERE oauth_uid = '{$auth_data['id']}' and oauth_provider = 'telegram'");
        if(mysqli_num_rows($c) > 0) {
          $browser = getBrowser();
          $os = getOS();
          $useragent = $_SERVER['HTTP_USER_AGENT'];
          $refer = $_SERVER['HTTP_REFERER'];
          $user = mysqli_fetch_assoc($c);
          mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$user['id']}')");
          $_SESSION['user_id']=$user['id'];
          sendmail("logged_in", $user['id'], $user, array('country' => $country,'city' => $city, 'os' => $os, 'browser' => $browser, 'useragent' => $useragent, 'datetime' => $dt));
          header("location: $dash_link");
        } else {
          $_SESSION['post'] = array('username' => $auth_data['username'], 'fullname' => $auth_data['first_name'], 'oauth_id' => $auth_data['id'], 'oauth_provider' => 'telegram', 'photo' => urldecode($auth_data['photo_url']));
          header('Location: '.$reg);
        }
        exit;
      }
    }catch (Exception $e) {
      die ($e->getMessage());
    }
  }
  if(isset($_GET['code']) && $site_settings['loginwithgoogle'] && file_exists('client.apps.googleusercontent.com.json')) {
    $client->authenticate($_GET['code']); 
    $accessToken = $client->getAccessToken(); 
    $client->setAccessToken($accessToken);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    if (isset($google_account_info->email)) {
      $browser = getBrowser();
      $os = getOS();
      $useragent = $_SERVER['HTTP_USER_AGENT'];
      $refer = $_SERVER['HTTP_REFERER'];
      $c = mysqli_query($DB_CONN, "SELECT * FROM `users` WHERE oauth_uid = '{$google_account_info->id}' and oauth_provider = 'google'");
      if(mysqli_num_rows($c) > 0) {
        $user = mysqli_fetch_assoc($c);
        mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$user['id']}')");
        $_SESSION['user_id']=$user['id'];
        sendmail("logged_in", $user['id'], $user, array('country' => $country,'city' => $city, 'os' => $os, 'browser' => $browser, 'useragent' => $useragent, 'datetime' => $dt));
        header("location: $dash_link");
      } else {
        $_SESSION['post'] = array('email' => $google_account_info->email, 'fullname' => $google_account_info->name, 'oauth_id' => $google_account_info->id, 'oauth_provider' => 'google', 'photo' => $google_account_info->picture);
        header('Location: '.$reg);
      }
      exit;
    } else
      exit('Could not retrieve profile information! Please try again later!');
  }
  assign_title('login');
  if($login_settings['disable']) {
    $link = $main_link['home'] ?: 'home'; 
    header("location: $link",  true,  301 );  exit;
  }
  case stripos($uri,'authorize') !== false:
  if(isset($_GET['action']) && $_GET['action'] == 'first') {
    unlink('install.php');
    header("location: login",  true,  301 );  exit;
  }
  if(isset($_GET['action']) && $_GET['action'] == "confirm") {
    $chk = mysqli_query($DB_CONN, "select * from users where code = '{$_GET['c']}' and status = 2");
    if(mysqli_num_rows($chk) > 0) {
      $user = mysqli_fetch_assoc($chk);
      $id = $user['id'];
      mysqli_query($DB_CONN,"update users set status = 1 where id = '{$id}'");
      sendmail("registration", $id, $user);
      call_alert(3); //Email Verified Successfully. Please <a href='login'>Login</a> to your account.
    } else
      header("location: {$preferences['domain']}",  true,  301 );  exit;
  }
  if(isset($_GET['alert_message']) && $_GET['alert_message'])
    call_alert(1); //Registered Successfully. Please <a href='login'>Login</a> to your account.
  if (isset($_POST['confirm'])) {
    if(code_check('login')) {
      unset($_SESSION['tfa_verify']);
      if($login_settings['after_login'] == 'login_redirect') {
        updateuserinfo();
        $smarty->display('login_redirect.tpl');
        exit;
      } else {
        header("location: $dash_link", true, 301); exit;
      }
    } else {
      $smarty->assign('action','login');
      $smarty->display('confirmation.tpl');
    exit;
    }
  } elseif(isset($_SESSION['tfa_verify'])) {
    $smarty->assign('action','login');
    $smarty->display('confirmation.tpl');
    exit;
  }
  if(isset($_POST['login']))
  {
    $browser = getBrowser();
    $os = getOS();
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $refer = $_SERVER['HTTP_REFERER'];
    $cc = captcha_check('login');
    if ($cc) {
      if (!empty($_POST['username']) && !empty($_POST['password'])) {
          if (!Csrf::verifyToken('login')) {
              header("location: /", true, 301);
              exit;
          } else {
              Csrf::removeToken('login');
          }
          $username = $_POST['username'];
          $opass = $_POST['password'];
          $tim = isset($security_settings['banned_timeout_signin']) ? $security_settings['banned_timeout_signin'] : 100000;
          $bc = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT COUNT(*) FROM `ip_blacklist` WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$tim} MINUTE) AND ip = '{$ip}'"))[0];
          if ($bc < $security_settings['max_attempts_signin'] || !$security_settings['max_attempts_signin']) {
              $admin = mysqli_query($DB_CONN, "SELECT * FROM users WHERE (username='{$username}' OR email='{$username}')") or die(mysqli_error($DB_CONN));
              if ($admin && mysqli_num_rows($admin) > 0) {
                  $user = mysqli_fetch_assoc($admin);
                  if (password_verify($opass, $user['password'])) {
                      mysqli_query($DB_CONN, "INSERT INTO `login_report`(`ip`, `useragent`, `refer`, `os`, `country`, `city`, `browser`, `user_id`) VALUES ('{$ip}', '{$useragent}', '{$refer}', '{$os}', '', '', '{$browser}', '{$user['id']}')");
                      if ($user['is_admin'] == 1) {
                                $_SESSION['admin_chk'] = $user['id'];
                                sendadminmail('admin_login_message', $ip);
                                
                                // Check if there was a last page stored
                                if(isset($_COOKIE['last_page'])) {
                                    $redirect_to = "admin?page=" . $_COOKIE['last_page'];
                                    setcookie('last_page', '', time() - 3600, '/'); // Clear the cookie after using it
                                } else {
                                    $redirect_to = "admin"; // Default redirect
                                }
                                
                                header("Location: $redirect_to", true, 301);
                                exit;
                            } elseif ($user['status'] == 1) {
                        $_SESSION['user_id']= $user['id'];
                        $uid = $user['id'];
                        sendmail("logged_in", $uid, $user, array('country' => $country, 'city' => $city, 'os' => $os, 'browser' => $browser, 'useragent' => $useragent, 'datetime' => $dt));
                        if (code_check_exists('login') && $login_settings['confirmation']) {
                          $_SESSION['tfa_verify'] = true;
                          $smarty->assign('action', 'login');
                          if ($login_settings['email_code']) {
                              sendotp("login");
                          }
                          $smarty->display('confirmation.tpl');
                          exit;
                        } else {
                          if ($login_settings['after_login'] == 'login_redirect') {
                              updateuserinfo();
                              $smarty->display('login_redirect.tpl');
                              exit;
                          } else {
                              header("Location: $dash_link", true, 301);
                              exit;
                          }
                        }
                      } else {
                          call_alert(14); // Access Denied
                      }
                  } else {
                      mysqli_query($DB_CONN, "INSERT INTO `ip_blacklist`(`username`, `ip`) VALUES ('{$username}', '{$ip}')");
                      call_alert(12); // Username or Password invalid!
                  }
              } else {
                  mysqli_query($DB_CONN, "INSERT INTO `ip_blacklist`(`username`, `ip`) VALUES ('{$username}', '{$ip}')");
                  call_alert(12); // Username or Password invalid!
              }
          } else {
              mysqli_query($DB_CONN, "INSERT INTO `ip_blacklist`(`username`, `ip`) VALUES ('{$username}', '{$ip}')");
              call_alert(16); // Your IP is blacklisted for 1 hour. Please try again later or contact support
          }
      } else {
          call_alert(13); // Username and Password must be provided!
      }
    }

  }
  $token = Csrf::getInputToken('login');
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('login.tpl');
  break; 
   case $main_link['news'] ?: 'news':
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $news_base = $main_link['news'] ?: 'news' ;
    if (count($uri_parts) == 2) {
    $slug = $uri_parts[1];
$stmt = $DB_CONN->prepare("SELECT news.*, users.fullname as author_name, users.bio as author_bio, users.slug as author_slug, users.photo as author_photo 
                          FROM news 
                          LEFT JOIN users ON news.author = users.id 
                          WHERE news.slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows) {
    $news = $result->fetch_assoc();
    $categories = getCategories($news['id'], 'news');
    $smarty->assign('categories', $categories);
    $category_names = array_column($categories, 'name');
    $category_names_string = implode(', ', $category_names);
    $news['category'] = $categories;
    $smarty->assign('news_detail', $news);
    $related_news = [];
    $related_products = [];
    
    foreach ($categories as $category) {
        $category_items = getCategoryItems($category['slug']);
        if ($category_items) {
            foreach ($category_items['news'] as $related) {
                if ($related['id'] != $news['id']) {
                    $related_news[$related['id']] = $related; 
                }
            }
            
            foreach ($category_items['products'] as $product) {
                $related_products[$product['id']] = $product; 
            }
        }
    }

    $related_news = array_values($related_news); 
    $related_news = array_slice($related_news, 0, 4); 

    $related_products = array_values($related_products);
    $related_products = array_slice($related_products, 0, 3); 
    $recent_posts_query = $DB_CONN->prepare("
    SELECT n.id, n.title, n.slug, n.short_description, n.image_url, n.datetime 
    FROM news n 
    WHERE n.status = '1' 
    AND n.id != ? 
    ORDER BY n.datetime DESC 
    LIMIT 4
");
$recent_posts_query->bind_param('i', $news['id']);
$recent_posts_query->execute();
$recent_posts = $recent_posts_query->get_result()->fetch_all(MYSQLI_ASSOC);
$smarty->assign('recent_posts', $recent_posts);
    // Assign to Smarty
    if (!empty($related_news)) {
        $smarty->assign('related_news', $related_news);
    }
    if (!empty($related_products)) {
        $smarty->assign('related_products', $related_products);
    }
    
    // FAQs query
    $faqs_query = $DB_CONN->prepare("SELECT * FROM faqs WHERE news_id = ? AND lang_id = ?");
    $faqs_query->bind_param("ii", $news['id'], $lang_id);
    $faqs_query->execute();
    $faqs_result = $faqs_query->get_result();
    $bfaqs = [];
    while ($faqq = $faqs_result->fetch_assoc()) {
        $bfaqs[] = $faqq;
    }
    $smarty->assign('bfaqs', $bfaqs);
    
    $page_title = $news['title'] . " - " . $siteName;
    $current_url = $siteURL . "/" . $news_base . "/" . $news['slug'];
    
    $smarty->assign('category_names_string', $category_names_string);
    $wordCount = improvedWordCount($news['content']);
    $dimensions = getImageDimensions($news['image_url']);
    $plainTextContent = convertHtmlToPlainText($news['content']);
    
    if ($news['seo_robots'] == 'yes') {
        $robots = 'index, follow';
    } else {
        $robots = 'noindex,follow';
    }

    $data = [
        'type' => 'news',
        'page_title' => $page_title,
        'pagename' => $news['title'],
        'description' => substr(strip_tags($news['short_description'] ?? $news['content']), 0, 160),
        'keywords' => $news['keywords'],
        'robots' => $robots,
        'image_url' => $news['image_url'] ?? null,
    ];

    assignSEOMetadata($smarty, $data, $current_url);

    // Prepare author data
    $author = [
        '@type' => 'Person',
        'name' => $news['author_name'] ?? ($siteName . ' Team'),
        'url' => $siteURL . '/authors/' . ($news['author_slug'] ?? '')
    ];

    // Add author image if available
    if (!empty($news['author_photo'])) {
        $author['image'] = [
            '@type' => 'ImageObject',
            'url' => $news['author_photo']
        ];
    }

    $pageSpecificData = [
        'pagetype' => $news['schema_type'],
        'content' => $plainTextContent,
        'currentURL' => $current_url,
        'pageName' => $news['title'],
        'headline' => substr($news['title'], 0, 110),
        'description' => $news['short_description'],
        'datePublished' => date('c', strtotime($news['created_at'] ?? 'now')),
        'dateModified' => date('c', strtotime($news['updated_at'] ?? $news['created_at'] ?? 'now')),
        'author' => $author,
        'image' => [
            '@type' => 'ImageObject',
            'url' => $news['image_url'] ?? $seo_settings['image'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height']
        ],
        'wordCount' => $wordCount,
        'readingTime' => calculateReadingTime($wordCount),
        'articleSection' => $category_names[0] ?? '',
        'keywords' => $news['keywords'] ?? '',
        'categories' => $category_names,
        'tags' => array_map('trim', explode(',', $news['keywords'] ?? '')),
        'publisher' => [
            '@type' => 'Organization',
            'name' => $siteName,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $siteLogo
            ]
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $current_url
        ],
        'breadcrumbs' => [
            ['url' => $siteURL, 'name' => $siteName],
            ['url' => $siteURL . "/$news_base", 'name' => ucfirst($main_link['news'] ? $main_link['news'] : 'news')],
            ['url' => $current_url, 'name' => $news['title']]
        ],
        'canonicalURL' => $current_url,
        'metaDescription' => substr($news['short_description'], 0, 160)
    ];

    $smarty->assign('breadcrumb', $pageSpecificData['breadcrumbs']);
    $schema_data = generateUnifiedSchema($pageSpecificData);
    $minifiedSchema = preg_replace('/\s+/', ' ', json_encode($schema_data, JSON_UNESCAPED_SLASHES));
    $smarty->assign('seo_schema', $minifiedSchema);
    $smarty->display('news_detail.tpl');
    exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
        die();
    }
    } elseif (count($uri_parts) == 1) {
    assign_title('news');
    $smarty->display('news.tpl');
    } else {
    header("HTTP/1.0 404 Not Found");
    assign_title('404');
    $smarty->display('404.tpl');
    die();
    }
    break;
    case $main_link['authors'] ? $main_link['authors'] : 'authors';
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $author_base = $main_link['authors'] ?: 'authors';
    if (count($uri_parts) == 2) {
    $slug = $uri_parts[1];
        $stmt = $DB_CONN->prepare("SELECT id, slug, fullname, username, email, bio, photo FROM users WHERE slug = ? AND author = ?");
        $author = '1';
        $stmt->bind_param("si", $slug, $author);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows) {
            $author = $result->fetch_assoc();
            $smarty->assign('author', $author);
            $blogs_query = $DB_CONN->prepare("SELECT id, title, slug, content, datetime, timestamp FROM news WHERE author = ? ORDER BY datetime DESC");
            $blogs_query->bind_param("i", $author['id']);
            $blogs_query->execute();
            $blogs_result = $blogs_query->get_result();
            
            $blogs = [];
            while ($blog = $blogs_result->fetch_assoc()) {
                $blogs[] = [
                    'title' => $blog['title'],
                    'url' => $siteURL . '/blog/' . $blog['slug'], // Adjust path as per your URL structure
                    'date_published' => $blog['datetime'],
                    'date_modified' => $blog['timestamp'] ?? $blog['datetime']
                ];
            }
            $smarty->assign('blogs', $blogs);
            
            $page_title = $author['fullname'] . " - " . $siteName;
            $current_url = $siteURL . "/" . $author_base . "/" . $author['slug'];
            
            if ($author['seo_robots'] == 'yes') {
                $robots = 'index, follow';
            } else {
                $robots = 'noindex,follow';
            }
            
            $data = [
                'type' => 'profile',
                'page_title' => $page_title,
                'pagename' => $author['title'] ?? $author['fullname'],
                'description' => substr(strip_tags($author['bio'] ?? ''), 0, 160),
                'robots' => $robots,
                'image_url' => $author['photo'] ?? null,
            ];
            
            assignSEOMetadata($smarty, $data, $current_url);
            
            $pageSpecificData = [
                'pagetype' => 'AuthorProfile',
                'content' => $author['bio'],
                'currentURL' => $current_url,
                'pageName' => $author['title'] ?? $author['fullname'],
                'photo' => $author['photo'] ?? null,
                'email' => $author['email'] ?? null,
                'username' => $author['username'] ?? null,
                'blogs' => $blogs, // Add blogs data here
                'breadcrumbs' => [
                    ['url' => $siteURL, 'name' => $siteName],
                    ['url' => $siteURL . "/$author_base", 'name' => ucfirst($author_base)],
                    ['url' => $current_url, 'name' => $author['title'] ?? $author['fullname']]
                ],
                'canonicalURL' => $current_url,
                'metaDescription' => substr(strip_tags($author['bio'] ?? ''), 0, 160)
            ];
            
            $smarty->assign('breadcrumb', $pageSpecificData['breadcrumbs']);
            
            $schema_data = generateUnifiedSchema($pageSpecificData);
            $minifiedSchema = preg_replace('/\s+/', ' ', json_encode($schema_data, JSON_UNESCAPED_SLASHES));
            $smarty->assign('seo_schema', $minifiedSchema);
            
            $smarty->display('author_detail.tpl');
            exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
        die();
    }
    } elseif (count($uri_parts) == 1) {
    assign_title('authors');
    $authors = getAuthors();
    $smarty->assign('authors', $authors);
    $smarty->display('authors.tpl');
    } else {
    header("HTTP/1.0 404 Not Found");
    assign_title('404');
    $smarty->display('404.tpl');
    die();
    }
    break;
    case $main_link['products'] ?: 'products':
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $products_base = $main_link['products'] ?: 'products';
    if (count($uri_parts) == 2) {
    $slug = $uri_parts[1];
    $stmt = $DB_CONN->prepare("SELECT * FROM products WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows) {
        $product = $result->fetch_assoc();
        $product['features'] = json_decode($product['features'], true);
        $smarty->assign('product', $product);
            
        $faqs_query = $DB_CONN->prepare("SELECT * FROM faqs WHERE product_id = ? AND lang_id = ?");
        $faqs_query->bind_param("ii", $product['id'], $lang_id);
        $faqs_query->execute();
        $faqs_result = $faqs_query->get_result();
        $pfaqs = [];
        while ($faqq = $faqs_result->fetch_assoc()) {
            $pfaqs[] = $faqq;
        }
        $smarty->assign('pfaqs', $pfaqs);
        $changelogs_query = $DB_CONN->prepare("SELECT * FROM changelogs WHERE product_id = ? ORDER BY date DESC");
            $changelogs_query->bind_param("i", $product['id']);
            $changelogs_query->execute();
            $changelogs_result = $changelogs_query->get_result();
            $changelogs = [];
            while ($changelog = $changelogs_result->fetch_assoc()) {
              $changelog['detail'] = nl2br($changelog['detail']);
              $changelogs[] = $changelog;
            }
            
            $smarty->assign('changelogs', $changelogs);
    
        $page_title = $product['name'] . " - " . $siteName;
        $current_url = $siteURL . "/" . $products_base . "/" . $product['slug'];
         if ($product['seo_robots'] == 'yes') {
        $robots = 'index, follow'; }else{ $robots = 'noindex,follow'; }
                $data = [
    'type' => 'product',
    'page_title' => $page_title,
    'pagename' => $product['name'],
    'description' => substr(strip_tags($product['short_description']), 0, 160),
    'keywords' => $product['keywords'] ?? '',
    'robots' => $robots,
    'image_url' => $product['image_url'] ?? null,
];

        assignSEOMetadata($smarty, $data, $current_url);
        $pageSpecificData = [
            'pagetype' => 'ProductPage',
            'description' => $product['short_description'],
            'currentURL' => $current_url,
            'pageName' => $product['name'],
            'datePublished' => $product['created_at'] ?? date('c'),
            'dateModified' => $product['updated_at'] ?? date('c'),
            'breadcrumbs' => [
                ['url' => $siteURL, 'name' => $siteName],
                ['url' => $siteURL . "/$products_base", 'name' => ucfirst($main_link['products'] ?: 'products')],
                ['url' => $current_url, 'name' => $product['name']]
            ]
        ];
        
        $smarty->assign('breadcrumb', $pageSpecificData['breadcrumbs']);
        $schema = generateUnifiedSchema($pageSpecificData, $product);
        $minifiedSchema = preg_replace('/\s+/', ' ', json_encode($schema, JSON_UNESCAPED_SLASHES));
        $smarty->assign('seo_schema', $minifiedSchema);
    
        $smarty->display('product_detail.tpl');
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
        die();
    }
    
    } elseif (count($uri_parts) == 1) {
    assign_title('products');
    $smarty->display('products.tpl');
    } else {
      header("HTTP/1.0 404 Not Found");
      assign_title('404');
      $smarty->display('404.tpl');
      die();
    }
    break;
     case $main_link['category'] ? $main_link['category'] : 'category':
    $uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    $category_base = $main_link['category'] ?: 'category';
    if (count($uri_parts) == 1) {
      assign_title('category');
    $allCategories = getAllCategories();
    $smarty->assign('allCategories', $allCategories);
    $smarty->display('category.tpl');
    } elseif (count($uri_parts) == 2 && $uri_parts[0] == $category_base) {
    $category_slug = $uri_parts[1];
    $categoryItems = getCategoryItems($category_slug);
    
    if ($categoryItems) {
        $smarty->assign('categoryProducts', $categoryItems['products']);
        $smarty->assign('categoryNews', $categoryItems['news']);
        $smarty->assign('currentCategory', $categoryItems['category']);
        
        $page_title = $categoryItems['category']['name'] . " - " . $siteName;
        $current_url = $siteURL . "/" . $category_base . "/" . $category_slug;
        
         if ($categoryItems['category']['seo_robots'] == 'yes') {
        $robots = 'index, follow'; }else{ $robots = 'noindex,follow'; }
            $data = [
        'type' => 'website',
        'page_title' => $page_title,
        'pagename' => $categoryItems['category']['name'],
        'description' => $categoryItems['category']['description'],
        'keywords' => $categoryItems['category']['name'] . ", products, news",
        'robots' => $robots,
        'image_url' => $categoryItems['image_url'] ?? null,
    ];
    
        assignSEOMetadata($smarty, $data, $current_url);
        $pageSpecificData = [
        'pagetype' => 'Categories',
        'currentURL' => $current_url,
        'pageName' => $categoryItems['category']['name'],
        'name' => $categoryItems['name'] ?? 'Category',
        'description' => $categoryItems['description'] ?? 'Browse our collection of products and news in this category',
        'url' => $current_url, // Assuming you have this variable for the current page URL
        'category' => [
            'name' => $categoryItems['category']['name'],
            'description' => $categoryItems['category']['description'] ?? '',
            'products' => $categoryItems['products'],
            'news' => $categoryItems['news']
        ],
            'breadcrumbs' => [
                            ['url' => $siteURL, 'name' => $siteName],
                            ['url' => $siteURL . "/$category_base", 'name' => ucfirst($main_link['category'] ?: 'category')],
                            ['url' => $current_url, 'name' => ucfirst($categoryItems['category']['slug'])]
                        ],
            'canonicalURL' => $current_url,
    ];
$smarty->assign('breadcrumb', $pageSpecificData['breadcrumbs']);
     $schema_data = generateUnifiedSchema($pageSpecificData);
     $minifiedSchema = preg_replace('/\s+/', ' ', json_encode($schema_data, JSON_UNESCAPED_SLASHES));
     
     $smarty->assign('seo_schema', $minifiedSchema);
     $smarty->display('category_detail.tpl');
    } else {
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
        die();
    }
    } else {
    header("HTTP/1.0 404 Not Found");
    assign_title('404');
    $smarty->display('404.tpl');
    die();
    }
    break; 
case 'webmanifest':
    if ($seo_settings['manifest_url']) {
        header('Content-Type: application/manifest+json');
        
        $manifest = [
            "name" => $seo_settings['sitename'] ?: $sitename,
            "short_name" => $seo_settings['alternateName'] ?: $seo_settings['sitename'],
            "description" => $seo_settings['description'],
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => $seo_settings['background_color'] ?: '#FFFFFF',
            "theme_color" => $seo_settings['theme_color'] ?: '#000000',
            "icons" => [
                [
                    "src" => $seo_settings['favicon'],
                    "sizes" => "32x32", // Assuming this size, adjust if needed
                    "type" => "image/png",
                    "purpose" => "any"
                ],
                [
                    "src" => $seo_settings['favicon192'],
                    "sizes" => "192x192", // Assuming this size, adjust if needed
                    "type" => "image/png",
                    "purpose" => "any"
                ],
                [
                    "src" => $seo_settings['favicon512'],
                    "sizes" => "512x512", // Assuming this size, adjust if needed
                    "type" => "image/png",
                    "purpose" => "maskable"
                ]
            ],
            "lang" => $seo_settings['primaryLanguage'] ?: 'en',
        ];

        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    break;
case 'robots.txt':
    header("Content-Type: text/plain");
    ob_start();
    echo "#  ____  _ _____ ____  _____ ____  ____   ____ ___  __  __\n";
    echo "# | __ )(_)_   _|  _ \| ____|  _ \/ ___| / ___/ _ \|  \/  |\n";
    echo "# |  _ \| | | | | | | |  _| | |_) \___ \| |  | | | | |\/| |\n";
    echo "# | |_) | | | | | |_| | |___| _ < ___) | |__| |_| | |  | |\n";
    echo "# |____/|_| |_| |____/|_____|_| \_\____(_)____\___/|_|  |_|\n";
    echo "#\n";
    if (isset($seo_settings['robots']) && $seo_settings['robots'] == 'true') {
        echo "User-agent: *\n";
        echo "Allow: /\n";
    } else {
        echo "User-agent: *\n";
        echo "Disallow: /\n";
    }
    if (isset($siteURL)) {
        echo "\n";
        echo "Sitemap: {$siteURL}/sitemap.xml\n";
    }
    echo ob_get_clean();
    exit;
    break;
 case 'sitemap.xml':
    header("Content-type: application/xml; charset=utf-8");
    ob_start();
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      echo "<!--\n";
    echo "# ____  _ _____ ____  _____ ____  ____   ____ ___  __  __\n";
    echo "# | __ )(_)_   _|  _ \| ____|  _ \/ ___| / ___/ _ \|  \/  |\n";
    echo "# |  _ \| | | | | | | |  _| | |_) \___ \| |  | | | | |\/| |\n";
    echo "# | |_) | | | | | |_| | |___| _ < ___) | |__| |_| | |  | |\n";
    echo "# |____/|_| |_| |____/|_____|_| \_\____(_)____\___/|_|  |_|\n";
    echo "-->\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Function to safely output URL entry
    function outputUrlEntry($url, $lastmod = null) {
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        if ($lastmod) {
            echo "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
        }
        echo "  </url>\n";
    }
foreach ($main_link as $key => $value) {
    if (strpos($key, '_sitemap') !== false && $value === 'yes') {
        $page_key = str_replace('_sitemap', '', $key);
        $url = ($page_key === 'home') ? $siteURL : $siteURL . '/' . ($main_link[$page_key] ?: $page_key);
        $modified_key = $page_key . '_modified';
        if (isset($main_link[$modified_key]) && strtotime($main_link[$modified_key])) {
            $date = date('Y-m-d', strtotime($main_link[$modified_key]));
        } else {
            $date = date('Y-m-d');
        }
        
        outputUrlEntry($url, $date);
    }
}
$category_base = $main_link['category'] ?: 'category';
    try {
        if (isset($DB_CONN) && $DB_CONN instanceof mysqli) {
            $stmt = $DB_CONN->prepare("SELECT slug FROM categories WHERE seo_robots = 'yes';");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                while ($cat = $result->fetch_assoc()) {
                    $cat_url = $siteURL . '/' . $category_base . '/' . $cat['slug'];
                    outputUrlEntry($cat_url);
                }
                $stmt->close();
            } else {
                error_log("Failed to prepare statement for categories sitemap");
            }
        } else {
            error_log("Database connection not available for categories sitemap");
        }
    } catch (Exception $e) {
        error_log("Error fetching categories for sitemap: " . $e->getMessage());
    }
$news_base = $main_link['news'] ?: 'blog';
    try {
        if (isset($DB_CONN) && $DB_CONN instanceof mysqli) {
            $stmt = $DB_CONN->prepare("SELECT slug, datetime FROM news WHERE seo_robots = 'yes' ORDER BY timestamp DESC;");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                while ($news = $result->fetch_assoc()) {
                    $news_url = $siteURL . '/' . $news_base . '/' . $news['slug'];
                    $lastmod = date('Y-m-d', strtotime($news['datetime']));
                    outputUrlEntry($news_url, $lastmod);
                }
                $stmt->close();
            } else {
                error_log("Failed to prepare statement for news sitemap");
            }
        } else {
            error_log("Database connection not available for news sitemap");
        }
    } catch (Exception $e) {
        error_log("Error fetching news for sitemap: " . $e->getMessage());
    }
 $products_base = $main_link['products'] ?: 'products';
    try {
        if (isset($DB_CONN) && $DB_CONN instanceof mysqli) {
            $stmt = $DB_CONN->prepare("SELECT slug, updated_at FROM products WHERE seo_robots = 'yes' ORDER BY updated_at DESC;");
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                while ($products = $result->fetch_assoc()) {
                    $product_url = $siteURL . '/' . $products_base . '/' . $products['slug'];
                    $lastmod = date('Y-m-d', strtotime($products['updated_at']));
                    outputUrlEntry($product_url, $lastmod);
                }
                $stmt->close();
            } else {
                error_log("Failed to prepare statement for products sitemap");
            }
        } else {
            error_log("Database connection not available for products sitemap");
        }
    } catch (Exception $e) {
        error_log("Error fetching products for sitemap: " . $e->getMessage());
    }
    echo '</urlset>';
    $xml_content = ob_get_clean();
    $xml = @simplexml_load_string($xml_content);
    if ($xml === false) {
        error_log("Invalid XML generated for sitemap: " . print_r(libxml_get_errors(), true));
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        echo '  <url>' . "\n";
        echo '    <loc>' . htmlspecialchars($siteURL) . '</loc>' . "\n";
        echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '  </url>' . "\n";
        echo '</urlset>';
    } else {
        echo $xml_content;
    }
    exit;
    break;
 // USER SIDE     
          case $main_link['head_tail'] ?: 'head_tail':
          login_redirect();
      $game = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `games` WHERE id = 1"));
      if($_POST['game']) {
        $amount = $_POST['amount'];
        $sel = $_POST['game'];

        $chk = mysqli_query($DB_CONN, "SELECT balance FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$game['limit_currency']}'");
        if(mysqli_num_rows($chk) == 0)
          $bal = 0;
        else
          $bal = mysqli_fetch_array($chk)[0];
        if($bal >= $withdraw) {
          $percentage = 0;
          $details = json_decode($game['details'], true);
          foreach ($details['game'] as $key => $value) {
            if($value['min'] <= $amount && $value['max'] >= $amount)
              $percentage = $value['chance'];
          }
          if($percentage) {
            $detail = "Game Played";
            add_balance($userinfo['id'], $game['limit_currency'], -$amount);
            mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, payment_method_id, ip, txn_type, detail, ref_id, am_type, package_id) values('{$userinfo['id']}', '{$amount}', '{$game['limit_currency']}', '{$ip}', 'game' , '{$detail}', '{$game['id']}', 'out', '{$sel}')");
            $win = biasedCoinFlip($percentage);
            if($win) {
              $detail = "Game Won";
              if($game['win_amount'])
                $amount += $game['win_amount'];
              else
                $amount += ($amount*($game['win_percentage']/100));
              mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, payment_method_id, ip, txn_type, detail, ref_id) values('{$userinfo['id']}', '{$amount}', '{$game['limit_currency']}', '{$ip}', 'game' , '{$detail}', '{$game['id']}')");
              if($game['faucet'])
                add_faucet($userinfo['id'], $game['limit_currency'], $amount);
              else
                add_balance($userinfo['id'], $game['limit_currency'], $amount);
              call_alert(123); //won
            } else {
              if($game['principal_return']) {
                $detail = "Game Loss Return";
                add_balance($userinfo['id'], $game['limit_currency'], $amount);
                mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, payment_method_id, ip, txn_type, detail, ref_id) values('{$userinfo['id']}', '{$amount}', '{$game['limit_currency']}', '{$ip}', 'game' , '{$detail}', '{$game['id']}')");
              }
              call_alert(123); //loss
            }
          } else
            call_alert(123); //amount not in range
          } else
        call_alert(64);
      }
      $smarty->assign('game',$game);
      assign_title('head_tail');
      $smarty->display('user_games_headtail.tpl');
      break;
      case $main_link['apply'] ?: 'apply':
          login_redirect();
      assign_title('apply');
       $a = true;
      if (isset($_POST['submit']) && isset($_POST['action']) && $_POST['action']=="apply" && $_GET['id']) 
      {
      if(!Csrf::verifyToken('apply')) {
        header("location: apply");
        exit;
      } else
        Csrf::removeToken('apply');
        $pp = mysqli_query($DB_CONN, "SELECT * FROM `tasks` where md5(id) = '{$_GET['id']}' ");
        if(mysqli_num_rows($pp) > 0 ) {
          $task = mysqli_fetch_assoc($pp);
          $details = json_decode($bounty['details'], true);
          $result = $_POST['result'];
          $comment = $_POST['comment'];
          mysqli_query($DB_CONN, "INSERT INTO `applications` (user_id,task_id,result,comment,type,status) values('{$userinfo['id']}','{$task['id']}','{$result}','{$comment}','{$task['type']}','0' )");
          call_alert(150); // Application Submitted Succesfully, Our Team will review and process.
          $smarty->display('user_bounty.tpl');
          die();
          $a = false;
        }
      } elseif($_GET['id']) {
        $pp = mysqli_query($DB_CONN, "SELECT * FROM `tasks` where md5(id) = '{$_GET['id']}'") or die(mysqli_error($DB_CONN));
        if(mysqli_num_rows($pp) > 0 ) {
          $bounty = mysqli_fetch_assoc($pp);
          $a = false;
        }
      }
      if($a) {
        call_alert(65); //Some Error. Please try again
        $smarty->display('user_bounty.tpl');
      } else {
      $smarty->assign('name',$bounty['name']);
      $smarty->assign('image_url',$bounty['image_url']);
      $smarty->assign('content',$bounty['content']);
      $smarty->assign('amount_min',$bounty['amount_min']);
      $smarty->assign('amount_max',$bounty['amount_max']);
      $smarty->assign('instructions',$bounty['instructions']);
      $smarty->assign('instructions_image_url',$bounty['instructions_image_url']);
      $token = Csrf::getInputToken('apply');
      $smarty->registerFilter('output','add_hidden');
      $smarty->display('user_application.tpl');
      }
      break;
      case $main_link['reviews'] ?: 'reviews':
      if(!$user_settings['reviews']) {
       header("location: $dash_link",  true,  301 );  exit;
      }
      assign_title('reviews');
     if (isset($_POST['submit'])) {
     login_redirect();
     if(!Csrf::verifyToken('reviews')) {
     header("location: reviews",  true,  301 );  exit;
     } else
      Csrf::removeToken('reviews');
     $cc = captcha_check('review');
     if($cc) {
        $review = $_POST['review'];
        $banstring = $user_settings['ban_words'];
        $banArray = explode(',', $banstring);
        if($rHasLink || strposa($review, $banArray) !== false) {
         mysqli_query($DB_CONN, "INSERT into reviews(user_id, review, status) values( '{$userinfo['id']}','{$review}', '0')");
         call_alert(111); //Your review is under moderation by Support, Will be published after moderation.
        }else{
          mysqli_query($DB_CONN, "INSERT into reviews(user_id, review, status) values( '{$userinfo['id']}','{$review}', '1')");
          if($user_settings['review_faucet']){
            $am = $user_settings['review_faucet_amount'];
            $det = str_replace(array("#faucet_amount#"), array(fiat($am)), $user_settings['review_faucet_memo']); 
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$det}','{$userinfo['id']}','{$am}', 'faucet', '{$user_settings['review_faucet_curreny']}', '{$package['id']}')");
            if($user_settings['review_faucet_type']=='0')
              add_balance($userinfo['id'], $user_settings['review_faucet_curreny'], $am);
            else
              add_faucet($userinfo['id'], $user_settings['review_faucet_curreny'], $am);
          }      
             call_alert(110); //Thank you, Your Review has been published Successfully.
            }

      
            }
        }
        $reviews = array();
        $i =0;
        $t = mysqli_query($DB_CONN, "SELECT *, (select fullname from users where id = reviews.user_id) as uname from reviews where user_id = '{$userinfo['id']}' order by id desc");
        while($review = mysqli_fetch_assoc($t)) {
          $reviews[$i] = $review;
          $reviews[$i]['review'] =  $review['review'];
          $reviews[$i]['datetime'] = $review['datetime'];
          $i++;
          }
        $smarty->assign('reviews',$reviews);
        $token = Csrf::getInputToken('reviews');
        $smarty->registerFilter('output','add_hidden');
      $smarty->display('user_reviews.tpl');
        break;
        case $main_link['faucet'] ?: 'faucet':
        login_redirect();
        assign_title('faucet');
        $amounts = faucets_list();
        if (isset($_POST['claim_faucet']) && $_POST['faucet'])
        {
          if(!Csrf::verifyToken('faucet')) {
            header("location: faucet");
            exit;
          } else
            Csrf::removeToken('faucet');
          $faucet=$_POST['faucet'];
          foreach ($amounts as $key => $value) {
            if($value['id'] == $faucet) {
              $am = $value['amount'];
              if($am) {
                $package_ = mysqli_query($DB_CONN, "SELECT * FROM `packages` WHERE id = '{$faucet}'");
                $package=mysqli_fetch_assoc($package_);
                $details = json_decode($package['details'], true);
                if($value['remain_time']) {
                  $to_time = strtotime($value['remain_time']);
                  $from_time = strtotime(date("Y-m-d H:i:s"));
                  $tt = round(abs($to_time - $from_time) / 60,2). " minutes";
                  if(!$amounts[$i]['limit_error'])
                    call_alert(120, $tt); //You have reached maximum no of Faucets allowed please wait
                  else
                    call_alert(122, $tt);
                } elseif($package['limit_currency']) {
                  $det = str_replace(array("#faucet_amount#","#plan#"), array(fiat($am), $package['name']), $details['memo']);
                  mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) VALUES ('{$det}','{$userinfo['id']}','{$am}', 'faucet', '{$package['limit_currency']}', '{$package['id']}')");
                  call_alert(121); //Faucet Claimed
                  if($value['type']=='0')
                    add_balance($userinfo['id'], $package['limit_currency'], $am);
                  else
                    add_faucet($userinfo['id'], $package['limit_currency'], $am);
                }
              }
            }
          }
        }
    $smarty->assign('faucets',$amounts);
    $token = Csrf::getInputToken('faucet');
    $smarty->registerFilter('output','add_hidden');
    $smarty->display('user_faucet.tpl');
    break;
    case $main_link['logs'] ?  : 'logs':
    login_redirect();
    assign_title('logs');
    if(!$user_settings['logs']) {
      header("location: $dash_link",  true,  301 );  exit;
    }
    $rows = array();
    $i = 1;
    $d = mysqli_query($DB_CONN, "SELECT * FROM `notifications` where user_id = '{$userinfo['id']}'");
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['logs'] ?: 20;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT * FROM `notifications` where user_id = '{$userinfo['id']}' order by id desc limit {$start}, {$itemsPerPage}");
    while($de = mysqli_fetch_assoc($d)) {
      $de['datetime'] = date("F j, Y, g:i a", strtotime($de['timestamp']));
      $rows[] = $de;
      $i++;
    }
    $smarty->assign('heads',$heads);
    $smarty->assign('rows',$rows);
    $smarty->assign('paginator',$paginator);
    $smarty->display('user_logs.tpl');
    break;
    case $main_link['invested'] ?: 'invested':
    login_redirect();
    assign_title('invested');
    if(isset($_GET['reinvest']) && $_GET['reinvest']) {
    $id = $_GET['reinvest'];
    if($userinfo['reinvest']) {
        call_alert(65); // some error
    } else {
        $check_query = mysqli_query($DB_CONN, "SELECT * FROM package_deposits WHERE md5(id) = '{$id}' AND auto_reinvest = '1' and user_id = '{$userinfo['id']}'");
        if(mysqli_num_rows($check_query) > 0) {
            mysqli_query($DB_CONN, "UPDATE package_deposits SET auto_reinvest = '0' WHERE md5(id) = '{$id}'");
            call_alert(202); //  Auto Reinvest Disabled Successfully.
        } else {
            mysqli_query($DB_CONN, "UPDATE package_deposits SET auto_reinvest = '1' WHERE md5(id) = '{$id}'");
            call_alert(201); //  Auto Reinvest Enabled Successfully.
        }
    }
    }
    $rows = array();
    $i = 1;
    $d = mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` where user_id = '{$userinfo['id']}' and status = 1 ");
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['packages'] ?: 20;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT *, (SELECT currencies.name from currencies where id = package_deposits.payment_method_id) as currency FROM `package_deposits` where user_id = '{$userinfo['id']}' and status = 1 order by id desc limit {$start}, {$itemsPerPage}");
    while($de = mysqli_fetch_assoc($d)) {
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
      $de['last_earning_time'] = $de['avail'] > 0 ? date("F j, Y, g:i a", strtotime($de['last_earningDateTime'])) : 'N/A';
      $de['next_earning_time'] =  date("F j, Y, g:i a", $next);
      $de['next_earning'] = max($interval, 0);
      $de['reinvest'] = $details['auto_reinvest'];
      $de['datetime'] = date("F j, Y, g:i a", strtotime($de['datetime']));
      $de['profit'] = ($plan['percent_max'] / 100)*$de['amount'];
      $de['total_profit'] = $de['profit']*$package['duration'];
      $de['percentage'] = $plan['percent_max'];
      $de['total_percentage'] = $plan['percent_max']*$package['duration'];
      $de['name'] = $package['name'];
      $de['amount'] = $de['amount'];
      $de['cid'] = $de['payment_method_id'];
      $de['fee'] = $de['fee'];
      $de['duration'] = $package['duration'];
      $earned = fiat(mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT sum(amount) FROM `transactions` WHERE ref_id = '{$de['id']}' and txn_type = 'earning'"))[0]);
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
      // $de['remaining'] = secondsToWords($rem);
        $de['id'] = md5($de['id']);
      $rows[] = $de;
      $i++;
    }
    $smarty->assign('rows',$rows);
    $smarty->assign('paginator',$paginator);
    $smarty->display('user_invest_list.tpl');
  break;
  case $main_link['affiliates'] ?: 'affiliates':
    login_redirect();
    assign_title('affiliates');
    if($referral['enable']) {
     header("location: $dash_link",  true,  301 );  exit;
    }
    $rows = array();
    $d = mysqli_query($DB_CONN, "SELECT *, (SELECT sum(amount) from package_deposits where user_id = users.id and status = 1) as deposit FROM `users` where sponsor = '{$userinfo['id']}' order by id desc");
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['affiliates'] ?: 20;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT *, (SELECT sum(amount) from package_deposits where user_id = users.id and status = 1) as deposit FROM `users` where sponsor = '{$userinfo['id']}' order by id desc limit {$start}, {$itemsPerPage}");
    while($de = mysqli_fetch_assoc($d)) {
      $rows[] = $de;
      $i++;
    }
    $s_id = $userinfo['id'];
    for ($i=0; $i < $referral_settings['levels']; $i++) {
      $user_ids = array();
      $u = mysqli_query($DB_CONN, "SELECT id from users where sponsor in ({$s_id})");
      $levels[$i]['users'] = mysqli_num_rows($u);
      while ($sp = mysqli_fetch_assoc($u)) {
        $user_ids[] = $sp['id'];
      }
      $s_id = implode(",", $user_ids);
      $levels[$i]['earning'] = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT COALESCE(sum(amount), 0) from transactions where txn_type = 'referral' and user_id = '{$userinfo['id']}' and ref_id in ($s_id)"))[0];
      $r = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT count(DISTINCT user_id) as c, COALESCE(sum(amount), 0) as a FROM `package_deposits` WHERE user_id in ($s_id) and (status = 1 or last_earningDateTime) "));
      $levels[$i]['active_users'] = $r['c'];
      $levels[$i]['deposit'] = $r['a'];
    }
    $ref_pms = array();
    $r = mysqli_query($DB_CONN, "SELECT payment_method_id, (SELECT name from currencies where id = transactions.payment_method_id) as name, sum(amount) as am FROM `transactions` WHERE txn_type = 'referral' and user_id = '{$userinfo['id']}' GROUP by payment_method_id ");
    while ($rec = mysqli_fetch_assoc($r)) {
      $ref_pms[] = $rec;
    }
    $smarty->assign('ref_pms',$ref_pms);
    $smarty->assign('levels',$levels);
    $smarty->assign('heads',$heads);
    $smarty->assign('rows',$rows);
    $smarty->assign('paginator',$paginator);
    $smarty->display('user_affiliates.tpl');
  break;
  case $main_link['withdrawals'] ?: 'withdrawals':
  case $main_link['earnings'] ?: 'earnings':
  case $main_link['transfers'] ?: 'transfers':
  case $main_link['exchanges'] ?: 'exchanges':
  case $main_link['commissions'] ?: 'commissions':
  case $main_link['investments'] ?: 'investments':
  case $main_link['returned'] ?: 'returned':
  case $main_link['released'] ?: 'released':
  case $main_link['deposits'] ?: 'deposits':
  case $main_link['bonuses'] ?: 'bonuses':
  case $main_link['faucets'] ?: 'faucets':
  case $main_link['transactions'] ?: 'transactions':
  login_redirect();
  assign_title('transactions');
  $wi = false;
  switch ($uri) {
    case $main_link['withdrawals'] ?: 'withdrawals':
      $wi = true;
      assign_title('withdrawals');
      $_GET['type'] = 'withdraw';
      break;
    case $main_link['earnings'] ?: 'earnings':
      assign_title('earnings');
      $_GET['type'] = 'earning';
      break;
      case $main_link['transfers'] ?: 'transfers':
      assign_title('transfers');
      $_GET['type'] = 'transfer';
      break;
      case $main_link['exchanges'] ?: 'exchanges':
      assign_title('exchanges');
      $_GET['type'] = 'exchange';
      break;
      case $main_link['commissions'] ?: 'commissions':
      assign_title('commissions');
      $_GET['type'] = 'referral';
      break;
      case $main_link['investments'] ?: 'investments':
      assign_title('investments');
      $_GET['type'] = 'invest';
      break;
       case $main_link['released'] ?: 'released':
      assign_title('released');
      $_GET['type'] = 'release';
      break;
      case $main_link['returned'] ?: 'returned':
      assign_title('returned');
      $_GET['type'] = 'return';
      break;
       case $main_link['deposits'] ?: 'deposits':
      assign_title('deposits');
      $_GET['type'] = 'deposit';
      break;
       case $main_link['bonuses'] ?: 'bonuses':
      assign_title('bonuses');
      $_GET['type'] = 'bonus';
      break;
        case $main_link['faucets'] ?: 'faucets':
      assign_title('faucets');
      $_GET['type'] = 'faucet';
      break;
  }
    $rows = array();
    $w = "";
    if(isset($_GET['type']) && $_GET['type']) {
      $w .= " and txn_type = '{$_GET['type']}'";
      $smarty->assign('type',$_GET['type']);
    }
    if(isset($_GET['cid']) && $_GET['cid']) {
      $w .= " and payment_method_id = '{$_GET['cid']}'";
    }
    if(isset($_GET['from']) && $_GET['from']) {
      $from = date("Y-m-d", strtotime($_GET['from']));
      $w .= " and date(created_at) >= '{$from}'";
    }
    if(isset($_GET['to']) && $_GET['to']) {
      $to = date("Y-m-d", strtotime($_GET['to']));
      $w .= " and date(created_at) <= '{$to}'";
    }
    $d = mysqli_query($DB_CONN, "SELECT * FROM `transactions` where user_id = '{$userinfo['id']}' {$w}") or die(mysqli_error($DB_CONN));
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['transactions'] ?: 20;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    unset($_GET['page']);
    $urlPattern = '?'.http_build_query($_GET).'&page=(:num)';
    // $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT *, (select name from packages where id = transactions.package_id) as pname,
    (SELECT name from currencies where id = transactions.payment_method_id) as currency, (SELECT symbol from currencies where id = transactions.payment_method_id) as symbol FROM `transactions` where user_id = '{$userinfo['id']}' {$w} order by id desc limit {$start}, {$itemsPerPage}") or die(mysqli_error($DB_CONN));
    while($de = mysqli_fetch_assoc($d)) {
      $de['datetime'] = date("F j, Y, g:i a", strtotime($de['created_at'])); 
      $de['type'] = ucfirst($de['txn_type']);
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
      if($wi)
        $de['id'] = md5($de['id']);
      $rows[] = $de;
    }
    $dat = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT min(created_at) as mindate ,max(created_at) as maxdate FROM transactions WHERE user_id = '{$userinfo['id']}' {$w} "));
    $dast = $dat['mindate'] ?: date('Y-m-d', time());
    $daen = $dat['maxdate'] ?: date('Y-m-d', time());
    $startdate = date("Y-m-d", strtotime($dast));
    $enddate = date("Y-m-d", strtotime($daen)); 
    $smarty->assign('rows',$rows);
    $smarty->assign('startdate',$startdate);
    $smarty->assign('enddate',$enddate);
    $smarty->assign('paginator',$paginator);
    $smarty->display('user_transactions.tpl');
  break;

  case $main_link['dashboard'] ?: 'dashboard':
    login_redirect();
    assign_title('dashboard');

    if(isset($_GET['fail'])) {
       call_alert(23); //Payment Failed
    }
    if(isset($_GET['success'])) {
       call_alert(24);  //Payment Success
    }
    $smarty->display('user_dashboard.tpl');
  break;
  case $main_link['wallets'] ? : 'wallets':
  login_redirect();
  assign_title('wallets');

  if(!$user_settings['wallets']) {
    $link = $main_link['profile'] ? : 'profile'; 
   header("location: $link",  true,  301 );  exit;
  }
if (isset($_POST['payment_settings'])) {
    $validator = new CryptoValidator();
$currencyIdMap = [
    'bitcoin' => 1,
    'ethereum' => 4,
    'tron' => 14,
    'epaycore' => 17,
    'usdtbep20' => 19,
    'usdttrc20' => 18,
    'binancecoin' => 20,
    'usdterc20' => 21,
    'usdtpolygon' => 23,
    'matic' => 24
];

$isValid = true;
$walletUpdates = array();
$data = array();

foreach ($_POST as $field => $value) {
    if ($field === 'payment_settings' || empty($value)) {
        error_log("Skipping field {$field} - payment_settings or empty value");
        continue;
    }
    
    $currencyKey = $field;
    $validatorCurrencyId = isset($currencyIdMap[$currencyKey]) ? $currencyIdMap[$currencyKey] : null;
    
    $psCurrencyId = null;
    foreach ($ps as $ind => $setting) {
        if ($setting['field'] === $currencyKey) {
            $psCurrencyId = $ind;
            break;
        }
    }
    
    if ($validatorCurrencyId && $psCurrencyId !== null) {
        $validationResult = $validator->validate($validatorCurrencyId, $value, 'address');
        
        if (!$validationResult['valid']) {
            $isValid = false;
            call_alert(39, " " . $ps[$psCurrencyId]['name']); // Invalid address for Wallet
            $data[$currencyKey] = $value;
            break;
        }
        
        if ($user_settings['can_change_wallet_acc'] || empty($userinfo[$field])) {
            $walletUpdates[$field] = $value;
        }
    }
}

if ($isValid && !empty($walletUpdates)) {
    $walletsJson = json_encode($walletUpdates);
    $sanitizedData = db_filter(['wallets' => $walletsJson]);
    
    $updateQuery = "UPDATE users SET wallets = '{$sanitizedData['wallets']}' WHERE id = '{$user_id}'";
    $updateResult = mysqli_query($DB_CONN, $updateQuery);
    
    if ($updateResult) {
        sendmail("change_account", $user_id, $userinfo, $data);
        updateuserinfo();
        call_alert(38); // Wallet updated successfully
    } 
}
}

$smarty->display('user_wallets.tpl');
    break;
    case $main_link['2fa'] ? : '2fa':
        login_redirect();
assign_title('2fa');

if(!$user_settings['2fa']) {
  $link = $main_link['profile'] ?: 'profile'; 
  header("location: $link",  true,  301 );  exit;
}
if(isset($_SESSION['secret']) && $_SESSION['secret'] != '')
  $secret = $_SESSION['secret'];
else {
  $secret = TokenAuth6238::generateRandomClue();
  $_SESSION['secret'] = $secret;
}
$qr_url = TokenAuth6238::getBarCodeUrl($userinfo['username'],$pref['title'],$secret);
$smarty->assign('secret',$secret);
$smarty->assign('qr_url',$qr_url);

if(isset($_POST['en_2fa'])) {
  if (TokenAuth6238::verify($_POST['secret'],$_POST['code']))
  {
    $update_user = mysqli_query($DB_CONN,"UPDATE users set 2fa='{$_POST['secret']}' WHERE id='$user_id'");
    sendmail("change_account", $user_id, $userinfo, array('2FA' => 'enabled'));
    call_alert(36); //2FA Enabled
  }
  else
    call_alert(28); //2FA Invalid
}

if(isset($_POST['dis_2fa'])) {
  if (TokenAuth6238::verify($userinfo['2fa'],$_POST['code']))
  {
    $update_user = mysqli_query($DB_CONN,"UPDATE users set 2fa='' WHERE id='$user_id'");
    sendmail("change_account", $user_id, $userinfo, array('2FA' => 'disabled'));
    call_alert(37); //2FA Disabled
  }
  else
    call_alert(28); //2FA Invalid
}

updateuserinfo();
$smarty->display('user_2fa.tpl');
    break;
    case $main_link['password'] ?: 'password':
    login_redirect();
    assign_title('password');
   
if(isset($_POST['password'])) {
  $p = mysqli_query($DB_CONN,"SELECT password from users where id = '$user_id'");
  $p = mysqli_fetch_assoc($p);
  if($p['password'] == md5($_POST['ppassword'])) {
    if($_POST['password'] == $_POST['rpassword']) {
      $update_user = mysqli_query($DB_CONN,"UPDATE users set password=md5('$password') WHERE id='$user_id'");
      call_alert(32); //Password Changed Successfully
      sendmail("change_account", $user_id, $userinfo, array('password' => $_POST['password']));
    } else {
       call_alert(33); //Password and Retype Password Does not matched!
    }
  } else {
    call_alert(34); // Current Password Does not matched!
  } 
}

$smarty->display('user_password.tpl');
  break;
  case $main_link['profile'] ?: 'profile':
  login_redirect();
  $_SESSION['otp_action'] = 'Profile Updation';
  $user_id = $userinfo['id'];
  assign_title('profile');

$post = $_POST;
$submit = false;
if (isset($post['fullname'])) {
  unset($post['submit']);
  unset($post['save']);
  if(code_check_exists('profile') && $user_settings['confirmation']) {
    $_SESSION['post'] = $post;
    $smarty->assign('action','user');
    if($user_settings['email_code'])
      sendotp("Profile Updation");
    $smarty->display('user_confirmation.tpl');
    exit;
  } else
  $submit = true;
}
if(isset($post['confirm'])) {
  if(code_check('profile')) {
    $post = $_SESSION['post'];
    unset($_SESSION['post']);
    $submit = true;
  } else {
    $smarty->assign('action','user');
    $smarty->display('user_confirmation.tpl');
    exit;
  }
}
if($submit) {
  $cc = code_check('profile');
  if($cc):
  $data = array();
  $cont = true;
   if(count($post['payment'])) {
    $validator = new CryptoValidator();
    $currencyIdMap = [
        'bitcoin' => 1,
        'ethereum' => 4,
        'tron' => 14,
        'epaycore' => 17,
        'usdtbep20' => 19,
        'usdttrc20' => 18,
        'binance' => 20,
        'usdterc20' => 21,
        'usdtpolygon' => 23,
        'matic' => 24
    ];
    
    $isValid = true;
    $walletUpdates = array();
    foreach ($post['payment'] as $key => $value) {
        if($post['payment'][$key] != $userinfo['wallets'][$key]) {
            $data[$key] = $value;
        }
    }
    foreach ($post['payment'] as $field => $value) {
        if (empty($value)) {
            continue;
        }
        $currencyKey = str_replace("_acc", "", $field);
        $validatorCurrencyId = isset($currencyIdMap[$currencyKey]) ? $currencyIdMap[$currencyKey] : null;
        $psCurrencyId = null;
        foreach ($ps as $ind => $setting) {
            if ($setting['field'] === $currencyKey) {
                $psCurrencyId = $ind;
                break;
            }
        }
        if ($validatorCurrencyId && $psCurrencyId !== null) {
            $validationResult = $validator->validate($validatorCurrencyId, $value, 'address');
            
            if (!$validationResult['valid']) {
                $isValid = false;
                call_alert(39, " " . $ps[$psCurrencyId]['name']); // Invalid address for Wallet
                $cont = false;
                break;
            }
            
            $walletUpdates[$field] = $value;
        }
    }

    if($isValid && !empty($walletUpdates) && $cont) {
        $walletsJson = json_encode($walletUpdates);
        $sanitizedData = db_filter(['wallets' => $walletsJson]);
        
        $q = "UPDATE users set wallets = '{$sanitizedData['wallets']}' where `id` = '{$user_id}'";
        $update_user = mysqli_query($DB_CONN, $q);
        
        if (!$update_user) {
            $cont = false;
        }
    }
}
  if($register_settings['pin_code'] && $post['transaction']['code']) {
    if($post['transaction']['code'] == $post['transaction']['rcode'])
      mysqli_query($DB_CONN,"UPDATE users set pin_code = '{$_POST['transaction']['code']}' WHERE id='$user_id'");
    else {
      call_alert(30); //transaction code do not match
      $cont = false;
    }
  }
  if($post['password']['password'] && $cont) {
    if($post['password']['password'] == $post['password']['rpassword']){
       $password = password_hash($post['password']['password'], PASSWORD_DEFAULT);
      mysqli_query($DB_CONN,"UPDATE users set password = '{$password}' WHERE id='$user_id'");
    }else {
      call_alert(33); //password do not match
      $cont = false;
    }
  }

  if($cont) {
    unset($post['payment']);
    unset($post['transaction']);
    unset($post['password']);
    unset($post['2fa_code']);
    unset($post['pin_code']);
    unset($post['confirm']);
    unset($post['email_code']);
    foreach ($post as $key => $value) {
      if($key == 'email' && $user_settings['can_change_email'] && $value != $userinfo[$key])
        $data[$key] = $value;
      elseif($value != $userinfo[$key])
        $data[$key] = $value;
    }
    $cols = "`fullname` = '{$_POST['fullname']}', `question` = '{$_POST['question']}', `answer` = '{$_POST['answer']}', `phone` = '{$_POST['phone']}', `address` = '{$_POST['address']}', `city` = '{$_POST['city']}', `state` = '{$_POST['state']}', `zip` = '{$_POST['zip']}', `country` = '{$_POST['country']}'";
    if($user_settings['can_change_email']) {
      $cols .= ", `email` = '{$_POST['email']}'";
    }
    $a = mysqli_query($DB_CONN,"UPDATE users set {$cols} WHERE id='$user_id'");
    if($a) {
      sendmail("change_account", $user_id, $userinfo, $data);
      call_alert(31); //Profile Updated Successfully
    } else
    call_alert(5); //Invalid Data!
  }
  endif;
}

updateuserinfo();
$smarty->display('user_profile.tpl');
    break;
  case $main_link['logout'] ?: 'logout':
    setcookie("user_id", "", time() - 3600);
    session_destroy();
    if($user_settings['homepage_after_logout']) {
      header("location:index",  true,  301 );  exit;
    }else{
      header("location:login",  true,  301 );  exit;
    }
    call_alert(15); //You have sucessfully logged out!
     assign_title('home');
    $smarty->display('home.tpl');
  break;



  case 'status.php':
$url= $_SERVER['HTTP_HOST'];   
$url.= $_SERVER['REQUEST_URI'];
file_put_contents("test.txt", "********************{$url}************\n",FILE_APPEND);
file_put_contents("test.txt", $dt."\n",FILE_APPEND);
file_put_contents("test.txt", $ip."\n",FILE_APPEND);
file_put_contents("test.txt", print_r($_POST, true),FILE_APPEND);
file_put_contents("test.txt", print_r($_GET, true),FILE_APPEND);
file_put_contents("test.txt", print_r(json_decode(file_get_contents('php://input'), true), true),FILE_APPEND);
if(isset($_GET['veriff'])) {
    $rawPostData = file_get_contents('php://input');

    // Verify signature from header
    $headers = getallheaders();
    $signature = isset($headers['X-Hmac-Signature']) ? $headers['X-Hmac-Signature'] : '';
    file_put_contents("test.txt", print_r($headers, true),FILE_APPEND);
    
    // Verify the signature
    $calculated = hash_hmac('sha256', $rawPostData, $kyc_settings['veriff_secret']);
    file_put_contents("test.txt", $calculated, FILE_APPEND);
    if (!hash_equals($calculated, $signature)) {
        file_put_contents("test.txt", "Error: Invalid signature",FILE_APPEND);
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
    
    // Parse the JSON data
    $data = json_decode($rawPostData, true);
    // Check if this is a verification status event
    if (isset($data['data']['verification']) && isset($data['data']['verification']['decision'])) {
        $status = $data['data']['verification']['decision'];
        $vendorData = $data['vendorData'] ?? '';
        if ($status == 'approved') {
          if($kyc_settings['kyc_charges']) {
            add_balance($vendorData, $kyc_settings['kyc_charges_currency'], $kyc_settings['kyc_charges']);
          }
          $tdata = mysqli_real_escape_string($DB_CONN, json_encode($data));
          mysqli_query($DB_CONN, "UPDATE users set kyc = 1, kyc_data = '{$tdata}' where id = '{$vendorData}'");   
        }
    }
    
    // Always return a 200 OK to Veriff
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    die();
} 
if(isset($_GET['coinments'])) {
    //Coinments
  //var_dump($ip);
  // if   (!in_array($ip, array('2a02:4780:27:1521:0:e43:daf4:2','2a02:4780:27:1521:0:e43:daf4:1', '58.65.220.29'))) exit;
    $method = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from payment_methods where id = '7'"));
    $system = json_decode($method['currencies'], true);
    $api_key = encrypt_decrypt('decrypt', $system['secret_key']);
    $symbol = $_POST['symbol'];
    $txn_id = $_POST['txn_id'];
    $deposit = $_POST['amount'];
    $uhash = hash('sha256', $api_key.$symbol.$deposit.$txn_id);
    $hash = json_encode($_POST); // Store full POST data as hash
    
    // if($uhash == $_POST['hash'])
      confirm_deposit($_POST['id'],$deposit,$symbol,$txn_id,$hash,$_POST['address'],$_POST['confirmations']);

}
  break;
  case $main_link['deposit'] ?: 'deposit':
  login_redirect();
  assign_title('deposit');
  if(!$deposit_settings['topup']) {
    header("location: $dash_link",  true,  301 );  exit;
  }

if (isset($_POST['submit']) && $_POST['action'] == "deposit_request") {
    if (!Csrf::verifyToken('deposit')) {
        header("location: deposit", true, 301);
        exit;
    } else {
        Csrf::removeToken('deposit');
    }
    
    $deposit = $_POST['amount'];
    $credit = $deposit;
    $payment_method_id = $_POST['payment_method_id'];
    
    $method = mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$payment_method_id}'");
    $method = mysqli_fetch_assoc($method);
    $currency = $method['name'];
    
    $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '{$method['de_pm_id']}'"));
    $payment_method = $pm['name'];
    $system = json_decode($pm['currencies'], true);
    
    extract(deposit_fee($method, $deposit));
    
    if ($eligible1) {
        $DEPOSIT1 = mysqli_query($DB_CONN, "INSERT into transactions (user_id, amount, fee, payment_method_id, status, txn_type, detail)
            values('{$userinfo['id']}', '{$deposit}', '{$fee}', '{$payment_method_id}', '0', 'txn_type', 'Balance Deposit')");
            
        if ($DEPOSIT1) {
            $deposit = $deposit + $fee;
            $ID = mysqli_insert_id($DB_CONN);
            $package['name'] = "Account Balance";
            
            // Initialize the PaymentProcessor with all required parameters
            $processor = new PaymentProcessor(
                $pm['id'],
                $system,
                $DB_CONN,
                $preferences,
                $siteURL,
                $g_alert,
                $main_link
            );
            
            // Generate payment code
            $result = $processor->generatePaymentCode(
                $method['symbol'],
                "b".$ID,
                $package,
                $deposit,
                $userinfo,
                $method['address']
            );
            
            if (!$result || isset($result['error'])) {
                call_alert(65); // Some Error. Please try again
            } else {
                $form = $result['form'];
                $address = $result['address'];
                $img = $result['img'];
                $tag = $result['tag'];
                $amount = $result['amount'];
            }
        } else {
            call_alert(65); // Some Error. Please try again
        }
    }
}
$smarty->assign('payment_method',$method['name']);
$smarty->assign('payment_method_id',$method['id']);
$smarty->assign('address',$address);
isset($tag) ? $smarty->assign('tag',$tag) : '';
$smarty->assign('img',$img);
$smarty->assign('amount',$amount);
$smarty->assign('credit',$credit);
$smarty->assign('symbol',$symbol);

if($form != "")
    $smarty->assign('form',$form);
if(!empty($address))
    $smarty->assign('cur',$cur);

if(!empty($address) || $form != "") {
  $smarty->display('user_deposit.confirm.tpl');
  die();
}
$token = Csrf::getInputToken('deposit');
$smarty->registerFilter('output','add_hidden');
$smarty->assign('plans',$packages);
$smarty->display('user_deposit.tpl');
  break;
  case $main_link['cancel_withdraw'] ?: 'cancel_withdraw':
  login_redirect();
  assign_title('cancel_withdraw');
  if(!$withdraw_settings['cancel_withdraw']) {
    header("location: /", 301, true);
    exit;
  }
    $a = true;
    if (isset($_POST['submit']) && isset($_POST['action']) && $_POST['action']=="cancel_withdraw" && $_GET['id'] && $withdraw_settings['cancel_withdraw']) 
    {
    if(!Csrf::verifyToken('cancel')) {
      header("location: cancel");
      exit;
    } else
      Csrf::removeToken('cancel');
      $pp = mysqli_query($DB_CONN, "SELECT * FROM `transactions` where md5(id) = '{$_GET['id']}' and status = 0 and txn_type = 'withdraw' and user_id = '{$userinfo['id']}'");
      if(mysqli_num_rows($pp) > 0 && $withdraw_settings['cancel_withdraw']) {
        $perfect = mysqli_fetch_assoc($pp);
        add_balance($user_id, $perfect['payment_method_id'], $perfect['amount']+$perfect['fee']);
        $detail = $withdraw_settings['cancel_memo'];
        mysqli_query($DB_CONN, "UPDATE transactions set status = 2 , detail = '{$detail}' where id = '{$perfect['id']}'");
        call_alert(72); //Withdraw cancel Successfully
        $smarty->display('user_dashboard.tpl');
        die();
        $a = false;
      }
    } elseif($_GET['id']) {
      $pp = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = transactions.payment_method_id) as currency FROM `transactions` where md5(id) = '{$_GET['id']}' and status = 0 and txn_type = 'withdraw' and user_id = '{$userinfo['id']}'") or die(mysqli_error($DB_CONN));
      if(mysqli_num_rows($pp) > 0 && $withdraw_settings['cancel_withdraw']) {
        $dep = mysqli_fetch_assoc($pp);
        $a = false;
      }
    }
if($a) {
  call_alert(65); //Some Error. Please try again
  $smarty->display('user_dashboard.tpl');
} else {
$smarty->assign('withdraw',$dep['amount']);
$smarty->assign('currency',$dep['currency']);
$smarty->assign('credit',$dep['amount']+$dep['fee']);
$token = Csrf::getInputToken('cancel');
$smarty->registerFilter('output','add_hidden');
$smarty->display('user_withdraw_cancel.tpl');
}
  break;
  case $main_link['release_investment'] ? : 'release_investment':
       login_redirect();
       assign_title('release_investment');
    $a = true;
    if (isset($_POST['submit']) && isset($_POST['action']) && $_POST['id']) 
    {
    if(!Csrf::verifyToken('release')) {
      header("location: release");
      exit;
    } else
      Csrf::removeToken('release');
      $id = $_POST['id'];
      $chk = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = package_deposits.payment_method_id) as currency from package_deposits where md5(id) = '{$id}' and status = 1 and user_id = '{$user_id}'");
      if(mysqli_num_rows($chk) > 0) {
        $dep = mysqli_fetch_assoc($chk);
        if($_POST['action']=="release_deposit") {
          extract(calc_release($dep, $_POST['amount']));
          if(!$a) {
            $details = json_decode($package['details'], true);
            if(!$details['allowprincipalfull'] && $_POST['amount'] != $dep['amount'])
              mysqli_query($DB_CONN,"UPDATE package_deposits set amount = amount - $amount where id = '{$dep['id']}'");
            else
              mysqli_query($DB_CONN,"UPDATE package_deposits set status = 0 where id = '{$dep['id']}'");
            $amount = $amount - $fee;
            $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$dep['package_id']}'"));
            $details = json_decode($package['details'], true);
            if($details['release_to_pending']) {
              $field = strtolower(str_replace(' ','',$dep['currency']));
              $address = $userinfo['wallets'][$field];
              $memo = str_replace(array("#address#","#method#"), array($address ,$dep['currency']), $withdraw_settings['request_memo']);
              $w = mysqli_query($DB_CONN,"INSERT into transactions (user_id, address, amount, fee, payment_method_id, ip, txn_type, status, am_type, detail) values('{$user_id}', '{$address}', '{$amount}', '0', '{$dep['payment_method_id']}', '{$ip}', 'withdraw', '0', 'out','{$memo}')");
            } else {
              add_balance($user_id, $dep['payment_method_id'], $amount);
              $detail = str_replace(array("#amount#","#method#","#plan#", "#fee#"), array(fiat($amount), $dep['currency'], $package['name'], $fee), $deposit_settings['investment_released']);
              $w = mysqli_query($DB_CONN,"INSERT into transactions (user_id, amount, fee, payment_method_id, ip, txn_type, detail, ref_id) values('{$user_id}', '{$amount}', '{$fee}', '{$dep['payment_method_id']}', '{$ip}', 'release' , '{$detail}', '{$dep['id']}')");
            }
            call_alert(41); //Deposit Amount Released Successfully.
            $smarty->display('user_dashboard.tpl');
            die();
          }
        } elseif($_POST['action']=="confirm_release") {
          $amount = (float)$_POST['amount'];
          if($amount < 0) {
            $a = true;
            call_alert(65); //Some Error. Please try again
          }
          extract(calc_release($dep, $amount));
          $confirm = true;
        }
      }
    } elseif($_GET['id']) {
      $chk = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = package_deposits.payment_method_id) as currency from package_deposits where id = '{$_GET['id']}' and status = 1 and user_id = '{$user_id}'");
      if(mysqli_num_rows($chk) > 0) {
        $dep = mysqli_fetch_assoc($chk);
        extract(calc_release($dep));
        $details = json_decode($package['details'], true);
        $earned = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT sum(amount) FROM `transactions` WHERE txn_type = 'earning' and ref_id = '{$dep['id']}'"))[0];
        if(!$details['allowprincipalfull'])
          $confirm = false;
        else
          $confirm = true;
      }
    }
if($a) {
  call_alert(65); //Some Error. Please try again
  $smarty->display('user_dashboard.tpl');
} else {
$smarty->assign('package',$package);
$smarty->assign('confirm',$confirm);
$smarty->assign('earned',$earned);
$smarty->assign('investment',$dep);
$smarty->assign('amount',$amount);
$smarty->assign('fee',$fee);
$smarty->assign('feep',round(($fee/$amount)*100, 2));
$smarty->assign('credit',$amount-$fee);
$token = Csrf::getInputToken('release');
$smarty->registerFilter('output','add_hidden');
$smarty->display('user_investment.release.tpl');
}
  break;
  
  case $main_link['pay'] ?: 'pay':
      $payment_key = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($payment_key)) {
    die("Invalid payment request");
}

// Extract user ID from payment key (if we use a prefix format)
$parts = explode('_', $payment_key);
if (count($parts) != 2) {
    die("Invalid payment key format");
}

$user_telegram_id = $parts[0];
$actual_key = $parts[1];

// Get user state directly
$user_state = getUserState($user_telegram_id);

// Verify the payment key matches
if ($user_state['state'] != 'awaiting_payment_confirmation' || 
    !isset($user_state['data']['payment_key']) || 
    $user_state['data']['payment_key'] != $actual_key) {
    die("Payment not found or expired");
}

$payment_data = $user_state['data'];

// Check for payment link expiration (optional, 30 minutes)
$timestamp = isset($payment_data['timestamp']) ? intval($payment_data['timestamp']) : 0;
if ($timestamp > 0 && (time() - $timestamp) > 1800) {
    die("Payment link has expired. Please request a new payment link.");
}

// Log the payment access
$deposit_id = $payment_data['deposit_id'];
$user_id = getUserId($user_telegram_id); // Assuming you have this function to get internal user ID


// Extract form details
$payment_form = $payment_data['payment_form'];
$payment_processor = $payment_data['processor'];
$smarty->assign('processor',$payment_processor);
$smarty->assign('form',$payment_form);
$smarty->display('user_pay.tpl');

       break;
  case $main_link['invest'] ?: 'invest':
  login_redirect();
assign_title('invest');
$eligible1=false;
$u=mysqli_query($DB_CONN, "select * from users where id = '{$user_id}'");
$user = mysqli_fetch_assoc($u);
$last_30 = date("Y-m-d H:i:s", strtotime("-30 minutes"));
$img = "";
$plan_name = "";
$principal = false;
$address = "";
$currency = "";
if(isset($_POST['update_txn_id']) && isset($_POST['id'])) {
  mysqli_query($DB_CONN, "UPDATE package_deposits set txn_id = '{$_POST['txn_id']}' where id = '{$_POST['id']}' and status = 0 and user_id = '{$userinfo['id']}'");
 call_alert(47); //Payment Request Received. Payment will be approved after checking.
}
if (isset($_POST['submit'])) 
{
if (isset($_POST['action']) && $_POST['action']=="invest_request" && is_numeric($_POST['amount']))
{
  if(!Csrf::verifyToken('invest')) {
    header("location: invest");
    exit;
  } else
    Csrf::removeToken('invest');
  $package_id=$_POST['package_id'];
  $deposit=$_POST['amount'];
  $credit = $deposit;
  $payment_method_id=$_POST['payment_method_id'];
  $package_= mysqli_query($DB_CONN,"SELECT * from packages where status=1 AND id='{$package_id}'") or die(mysqli_error($DB_CONN));
  if(mysqli_num_rows($package_)>0)
  {
  $package=mysqli_fetch_assoc($package_);
  $details = json_decode($package['details'], true);
  $plan_name = $package['name'];
  $days = $package['duration'];
  $plan_= mysqli_query($DB_CONN,"SELECT MIN(range_min) as min, MAX(range_max) as max from package_plans where package_id='{$package_id}' and range_min != '' and range_max != ''");
  $plan=mysqli_fetch_assoc($plan_);
  if(stripos($payment_method_id, "account_") !== FALSE){
    $acc_id = (int)str_replace("account_", "", $payment_method_id);
  }elseif(stripos($payment_method_id, "faucet_") !== FALSE){
    $acc_id = (int)str_replace("faucet_", "", $payment_method_id);
  }else
    $acc_id = $payment_method_id;
  $method = mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$acc_id}'");
  $method = mysqli_fetch_assoc($method);
  $currency = $method['name'];
  $symbol = $method['symbol'];
  $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '{$method['de_pm_id']}'"));
  $system = json_decode($pm['currencies'], true);
  $principal = $package['principal_return'];
  $func = check_deposit($deposit, $package, $plan, $user_id, $method, $acc_id, $details, $_POST['compound']);
  $eligible1 = $func[0];
  $fee = $func[1];
  if ($eligible1) 
   {
     $plan__= mysqli_query($DB_CONN,"SELECT * from package_plans where range_min <= {$deposit} and range_max >= {$deposit} AND package_id='{$package_id}' ");
       if (mysqli_num_rows($plan__)>0) 
      {    $count=true;
        $PLAN=mysqli_fetch_assoc($plan__);
         if($PLAN['range_max']>=$deposit & $PLAN['range_min']<=$deposit & $count)
         {
          $DEPOSIT1= mysqli_query($DB_CONN,"INSERT INTO package_deposits (user_id,package_id,plan_id,amount,fee,status,payment_method_id,ip, auto_reinvest, compound) VALUES ('{$user_id}','{$package_id}','{$PLAN['id']}','{$deposit}','{$fee}',0,'{$acc_id}','{$ip}', '{$_POST['auto_reinvest']}', '{$_POST['compound']}')");
          if ($DEPOSIT1) 
           {
            $deposit = $deposit+$fee;
            $count=false;
            $ID=mysqli_insert_id($DB_CONN);
            if(stripos($payment_method_id, "account_") !== FALSE) {
              if($details['accept_account_balance']) {
                $bal = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT balance FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$acc_id}' limit 1"))[0];
                if($bal < $deposit) {
                  call_alert(59); //Insufficient Balance!
                } else {
                  if(isset($_POST['confirm'])) {
                      if($deposit_settings['deposit_from_balance_bonus'])
                      $am = $credit + ($credit * $deposit_settings['deposit_from_balance_bonus'] / 100) ; 
                      else
                    $am = $credit;
                    if($deposit_settings['deposit_from_balance_fee'])
                      $fee = ($am/100)*$deposit_settings['deposit_from_balance_fee'];
                    else
                      $fee = 0;
                    $am = $am-$fee;
                    $txnid = $currency.' Account Balance';
                    activate_package($ID, $am, $fee, $txnid, $userinfo['id'], $acc_id, $package_id, $PLAN['id'], $method, '', 'invest', $referral_settings['reffrom_balance']);
                    add_balance($user['id'], $acc_id, -$credit);
                    updateuserinfo();
                    call_alert(44); //Your Investment from the Account Balance has been active successfully.
                  } else {
                    $token = Csrf::getInputToken('invest');
                    $form = '<form method="POST">
                    '.$token.'
                    <input type="hidden" name="package_id" value="'.$_POST['package_id'].'" >
                    <input type="hidden" name="payment_method_id" value="'.$_POST['payment_method_id'].'" >
                    <input type="hidden" name="amount" value="'.$_POST['amount'].'" >
                    <input type="hidden" name="action" value="invest_request" >
                    <input type="hidden" name="submit" >
                    <input type="submit" name="confirm" value="'.$g_alert[95]['content'].'" class="'.$g_alert[95]['class'].'"   />
                    </form>';
                    $amount = $deposit;
                  }
                }
              } else
                call_alert(57); //Account Balance for this package not allowed
            } elseif(stripos($payment_method_id, "faucet_") !== FALSE) {
               if($details['accept_faucet_balance']) {
                $bal = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT faucet FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$acc_id}' limit 1"))[0];
                if($bal < $deposit) {
                  call_alert(59); //Insufficient Balance!
                } else {
                  if(isset($_POST['confirm'])) {
                    $am = $credit;
                    if($deposit_settings['deposit_from_balance_fee'])
                      $fee = ($am/100)*$deposit_settings['deposit_from_balance_fee'];
                    else
                      $fee = 0;
                    $am = $am-$fee;
                    $txnid = $currency.' Faucet Balance';
                    activate_package($ID, $am, $fee, $txnid, $userinfo['id'], $acc_id, $package_id, $PLAN['id'], $method, '', 'invest', $referral_settings['reffrom_balance']);
                    add_faucet($user['id'], $acc_id, -$credit);
                    updateuserinfo();
                    call_alert(441); //Your Investment from the Faucet Balance has been active successfully.
                  } else {
                    $token = Csrf::getInputToken('invest');
                    $form = '<form method="POST">
                    '.$token.'
                    <input type="hidden" name="package_id" value="'.$_POST['package_id'].'" >
                    <input type="hidden" name="payment_method_id" value="'.$_POST['payment_method_id'].'" >
                    <input type="hidden" name="amount" value="'.$_POST['amount'].'" >
                    <input type="hidden" name="action" value="invest_request" >
                    <input type="hidden" name="submit" >
                    <input type="submit" name="confirm" value="'.$g_alert[94]['content'].'" class="'.$g_alert[95]['class'].'"   />
                    </form>';
                    $amount = $deposit;
                  }
                }
              } else
                call_alert(571); //Faucet Balance for this package not allowed 
            }else {
              if ($details['accept_processings']) {
                    // Initialize the PaymentProcessor
                    $processor = new PaymentProcessor(
                        $pm['id'],
                        $system,
                        $DB_CONN,
                        $preferences,
                        $siteURL,
                        $g_alert,
                        $main_link
                    );
                    $result = $processor->generatePaymentCode(
                        $method['symbol'],
                        $ID,
                        $package,
                        $deposit,
                        $userinfo,
                        $method['address']
                    );
                    
                    if (!$result || isset($result['error'])) {
                        call_alert(65); // Some Error. Please try again
                    } else {
                        $form = $result['form'];
                        $address = $result['address'];
                        $img = $result['img'];
                        $tag = $result['tag'];
                        $amount = $result['amount'];
                    }
                } else {
                    call_alert(58); // Payment Processor for this package not allowed
                }
            }
          } //if inserted amount deposit
          else
            call_alert(49); //Please check Max amount to deposit in 
          } //if valid for min and max range 
        } //if package amount min valid
      }
    } else
      call_alert(50);// invalid package
  }
}
$pri = $principal == 1 ? "Yes" : "No (included in profit)";
$perc = $package['etype'] == 0 ? $PLAN['percent_max']."%" : $PLAN['percent_min']."% - ".$PLAN['percent_max']."%";
$smarty->assign('plan_name', $plan_name);
$smarty->assign('principal', $pri);
$smarty->assign('principal_hold', $package['principal_hold']);
$smarty->assign('etype', $package['etype']);
$smarty->assign('fee', $fee);
$smarty->assign('payment_method', $method['name']);
$smarty->assign('principal_hold', $package['principal_hold']);
$smarty->assign('allowprincipal', $package['allowprincipal']);
$smarty->assign('details', $details);
if ($details['auto_reinvest'] && $_POST['auto_reinvest']) {
    $smarty->assign('auto_reinvest', "Yes");
}
$smarty->assign('work_week', $package['earnings_mon_fri']);
$smarty->assign('payment_method_id', $method['id']);
$frequencies = [
    'hours' => ['label' => 'Hour', 'period' => $package['diff_in_seconds'] / 3600 / 24],
    'hourly' => ['label' => 'Hourly', 'period' => $package['duration']],
    'daily' => ['label' => 'Daily', 'period' => $package['duration']],
    'weekly' => ['label' => 'Weekly', 'period' => $package['duration']],
    'monthly' => ['label' => 'Month', 'period' => $package['duration']],
    'days' => ['label' => 'Day', 'period' => $package['diff_in_seconds'] / 3600 / 24],
    'weeks' => ['label' => 'Week', 'period' => $package['diff_in_seconds'] / 3600 / 24],
    'bi-weeks' => ['label' => 'Bi Week', 'period' => $package['diff_in_seconds'] / 3600 / 24],
    'months' => ['label' => 'Month', 'period' => $package['diff_in_seconds'] / 3600 / 24]
];
$frequency = $package['frequency'];
$default = ['label' => ucfirst($frequency), 'period' => $package['diff_in_seconds'] / 3600 / 24];
$info = $frequencies[$frequency] ?? $default;
$p = ($frequency == 'hourly' || $frequency == 'daily' || $frequency == 'weekly' || $frequency == 'monthly') 
    ? $info['label'] . ' for ' . $info['period'] . ' ' . $info['label']
    : 'After ' . $info['period'] . ' ' . $info['label'];
$smarty->assign('profit', $perc . '&nbsp;' . $p);
$smarty->assign('period', $info['period']);
$smarty->assign('address', $address);
if (isset($tag)) {
    $smarty->assign('tag', $tag);
}
$smarty->assign('img', $img);
$smarty->assign('amount', $amount);
$smarty->assign('credit', $credit);
$smarty->assign('symbol', $symbol);
if($form != "")
    $smarty->assign('form',$form);
if(!empty($address))
    $smarty->assign('cur',$cur);
if(!empty($address) || $form != "") {
  $smarty->display('user_invest.confirm.tpl');
  die();
}
$package_= mysqli_query($DB_CONN,"select * from packages where status=1 and id not in (SELECT package_id FROM `package_deposits` WHERE user_id = 1 and status = 1) order by id") or die(mysqli_error($DB_CONN));
$packages = array();
$i = 0;
while ($package=mysqli_fetch_assoc($package_))
{
    $packages[$i] = $package;
    if($i == 0)
        $packages[$i]['a'] = 1;
    else
        $packages[$i]['a'] = 0;
    switch ($package['diff_in_seconds']) {
        case '3600':
            $t = 'hourly';
            break;
        case '86400':
            $t = 'daily';
            break;
        case '604800':
            $t = 'weekly';
            break;
        case '1209600':
            $t = 'bi-weekly';
            break;
        case '2592000':
            $t = 'monthly';
            break;
        case '77676000':
            $t = 'quarterly';
            break;
        case '15552000':
            $t = 'semi-annually';
            break;
        case '31104000':
            $t = 'annually';
            break;
        case ($package['diff_in_seconds']%3600):
            $t = "after hours";
            break;
        case ($package['diff_in_seconds']%86400):
            $t = "after days";
            break;
        case ($package['diff_in_seconds']%86400*7):
            $t = "after weeks";
            break;
        case ($package['diff_in_seconds']%86400*14):
            $t = "after bi-weeks";
            break;
        case ($package['diff_in_seconds']%86400*30):
            $t = "after months";
            break;
    }
    $packages[$i]['type'] = $t;
    $plan_= mysqli_query($DB_CONN,"SELECT * FROM `package_plans` where package_id = '{$package['id']}'") or die(mysqli_error($DB_CONN));
    $j = 0;
    while($plan = mysqli_fetch_assoc($plan_)) {
        if($plan['range_min']) {
            $packages[$i]['plans'][$j] = $plan;
            $packages[$i]['plans'][$j]['deposit'] = $plan['range_min'];
            if($package['etype'] == 1) {
                $packages[$i]['plans'][$j]['percent'] = number_format($plan['percent_min'],2)."% - ".number_format($plan['percent_max'],2)."%";
            } else {
                $packages[$i]['plans'][$j]['percent'] = number_format($plan['percent_max'],2);
            }
            $j++;
        }
    }
    $i++;
}
$token = Csrf::getInputToken('invest');
$smarty->registerFilter('output','add_hidden');
$smarty->assign('plans',$packages);
$smarty->display('user_invest.tpl');
  break;
    case $main_link['exchange'] ?: 'exchange':
    login_redirect();
    $_SESSION['otp_action'] = 'Funds Exchange';
    assign_title('exchange');
    if (!$exchange_settings['enable']) { 
   header("location: $dash_link",  true,  301 );  exit;
  }
  $str = "";
  $a1 = true;
  $post = $_POST;
  $submit = false;
  if (isset($post['submit']) && $post['action'] == 'exchange') {
    if(code_check_exists('exchange') && $exchange_settings['confirmation']) {
      $_SESSION['post'] = $post;
      $smarty->assign('action','exchange');
      if($exchange_settings['email_code'])
        sendotp("Funds Exchange");
      $smarty->display('user_confirmation.tpl');
      exit;
    } else
    $submit = true;
  }

if(isset($post['confirm'])) {
  if(code_check('exchange')) {
    $post = $_SESSION['post'];
    unset($_SESSION['post']);
    $submit = true;
  } else {
    $smarty->assign('action','exchange');
    $smarty->display('user_confirmation.tpl');
    exit;
  }
}
    if(isset($post['submit'])) {
    $_POST['csrftoken'] = $post['csrftoken'];
    if(!Csrf::verifyToken('exchange')) {
       header("location: exchange",  true,  301 );  exit;
    } else
      Csrf::removeToken('exchange');
    $from_currency=$post['from_currency'];
    $to_currency=$post['to_currency'];
    $from_amount=$post['from_amount'];
    if ($from_amount <= 0) {
      $a1 = false;
      call_alert(42); // Try higher amount. Minimum Deposit is
    }
    if($exchange_settings['exchange_limit'] && $exchange_settings['limit_period']) {
      $cond = getcond($exchange_settings['limit_period']);
      $c = mysqli_query($DB_CONN, "SELECT * FROM `transactions` WHERE txn_type = 'exchange' and user_id = '{$userinfo['id']}' and am_type = 'out' and {$cond}");
      if(mysqli_num_rows($c) >= $exchange_settings['exchange_limit']) {
        $a1 = false; 
        call_alert(84); //Exchange Limit Reached
      }
    }
    if($post['from_currency'] && $post['to_currency'] && $from_amount) {
    $rate = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = exchange.from_currency) as fcurrency, (SELECT name from currencies where id = exchange.to_currency) as tcurrency from exchange where from_currency = '{$from_currency}' and to_currency = '{$to_currency}' order by id desc limit 1");
    if(mysqli_num_rows($rate)) {
      $exchange = mysqli_fetch_assoc($rate);
      $balance = $userinfo['balances'][$post['from_currency']];
      $to_amount = $from_amount*((100-$exchange['rate'])/100);
      if($balance >= $from_amount) {
        if($post['action'] == 'exchange' && $submit) {
          $a1 = code_check('exchange');
          if($a1) {
            add_balance($userinfo['id'], $from_currency, -$from_amount);
            add_balance($userinfo['id'], $to_currency, $to_amount);
            $rep = array("#from_currency#", "#to_currency#", "#from_amount#", "#to_amount#");
            $with = array($exchange['fcurrency'], $exchange['tcurrency'], $from_amount, $to_amount);
            $detailf = str_replace($rep, $with, $exchange_settings['from_memo']);
            $detailt = str_replace($rep, $with, $exchange_settings['to_memo']);
            $fee = $from_amount - $to_amount;
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`user_id`, `amount`, `fee`, `txn_type`, `am_type`, `payment_method_id`, `detail`, `ip`) VALUES ('{$userinfo['id']}','{$from_amount}', '0', 'exchange', 'out', '{$from_currency}', '{$detailf}', '{$ip}')");
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`user_id`, `amount`, `fee`, `txn_type`, `am_type`, `payment_method_id`, `detail`, `ip`) VALUES ('{$userinfo['id']}','{$to_amount}', '{$fee}', 'exchange', 'in', '{$to_currency}', '{$detailt}', '{$ip}')");
            sendmail("exchange_user_notification", $userinfo['id'], $userinfo, array("from_currency" => $exchange['fcurrency'], "to_currency" => $exchange['tcurrency'], "amount_from" => $from_amount, "amount_to" => $to_amount));
            call_alert(81); //Balance Exchanged Successfully
          }
        } elseif($post['action'] == 'confirm') {
          $rate = $exchange['rate'];
          $str .= '<input type=hidden name=from_currency value="'.$from_currency.'" />';
          $str .= '<input type=hidden name=to_currency value="'.$to_currency.'" />';
          $str .= '<input type=hidden name=from_amount value="'.$from_amount.'" />';
          $str .= '<input type=hidden name=rate value="'.$rate.'" />';
          $str .= '<input type=hidden name=action value="exchange" />';
          $smarty->assign('from_currency',$exchange['fcurrency']);
          $smarty->assign('to_currency',$exchange['tcurrency']);
          $smarty->assign('from_id',$from_currency);
          $smarty->assign('to_id',$to_currency);
          $smarty->assign('from_amount',$from_amount);
          $smarty->assign('to_amount',$to_amount);
          $smarty->assign('rate',$rate);
          $smarty->assign('confirm',true);
        }
      } else
        call_alert(82); //Insufficient Balance
      } else
        call_alert(83); // Invalid Selection
    }
    }
    $plans = array();
    $i=0;
    $ex = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = exchange.from_currency) as fcurrency, (SELECT name from currencies where id = exchange.to_currency) as tcurrency FROM `exchange`");
    while ($exchange = mysqli_fetch_assoc($ex)) {
      $plans[$i] = $exchange;
        $i++;
    }
    $token = Csrf::getInputToken('exchange');
    $token .= $str;
    $smarty->registerFilter('output','add_hidden');
    $smarty->assign('exchange',$plans);
    $smarty->display('user_exchange.tpl');
    break;
    case $main_link['transfer'] ? : 'transfer':
    login_redirect();
    $_SESSION['otp_action'] = 'Funds Transfer';
    assign_title('transfer');

    if (!$transfer_settings['enable']) {
   header("location: $dash_link",  true,  301 );  exit;
  }
  $a1 = true;
  $str = "";
  $post = $_POST;
  $submit = false;
  if (isset($post['submit'])) {
    if(code_check_exists('transfer') && $transfer_settings['confirmation']) {
      $submit = false;
      $_SESSION['post'] = $post;
      $smarty->assign('action','transfer');
      if($transfer_settings['email_code'])
        sendotp("Funds Transfer");
      $smarty->display('user_confirmation.tpl');
      exit;
    } else
    $submit = true;
  }
if(isset($post['confirm'])) {
  if(code_check('transfer')) {
    $post = $_SESSION['post'];
    unset($_SESSION['post']);
    $submit = true;
  } else {
    $smarty->assign('action','transfer');
    $smarty->display('user_confirmation.tpl');
    exit;
  }
}
    if($post['amount']) {
      $_POST['csrftoken'] = $post['csrftoken'];
      if(!Csrf::verifyToken('transfer')) {
        header("location: transfer",  true,  301 );  exit;
      } else
        Csrf::removeToken('transfer');
      $om = $amount = $post['amount'];
      $transferto=strtolower($post['transferto']);
      $transferfrom=strtolower($userinfo['username']);
      $payment_method_id=$post['payment_method_id'];
      $comment = $post['comment'];
    }
 
    if(isset($post['submit']) && is_numeric($post['amount']) && $post['payment_method_id'] && $submit) {
      if($a1) {
        $to_detail = str_replace(array("#username#", "#comment#"), array($transferto, $comment), $transfer_settings['to_memo']);
        $from_detail = str_replace(array("#username#", "#comment#"), array($transferfrom, $comment), $transfer_settings['from_memo']);
        extract(gettransfer($amount, $transferto, $payment_method_id, $method));
        $a1 = code_check('transfer');
        if($a1) {
          $pfee = $rfee = 0;
          $credit = $amount;
          if($fee) {
            if($transfer_settings['internal_transfer_fee_payer'] == 'payer') {
              $credit = $amount;
              $amount += $fee;
              $pfee = $fee;
            } else {
              $credit = $amount-$fee;
              $rfee = $fee;
            }
          }
          add_balance($userinfo['id'], $payment_method_id, -$amount);
          add_balance($userid, $payment_method_id, $credit);
          mysqli_query($DB_CONN, "INSERT into transactions(txn_type, payment_method_id, user_id, amount, fee, detail, am_type)
            values('transfer', '{$payment_method_id}', '{$userinfo['id']}', '{$amount}', '{$pfee}', '{$to_detail}', 'out')");
          mysqli_query($DB_CONN, "INSERT into transactions(txn_type, payment_method_id, user_id, amount, fee, detail)
            values('transfer', '{$payment_method_id}', '{$userid}', '{$credit}', '{$rfee}', '{$from_detail}')");
          sendmail("transfer_to_notification", $userinfo['id'], $userinfo, array("amount" => $amount, "to_username" => $transferto));
          sendmail("transfer_from_notification", $userid, $touser, array("amount" => $credit, "from_username" => $transferfrom));
          call_alert(77, " to $transferto"); //Amount Transferred Successfully
        }
        }
    } elseif(isset($post['transfer_submit']) && is_numeric($post['amount']) && $post['payment_method_id']) {
      extract(gettransfer($amount, $transferto, $payment_method_id, $method));
      if($a1) {
        if($transfer_settings['internal_transfer_fee_payer'] == 'payer') {
          $credit = $amount;
          $amount += $fee;
        } else
          $credit = $amount-$fee;
        $str .= '<input type=hidden name=amount value="'.$om.'" />';
        $str .= '<input type=hidden name=transferto value="'.$transferto.'" />';
        $str .= '<input type=hidden name=payment_method_id value="'.$payment_method_id.'" />';
        $str .= '<input type=hidden name=comment value="'.$comment.'" />';
        $smarty->assign('system',$method['name']);
        $smarty->assign('payment_method_id',$payment_method_id);
        $smarty->assign('amount',$amount);
        $smarty->assign('transferto',$transferto);
        $smarty->assign('debit',$credit);
        $smarty->assign('fee',$fee);
        $smarty->assign('comment',$comment);
        $smarty->assign('str',$str);
        $smarty->assign('confirm',true);
      }
    }

  $token = Csrf::getInputToken('transfer');
  $token .= $str;
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('user_transfer.tpl');
  break;
  case $main_link['kyc'] ? $main_link['kyc'] : 'kyc':
        login_redirect();
      assign_title('kyc');

  if (!$kyc_settings['enable']) {
   header("location: $dash_link",  true,  301 );  exit;
  }
  $a = true;
  if(isset($_POST['submit']) && $_FILES["front_image"]["name"] && $_FILES["back_image"]["name"] && $userinfo['kyc'] == 0) {
    if(!Csrf::verifyToken('kyc')) {
      header("location: kyc",  true,  301 );  exit;
      die();
    } else
      Csrf::removeToken('kyc');
    $data = array();
    upload_image('front_image');
    upload_image('back_image');
    upload_image('address_image');
    foreach($_POST as $a=>$b) {
    if($a != "submit" && $a != "csrftoken" && $b) {
      $data[$a] = $b;
      }
    }
    $data = mysqli_real_escape_string($DB_CONN, json_encode($data));
    if(mysqli_query($DB_CONN, "UPDATE users set kyc_data = '{$data}', kyc = 2 where id = '{$userinfo['id']}'"))
      call_alert(87); //KYC Received wll be reviewed shortly

  } else
    call_alert(65); //Some Error. Please try again
  if($userinfo['kyc'] == 1)
    call_alert(89); //KYC already approved
  if($userinfo['kyc'] == 2)
    call_alert(88); //KYC is already in process please wait for it
  $token = Csrf::getInputToken('kyc');
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('user_kyc.tpl');
  break;
  case $main_link['withdraw'] ?: 'withdraw':
  login_redirect();
  assign_title('withdraw');
  $with_link = $main_link['withdrawals'] ?: 'withdrawals';
if(!$withdraw_settings['enable']) {
   header("location: $dash_link",  true,  301 );  exit;
}
if($withdraw_settings['kyc'] && $userinfo['kyc'] != 1)
  call_alert(90, "withdraw"); //KYC is required for 
if(isset($_POST['enable_auto']) && $userinfo['auto_withdraw'] != 3 && $userinfo['auto_withdraw'] != 4) {
  if(mysqli_query($DB_CONN, "UPDATE users set auto_withdraw = 1 where id = '{$user_id}'"))
    call_alert(67); //Auto Withdraw Enabled Successfully and will automatically process when balance reaches minimum withdraw amount
}

if(isset($_POST['disable_auto']) && $userinfo['auto_withdraw'] != 3 && $userinfo['auto_withdraw'] != 4) {
  if(mysqli_query($DB_CONN, "UPDATE users set auto_withdraw = 2 where id = '{$user_id}'"))
    call_alert(68); //Auto Withdraw Disabled Successfully
}
$post = $_POST;
$submit = false;
if (isset($post['submit']) && $post['action'] == 'withdraw') {
  if(code_check_exists('withdraw')) {
    if($withdraw_settings['confirmation']) {
      $_SESSION['post'] = $post;
      $smarty->assign('action','withdraw');
      if($withdraw_settings['email_code'])
        sendotp("Funds Withdraw");
      $smarty->display('user_confirmation.tpl');
      exit;
    } else {
      if(!code_check('withdraw')) {
        $post['action'] = 'with_submit';
      } else
      $submit = true;
    }
  } else
  $submit = true;
}
if(isset($post['confirm'])) {
  if(code_check('withdraw')) {
    $post = $_SESSION['post'];
    unset($_SESSION['post']);
    $submit = true;
  } else {
    $smarty->assign('action','withdraw');
    $smarty->display('user_confirmation.tpl');
    exit;
  }
}
$_SESSION['amount'] = "";
$str = "";
if(is_numeric($post['amount']))
  $_SESSION['amount'] = $post['amount'];
if (isset($post['submit']) && isset($post['action']) && is_numeric($post['amount']) && $post['amount'] > 0 && $withdraw_settings['enable'])  {
    $_SESSION['otp_action'] = 'Funds Withdraw';
    $withdraw=$post['amount'];
    $payment_method_id=$post['payment_method_id'];
    $_POST['csrftoken'] = $post['csrftoken'];
    if(!Csrf::verifyToken('withdraw')) {
      header("location: withdraw",  true,  301 );
      exit; 
    } else
      Csrf::removeToken('withdraw');

    if ($withdraw_settings['address'] && $_POST['address']) {
      $custom_address = $_POST['address'] ?? null;
      $data = check_withdraw($withdraw, $payment_method_id, $custom_address);
    }
    else
      $data = check_withdraw($withdraw, $payment_method_id);
    if(!$data[0]):
      $withdraw = $data[1];
      $fee = $data[2];
      $address = $data[3];
      $method = $data[4];
    if($withdraw_settings['multiple_account']) {
      $li = mysqli_query($DB_CONN, "SELECT DISTINCT user_id FROM `login_report` WHERE ip in (SELECT DISTINCT ip FROM `login_report` WHERE user_id = '{$userinfo['id']}') AND user_id != '{$userinfo['id']}' ");
      if (mysqli_num_rows($li) > 0) {
        if (!$userinfo['allowed_multiple']) {
            $submit = false;
            call_alert(163); // Your withdrawals are Blacklisted as you have created more than 1 account from the same device.
        } else {
            $submit = true; // Allow withdrawal if the user is allowed multiple accounts.
        }
      }
    }
    if ($post['action']=="withdraw" && $submit) {
      $ww = code_check('withdraw');
      if($ww) {
        $memo = str_replace(array("#address#","#method#"), array($address ,$method['name']), $withdraw_settings['request_memo']);
        $w = mysqli_query($DB_CONN,"INSERT into transactions (user_id, address, amount, fee, payment_method_id, ip, txn_type, status, am_type, detail) values('{$user_id}', '{$address}', '{$withdraw}', '{$fee}', '{$payment_method_id}', '{$ip}', 'withdraw', '0', 'out','{$memo}')");
        $wid = mysqli_insert_id($DB_CONN);
        $amount = $withdraw;
        if($w) {
          add_balance($user_id, $payment_method_id, -$amount);
          updateuserinfo();
          call_alert(63); //Withdraw Request Received Successfully
          sendmail("withdraw_request_user_notification", $userinfo['id'], $userinfo, array('amount' => $withdraw, 'account' => $method['name'], 'address' => $address, 'dt' => $dt));
          $delay = $a = true;
          $day = date('l');
          if($withdraw_settings['instantwithdraw_2fa'] && !$userinfo['2fa'])
            $a = false;
          if($withdraw_settings['instantwithdraw_weekdays'] && ($day == "Sunday" || $day == "Saturday"))
            $a = false;
          if($withdraw_settings['delay_instant_withdraw'] || $userinfo['delay'])
            $delay = false;
          if($withdraw_settings['withtype'] && $userinfo['auto_withdraw'] != 3 && $a && $delay) {
          $o_am = $amount;
          $amount = $amount-$fee;
          if(!$withdraw_settings['max_daily_withdraw_limit'])
            $withdraw_settings['max_daily_withdraw_limit'] = 10000000000000000000;
          if($amount < $withdraw_settings['max_daily_withdraw_limit']) {
            if($userinfo['wi_pm_id'] && !in_array($method['wi_pm_id'], array(2, 6, 17)))
              $method['wi_pm_id'] = $userinfo['wi_pm_id'];
              $system = json_decode($method['payment_system'], true);
               $method['system'] = $system;
              $processor = new PaymentProcessor($method['wi_pm_id'], $method['system'], $DB_CONN,$preferences, $siteURL,$g_alert, $main_link,$siteName);
              $tx_id = $processor->processWithdrawal($address, $amount, $wid, $method['symbol']);
                        
                       
           // $tx_id = sendwithdraw($method['wi_pm_id'], $address, $amount, $wid, $method['symbol']);
            if($tx_id) {
              $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($address ,$method['name'], $tx_id), $withdraw_settings['instant_memo']);
              mysqli_query($DB_CONN, "UPDATE `transactions` set status = 1, txn_id = '{$tx_id}', detail = '{$memo}' where id = {$wid}");
              $with = mysqli_fetch_assoc(mysqli_query($DB_CONN,"SELECT tx_url from transactions where id = {$wid}"));
              if($method['wi_pm_id'] != 10 && $method['wi_pm_id'] != 11)
                sendmail("withdraw_user_notification", $userinfo['id'], $userinfo, array('amount' => $amount, 'txn_id' => $tx_id, 'account' => $method['name'], 'tx_url' => $with['tx_url'], 'address' => $address));
              call_alert(61, $tx_id);
              withdraw_redirect($wid, $method, $tx_id);
            }
          }
        } else {
            if($withdraw_settings['withtype'] && $userinfo['auto_withdraw'] != 3 && $a && !$delay) {
              if($userinfo['delay'])
                $delay = $userinfo['delay'];
              else
                $delay = $withdraw_settings['delay_instant_withdraw'];
              mysqli_query($DB_CONN, "UPDATE `transactions` set ref_id = '{$delay}' where id = {$wid}");
            }
            withdraw_redirect($wid, $method);
        }
        } else
          call_alert(65); //Some Error. Please try again
        }
      } elseif($post['action'] == 'with_submit') {
        $str .= '<input type=hidden name=amount value="'.$withdraw.'" />';
        $str .= '<input type=hidden name=payment_method_id value="'.$payment_method_id.'" />';
        //var_dump($str);
        $debit = $withdraw-$fee;
        $smarty->assign('system',$method['name']);
        $smarty->assign('payment_method_id',$payment_method_id);
        $smarty->assign('address',$address);
        $smarty->assign('amount',$withdraw);
        $smarty->assign('debit',$debit);
        $smarty->assign('symbol',$method['symbol']);
        $smarty->assign('credit',fromcurrency($debit, $method['symbol']));
        $smarty->assign('fee',$fee);
        $smarty->assign('confirm',true);
      }
    endif;
    }

if(isset($_SESSION['amount']))
  $smarty->assign('amount',$_SESSION['amount']);
unset($_SESSION['amount']);
$token = Csrf::getInputToken('withdraw');
$token .= $str;
$smarty->registerFilter('output','add_hidden');
$smarty->display('user_withdraw.tpl');
  break;
case $main_link['support'] ?: 'support':
    login_redirect();
    assign_title('support');
    if(!$preferences['support']) {
        header("location: $dash_link", true, 301); 
        exit;
    }
    if($userinfo['support'] == '1') {
        header("location: $dash_link", true, 301); 
        exit;
    }

  if (isset($_POST['submit'])) {
      if(!Csrf::verifyToken('support')) {
     header("location: support",  true,  301 );  exit;
    } else
      Csrf::removeToken('support');
     $cc = captcha_check('support');
     if($cc) {
         $subject = $_POST['subject'];
         $message = $_POST['message'];
        mysqli_query($DB_CONN, "INSERT into tickets(subject, user_id) values('{$subject}', '{$userinfo['id']}')");
        $id = mysqli_insert_id($DB_CONN);
        mysqli_query($DB_CONN, "INSERT into ticket_replies(ticket_id, message, type) values('$id', '{$message}', 0)");
        $id = md5($id);
        $status = "Open";
        sendadminmail("admin_ticket_message", $userinfo['id'], $userinfo, array('id' => $id, 'msg' => $message, 'subject' => $_POST['subject'], 'status' => $status));
        call_alert(100);
     }
    }
  $tickets = array();
  $i =0;
  $t = mysqli_query($DB_CONN, "SELECT * from tickets where user_id = '{$userinfo['id']}' order by id desc");
  while($ticket = mysqli_fetch_assoc($t)) {
    $tickets[$i] = $ticket;
    $tickets[$i]['datetime'] = $ticket['datetime'];
    $tickets[$i]['id'] = md5($ticket['id']);
    $i++;
    }
  
  $smarty->assign('tickets',$tickets);
 $smarty->assign('total_tickets', number_format($preferences['total_tickets']));
$smarty->assign('pending_tickets', number_format($preferences['pending_tickets']));
$smarty->assign('closed_tickets', number_format($preferences['closed_tickets']));
$smarty->assign('opened_tickets', number_format($preferences['new_tickets']));
  $token = Csrf::getInputToken('support');
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('user_support.tpl');
  break;
  case $main_link['ticket'] ?  : 'ticket':
  login_redirect();
  assign_title('ticket');
  if(!$user_settings['support']) {
    header("location: $dash_link",  true,  301 );  exit;
  }
  if(isset($_GET['id'])) {
    $id =$_GET['id'];
  }
  if (isset($_POST['submit'])) {
    if(!Csrf::verifyToken('support')) {
     header("location: support",  true,  301 );  exit;
    } else
      Csrf::removeToken('support');
    $cc = captcha_check('support');
    if($cc) {
      $message = $_POST['message'];
      $ticket = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from tickets where md5(id) = '{$id}'"));
      $q = mysqli_query($DB_CONN, "INSERT into ticket_replies(ticket_id, user_id, message, type) values('{$ticket['id']}', '{$userinfo['id']}', '{$message}', 0)");
      $status = "Replied";
     sendadminmail("admin_ticket_reply", $userinfo['id'], $userinfo, array('id' => $id, 'msg' => $message, 'subject' => $ticket['subject'], 'status' => $status));
     call_alert(100); //Ticket Updated Successfully.
   }
  }

  if(isset($_GET['id'])) {
    $t = mysqli_query($DB_CONN, "select *, (select type from ticket_replies where ticket_id = tickets.id order by id desc limit 1) as type from tickets where md5(id) = '{$id}' and user_id = '{$userinfo['id']}'");
    $ticket = mysqli_fetch_assoc($t);
  } 
  $ticket['datetime'] = $ticket['datetime'];
  $ticket['status'] = $ticket['status'];
  $ticket['creator'] = $ticket['type'] ? "Support" : "User";
  $ticket['type'] = $ticket['type'];
  $ticket['id'] = md5($ticket['id']);
  $smarty->assign('ticket',$ticket);
  $replies = array();
  $i = 0;
  $r = mysqli_query($DB_CONN, "select * from ticket_replies where md5(ticket_id) = '{$id}'");
  while($reply = mysqli_fetch_assoc($r)) {
    $replies[$i] = $reply;
    $replies[$i]['message'] = str_replace(array("\\n", "\\r", "\\r\\n"), array("<br>", "<br>", "<br>"), $reply['message']);
    $replies[$i]['type'] = $reply['type'] ? "Support" : "User";
    $i++;
  }
  $smarty->assign('replies',$replies);
  $token = Csrf::getInputToken('support');
  $smarty->registerFilter('output','add_hidden');
  $smarty->display('user_support.ticket.tpl');
  break;
 default:
$page_found = false; 
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($uri, '/'));
if (!$page_found) {
   $page_key = strtolower(trim($uri, '/'));
    if (isset($extra_pages[$page_key])) {
        $template_file = $extra_pages[$page_key]['template'] ?? null;
        if ($template_file && $extra_pages[$page_key]['xx'] == "xx" && count($uri_parts) == 1) {
            $title_key = str_replace('.tpl', '', $page_key);
            assign_title($title_key);
            $smarty->display($template_file);
        } else {
            header("HTTP/1.0 404 Not Found");
            assign_title('404');
            $smarty->display('404.tpl');
        }
    } else if (empty($uri_parts[0])) { 
    $reg = $main_link['register'] ?: 'register'; 
    
    if($referral_settings['redirect']) {
        header("Location: $reg", true, 301);
        exit(); 
    }

    $template_file = 'home.tpl'; 
     assign_title('home');
    $smarty->display($template_file);
} else {
  
        header("HTTP/1.0 404 Not Found");
        assign_title('404');
        $smarty->display('404.tpl');
    }
}
break;
}
}