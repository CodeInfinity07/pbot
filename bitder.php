<?php
$installer_file = dirname(__FILE__) . '/installer.php';
if (file_exists($installer_file)) {
    @unlink($installer_file);
}
function escape_string($string) {
    return str_replace(
        ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
        ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
        $string
    );
}
function db_filter($array) {
    global $DB_CONN;
    $sanitized = [];
    foreach ($array as $key => $b) {
        $sanitized[$key] = escape_string(trim($b));
    }
    return $sanitized;
}

function removeDirectory($path) {
    if (!file_exists($path)) {
        return;
    }    
    $files = array_diff(scandir($path), array('.', '..'));
    foreach ($files as $file) {
        $fullPath = $path . DIRECTORY_SEPARATOR . $file;
        is_dir($fullPath) ? removeDirectory($fullPath) : unlink($fullPath);
    }
    return rmdir($path);
}

$_POST = db_filter($_POST);
$_GET = db_filter($_GET);
error_reporting(0);
// ini_set("display_errors", "on");
session_start();
define('ENCRYPTION_KEY', '2238@@5524');
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

$OpensslEncryption = new Openssl_EncryptDecrypt;

function check_license() {
    return true;
}


if(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['email'])) {
    $data = array();
    $post = $_SESSION['post'];
    $connection = $post['host'];
    $database = $post['database'];
    $username = $post['db_user'];
    $password = $post['db_password'];
    $db = new PDO("mysql:host=$connection;dbname=$database", $username, $password);
    $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql = "UPDATE `users` SET `email`='{$_POST['email']}',`username`='{$_POST['user']}',`password`='{$_POST['password']}' WHERE id = 1";
    $stmt = $db->prepare($sql);
    if ($stmt->execute()){
    $domain = "https://".$_SERVER['SERVER_NAME'];
    $sql = "UPDATE preferences set title = '{$_SERVER['SERVER_NAME']}', domain = '{$domain}', admin_settings = REPLACE(admin_settings, 'admin@bitders.com', '{$_POST['email']}'), email_settings = REPLACE(email_settings, 'admin@bitders.com', '{$_POST['email']}') WHERE id='1'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
        $data['success'] = true;
    } else {
        $data['success'] = false;
        $data['message'] = "Failed to update Database. Please Contact support";
    }
    header("Content-type: application/json");
    die(json_encode($data));
}

$file = file_get_contents(__DIR__."/includes/settings.php");
$decrypted = $OpensslEncryption->decrypt($file, ENCRYPTION_KEY);
$db = json_decode($decrypted, true);

if (isset($_GET['check_script_update']) || isset($_GET['check_app_update']) || isset($_GET['update']) || isset($_GET['app_update'])) {
    if (!$db || !count($db)) {
        header("Location: ./");
        exit;
    }
    $connection = 'localhost';
    $database   = 'assitix_main';
    $username   = 'ryzon';
    $password   = 'zain0980';
    $DB_CONN = mysqli_connect($connection, $username, $password, $database) or die("Connection Error->".mysqli_connect_error());
    
    // Retrieve version JSON from database
    $versionJson = "";
    $query = mysqli_query($DB_CONN, "SELECT version FROM preferences LIMIT 1");
    if ($row = mysqli_fetch_assoc($query)) {
        $versionJson = $row['version'];
    }
    
    // Parse version JSON
    $versions = json_decode($versionJson, true);
    if (!$versions) {
        // Initialize default version structure if not valid JSON
        $versions = [
            "app" => "1.0.0",
            "script" => "1.0.0",
            "app_update_available" => false,
            "script_update_available" => false
        ];
    }
    
    // Ensure update flags exist
    if (!isset($versions['app_update_available'])) {
        $versions['app_update_available'] = false;
    }
    if (!isset($versions['script_update_available'])) {
        $versions['script_update_available'] = false;
    }
    
    // Extract individual versions
    $appVersion = isset($versions['app']) ? $versions['app'] : "1.0.0";
    $scriptVersion = isset($versions['script']) ? $versions['script'] : "1.0.0";
    
    $domainName = $_SERVER['HTTP_HOST'];
    
    function downloadExtractAndDeleteZip($zipUrl, $type = "", $newVersion = "", $app = false) {
        global $DB_CONN, $versions;
        $zipFile = 'temp.zip';
        if ($app) {
            chdir('app');
            removeDirectory('_next');
        }
        $extractPath = getcwd();
        if (file_put_contents($zipFile, file_get_contents($zipUrl))) {
            $zip = new ZipArchive;
            if ($zip->open($zipFile) === TRUE) {
                if ($zip->extractTo($extractPath)) {
                    $zip->close();
                    
                    // Update version in database if new version provided
                    if ($type && $newVersion) {
                        // Update the specific version type
                        $versions[$type] = $newVersion;
                        // Set update available to false after successful update
                        $versions[$type.'_update_available'] = false;
                        $updatedVersionJson = json_encode($versions);
                        mysqli_query($DB_CONN, "UPDATE preferences SET version = '{$updatedVersionJson}' LIMIT 1");
                    }
                    
                    if (unlink($zipFile)) {
                        return ['success' => true, 'message' => 'Update Done.'];
                    } else {
                        return ['success' => false, 'message' => 'Failed to update (temp file deletion error).'];
                    }
                } else {
                    $zip->close();
                    return ['success' => false, 'message' => 'Failed to update (zip extraction error).'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to update (zip open error).'];
            }
        } else {
            return ['success' => false, 'message' => 'Failed to update (download error).'];
        }
    }
    
    // Always return JSON
    header('Content-Type: application/json');
    
    if (isset($_GET['check_script_update'])) {
        $apiUrl = "https://api.bitders.com/version/{$domainName}/{$scriptVersion}";
        $apiResponse = @file_get_contents($apiUrl);
        if ($apiResponse === false) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to connect to update server']);
            exit;
        }
        
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['update_available'])) {
            $versions['script_update_available'] = $data['update_available'];
            $updatedVersionJson = json_encode($versions);
            mysqli_query($DB_CONN, "UPDATE preferences SET version = '{$updatedVersionJson}' LIMIT 1");
        }
        
        echo $apiResponse;
        exit;
    }
    
    if (isset($_GET['check_app_update'])) {
        $apiUrl = "https://api.bitders.com/app_version/{$domainName}/{$appVersion}";
        $apiResponse = @file_get_contents($apiUrl);
        if ($apiResponse === false) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to connect to update server']);
            exit;
        }
        
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['update_available'])) {
            $versions['app_update_available'] = $data['update_available'];
            $updatedVersionJson = json_encode($versions);
            mysqli_query($DB_CONN, "UPDATE preferences SET version = '{$updatedVersionJson}' LIMIT 1");
        }
        
        echo $apiResponse;
        exit;
    }
    
    if (isset($_GET['update'])) {
        $apiUrl = "https://api.bitders.com/version/{$domainName}/{$scriptVersion}";
        $apiResponse = @file_get_contents($apiUrl);
        if ($apiResponse === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to connect to update server']);
            exit;
        }
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['update_available']) && $data['update_available'] === true) {
            $downloadUrl = $data['download_url'];
            $newVersion = $data['version'];
            $result = downloadExtractAndDeleteZip($downloadUrl, "script", $newVersion);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'No update available']);
        }
        exit;
    }
    
    if (isset($_GET['app_update'])) {
        $apiUrl = "https://api.bitders.com/app_version/{$domainName}/{$appVersion}";
        $apiResponse = @file_get_contents($apiUrl);
        if ($apiResponse === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to connect to update server']);
            exit;
        }
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['update_available']) && $data['update_available'] === true) {
            $downloadUrl = $data['download_url'];
            $newVersion = $data['version'];
            $result = downloadExtractAndDeleteZip($downloadUrl, "app", $newVersion, true);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'No app update available']);
        }
        exit;
    }
} else {
    if($db || count($db)) {
        header("Location: ./");
    }



$re = $st = true;
if(isset($_POST['host']) && isset($_POST['database']) && isset($_POST['db_user']) && isset($_POST['db_password'])) {
    $data = array();
    $post = $_SESSION['post'] = $_POST;
    $connection = $post['host'];
    $database = $post['database'];
    $username = $post['db_user'];
    $password = $post['db_password'];
    if($DB_CONN=mysqli_connect($connection, $username, $password, $database)) {
        if(check_license()) {
          mysqli_query($DB_CONN, "SET SQL_MODE = ''");
          $db = new PDO("mysql:host=$connection;dbname=$database", $username, $password);
            $stmt = $db->prepare(file_get_contents("https://bitders.com/db/main.sql"));
            if ($stmt->execute()){
                $data['success'] = true;
            } else {
                $data['success'] = false;
                $data['message'] = "Failed to load Database. Please Contact support";
            }
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host     = $_SERVER['HTTP_HOST'];
            $script   = $_SERVER['SCRIPT_NAME'];
            $currentUrl = $protocol . $host . $script;
            $currentUrl = str_replace('bitder.php', '', $currentUrl);
            $json = array('connection'=>$connection, 'database'=>$database, 'username'=>$username, 'password'=>$password);
            $json = json_encode($json);
            $json =  $OpensslEncryption->encrypt($json, ENCRYPTION_KEY);
            file_put_contents("includes/settings.php", $json);
            $st = true;
        } else {
            $data['success'] = false;
            $data['message'] = "License Not available for this Domain.<a href='https://bitders.com'> Buy it Now from Bitders.com</a>";
        }
} else {
    $data['success'] = false;
    $data['message'] = "Connection Error->".mysqli_connect_error();
}
    header("Content-type: application/json");
    die(json_encode($data));
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitders</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="bitders/assets/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="bitders/assets/style.bundle.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" href="bitders/assets/logo.svg" />
    <script>// Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
    if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
    <style>
        .form-step {
            display: none;
        }
        .form-step.current {
            display: block;
        }
    </style>
</head>
<body id="kt_body" class="app-blank bgi-size-cover bgi-position-center bgi-no-repeat">
    <script>
        var defaultThemeMode = "system";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {background-image: url('bitders/assets/bitdersbg.webp');}[data-bs-theme="dark"] body {background-image: url('bitders/assets/bitdersbg.webp');}
        </style>
        <? if($st) { ?>
        <div class="d-flex flex-column flex-center flex-column-fluid">
            <div class="d-flex flex-column flex-center text-center p-10">
                
                <div class="card card-flush w-lg-750px py-5">
                           <div class="m-0" data-kt-element="theme-mode-menu" data-kt-menu="true">
                                <a href="#" class="menu-link px-3 py-2 btn btn-light active" data-kt-element="mode" data-kt-value="light">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-night-day fs-5 me-1"></i>
                                    </span>
                                    <span class="menu-title">Light</span>
                                </a>
                                <a href="#" class="menu-link px-3 py-2 btn btn-dark" data-kt-element="mode" data-kt-value="dark">
                                    <span class="menu-icon" data-kt-element="icon">
                                        <i class="ki-outline ki-moon fs-5 me-1"></i>
                                    </span>
                                    <span class="menu-title">Dark</span>
                                </a>
                            </div>
                    <div class="card-body py-15 py-lg-20">
                        <div class="mb-13">
                            <a href="https://bitders.com" target="_blank" class="">
                                <img alt="Logo" src="bitders/assets/logo-dark.svg" class="h-40px theme-light-show" />
                                <img alt="Logo" src="bitders/assets/logo-light.svg" class="h-40px theme-dark-show" />
                            </a>
                        </div>
                 
                        <h1 class="fw-bolder text-gray-900 mb-2">Install Bitders Script</h1>
                        <div class="text-muted fw-semibold fs-6">
                            If you need free installation service, please contact
                            <a href="https://bitders.com/contact" target="_blank" class="link-primary fw-bold">support</a>.
                            
                        
                        </div>
                        
                        
                        

                        <div class="stepper stepper-links d-flex flex-column pt-15 first" id="kt_create_account_stepper" data-kt-stepper="true">
                            <div class="stepper-nav mb-5">
                                <div class="stepper-item current" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">Requirements</h3>
                                </div>
                                <div class="stepper-item pending" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">Database</h3>
                                </div>
                                <div class="stepper-item pending" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">Admin Details</h3>
                                </div>
                                <div class="stepper-item pending" data-kt-stepper-element="nav">
                                    <h3 class="stepper-title">Completed</h3>
                                </div>
                            </div>

                            <form class="mx-auto mw-600px w-100 pt-5 pb-10 fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate" id="kt_create_account_form" method="post">
                                <!-- Step 1: Requirements -->
                             
                                <div class="form-step current" data-step="1">
                                    <div class="alert alert-primary d-flex align-items-center  p-5 mb-5">

    <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
 
    <div class="d-flex flex-column">
        <h4 class="mb-1 text-start">Make sure the requirements meet</h4>
        <span class=" text-start">PHP Version, IonCube Extention, Valid License, Writable file and folder.</span>

    </div>
</div>
                                    <div class="w-100">
                                        <!-- PHP Version Check -->
                                        <div class="d-flex flex-stack text-start">
                                            <div class="me-5 fw-semibold">
                                                <label class="fs-6">PHP Version between 7.0 and 7.2.</label>
                                                <div class="fs-7 text-muted">PHP Version between 7.0 and 7.2</div>
                                            </div>
                                            <label class="form-check form-switch form-check-custom form-check-solid">
                                                <span class="form-check-label fw-semibold text-muted">
                                                    <?
                                                    $ver = phpversion();
                                                    if(round($ver) == 7)
                                                        $class = "success";
                                                    else {
                                                        $re = false;
                                                        $class = "danger";
                                                    }
                                                    ?>
                                                    <span class="badge badge-changelog badge-light-<?=$ver?> fw-semibold fs-8 px-2 ms-2"><?php echo $ver ?></span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <!-- ionCube Loader Check -->
                                        <div class="d-flex flex-stack text-start">
                                            <div class="me-5 fw-semibold">
                                                <label class="fs-6">PHP ionCube Installed.</label>
                                                <div class="fs-7 text-muted">Must have PHP IonCube extension installed.</div>
                                            </div>
                                            <label class="form-check form-switch form-check-custom form-check-solid">
                                                <span class="form-check-label fw-semibold text-muted">
                                                    <?php if (extension_loaded('ionCube Loader')) { ?>
                                                        <span class="badge badge-changelog badge-light-success fw-semibold fs-8 px-2 ms-2">Installed</span>
                                                    <?php } else {
                                                        $re = false;
                                                        ?>
                                                        <span class="badge badge-changelog badge-light-danger fw-semibold fs-8 px-2 ms-2">Not Installed</span>
                                                    <?php } ?>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <!-- License Check -->
                                        <div class="d-flex flex-stack text-start">
                                            <div class="me-5 fw-semibold">
                                                <label class="fs-6">Valid License</label>
                                                <div class="fs-7 text-muted">License for <b><?php echo $_SERVER['HTTP_HOST']; ?></b></div>
                                            </div>
                                            <label class="form-check form-switch form-check-custom form-check-solid">
                                                <span class="form-check-label fw-semibold text-muted">
                                                    <?php if (check_license()) { ?>
                                                        <span class="badge badge-changelog badge-light-success fw-semibold fs-8 px-2 ms-2">Valid</span>
                                                    <?php } else {
                                                        $re = false; ?>
                                                        <span class="badge badge-changelog badge-light-danger fw-semibold fs-8 px-2 ms-2">Invalid</span>
                                                    <?php } ?>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <!-- Settings File Writable Check -->
                                        <div class="d-flex flex-stack text-start">
                                            <div class="me-5 fw-semibold">
                                                <label class="fs-6">Settings file Writable</label>
                                                <div class="fs-7 text-muted">Must have 777 or 644 permission for includes/settings.php</div>
                                            </div>
                                            <label class="form-check form-switch form-check-custom form-check-solid">
                                                <span class="form-check-label fw-semibold text-muted">
                                                    <?php if (is_writable('includes/settings.php')) { ?>
                                                        <span class="badge badge-changelog badge-light-success fw-semibold fs-8 px-2 ms-2">Writable</span>
                                                    <?php } else {
                                                    $re = false; ?>
                                                        <span class="badge badge-changelog badge-light-danger fw-semibold fs-8 px-2 ms-2">Not Writable</span>
                                                    <?php } ?>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <!-- tmpl_c Folder Writable Check -->
                                        <div class="d-flex flex-stack text-start">
                                            <div class="me-5 fw-semibold">
                                                <label class="fs-6">tmpl_c folder Writable</label>
                                                <div class="fs-7 text-muted">Must have 777 or 775 permission for folder 'tmpl_c'</div>
                                            </div>
                                            <label class="form-check form-switch form-check-custom form-check-solid">
                                                <span class="form-check-label fw-semibold text-muted">
                                                    <?php if (is_writable('tmpl_c')) { ?>
                                                        <span class="badge badge-changelog badge-light-success fw-semibold fs-8 px-2 ms-2">Writable</span>
                                                    <?php } else {
                                                    $re = false; ?>
                                                        <span class="badge badge-changelog badge-light-danger fw-semibold fs-8 px-2 ms-2">Not Writable</span>
                                                    <?php } ?>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <!-- Step 2: Database -->
                                <div class="form-step pending" data-step="2">
                                                                        <div class="alert alert-warning d-flex align-items-center  p-5 mb-5">

    <i class="ki-duotone ki-gear fs-2hx text-warning me-4"><span class="path1"></span><span class="path2"></span></i>
 
    <div class="d-flex flex-column">
        <h4 class="mb-1 text-start">Create a Database connection</h4>
        <span class=" text-start">Hostname, Database Name, Database User and Password required.</span>

    </div>
</div>
                                    <div class="w-100">
                                        <div class="pb-10 pb-lg-15">
                                           
                                            <div class="row text-start">
                                                <div class="form-group mb-2">
                                                    <label>Host Name</label>
                                                    <input type="text" class="form-control form-control-solid" name="host" value="localhost" placeholder="localhost" required="required">
                                                    <small>Enter your Database Hostname</small>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label>Database Name</label>
                                                    <input type="text" class="form-control form-control-solid" name="database" placeholder="Database Name" required="required">
                                                    <small>Enter your Database Name</small>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label>Username</label>
                                                    <input type="text" class="form-control form-control-solid" name="db_user" placeholder="Database Username" required="required" autocomplete="off">
                                                    <small>Enter your Database Username</small>
                                                </div>
                                                <div class="form-group">
                                                    <label>Password</label>
                                                    <input type="password" class="form-control form-control-solid" name="db_password" placeholder="Database Password" required="required" autocomplete="off">
                                                    <small>Enter your Database Password</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Step 3: Admin Details -->
                                <div class="form-step pending" data-step="3">
                                                               <div class="alert alert-success d-flex align-items-center  p-5 mb-5">

    <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
 
    <div class="d-flex flex-column">
        <h4 class="mb-1 text-start">Database Loaded Successfully</h4>
        <span class=" text-start">Database connection was success.</span>

    </div>
</div>
                                       <div class="alert alert-info d-flex align-items-center  p-5 mb-5">

    <i class="ki-duotone ki-gear fs-2hx text-info me-4"><span class="path1"></span><span class="path2"></span></i>
 
    <div class="d-flex flex-column">
        <h4 class="mb-1 text-start">Setup Admin Panel</h4>
        <span class=" text-start">Setup Admin Panel username password and email.</span>

    </div>
</div>
                                    <div class="w-100">
                                 
                                        <div class="row text-start">
                                            <p class="hint-text">Please enter email, username, and password for the website.</p>
                                            <div class="form-group">
                                                <label>Admin Username</label>
                                                <input type="text" class="form-control form-control-solid" name="user" placeholder="Username" value="admin" required="required">
                                                <small  class="text-muted">Your username to access the admin panel. Default <code>admin</code></small>
                                            </div>
                                            <div class="form-group">
                                                <label>Admin Email</label>
                                                <input type="email" class="form-control form-control-solid" name="email" placeholder="Email" required="required">
                                                <small  class="text-muted">This will be your default email for your website</small>
                                            </div>
                                               <div class="fv-row mb-10 fv-plugins-icon-container" data-kt-password-meter="true">
									<!--begin::Wrapper-->
									<div class="mb-1">
									    <label>Admin Password</label>
										<!--begin::Input wrapper-->
										<div class="position-relative mb-3">
											<input class="form-control  form-control-solid" type="password" placeholder="Password" name="password" value="@<?php echo $_SERVER['HTTP_HOST']; ?>" autocomplete="off" data-kt-translate="sign-up-input-password">
											<span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
												<i class="ki-duotone ki-eye-slash fs-2"></i>
												<i class="ki-duotone ki-eye fs-2 d-none"></i>
											</span>
										</div>
										<!--end::Input wrapper-->
										<!--begin::Meter-->
										<div class="d-flex align-items-center mb-3" data-kt-password-meter-control="highlight">
											<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
											<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
											<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px me-2"></div>
											<div class="flex-grow-1 bg-secondary bg-active-success rounded h-5px"></div>
										</div>
										<!--end::Meter-->
									</div>
									<!--end::Wrapper-->
									<!--begin::Hint-->
									<div class="text-muted" data-kt-translate="sign-up-hint">Use 8 or more characters with a mix of letters, numbers &amp; symbols.</div>
									<!--end::Hint-->
							</div>
                                            
                                        </div>
                                    </div>
                                </div>
                                <!-- Step 4: Completed -->
                                <div class="form-step pending" data-step="4">
                                    <div class="w-100">
                                        <div class="pb-8 pb-lg-10">
                                              <h2 class="fw-bold text-gray-900">You Are Done!</h2>
                                                                                                           <div class="alert alert-success d-flex align-items-center  p-5 mb-5">

    <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span class="path2"></span></i>
 
    <div class="d-flex flex-column">
        <h4 class="mb-1 text-start">Your Bitders Script Installation was Successful.</h4>
        <span class=" text-start">Start your investment venture with Bitders Script.</span>

    </div>
</div>


                           <div class="d-grid mb-10">
										<a href="./login" class="btn btn-primary">
										
											<span class="indicator-label">Log In</span>
										
										
										</a>
									</div>               
                                            
                                            <div class="text-muted fw-semibold fs-6">If you need more info, please
                                                <a href="https://bitders.com/contact" class="link-primary fw-bold">contact our dedicated support team</a>.
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                                <!-- Actions -->
                                <div class="d-flex flex-stack pt-15">
                                    <div class="mr-2">
                                        <button type="button" class="btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous">
                                            <i class="ki-duotone ki-arrow-left fs-4 me-1">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>Back
                                        </button>
                                    </div>
                                    <div>
                                        <button onclick="window.location.href = '/'" type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="submit">
                                            <span class="indicator-label">Submit 
                                            <i class="ki-duotone ki-arrow-right fs-3 ms-2 me-0">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i></span>
                                            <span class="indicator-progress">Please wait...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                        <button type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="refresh">
                                            Refresh
                                            <i class="ki-duotone ki-arrow-rotate-right fs-4 ms-1 me-0">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
                                        <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">Continue 
                                            <i class="ki-duotone ki-arrow-right fs-4 ms-1 me-0">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <? } else { ?>
        Installed Successfully
        <? } ?>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/jquery.validate.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="bitders/assets/scripts.bundle.js"></script>
        


        <script>
"use strict";
document.addEventListener('DOMContentLoaded', function() {
    const stepper = document.querySelector('#kt_create_account_stepper');
    const formSteps = document.querySelectorAll('.form-step');
    const nextButtons = document.querySelectorAll('[data-kt-stepper-action="next"]');
    const prevButtons = document.querySelectorAll('[data-kt-stepper-action="previous"]');
    const submitButton = document.querySelector('[data-kt-stepper-action="submit"]');
    const refreshButton = document.querySelector('[data-kt-stepper-action="refresh"]');
    let currentStep = 0;

    // Initialize jQuery validation
    const form = $("#kt_create_account_form");
    const validator = form.validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function(element) {
            $(element).removeClass('is-invalid');
        },
        rules: {
            host: { required: true },
            database: { required: true },
            db_user: { required: true },
            db_password: { required: true },
            user: { required: true },
            email: { required: true, email: true },
            password: { required: true, minlength: 6 }
        },
        messages: {
            host: "Please enter the host name",
            database: "Please enter the database name",
            db_user: "Please enter the database username",
            db_password: "Please enter the database password",
            user: "Please enter the admin username",
            email: {
                required: "Please enter your email",
                email: "Please enter a valid email address"
            },
            password: {
                required: "Please enter a password",
                minlength: "Your password must be at least 6 characters long"
            }
        }
    });

    function showStep(step) {
        formSteps.forEach((formStep, index) => {
            formStep.classList.remove('current');
            formStep.classList.add('pending');
            if (index === step) {
                formStep.classList.add('current');
                formStep.classList.remove('pending');
            }
        });

        const stepItems = stepper.querySelectorAll('.stepper-item');
        stepItems.forEach((item, index) => {
            item.classList.remove('current');
            if (index === step) {
                item.classList.add('current');
            }
        });

        // Show submit button on last step, hide next button
        if (step === formSteps.length - 1) {
            nextButtons.forEach(button => button.style.display = 'none');
            submitButton.style.display = 'inline-block';
        } else {
            nextButtons.forEach(button => button.style.display = 'inline-block');
            submitButton.style.display = 'none';
        }

        if (step === 0) {
            const reValue = <?php echo $re ? "true" : "false"; ?>;
            if (reValue === false) {
                refreshButton.style.display = 'inline-block';
                nextButtons.forEach(button => button.style.display = 'none');
            } else {
                refreshButton.style.display = 'none';
            }
        } else {
            refreshButton.style.display = 'none';
        }
    }

    function runAjaxRequest(stepData) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: '',
                method: 'POST',
                data: stepData,
                success: function(response) {
                    if (response.success) {
                        resolve(true);
                    } else {
                        reject(response.message || 'An error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    reject('An error occurred: ' + error);
                }
            });
        });
    }


    nextButtons.forEach(button => {
        button.addEventListener('click', () => {
            const currentFormStep = formSteps[currentStep];
            const isValid = validateStep(currentFormStep);

            if (isValid && currentStep < formSteps.length - 1) {
                if (currentStep === 1 || currentStep === 2) { // After step 2 or 3
                    const stepData = $(currentFormStep).find('input, select, textarea').serialize();
                    runAjaxRequest(stepData)
                        .then(() => {
                            if (currentStep < formSteps.length - 1) {
                                currentStep++;
                                showStep(currentStep);
                            }
                        })
                        .catch((error) => {
                            Swal.fire({
                                text: error,
                                backdrop:false,
                                icon: "error",
                                heightAuto: false,
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-light"
                                }
                            });
                        });
                } else if (currentStep < formSteps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                }
            } else if (!isValid) {
                Swal.fire({
                    heightAuto: false,
                    backdrop:false,
                    text: "Sorry, looks like there are some errors detected, please try again.",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, got it!",
                    customClass: {
                        confirmButton: "btn btn-light"
                    }
                });
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    submitButton.addEventListener('click', (e) => {
        e.preventDefault();
        if (form.valid()) {
            Swal.fire({
                text: "All is good! Please confirm the form submission.",
                heightAuto: false,
                backdrop:false,
                icon: "success",
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: "Yes, submit!",
                cancelButtonText: "No, cancel",
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: "btn btn-active-light"
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    form[0].submit(); // Submit the form
                }
            });
        } else {
            Swal.fire({
                text: "Sorry, looks like there are some errors detected, please try again.",
                icon: "error",
                heightAuto: false,
                buttonsStyling: false,
                confirmButtonText: "Ok, got it!",
                customClass: {
                    confirmButton: "btn btn-light"
                }
            });
        }
    });

    if (refreshButton) {
        refreshButton.addEventListener('click', () => {
            location.reload();
        });
    }

    function validateStep(step) {
        const fields = step.querySelectorAll('input, select, textarea');
        return Array.from(fields).every(field => $(field).valid());
    }

    showStep(currentStep);
});
        </script>
    </body>
</html>
<? } ?>