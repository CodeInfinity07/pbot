<?php
include_once('includes/connection.php');
$allowed_origins = [];
if (isset($site_settings['webapp_url']) && !empty($site_settings['webapp_url'])) {
    if (is_string($site_settings['webapp_url'])) {
        $urls = array_map('trim', explode(',', $site_settings['webapp_url']));
        $allowed_origins = array_merge($allowed_origins, $urls);
    } 
    else if (is_array($site_settings['webapp_url'])) {
        $allowed_origins = array_merge($allowed_origins, $site_settings['webapp_url']);
    }
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, token, X-User-Timezone");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if(isset($_SERVER['HTTP_X_USER_TIMEZONE']) && $_SERVER['HTTP_X_USER_TIMEZONE']) {
    $tz = $_SERVER['HTTP_X_USER_TIMEZONE'];
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
$timezone = $_SERVER['HTTP_X_USER_TIMEZONE'] ?? 'Not Set';
$token = $_SERVER['HTTP_TOKEN'] ?? 'Not Set';
$logEntry = "********************************\n" .
    "Date: " . $dt . "\n" .
    "IP: " . $ip . "\n" .
    "Timezone: " . $timezone . "\n" .
    "Token: " . $token . "\n" .
    "POST Data:\n" . print_r($_POST, true) . "\n" .
    "Raw Input:\n" . print_r(json_decode(file_get_contents('php://input'), true), true);

// Write all information in a single operation
file_put_contents("api.txt", $logEntry, FILE_APPEND);

$is_api = true;
$PackagesData= [];

foreach ($packages as $package) {
    $planData = [
        'id' => $package['id'],
        'name' => $package['name'],
        'etype' => $package['etype'],
        'frequency' => $package['frequency'],
        'duration' => $package['duration'],
        'earnings_days' => $package['days'],
        'principal_return' => $package['principal'],
        'features' => [
            'reinvest' => $package['reinvest'],
            'allowprincipal' => $package['allowprincipal'],
            'cashback' => [
                'amount' => $package['cashback_bonus_amount'],
                'percentage' => $package['cashback_bonus_percentage']
            ],
            
        ],
        'principal_details' => [
    'principal_return' => $package['principal_return'],
            'principal_hold' => $package['principal_hold']
        ],
        'methods' => [
            'accept_processings' => $package['accept_processings'] ?? '0',
            'accept_account_balance' => $package['accept_account_balance'] ?? '0'
        ],
        'referral' => [
            'percentage' => $package['referral'],
            'compound' => $package['referral_compound']
        ],
        'limit_currency' => $package['limit_currency'] ?? false,
        'description' => $package['description'],
        'status' => $package['status'],
        'plans' => []
    ];
    
    // Add compound details if enabled
    if (isset($package['compound_enable']) && $package['compound_enable'] === "true") {
        $planData['features']['compound'] = [
            'compound_end' => $package['compound_end'] === "true" ? "Yes" : "No",
            'compound_min' => $package['compound_min'],
            'compound_max' => $package['compound_max'],
            'compound_percent_min' => $package['compound_percent_min'],
            'compound_percent_max' => $package['compound_percent_max']
        ];
    } elseif (isset($package['compound'])) {
        $planData['features']['compound'] = $package['compound'];
    }
    
    // Format plans
    if (!empty($package['plans'])) {
        foreach ($package['plans'] as $plan) {
            $planOption = [
                 'name' => $plan['name'],
                'min_deposit' => $plan['minimum_deposit'],
                'max_deposit' => $plan['maximum_deposit']
            ];
            // Add percentage based on etype
            if ($package['etype'] == 0) {
                $planOption['percent'] = $plan['percentage'];
            } else {
                $planOption['percent_min'] = $plan['percentage_min'];
                $planOption['percent_max'] = $plan['percentage_max'];
            }
            $planData['plans'][] = $planOption;
        }
    }
    $PackagesData[] = $planData;
}


 $affiliateData = [
    'system' => $referral_settings['system'],
    'tiers' => []
];
// Restructure the tier data
foreach ($referral_settings['tier'] as $tierId => $tier) {
    $affiliateData['tiers'][] = [
        'id' => $tierId,
        'name' => $tier['name'],
        'levels' => $tier['level']
    ];
}
function getUserProfileData($userinfo, $includeToken = false, $jwt = '') {
    global $DB_CONN, $kyc_settings, $user_settings, $telegram_settings, $site_settings, $withdraw_settings, $data;
     $data['status'] = true;
    if ($includeToken && $jwt) {
        $data['token'] = $jwt;
    }
    $data['temp_id'] = sha1($userinfo['id']);
     $kycFields = [];
 
    if ($kyc_settings['enable'] === 'true') {
        $kycFields = [
            'personal' => [
                'name' => [
                    'required' => $kyc_settings['name'] === 'true',
                    'type' => 'text'
                ],
                'phone' => [
                    'required' => $kyc_settings['phone'] === 'true',
                    'type' => 'tel'
                ],
                'country' => [
                    'required' => $kyc_settings['country'] === 'true',
                    'type' => 'select'
                ]
            ],
            'address' => [
                'required' => $kyc_settings['address'] === 'true',
                'type' => 'text',
                'proof' => [
                    'required' => $kyc_settings['bill'] === 'true',
                    'type' => 'file',
                    'acceptedTypes' => ['image/jpeg', 'image/png', 'application/pdf']
                ]
            ],
            'documents' => [
                'identity' => [
                    'types' => [
                        'license' => [
                            'enabled' => $kyc_settings['document']['license'] === 'true',
                            'front' => $kyc_settings['front'] === 'true',
                            'back' => $kyc_settings['back'] === 'true'
                        ],
                        'idcard' => [
                            'enabled' => $kyc_settings['document']['idcard'] === 'true',
                            'front' => $kyc_settings['front'] === 'true',
                            'back' => $kyc_settings['back'] === 'true'
                        ],
                        'passport' => [
                            'enabled' => $kyc_settings['document']['passport'] === 'true',
                            'front' => $kyc_settings['front'] === 'true'
                        ]
                    ],
                    'selfie' => [
                        'required' => $kyc_settings['selfie'] === 'true',
                        'type' => 'file',
                        'acceptedTypes' => ['image/jpeg', 'image/png']
                    ]
                ]
            ]
        ];
    }
   $dailyProfit = calculateDailyInvestmentProfit($userinfo['id']);
$taskData = handleTasks('get_data');
       $totaltaps = $user_settings['default_taps']+$taskData['total_taps'];
           $secret = TokenAuth6238::generateRandomClue();
    $data = array_merge($data, [
        
        'profile' => $userinfo['profile'],
        'kyc' => [
            'enabled' => $kyc_settings['enable'] === 'true',
            'fields' => $kycFields
        ],
        'wallets' => $userinfo['pm_wallets'],
       'accountBalance' => [
            'balance' => fiat($userinfo['accountbalance']),
            'faucetBalance' => fiat($userinfo['faucetbalance'])
        ],
        'tasks' => $taskData['tasks'],
        'taps' => $totaltaps,
        'secret' => $secret,
        'earnings' => [
            'total' => fiat($userinfo['earnings']),
            'today' => fiat($userinfo['earnings_today']),
            'bonuses' => fiat($userinfo['bonuses_total'] ?? 0),
            'faucets' => [
                'total' => fiat($userinfo['faucets_total'] ?? 0),
                'today' => fiat($userinfo['faucets_today'])
            ]
        ],
        'investmentStats' => [
            'total' => fiat($userinfo['investments_total'] ?? 0),
            'active' => fiat($userinfo['investments_active']),
            'returned' => fiat($userinfo['investment_returned_total'] ?? 0),
            'released' => fiat($userinfo['investments_release_total'] ?? 0)
        ],
        'withdrawalStats' => [
            'total' => fiat($userinfo['withdrawals_total'] ?? 0),
            'pending' => fiat($userinfo['withdrawals_pending'])
        ],
        'referralStats' => [
            'count' => $userinfo['affiliates'],
            'totalEarnings' => fiat($userinfo['affiliates_total'] ?? 0),
            'todayEarnings' => fiat($userinfo['affiliates_today'] ?? 0),
            'totalInvestments' => fiat($userinfo['affiliates_investment'])
        ],
        'transferStats' => [
            'total' => fiat($userinfo['transfers_total'] ?? 0)
        ],
        'exchangeStats' => [
            'total' => fiat($userinfo['exchanges_total'] ?? 0)
        ],
        'TradingStats' => [
            'activeTrades' => $userinfo['activeTrades'] ?? 0,
            'totalTrades' => $userinfo['totalTrades'] ?? 0,
            'totalPnl' => fiat($userinfo['totalPnl'] ?? 0),
            'todayPnl' => fiat($userinfo['todayPnl'] ?? 0),
            'todayPnlDash' => fiat($userinfo['todayPnl'] ?? 0), //this one is new
        ],
         'profits_balance' => [
        'hourly' => fiat($dailyProfit / 24),
        'daily' => fiat($dailyProfit),
        'weekly' => fiat($dailyProfit * 7),
        'monthly' => fiat($dailyProfit * 30),
        'annual' => fiat($dailyProfit * 365)
    ],
    'profits_faucet' => [
        'hourly' => fiat(($dailyProfit * 2) / 24),
        'daily' => fiat($dailyProfit * 2),
        'weekly' => fiat(($dailyProfit * 2) * 7),
        'monthly' => fiat(($dailyProfit * 2) * 30),
        'annual' => fiat(($dailyProfit * 2) * 365)
    ],
        'rewards' => [
           'availableTaps' => ($totaltaps ?? 0) - ($userinfo['available_taps'] ?? 0),
           
        ],
            'dailyBonus' => [
                'streak' => [
                    'currentStreak' => $streakData['next_day'],
                    'completedDays' => array_map(function($completion) {
                        return [
                            'day' => $completion['day'],
                            'date' => date('Y-m-d', strtotime($completion['date'])),
                            'formattedDate' => date('M j, Y', strtotime($completion['date']))
                        ];
                    }, $streakData['completed_days'])
                ]
            ],
        'settings' => [
            'canChangeWallet' => $user_settings['can_change_wallet_acc'] ?? false,
            'canChangeEmail' => $user_settings['can_change_email'] ?? false,
            'withDrawalSettings' => [
                'address' => $withdraw_settings['address'] ?? false,
                'instantWithdraw2FA' => $withdraw_settings['instantwithdraw_2fa'] ?? false,
                'instantWithdrawWeekdays' => $withdraw_settings['instantwithdraw_weekdays'] ?? false,
                'multipleAccount' => $withdraw_settings['multiple_account'] ?? false
            ]
        ]
    ]);
}



// Initialize basic variables
$url = $siteURL;
$data = array();

// Handle preflight requests first
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Main request handling
try {
    $auth = new APIAuth($DB_CONN, $security_settings);
    
    // Get request data
    $requestData = json_decode(file_get_contents('php://input'), true);
    if(count($requestData) > 0) {
        $_POST = $requestData;
    }
    $_POST = db_filter($_POST);
    $_GET = db_filter($_GET);
    
    // Initialize response data
    $data = array();
    
    if (isset($_POST['type'])) {
        // Define public endpoints that don't require authentication
        $public_endpoints = ['login', 'register','language' , 'recover', 'check_oauth','sitedata', 'contact','seo', 'verify', 'resend'];
        
        // Check if authentication is required
        if (!in_array($_POST['type'], $public_endpoints)) {
            $authData = $auth->authenticate();
            if (!$authData) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }
        }
        
switch($_POST['type']) 
 {

case 'seo':
    if (isset($_POST['page'])) {
       
            $page_key = $_POST['page'];
            $seo_data = assign_title($page_key, true);
            
            $data['result'] = true;
            $data['metadata'] = $seo_data['metadata'];
            $data['schema'] = $seo_data['schema'];
    } else {
 
    }
    break;

   
    case 'sitedata':
    $sliders = [];
foreach ($infobox_settings['sliders'] as $index => $image) {
    $sliders[] = [
        'image' => $image,
        'title' => $infobox_settings['slider_meta'][$index]['title'] ?? '',
        'subtitle' => $infobox_settings['slider_meta'][$index]['subtitle'] ?? '',
        'description' => $infobox_settings['slider_meta'][$index]['description'] ?? '',
        'id' => 'slide-' . $index
    ];
}
    $data['API_URL'] = $siteURL .'/api.php';
    $data['title'] = $preferences['title'];
    $data['url'] = $site_settings['webapp_url'];
    if($telegram_settings['mini_app_link']){
    $data['telegram_link'] = true;
    $data['mini_app_link'] = $telegram_settings['mini_app_link'];
    }
    if($site_settings['tgonly'])
    $data['tgonly'] = $site_settings['tgonly'];
    $data['currency']= $preferences['currency'];
    $data['symbol']= $preferences['symbol'];
    $data['round']= $preferences['round'];
    $data['email']= $email_settings['email'];
    $data['languages']= $languagues;
    $data['theme'] = [
        'colors' => [
            'primary'         => $infobox_settings['primary']         ?? '#2563eb',
            'primary_hover'   => $infobox_settings['primary_hover']   ?? '#1d4ed8',
            'dark'            => $infobox_settings['dark']            ?? '#111827',
            'dark_lighter'    => $infobox_settings['dark_lighter']    ?? '#1f2937',
            'light'           => $infobox_settings['light']           ?? '#f8fafc',
            'light_darker'    => $infobox_settings['light_darker']    ?? '#f1f5f9',
            'border_light'    => $infobox_settings['border_light']    ?? '#e2e8f0',
            'border_dark'     => $infobox_settings['border_dark']     ?? '#374151',
            'disabled'        => $infobox_settings['disabled']        ?? '#94a3b8',
            'accent'          => $infobox_settings['accent']          ?? '#3b82f6',
            'accent_success'  => $infobox_settings['accent_success']  ?? '#10b981',
            'accent_warning'  => $infobox_settings['accent_warning']  ?? '#f59e0b',
            'accent_error'    => $infobox_settings['accent_error']    ?? '#ef4444'
        ],
        'gradients' => [
            'light' => "linear-gradient(to bottom right, " 
                        . ($infobox_settings['light']       ?? '#f8fafc') . ", " 
                        . ($infobox_settings['light_darker']  ?? '#f1f5f9') . ")",
            'dark'  => "linear-gradient(to bottom right, " 
                        . ($infobox_settings['dark']        ?? '#111827') . ", " 
                        . ($infobox_settings['dark_lighter']  ?? '#1f2937') . ")"
        ]
     ];
    unset($captcha_settings['hcaptcha']['hsecretkey']);
    unset($captcha_settings['google']['gserverkey']);
    unset($captcha_settings['cloudflare']['csecretkey']);
    unset($alerts_settings['onesignal_app_key']);
    unset($alerts_settings['telegram_token']);
     unset($alerts_settings['VAPID_PRIVATE_KEY']);
    $data['alert_settings'] = $alerts_settings;
$navigation_sections = ['navbar', 'sidebar', 'bottomnav', 'tradingfeatures', 'quickactions'];
$simplified_nav = [];

foreach ($navigation_sections as $section) {
    $simplified_nav[$section] = [];
    
    if (isset($infobox_settings[$section])) {
        $enabled_items = [];
        
        foreach ($infobox_settings[$section] as $key => $value) {
            if (isset($value['enabled']) && $value['enabled'] === 'true') {
                // Create the item with all its properties
                $item_data = [
                    'enabled' => 'true',
                    'order' => (int)($value['order'] ?? 0)
                ];
                
                // Add the after_login property if it exists
                if (isset($value['after_login'])) {
                    $item_data['after_login'] = $value['after_login'];
                }
                
                $enabled_items[$key] = $item_data;
            }
        }
        
        // Sort by order
        uasort($enabled_items, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        // Build the final structure
        foreach ($enabled_items as $key => $item) {
            $simplified_nav[$section][$key] = $item;
        }
    }
    
    $data[$section] = $simplified_nav[$section];
}
      unset($infobox_settings['navbar']);
       unset($infobox_settings['sidebar']);
        unset($infobox_settings['bottomnav']);
        unset($infobox_settings['quickactions']);
        unset($infobox_settings['tradingfeatures']);
     $data['infobox_settings'] = $infobox_settings;
    $data['captcha_settings'] = $captcha_settings;
    $data['register_settings'] = $register_settings;
     $data['seo_settings'] = $seo_settings;
    $data['login_settings'] = $login_settings;
     unset($user_settings['review_faucet_memo']);
      unset($user_settings['ban_words']);
    $data['user_settings'] = $user_settings;
      unset($referral_settings['tier']);
      unset($referral_settings['max_commission']);
    $data['referral_settings'] = $referral_settings;
     unset($deposit_settings['partial_payments_balance']);
      unset($deposit_settings['deposit_umemo']);
        unset($deposit_settings['deposit_from_balance_fee']);
          unset($deposit_settings['deposit_from_balance_bonus']);
            unset($deposit_settings['memo_invest']);
              unset($deposit_settings['investment_released']);
                unset($deposit_settings['investment_returned']);
                  unset($deposit_settings['auto_reinvest']);
    $data['deposit_settings'] = $deposit_settings;
$all_keys = array_keys($withdraw_settings);

// Keys to keep
$keys_to_keep = ['enable', 'address', 'pin_code', 'email_code', '2fa_code', 'confirmation'];

// Unset all keys except those we want to keep
foreach ($all_keys as $key) {
    if (!in_array($key, $keys_to_keep)) {
        unset($withdraw_settings[$key]);
    }
}

$data['withdraw_settings'] = $withdraw_settings;
      unset($transfer_settings['from_memo']);
       unset($transfer_settings['to_memo']);
    $data['transfer_settings'] = $transfer_settings;
     unset($exchange_settings['from_memo']);
       unset($exchange_settings['to_memo']);
    $data['exchange_settings'] = $exchange_settings;
 
   $data['trading_settings'] = $trading_settings;
     $data['kyc_settings'] = $kyc_settings;
$data['stats'] = [];

// User Statistics
if ($infobox_settings['total_users'] == 'true') {
    $data['stats']['total_users'] = $preferences['total_users'];
}
if ($infobox_settings['active_users'] == 'true') {
    $data['stats']['active_users'] = $preferences['active_users'];
}
if ($infobox_settings['today_users'] == 'true') {
    $data['stats']['today_users'] = $preferences['today_users'];
}
if ($infobox_settings['newest_member'] == 'true') {
    $data['stats']['newest_member'] = $preferences['newest_member'];
}
// Deposit Statistics
if ($infobox_settings['total_deposit'] == 'true') {
    $data['stats']['total_deposit'] = $preferences['total_deposit'];
}
if ($infobox_settings['today_deposit'] == 'true') {
    $data['stats']['today_deposit'] = $preferences['today_deposit'];
}
if ($infobox_settings['largest_investment'] == 'true') {
    $data['stats']['largest_investment'] = $preferences['largest_investment'];
}
if ($infobox_settings['smallest_investment'] == 'true') {
    $data['stats']['smallest_investment'] = $preferences['smallest_investment'];
}
// Withdrawal Statistics
if ($infobox_settings['total_withdraw'] == 'true') {
    $data['stats']['total_withdraw'] = $preferences['total_withdraw'];
}
if ($infobox_settings['today_withdraw'] == 'true') {
    $data['stats']['today_withdraw'] = $preferences['today_withdraw'];
}
if ($infobox_settings['largest_withdraw'] == 'true') {
    $data['stats']['largest_withdraw'] = $preferences['largest_withdraw'];
}
if ($infobox_settings['smallest_withdraw'] == 'true') {
    $data['stats']['smallest_withdraw'] = $preferences['smallest_withdraw'];
}
// Last Transaction Statistics
if ($infobox_settings['last_deposit_stat'] == 'true') {
    $data['stats']['last_deposit'] = $stat_last_deposit;
}
if ($infobox_settings['last_withdrawal_stat'] == 'true') {
    $data['stats']['last_withdrawal'] = $stat_last_withdrawal;
}
// Site Statistics
if ($infobox_settings['days_online'] == 'true') {
    $data['stats']['days_online'] = floor((time()-strtotime($preferences['datetime']))/86400);
}
    $data['packages'] = $PackagesData;
    $data['affiliates'] = $affiliateData;
    if($infobox_settings['last_reviews']) {
        $data['reviews'] = $reviews;
    }
    if(isset($faucet_packages) && !empty($faucet_packages)) {
        $data['faucet_packages'] = $faucet_packages;
    }
    if($infobox_settings['last_deposits']) {
        $data['last_deposits'] = $deposits;
    }
    if($infobox_settings['last_withdraws']) {
        $data['last_withdraws'] = $withdraws;
    }
    if($infobox_settings['top_depositors']) {
        $data['top_investors'] = $investors;
    }
    if($infobox_settings['top_refferals']) {
        $data['top_referrals'] = $referrals;
    }
    if($infobox_settings['last_transactions']) {
        $data['last_transactions'] = $transactions;
    }
   

    $data['sliders'] = $sliders;
  //  $data['bonus_amounts'] = $bonus_amounts;
      
    $pairs_query = mysqli_query($DB_CONN, "SELECT * FROM trading_pairs WHERE status = 1 ORDER BY id ASC");
    $pairs = [];
    
    while ($pair = mysqli_fetch_assoc($pairs_query)) {
        $pairs[] = [
            'id' => $pair['id'],
            'name' => $pair['name'],
            'pair' => $pair['pair'],
        ];
    }
    
    $data['trading_pairs'] = $pairs;
    $data['payment_methods'] = $payments;

$tasks = [];
foreach ($languagues as $key => $value) {
    $query = "
        SELECT 
            t.*,
            ac.name as account_currency_name,
            ac.id as account_currency_id,
            fc.name as faucet_currency_name,
            fc.id as faucet_currency_id
        FROM tasks t
        LEFT JOIN currencies ac ON JSON_UNQUOTE(JSON_EXTRACT(t.details, '$.account_currency')) = ac.id
        LEFT JOIN currencies fc ON JSON_UNQUOTE(JSON_EXTRACT(t.details, '$.faucet_currency')) = fc.id
        WHERE t.status = '1' AND t.lang_id = '{$value['id']}'
    ";
    
    $tasks_query = mysqli_query($DB_CONN, $query);
    
    while ($task_item = mysqli_fetch_assoc($tasks_query)) {
        foreach (['name', 'content', 'instructions'] as $field) {
            if (isset($task_item[$field]) && !empty($task_item[$field])) {
                $text = str_replace(["\r\n", "\r", "\n"], " ", trim($task_item[$field]));
                if (!mb_check_encoding($text, 'UTF-8')) {
                    $text = mb_convert_encoding($text, 'Windows-1252,ISO-8859-1', 'UTF-8');
                }
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $task_item[$field] = $text;
            }
        }

        $details = json_decode($task_item['details'], true);
        
        $clean_task = [
            'id' => $task_item['id'],
            'name' => $task_item['name'],
            'url' => $task_item['url'],
            'telegram_url' => ($task_item['type'] == 2 && isset($details['selected_task']) && 
            in_array($details['selected_task'], ['channel', 'group'])) ? true : false,
            'outer_url' => ($task_item['type'] == 1) ? true : false,
            'inside_url' => ($task_item['type'] == 2) ? true : false,
            'image_url' => $task_item['image_url'],
            'image_url' => $task_item['image_url'],
            'content' => $task_item['content'],
            'type' => $task_item['type'],
            'instructions' => $task_item['instructions'],
            'instructions_image_url' => $task_item['instructions_image_url'],
            'status' => $task_item['status'],
            
            // Account Balance Fields
            'account_balance_enabled' => $details['account_balance_enabled'] ?? false,
            'account_amount_type' => $details['account_amount_type'] ?? null,
            'account_amount_min' => fiat($details['account_amount_min']) ?? null,
            'account_amount_max' => fiat($details['account_amount_max']) ?? null,
            'account_currency_name' => $task_item['account_currency_name'] ?? null,
            'account_currency_id' => $task_item['account_currency_id'] ?? null,
            'account_currency_icon' => $task_item['account_currency_id'] ? $siteURL . '/images/icons/' . $task_item['account_currency_id'] . '.svg' : null,
            
            // Faucet Balance Fields
            'faucet_balance_enabled' => $details['faucet_balance_enabled'] ?? false,
            'faucet_amount_type' => $details['faucet_amount_type'] ?? null,
            'faucet_amount_min' => fiat($details['faucet_amount_min']) ?? null,
            'faucet_amount_max' => fiat($details['faucet_amount_max']) ?? null,
            'faucet_currency_name' => $task_item['faucet_currency_name'] ?? null,
            'faucet_currency_id' => $task_item['faucet_currency_id'] ?? null,
            'faucet_currency_icon' => $task_item['faucet_currency_id'] ? $siteURL . '/images/icons/' . $task_item['faucet_currency_id'] . '.svg' : null,
            
            // Taps Fields
            'taps_enabled' => $details['taps_enabled'] ?? false,
            'taps_amount' => $details['taps_amount'] ?? null
        ];
        
        if (!empty($task_item['translation_of'])) {
            $clean_task['translation_of'] = $task_item['translation_of'];
        }
        
        if ($value['id'] == 1) {
            $tasks[$key][$task_item['id']] = $clean_task;
        } else {
            if (!empty($task_item['translation_of'])) {
                $tasks[$key][$task_item['translation_of']] = $clean_task;
            }
        }
    }
}
$data['task'] = $tasks;
$news = [];
foreach ($languagues as $key => $value) {
    $news_query = mysqli_query($DB_CONN, "
        SELECT news.*, users.fullname, users.bio, users.photo 
        FROM news 
        JOIN users ON users.id = news.author 
        WHERE news.lang_id = '{$value['id']}' 
        AND news.status = 1
    ");
    
    while ($news_item = mysqli_fetch_assoc($news_query)) {
        // Process text fields
        foreach (['title', 'content', 'fullname', 'bio'] as $field) {
            if (isset($news_item[$field])) {
                $text = str_replace(["\r\n", "\r", "\n"], " ", trim($news_item[$field]));
                if (!mb_check_encoding($text, 'UTF-8')) {
                    $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252,ISO-8859-1');
                }
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $news_item[$field] = $text;
            }
        }
        
        // For base language, use the item's own ID
        if ($value['id'] == 1) {
            $news[$key][$news_item['id']] = $news_item;
        } else {
            // For translations, store under the original article's ID
            // Only store if translation_of is not empty to maintain data integrity
            if (!empty($news_item['translation_of'])) {
                $news[$key][$news_item['translation_of']] = $news_item;
            }
        }
    }
}
$data['new'] = $news;

$faq_categories = [];
foreach ($languagues as $key => $value) {
    // Modified query to get both original and translated categories
    $category_query = mysqli_query($DB_CONN, "
        SELECT c.id, c.name, c.translation_of, c.lang_id 
        FROM categories c 
        WHERE c.lang_id = '{$value['id']}'
    ");
    
    while ($category = mysqli_fetch_assoc($category_query)) {
        $category_name = str_replace(["\r\n", "\r", "\n"], " ", trim($category['name']));
        if (!mb_check_encoding($category_name, 'UTF-8')) {
            $category_name = mb_convert_encoding($category_name, 'UTF-8', 'Windows-1252,ISO-8859-1');
        }
        $category_name = mb_convert_encoding($category_name, 'UTF-8', 'UTF-8');
        $category_name = preg_replace('/\s+/', ' ', $category_name);
        
        // Store by original category ID for both languages
        if ($value['id'] == 1) {
            $faq_categories[$key][$category['id']] = $category_name;
        } else {
            // For translations, store by the original ID but use translated name
            $faq_categories[$key][$category['translation_of']] = $category_name;
        }
    }
}

$faqs = [];
foreach ($languagues as $key => $value) {
    $faq_query = mysqli_query($DB_CONN, "SELECT question, answer, category_id FROM faqs WHERE lang_id = '{$value['id']}'");
    while ($faq = mysqli_fetch_assoc($faq_query)) {
        // Text cleaning code remains the same
        $question = str_replace(["\r\n", "\r", "\n"], " ", trim($faq['question']));
        $answer = str_replace(["\r\n", "\r", "\n"], " ", trim($faq['answer']));
        
        if (!mb_check_encoding($question, 'UTF-8')) {
            $question = mb_convert_encoding($question, 'UTF-8', 'Windows-1252,ISO-8859-1');
        }
        $question = mb_convert_encoding($question, 'UTF-8', 'UTF-8');
        
        if (!mb_check_encoding($answer, 'UTF-8')) {
            $answer = mb_convert_encoding($answer, 'UTF-8', 'Windows-1252,ISO-8859-1');
        }
        $answer = mb_convert_encoding($answer, 'UTF-8', 'UTF-8');
        
        $question = preg_replace('/\s+/', ' ', $question);
        $answer = preg_replace('/\s+/', ' ', $answer);
        
        $faq_item = [
            'question' => $question,
            'answer' => $answer
        ];
        
        // Simplified category mapping - use category_id directly since FAQs already use base category IDs
        if (!empty($faq['category_id']) && isset($faq_categories[$key][$faq['category_id']])) {
            $faq_item['category'] = $faq_categories[$key][$faq['category_id']];
        }
        
        $faqs[$key][] = $faq_item;
    }
}

$data['faq'] = $faqs;
$contents = [];
foreach ($languagues as $key => $value) {
    $content_query = mysqli_query($DB_CONN, "SELECT * FROM content WHERE lang_id = '{$value['id']}'");
    while($item = mysqli_fetch_assoc($content_query)) {
        $decoded_content = json_decode($item['content_data'], true);
        if (is_array($decoded_content)) {
            array_walk_recursive($decoded_content, function(&$value) {
                if (is_string($value)) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1');
                    }
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            });
        }
        
        $contents[$key][$item['page']] = $decoded_content;
    }
}

$data['contents'] = $contents;
       $pages = array(
            'home', 'about', 'faqs', 'contact', 'login', 'register', 
            'invest', 'dashboard', 'deposit', 'withdraw', 'profile', 
            'transactions', 'affiliates', 'terms', 'privacy', 'news'
        );
        
        $all_seo_data = array();
        
        foreach ($pages as $page) {
            $seo_data = assign_title($page, true);
            $all_seo_data[$page] = array(
                'metadata' => $seo_data['metadata'],
                'schema' => $seo_data['schema']
            );
        }
        
        // Add recent news articles
        $news_query = mysqli_query($DB_CONN, 
            "SELECT news.*, users.fullname as author_name, users.bio as author_bio, 
            users.slug as author_slug, users.photo as author_photo 
            FROM news 
            LEFT JOIN users ON news.author = users.id 
            WHERE news.status = 1 
            ORDER BY datetime DESC LIMIT 10");
            
        while ($news = mysqli_fetch_assoc($news_query)) {
            $categories = getCategories($news['id'], 'news');
            $category_names = array_column($categories, 'name');
            $dimensions = getImageDimensions($news['image_url']);
            $wordCount = improvedWordCount($news['content']);
            $plainTextContent = convertHtmlToPlainText($news['content']);
            $news_base = $main_link['news'] ?: 'news';
            $current_url = $siteURL . "/" . $news_base . "/" . $news['slug'];

            $pageSpecificData = [
                'pagetype' => $news['schema_type'] ?? 'Article',
                'currentURL' => $current_url,
                'pageName' => $news['title'],
                'headline' => substr($news['title'], 0, 110),
                'description' => $news['short_description'],
                'content' => $plainTextContent,
                'datePublished' => date('c', strtotime($news['created_at'] ?? 'now')),
                'dateModified' => date('c', strtotime($news['updated_at'] ?? $news['created_at'] ?? 'now')),
                'author' => [
                    '@type' => 'Person',
                    'name' => $news['author_name'] ?? ($siteName . ' Team'),
                    'url' => $siteURL . '/authors/' . ($news['author_slug'] ?? '')
                ],
                'image' => [
                    '@type' => 'ImageObject',
                    'url' => $news['image_url'],
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height']
                ],
                'wordCount' => $wordCount,
                'readingTime' => calculateReadingTime($wordCount),
                'categories' => $category_names,
                'tags' => array_map('trim', explode(',', $news['keywords'] ?? '')),
                'breadcrumbs' => [
                    ['url' => $siteURL, 'name' => $siteName],
                    ['url' => $siteURL . "/$news_base", 'name' => ucfirst($news_base)],
                    ['url' => $current_url, 'name' => $news['title']]
                ]
            ];

            $schema = generateUnifiedSchema($pageSpecificData);
            
            $all_seo_data[$news['slug']] = [
                'metadata' => [
                    'basic' => [
                        'title' => $news['title'] . " - " . $siteName,
                        'pagename' => $news['title'],
                        'description' => substr(strip_tags($news['short_description'] ?? $news['content']), 0, 160),
                        'keywords' => $news['keywords'],
                        'robots' => $news['seo_robots'] == 'yes' ? 'index, follow' : 'noindex, follow',
                        'canonical' => $current_url
                    ],
                    'og' => [
                        'type' => 'article',
                        'title' => $news['title'] . " - " . $siteName,
                        'description' => substr(strip_tags($news['short_description'] ?? $news['content']), 0, 160),
                        'url' => $current_url,
                        'image' => $news['image_url'],
                        'site_name' => $siteName
                    ],
                    'twitter' => [
                        'card' => 'summary_large_image',
                        'title' => $news['title'] . " - " . $siteName,
                        'description' => substr(strip_tags($news['short_description'] ?? $news['content']), 0, 160),
                        'image' => $news['image_url']
                    ]
                ],
                'schema' => $schema
            ];
        }

        $data['metadata'] = $all_seo_data;
   break;
case 'check_oauth':
    $initData = $_POST['initData'] ?? '';
    $startParam = $_POST['start_param'] ?? '';

    try {
        // Security checks - keeping these intact
        if (empty($initData)) {
            throw new Exception('No init data provided!');
        }
        
        if (strlen($initData) > 1024) {
            throw new Exception('Init data too large');
        }
        
        // Validate init data - keeping the hash validation
        parse_str($initData, $parsedData);
        $hash = $parsedData['hash'] ?? '';
        unset($parsedData['hash']);
        
        ksort($parsedData);
        $dataCheckString = '';
        foreach ($parsedData as $key => $value) {
            $dataCheckString .= $key . '=' . $value . "\n";
        }
        $dataCheckString = rtrim($dataCheckString, "\n");
        
        $secretKey = hash_hmac('sha256', $tgtoken, "WebAppData", true);
        $checkHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));
        
        if (!hash_equals($checkHash, $hash)) {
            throw new Exception('Invalid hash!');
        }

        // Simplified user data processing
        $user = json_decode($parsedData['user'] ?? '{}', true);
          
       $userData = [
    'telegramId' => $user['id'] ?? '',
    'username' => $user['username'] ?? $user['first_name'] ?? '',
    'firstName' => $user['first_name'] ?? '',
    'lastName' => $user['last_name'] ?? '',
    'fullName' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
    'photoUrl' => $user['photo_url'] ?? '/default-avatar.png',
    'languageCode' => $user['language_code'] ?? 'en',
    'isPremium' => isset($user['is_premium']) ? $user['is_premium'] : 0,
    'isBot' => isset($user['is_bot']) ? $user['is_bot'] : 0,
    'sponsor' => $startParam ?: ($parsedData['start_param'] ?? '')
];

            // Get sponsor's user ID if sponsor exists
            $sponsorId = null;
            if (!empty($userData['sponsor'])) {
                $stmt = $DB_CONN->prepare("SELECT id FROM users WHERE oauth_uid = ? AND oauth_provider = 'telegram'");
                $stmt->bind_param("s", $userData['sponsor']);
                $stmt->execute();
                $result = $stmt->get_result();
                $sponsorData = $result->fetch_assoc();
                if ($sponsorData) {
                    $sponsorId = $sponsorData['id'];
                }
                $stmt->close();
            }
            // Check for existing user
        $stmt = $DB_CONN->prepare("SELECT id, status FROM users WHERE oauth_uid = ? AND oauth_provider = 'telegram'");
$stmt->bind_param("s", $userData['telegramId']);
$stmt->execute();
$result = $stmt->get_result();
$existingUser = $result->fetch_assoc();
$stmt->close();


          if ($existingUser) {
    // Update existing user in users table
    $stmt = $DB_CONN->prepare("UPDATE users SET username = ?, fullname = ?, photo = ? WHERE id = ?");
    $stmt->bind_param("sssi", $userData['username'], $userData['fullName'], $userData['photoUrl'], $existingUser['id']);
    $stmt->execute();
    $userId = $existingUser['id'];
    $stmt->close();
    
    // Check if user exists in telegram_users table
    $stmt = $DB_CONN->prepare("SELECT user_id FROM telegram_users WHERE user_id = ?");
    $stmt->bind_param("i", $userData['telegramId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingTelegramUser = $result->fetch_assoc();
    $stmt->close();
    
    if ($existingTelegramUser) {
        // Update existing record in telegram_users
        $stmt = $DB_CONN->prepare("UPDATE telegram_users SET 
            username = ?,
            first_name = ?,
            last_name = ?,
            photo_path = ?,
            language_code = ?,
            is_premium = ?,
            last_seen = CURRENT_TIMESTAMP
            WHERE user_id = ?");
        $stmt->bind_param(
            "sssssii",
            $userData['username'],
            $userData['firstName'],
            $userData['lastName'],
            $userData['photoUrl'],
            $userData['languageCode'],
            $userData['isPremium'],
            $userData['telegramId']
        );
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert into telegram_users if not exists
        $stmt = $DB_CONN->prepare(
            "INSERT INTO telegram_users (
                user_id, username, first_name, last_name, 
                photo_path, language_code, is_premium, is_bot
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "isssssii",
            $userData['telegramId'],
            $userData['username'],
            $userData['firstName'],
            $userData['lastName'],
            $userData['photoUrl'],
            $userData['languageCode'],
            $userData['isPremium'],
            $userData['isBot']
        );
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Insert new user with timezone and sponsor into users table
    $sponsorId = $sponsorId ?? 0;
    
    $stmt = $DB_CONN->prepare(
        "INSERT INTO users (oauth_uid, oauth_provider, fullname, username, photo, status, sponsor) 
         VALUES (?, 'telegram', ?, ?, ?, 1, ?)"
    );
    $stmt->bind_param("ssssi", 
        $userData['telegramId'],
        $userData['fullName'],
        $userData['username'],
        $userData['photoUrl'],
        $sponsorId
    );
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $stmt->close();
        
        // Insert into telegram_users table
        $stmt = $DB_CONN->prepare(
            "INSERT INTO telegram_users (
                user_id, username, first_name, last_name, 
                photo_path, language_code, is_premium, is_bot
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "isssssii",
            $userData['telegramId'],
            $userData['username'],
            $userData['firstName'],
            $userData['lastName'],
            $userData['photoUrl'],
            $userData['languageCode'],
            $userData['isPremium'],
            $userData['isBot']
        );
        $stmt->execute();
        $stmt->close();
        
        if($register_settings['register_bonus'])
            add_balance($userId, $register_settings['register_bonus_currency'], $register_settings['register_bonus_amount']);
    } else {
        $stmt->close();
    }
}

        // Insert login report
         $browser = getBrowser();
    $os = getOS();
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $refer = $_SERVER['HTTP_REFERER'];
$country = ''; // Set this based on IP geolocation
$city = ''; // Set this based on IP geolocation
        
        $stmt = $DB_CONN->prepare("INSERT INTO login_report (ip, useragent, refer, os, country, city, browser, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $ip, $useragent, $refer, $os, $country, $city, $browser, $userId);
        if (!$stmt->execute()) {
    error_log("Login report insertion failed: " . $stmt->error);
}
        $stmt->close();

        // Setup session and return data
       $user_id  = $_SESSION['user_id'] = $userId;
        setcookie('user_id', $user_id, time() + (86400 * 30), "/");
        $jwt = $auth->generateJWT($user_id);

        updateuserinfo();
        call_alert(115); // logged in successfully
        getUserProfileData($userinfo, true, $jwt);
        
    } catch (Exception $e) {
        $data = [
            'result' => false,
            'message' => $e->getMessage()
        ];
    }
    break;
  case 'contact':
        // For submitting contact form
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
            if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
                $data['result'] = false;
                $data['message'] = 'Required fields are missing';
                break;
            }
            $cc = captcha_check('contact');
            if (!$cc) {
                $data['result'] = false;
                $data['message'] = 'Captcha verification failed';
                break;
            }
            // Prepare email data
            $emailData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'message' => $_POST['message'],
                'subject' => $_POST['subject'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ];
    
            // Send email
            sendmail("contact", 0, "", $emailData);
    
            $data['result'] = true;
            $data['message'] = 'Message sent successfully';
        } else {

            
        }
        break;
    
case 'upload_data':
    // Check if file exists
    if (!isset($_FILES["file"]["name"]) || !$_FILES["file"]["name"]) {
        $data['result'] = false;
        $data['message'] = 'File is required';
        break;
    }

    // Directory handling
    $upload_dir = "{$_POST['table_name']}/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!is_writable($upload_dir)) {
        $data['result'] = false;
        $data['message'] = 'Directory is not writable';
        break;
    }

    // File upload
    $tmp_file = $_FILES['file']['tmp_name'];
    
    $name = $upload_dir.$_POST['table_name']."_".$userinfo['id']."_".mt_rand(10,10000)."_".$_FILES["file"]["name"];
    $url = $siteURL.'/'.$name;
    
    if (!move_uploaded_file($tmp_file, $name)) {
        $data['result'] = false;
        $data['message'] = 'File upload failed: ' . error_get_last()['message'];
        break;
    }

    $data['file_name'] = $url;
    $data['result'] = true;
    $data['message'] = 'Added Successfully';
    break;
case 'daily_bonus':
    $data['result'] = true;
    $streak_info = getCurrentStreak();
    
    // Get the streak day number for the next claim
    $data['current_streak'] = $streak_info['next_day'];
    
    // Format completed days for frontend display
    $formatted_days = [];
    foreach ($streak_info['completed_days'] as $completion) {
        $formatted_days[] = [
            'day' => $completion['day'],
            'date' => date('Y-m-d', strtotime($completion['date'])),
            'formatted_date' => date('M j, Y', strtotime($completion['date']))  // Optional: adds formatted date like "Jan 7, 2025"
        ];
    }
    $data['completed_days'] = $formatted_days;
    break;
case 'claim_daily_bonus':
    $data = claimDailyBonus();
    break;
    case 'kyc':
  // if (!) {
  //  header("location: $dash_link",  true,  301 );  exit;
  // }
  $a = true;
  if($userinfo['kyc'] == 0 && $kyc_settings['enable']) {
    // upload_image('front_image');
    // upload_image('back_image');
    // upload_image('address_image');
    $kyc_data = array();
    foreach($_POST as $a=>$b) {
        if($a != "type" && $b)
          $kyc_data[$a] = $b;
    }
    $kyc_data = mysqli_real_escape_string($DB_CONN, json_encode($kyc_data));
    if(mysqli_query($DB_CONN, "UPDATE users set kyc_data = '{$kyc_data}', kyc = 2 where id = '{$userinfo['id']}'"))
      call_alert(87); //KYC Received wll be reviewed shortly
  } else
    call_alert(65); //Some Error. Please try again
  if($userinfo['kyc'] == 1)
    call_alert(89); //KYC already approved
  if($userinfo['kyc'] == 2)
    call_alert(88); //KYC is already in process please wait for it
     break;
case 'login':
    $username = $_POST['username'];
    $password = $_POST['password'];
    // Check IP blacklist
    $tim = isset($security_settings['banned_timeout_signin']) ? $security_settings['banned_timeout_signin'] : 100000;
    $bc = mysqli_fetch_array(mysqli_query($DB_CONN, 
        "SELECT COUNT(*) FROM `ip_blacklist` 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL {$tim} MINUTE) 
        AND ip = '{$ip}'"))[0];

    if ($bc >= $security_settings['max_attempts_signin'] && $security_settings['max_attempts_signin']) {
        call_alert(16); // IP blacklisted message
        break;
    }

    // Validate credentials
    $admin = mysqli_query($DB_CONN, "SELECT * FROM users WHERE (username='{$username}' OR email='{$username}')");
    if ($admin && mysqli_num_rows($admin) > 0) {
        $user = mysqli_fetch_assoc($admin);
        if (password_verify($password, $user['password'])) {
            // Log login attempt
            $browser = getBrowser();
            $os = getOS();
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            $refer = $_SERVER['HTTP_REFERER'];


       mysqli_query($DB_CONN, "INSERT INTO `login_report`(`ip`, `useragent`, `refer`, `os`, `country`, `city`, `browser`, `user_id`) VALUES ('{$ip}', '{$useragent}', '{$refer}', '{$os}', '', '', '{$browser}', '{$user['id']}')");

            // Handle admin login
            if ($user['is_admin'] == 1) {
            // Generate secure access token
            $access_data = generateSecureToken($user['id'], false);
            $access_link = $siteURL . '/admin?access_token=' . $access_data['token'] . "&vk=" . $access_data['verification_key'];
            
            $data['result'] = true;
            $data['redirect'] = $access_link;
            break;
        }

            // Check user status
            if ($user['status'] != 1) {
                call_alert(14); // Access Denied
                break;
            }
 // Store the user ID temporarily for verification process
            $_SESSION['temp_id'] = $user['id'];
            
            // Check if email verification is needed for login
            $ccn = code_check_new('login', $login_settings);
            if (!$ccn) {
                $data['temp_id'] = sha1($user['id']);
                break;
            }
            
            sendmail("logged_in", $user['id'], $user, array(
               'country' => $country,
               'city' => $city,
               'os' => $os,
            'browser' => $browser,
               'useragent' => $useragent,
               'datetime' => $dt
           ));
            
           

            // Complete login process
            $_SESSION['user_id'] = $user_id = $user['id'] ;
            updateuserinfo();
            $jwt = $auth->generateJWT($user['id']);
       
            $data['result'] = true;
            call_alert(115); // Logged in successfully
            getUserProfileData($userinfo, true, $jwt);

        } else {
            // Log failed attempt
            mysqli_query($DB_CONN, "INSERT INTO `ip_blacklist`(`username`, `ip`) VALUES ('{$username}', '{$ip}')");
            call_alert(12); // Username or Password invalid
        }
    } else {
        // Log failed attempt
        mysqli_query($DB_CONN, "INSERT INTO `ip_blacklist`(`username`, `ip`) VALUES ('{$username}', '{$ip}')");
        call_alert(12); // Username or Password invalid
    }
    break;

    case 'register':
    $data['result'] = false;
    $cc = captcha_check('register');
    $_SESSION['ref'] = $_POST['sponsor'];
    if($register_settings['sponsor_must']) {
      $sc = mysqli_query($DB_CONN,"SELECT id from users where username='{$_POST['sponsor']}' limit 1");
      if(mysqli_num_rows($sc) == 0) {
        $cc = false;
        //Registration without a sponsor is not allowed
        call_alert(8);
      }
    }
    if($register_settings['pin_code']) {
      $sc = mysqli_query($DB_CONN,"select id from users where username='{$_POST['sponsor']}' limit 1");
      if($_POST['pin_code'] == '' || strlen($_POST['pin_code']) < 4)  {
        $cc = false;
        //Registration without Pin Code is not allowed and Pin Code length must be greater than 4
        call_alert(9);
      }
    }
    $_POST['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $e_domain = explode('@', $_POST['email'])[1];
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !checkdnsrr($e_domain, 'MX') || !checkdnsrr($e_domain, 'A')) {
      //Email address is not valid
      call_alert(7);
      $cc = false;
    }
    $email = $_POST['email'];
    if (isDisposableEmail($email, $disposableEmailDomains)) {
        //Email address is not valid
        call_alert(7);
        $cc = false;
    }
    if ($_POST['password'] != $_POST['confirm_password']) {
      //Password and Confirm password does not match
      call_alert(10);
      $cc = false;
    }
    $_POST['username'] = trim($_POST['username']);
       if (!ctype_alnum($_POST['username']) || empty($_POST['username'])) {
      //Username can only contains letters and numbers
      call_alert(11);
      $cc = false;
    }
  if($cc) {
   if(!empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password']) )
    {
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
            $ipdat = @json_decode(file_get_contents("http://ip-api.com/json/".$ip), true);
            $tz = $ipdat['timezone'];
            $country = $ipdat['country'];
            $city = $ipdat['city'];
            $upd_query = "INSERT into users (username, email, password, fullname, sponsor, status, phone, address,city ,state ,zip ,country, pin_code,wallets,question,answer,came_from, timezone, `oauth_provider`, `oauth_uid`, `photo`) values('{$username}', '{$email}', '{$password}', '{$_POST['fullname']}', '{$sponsor}', '1', '{$phone}','{$address}','{$city}','{$state}','{$zip}', '{$country}', '{$_POST['pin_code']}','$co','{$_POST['question']}','{$_POST['answer']}','{$_SESSION['came_from']}', '{$tz}', '{$_SESSION['post']['oauth_provider']}', '{$_SESSION['post']['oauth_id']}', '{$_SESSION['post']['photo']}')";
            unset($_SESSION['post']);
            if(mysqli_query($DB_CONN,$upd_query))
            {
                $data['result'] = true;
                $id = mysqli_insert_id($DB_CONN);
                if($register_settings['register_bonus'])
                 add_balance($id, $register_settings['register_bonus_currency'], $register_settings['register_bonus_amount']);
                $user = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from users where id = '{$id}'"));
                if($_SESSION['ref_id']) {
                  mysqli_query($DB_CONN, "UPDATE `refer_stats` SET `user_id`='{$id}' WHERE id = '{$_SESSION['ref_id']}'");
                }
                unset($_SESSION['ref']);
                unset($_SESSION['ref_id']);
                $co = mysqli_real_escape_string($DB_CONN, json_encode($_POST['payment']));
                $q = "UPDATE users set wallets = '{$co}' where `id` = '{$id}'";
                $update_user = mysqli_query($DB_CONN,$q);
                $_SESSION['temp_id'] = $id;
                $ccn = code_check_new('register', $register_settings);
              if($register_settings['double_optin_reg']) {
                $code = genPinCode(32);
                mysqli_query($DB_CONN,"UPDATE users set status = 0, code = '{$code}' where id = '{$id}'");
                sendmail("confirm_registration", $id, $user, array('code' => $code));
                call_alert(2); //Registered Successfully. Please Check Your Inbox/Spam folder to confirm your account.
              } elseif(!$ccn) {
                // Email verification needed
                sendotp('register', $id);
                $data['temp_id'] = sha1($id);
                mysqli_query($DB_CONN, "UPDATE users SET status = 0 WHERE id = '{$id}'");
                call_alert(2); // Check inbox/spam to confirm account
              }  else {
                $browser = getBrowser();
                $os = getOS();
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                $refer = $_SERVER['HTTP_REFERER'];
                mysqli_query($DB_CONN,"INSERT INTO `login_report`(`ip`, `useragent`,`refer`,`os`,`country`,`city`,`browser`, `user_id`) VALUES ('{$ip}','{$useragent}','{$refer}','{$os}','','','{$browser}','{$id}')");
                $_SESSION['user_id'] = $id;
                if($register_settings['after_register'] == 'dashboard_page' || $register_settings['after_register'] == 'login_redirect_page' || $register_settings['after_register'] == 'tap_page' || $register_settings['after_register'] == 'home_page') { 
                    $jwt = $auth->generateJWT($id);
                    $user_id = $id;
                    updateuserinfo();
                    $data['result'] = true;
                    call_alert(115, '', 'success'); //logged in successfully
                    getUserProfileData($userinfo, true, $jwt);
                } else {
                    sendmail("registration", $id, $user);
                    call_alert(1);
                }
              }
            }
          }
        }
        else
          call_alert(6);
      }
      else
       call_alert(4);
    }
    else
      call_alert(5);
    }
  break;
  case 'verify':
    $data['result'] = false;
    $page = $_POST['page'];
    $temp_id = $_POST['temp_id'];
    // Retrieve user ID from temp_id (hash) or session
    $user_id = null;
    if (!empty($temp_id)) {
        // Try to find user from sha1 hash
        $hash_query = mysqli_query($DB_CONN, "SELECT id FROM users WHERE SHA1(id) = '{$temp_id}'");
        if (mysqli_num_rows($hash_query) > 0) {
            $user_id = mysqli_fetch_assoc($hash_query)['id'];
        } 
    } 
    
    if (empty($user_id)) {
        call_alert(3); // Invalid verification request
        break;
    }
    
    $_SESSION['temp_id'] = $user_id;
    
    // Create settings array based on page
    switch ($page) {
        case 'login':
            $settings = $login_settings;
            break;
        case 'register':
            $settings = $register_settings;
            break;
        case 'forgot':
            $settings = [
                'email_code' => isset($user_settings['forgot_email_code']) ? $user_settings['forgot_email_code'] : false
            ];
            break;
        default:
            $settings = [];
            break;
    }
    
    // Check verification status
    $verification_result = code_check($page, $page);
    
    // If all verifications passed, proceed with page-specific actions
    if ($verification_result) {
        switch ($page) {
            case 'register':
                // Activate user account
                mysqli_query($DB_CONN, "UPDATE users SET status = 1 WHERE id = '{$user_id}'");
                
                // Log user login
                $browser = getBrowser();
                $os = getOS();
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                $refer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                $ip = $_SERVER['REMOTE_ADDR'];
                
                mysqli_query($DB_CONN, "INSERT INTO `login_report`(`ip`, `useragent`, `refer`, `os`, `country`, `city`, `browser`, `user_id`) VALUES ('{$ip}', '{$useragent}', '{$refer}', '{$os}', '', '', '{$browser}', '{$user_id}')");
                
                // Start session
                $_SESSION['user_id'] = $user_id;
                $jwt = $auth->generateJWT($user_id);
                updateuserinfo();
                
                // Return success
                $data['result'] = true;
                call_alert(115, '', 'success'); // Logged in successfully
                getUserProfileData($userinfo, true, $jwt);
                break;
                
            case 'login':
                // Start session
                $_SESSION['user_id'] = $user_id;
                $jwt = $auth->generateJWT($user_id);
                updateuserinfo();
                
                // Return success
                $data['result'] = true;
                call_alert(115); // Logged in successfully
                getUserProfileData($userinfo, true, $jwt);
                break;
                
            case 'forgot':
                // Allow password reset
                $_SESSION['allow_password_reset'] = true;
                $_SESSION['temp_id'] = $user_id;
                $data['result'] = true;
                $data['allow_password_reset'] = true;
                break;
                
            default:
                // For other verification cases
                $data['result'] = true;
                call_alert(306); // Verification successful
                break;
        }
    } else {
        // If verification failed but some methods passed, return the verification status
        // This will be used by the client to only show necessary verification inputs
        $data['result'] = false;
        $data['partial_verification'] = true;
        // verification_status is already set in code_check
    }
    break;
    case 'resend':
    $data['result'] = false;
    $page = $_POST['page'];
    $temp_id = $_POST['temp_id'];
    
    // Determine user ID
    $user_id = null;
    if($userinfo['id'])
     $temp_id = sha1($userinfo['id']);
    if (!empty($temp_id)) {
        // Try to find user from sha1 hash
        $hash_query = mysqli_query($DB_CONN, "SELECT id FROM users WHERE SHA1(id) = '{$temp_id}'");
        if (mysqli_num_rows($hash_query) > 0) {
            $user_id = mysqli_fetch_assoc($hash_query)['id'];
        } else {
            $user_id = $_SESSION['temp_id'] ?? null;
        }
    } elseif (!empty($_SESSION['temp_id'])) {
        $user_id = $_SESSION['temp_id'];
    } elseif (!empty($userinfo['id'])) {
        $user_id = $userinfo['id'];
    }
    
    if (empty($user_id)) {
        call_alert(305); // Invalid resend request
        break;
    }
    
    // Resend OTP
    if (sendotp($page, $user_id)) {
        $data['result'] = true;
    }
    break;


  case 'transfer':
  $om = $amount = $_POST['amount'];
  $transferto=strtolower($_POST['transferto']);
  $transferfrom=strtolower($userinfo['username']);
  $payment_method_id=$_POST['payment_method_id'];
  $comment = $_POST['comment'];
  $data['result'] = false;

  if($_POST['amount'] && $_POST['transferto'] && $_POST['payment_method_id'] && $userinfo['id']) {
        $to_detail = str_replace(array("#username#", "#comment#"), array($transferto, $comment), $transfer_settings['to_memo']);
        $from_detail = str_replace(array("#username#", "#comment#"), array($transferfrom, $comment), $transfer_settings['from_memo']);
        extract(gettransfer($amount, $transferto, $payment_method_id));
            $a1 = code_check_new('transfer', $transfer_settings);
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
    break;
    case 'exchange_list':
    $ex = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = exchange.from_currency) as fcurrency, (SELECT name from currencies where id = exchange.to_currency) as tcurrency FROM `exchange`");
    $data['result'] = true;
    while ($exchange = mysqli_fetch_assoc($ex)) {
      $data['exchange'][] = $exchange;
    }
    break;
    case 'exchange':
    $from_currency=$_POST['from_currency'];
    $to_currency=$_POST['to_currency'];
    $from_amount=$_POST['from_amount'];
    $check = $_POST['check'];
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
    $data['result'] = false;
    if($from_currency && $to_currency && $from_amount) {
    $rate = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = exchange.from_currency) as fcurrency, (SELECT name from currencies where id = exchange.to_currency) as tcurrency, (SELECT symbol from currencies where id = exchange.from_currency) as fsymbol, (SELECT symbol from currencies where id = exchange.to_currency) as tsymbol from exchange where from_currency = '{$from_currency}' and to_currency = '{$to_currency}' order by id desc limit 1");
    if(mysqli_num_rows($rate)) {
      $exchange = mysqli_fetch_assoc($rate);
      $balance = $userinfo['balances'][$from_currency];
      $to_amount = $from_amount;
      if(isown()) {
        if (strpos($cur, "USD") === false)
            $to_amount = currencytousd($to_amount, $exchange['fsymbol']);
        $to_amount = currencytousd($to_amount, $exchange['tsymbol'], false);
      }
      $to_amount = $to_amount*((100-$exchange['rate'])/100);
      $fee = $from_amount - $to_amount;
      if($check == "1") {
      if($balance >= $from_amount) {
          $a1 = code_check_new('exchange', $exchange_settings);
          if($a1) {
            add_balance($userinfo['id'], $from_currency, -$from_amount);
            add_balance($userinfo['id'], $to_currency, $to_amount);
            $rep = array("#from_currency#", "#to_currency#", "#from_amount#", "#to_amount#");
            $with = array($exchange['fcurrency'], $exchange['tcurrency'], $from_amount, $to_amount);
            $detailf = str_replace($rep, $with, $exchange_settings['from_memo']);
            $detailt = str_replace($rep, $with, $exchange_settings['to_memo']);
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`user_id`, `amount`, `fee`, `txn_type`, `am_type`, `payment_method_id`, `detail`, `ip`) VALUES ('{$userinfo['id']}','{$from_amount}', '0', 'exchange', 'out', '{$from_currency}', '{$detailf}', '{$ip}')");
            mysqli_query($DB_CONN, "INSERT INTO `transactions`(`user_id`, `amount`, `fee`, `txn_type`, `am_type`, `payment_method_id`, `detail`, `ip`) VALUES ('{$userinfo['id']}','{$to_amount}', '{$fee}', 'exchange', 'in', '{$to_currency}', '{$detailt}', '{$ip}')");
            sendmail("exchange_user_notification", $userinfo['id'], $userinfo, array("from_currency" => $exchange['fcurrency'], "to_currency" => $exchange['tcurrency'], "amount_from" => $from_amount, "amount_to" => $to_amount));
            call_alert(81); //Balance Exchanged Successfully
            $data['result'] = true;
          }
      } else
        call_alert(82); //Insufficient Balance
    } else {
        $data['rates'] = true;
        if(isown())
            $data['receive_amount'] = fiat($to_amount, 0, $exchange['tsymbol']);
        else
            $data['receive_amount'] = fiat($to_amount);
      //  $data['receive_fee'] = fiat($fee);   
    }
      } else
        call_alert(83); // Invalid Selection
    }
    break;
    case 'withdraw':
if (!empty($userinfo['id']) && !empty($_POST['amount']) && !empty($_POST['payment_method_id'])) {
    $withdraw = $_POST['amount'];
    $payment_method_id = $_POST['payment_method_id'];
    $user_id = $userinfo['id'] ;
    
    updateuserinfo();
    
    if ($withdraw_settings['address']) {
        $custom_address = $_POST['address'] ?? null;
        $cdata = check_withdraw($withdraw, $payment_method_id, $custom_address);
    }
    else
    {
        $cdata = check_withdraw($withdraw, $payment_method_id);
    }

    if (!$cdata[0]):
        $withdraw = $cdata[1];
        $fee = $cdata[2];
        $address = $cdata[3];
        $method = $cdata[4];
    if($withdraw_settings['multiple_account']) {
        $li = mysqli_query($DB_CONN, "SELECT DISTINCT user_id FROM `login_report` WHERE ip in (SELECT DISTINCT ip FROM `login_report` WHERE user_id = '{$userinfo['id']}') AND user_id != '{$userinfo['id']}' ");
        if (mysqli_num_rows($li) > 0) {
        if (!$userinfo['allowed_multiple']) {
            $submit = false;
            call_alert(163); // Your withdrawals are Blacklisted as you have created more than 1 account from the same device.
        } else
            $submit = true; // Allow withdrawal if the user is allowed multiple accounts.
        }
    }
    $ww = code_check_new('withdraw', $withdraw_settings);
    if($ww) {
      $memo = str_replace(array("#address#","#method#"), array($address ,$method['name']), $withdraw_settings['request_memo']);
      $w = mysqli_query($DB_CONN,"INSERT into transactions (user_id, address, amount, fee, payment_method_id, ip, txn_type, status, am_type, detail) values('{$userinfo['id']}', '{$address}', '{$withdraw}', '{$fee}', '{$payment_method_id}', '{$ip}', 'withdraw', '0', 'out','{$memo}')");
      $wid = mysqli_insert_id($DB_CONN);
      $amount = $withdraw;
      if($w) {
        add_balance($user_id, $payment_method_id, -$amount);
        updateuserinfo();
        call_alert(63);//Withdraw Request Received Successfully
        sendmail("withdraw_request_user_notification", $userinfo['id'], $userinfo, array('amount' => $withdraw, 'account' => $method['name'], 'address' => $address, 'dt' => $dt));
        $data['id'] = $wid;
        $data['address'] = $address;
        $data['amount'] = fiat($amount);
        $data['fee'] = fiat($fee);
        $data['currency'] = $method['name'];
        $data['icon'] = $siteURL . '/images/icons/' . $method['id'] . '.svg';
        $data['detail'] = $memo;
        $delay = $a = true;
        $day = date('l');
        if($withdraw_settings['instantwithdraw_2fa'] && !$userinfo['2fa'])
          $a = false;
        if($withdraw_settings['instantwithdraw_weekdays'] && ($day == "Sunday" || $day == "Saturday"))
          $a = false;
        if($withdraw_settings['delay_instant_withdraw'] || $userinfo['delay']){
          $delay = false;
          $data['delay'] = $withdraw_settings['delay_instant_withdraw'];
        }
        if($withdraw_settings['withtype'] && $userinfo['auto_withdraw'] != 3 && $a && $delay) {
        $o_am = $amount;
        $amount = $amount-$fee;
        if(!$withdraw_settings['max_daily_withdraw_limit'])
          $withdraw_settings['max_daily_withdraw_limit'] = 10000000000000000000;
        if($amount < $withdraw_settings['max_daily_withdraw_limit']) {
          if($userinfo['wi_pm_id'] && !in_array($method['wi_pm_id'], array(2, 6, 17)))
            $method['wi_pm_id'] = $userinfo['wi_pm_id'];
         // $tx_id = sendwithdraw($method['wi_pm_id'], $address, $amount, $wid, $method['symbol']);
          $processor = new PaymentProcessor($method['wi_pm_id'], $method['system'], $DB_CONN,$preferences, $siteURL,$g_alert, $main_link,$siteName);
              $tx_id = $processor->processWithdrawal($address, $amount, $wid, $method['symbol']);
          if($tx_id) {
            $memo = str_replace(array("#address#","#method#", "#txn_id#"), array($address ,$method['name'], $tx_id), $withdraw_settings['instant_memo']);
            mysqli_query($DB_CONN, "UPDATE `transactions` set status = 1, txn_id = '{$tx_id}', detail = '{$memo}' where id = {$wid}");
            $with = mysqli_fetch_assoc(mysqli_query($DB_CONN,"SELECT tx_url from transactions where id = {$wid}"));
            if($method['wi_pm_id'] != 10 && $method['wi_pm_id'] != 11)
              sendmail("withdraw_user_notification", $userinfo['id'], $userinfo, array('amount' => $amount, 'txn_id' => $tx_id, 'account' => $method['name'], 'tx_url' => $with['tx_url'], 'address' => $address));
            $data['tx'] = $tx_id;
            $data['tx_url'] = $with['tx_url'];
            $data['detail'] = $memo;
            call_alert(61, $tx_id);
            // withdraw_redirect($wid, $method, $tx_id);
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
          // withdraw_redirect($wid, $method);
      }
      } else
        call_alert(65); //Some Error. Please try again
      }
      endif;
   } else {
        $data['status']=false;
        $data['result']=false;
        $data['message'] = "Invalid fields";
    }
    break;
    case 'recover':
    // Handle initial password reset request
    if (isset($_POST['email'])) {
        $cc = captcha_check('forgot');
        if($cc) {
            $ui = mysqli_query($DB_CONN, "SELECT * from users where email = '{$_POST['email']}' and is_admin = 0");
            if (mysqli_num_rows($ui)) {
                $user = mysqli_fetch_assoc($ui);
                $uid = $user['id'];
                if ($user_settings['forgot_email_code']) {
                    $data['email'] = $user['email'];
                    $data['temp_id'] = $uid;
                    sendotp("Password Reset");
                    call_alert(22); // Please check your email for OTP
                    $data['require_otp'] = true;
                } elseif ($user_settings['forgot_email_link']) {
                    $code = genPinCode(32);
                    mysqli_query($DB_CONN, "UPDATE users set code = '{$code}', code_timestamp = CURRENT_TIMESTAMP where id = '{$uid}'");
                    sendmail("forgot_password_confirm", $uid, $user, array('code' => $code));
                    call_alert(22); // Reset link sent successfully
                } else {
                    $pass = genPinCode(8);
                    $code = password_hash($pass, PASSWORD_DEFAULT);
                    mysqli_query($DB_CONN, "UPDATE users set password = '{$code}' where id = '{$uid}'");
                    sendmail("forgot_password", $uid, $user, array('pass' => $pass));
                    call_alert(22); // Password sent to email
                }
            } else
                call_alert(22); // Generic success message
        }
    }
    // Handle verification of reset code/link
    elseif (isset($_POST['code'])) {
        $ui = mysqli_query($DB_CONN, "SELECT * from users where code = '{$_POST['code']}' and code_timestamp > NOW() - INTERVAL 15 MINUTE");
        if (mysqli_num_rows($ui)) {
            $user = mysqli_fetch_assoc($ui);
            $_SESSION['temp_id'] = $user['id'];
            $data['result'] = true;
            $data['allow_password_reset'] = true;
        } else {
            call_alert(1); // Invalid or expired link
        }
    }
    // Handle email verification code
    elseif (isset($_POST['email_code']) && $_SESSION['temp_id']) {
        if ($_POST['email_code'] == $_SESSION['email_code']) {
            $data['result'] = true;
            $data['allow_password_reset'] = true;
        } else
            call_alert(30); // Invalid code
    }
    // Handle password reset
    elseif (isset($_POST['password']) && isset($_POST['confirm_password']) && $_SESSION['temp_id']) {
        if ($_POST['password'] != $_POST['confirm_password']) {
            call_alert(10); // Passwords don't match
            break;
        }
        if (strlen($_POST['password']) < 8) {
            call_alert(6); // Password too short
            break;
        }
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        mysqli_query($DB_CONN, "UPDATE users set password = '{$password}' where id = '{$_SESSION['temp_id']}'");
        unset($_SESSION['temp_id']);
        unset($_SESSION['email_code']);
        unset($_SESSION['email']);
        call_alert(32); // Password changed successfully
    } else {
        $data['result'] = false;
        $data['message'] = 'Invalid request';
    }
    break;


case 'tfa':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestData = $_POST;
        // Enable 2FA
        if (isset($requestData['enable_2fa']) && isset($requestData['code']) && isset($requestData['secret'])) {
            $secret = $requestData['secret'];
            if (TokenAuth6238::verify($secret, $requestData['code'])) {
                $updateQuery = "UPDATE users SET 2fa = '{$secret}' WHERE id = '{$user_id}'";
                $updateResult = mysqli_query($DB_CONN, $updateQuery);
                

                if ($updateResult) {

                    sendmail("change_account", $user_id, $userinfo, array('2FA' => 'enabled'));
                     call_alert(36); //2FA Enabled
                    updateuserinfo();

                } else {
                    http_response_code(500);
                    $data['success'] = false;
                    $data['error'] = 'Database update failed';
                }

            } else {
               call_alert(28); //2FA Invalid
            }
            break;
        }

        

        // Disable 2FA

        if (isset($requestData['disable_2fa']) && isset($requestData['code'])) {
            if (empty($userinfo['2fa'])) {
                http_response_code(400);
                $data['success'] = false;
                $data['error'] = '2FA is not enabled';
                break;
            }
            if (TokenAuth6238::verify($userinfo['2fa'], $requestData['code'])) {
                $updateQuery = "UPDATE users SET 2fa = '' WHERE id = '{$user_id}'";
                $updateResult = mysqli_query($DB_CONN, $updateQuery);
                if ($updateResult) {
                    sendmail("change_account", $user_id, $userinfo, array('2FA' => 'disabled'));
                     call_alert(37); //2FA Disabled
                    updateuserinfo();
                } else {
                    http_response_code(500);
                    $data['success'] = false;
                    $data['error'] = 'Database update failed';
                }
            } else {
                call_alert(28); //2FA Invalid
            }
            break;
        }
    }
    break;
    case 'wallets':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $validator = new CryptoValidator();
        $currencyIdMap = [
            'bitcoin' => 1,
            'ethereum' => 4,
            'tron' => 14,
            'epaycore' => 17,
            'usdtbep20' => 19,
            'usdt' => 19,
            'usdttrc20' => 18,
            'binance' => 20,
            'usdterc20' => 21,
            'primarywallet' => 21,
            'usdtpolygon' => 23,
            'matic' => 24
        ];

        $isValid = true;
        $walletUpdates = array();
        $validationErrors = array();
        $requestData = $_POST;
        
        // Get current user wallets
        $currentWallets = json_decode($userinfo['wallets'], true) ?: array();

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
            
            // Check if wallet can be updated
            $canUpdate = true;
            if (!$user_settings['can_change_wallet_acc']) {
                // If wallet change is restricted and wallet already exists, skip update
                if (isset($currentWallets[$currencyKey]) && !empty($currentWallets[$currencyKey])) {
                    $canUpdate = false;
                    continue;
                }
            }
            
            // Only proceed with validation if update is allowed
            if ($canUpdate && $validatorCurrencyId && $psCurrencyId !== null) {
                $validationResult = $validator->validate($validatorCurrencyId, $value, 'address');
                
                if (!$validationResult['valid']) {
                    $isValid = false;
                    call_alert(39, " " . $ps[$psCurrencyId]['name']); // Invalid address for Wallet
                    $data[$currencyKey] = $value;
                    break;
                }
                
                $walletUpdates[$currencyKey] = $value;
            }
        }

        // If no updates are needed, return success
        if (empty($walletUpdates))
            break;

        // Merge new wallet updates with existing wallets
        $updatedWallets = array_merge($currentWallets, $walletUpdates);
        $walletsJson = json_encode($updatedWallets);
        $sanitizedData = db_filter(['wallets' => $walletsJson]);
        
        $updateQuery = "UPDATE users SET wallets = '{$sanitizedData['wallets']}' 
                       WHERE id = '{$user_id}'";
        $updateResult = mysqli_query($DB_CONN, $updateQuery);

        if ($updateResult) {
            sendmail("change_account", $user_id, $userinfo, $walletUpdates);
            call_alert(38);
            $data['wallets'] = $updatedWallets;
            updateuserinfo();
            getUserProfileData($userinfo);
        } else {
            http_response_code(500);
            $data['success'] = false;
            $data['error'] = 'Database update failed';
        }
        break;
    }

    // Handle unsupported methods
    http_response_code(405);
    $data['success'] = false;
    $data['error'] = 'Method not allowed';
    break;
    
case 'get_profile':
    try {
        getUserProfileData($userinfo);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'result' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
    break;
    case 'dashboard':
    $logs = array();
    $limit = $infobox_settings['dlogs'] ?: 5;
    $mpa = mysqli_query($DB_CONN, "SELECT title, content, timestamp as date FROM `notifications` WHERE user_id='{$userinfo['id']}' ORDER BY `date` DESC LIMIT {$limit}");
    while ($mpaa = mysqli_fetch_assoc($mpa)) {
        $logs[] = $mpaa;

    }
    $data['logs'] = $logs;
    $trans = array();
    $limit = $infobox_settings['dtransactions'] ?: 5;
    $transa = mysqli_query($DB_CONN, "SELECT *,(SELECT symbol from currencies WHERE id = transactions.payment_method_id) as symbol FROM `transactions` WHERE user_id='{$userinfo['id']}' ORDER BY `created_at` DESC LIMIT {$limit}");
    while ($transaa = mysqli_fetch_assoc($transa)) {
        $transaa['date'] = date("F j, Y, g:i a" ,strtotime($transaa['created_at']));
        $transaa['cid'] = $transaa['payment_method_id'];
          $transaa['icon'] = $siteURL . '/images/icons/' . $transaa['payment_method_id'] . '.svg';
        $transaa['txn_type'] = ucfirst($transaa['txn_type']);;
        $trans[] = $transaa;

    }
   $data['transactions'] = $trans;
    break;
case 'profile':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $userinfo['id'];
         code_check_new('profile', $user_settings);
        
        $post = $_POST;
        $updates = array();
        // Handle password update

        if(!empty($post['new_password'])) {
            if(!password_verify($post['current_password'], $userinfo['password'])) {
                call_alert(34);
                break;
            }
            if ($_POST['new_password'] != $_POST['confirm_password']) {
                call_alert(10);
                break;
            }
            if($a && strlen($_POST['new_password'])<8) {
                call_alert(6);
                break;
            }
            $updates['password'] = password_hash($post['new_password'], PASSWORD_DEFAULT);
        }

         if($user_settings['pin_code'] && !empty($post['new_transaction_code'])) {
            // Verify current transaction code
            if(empty($post['current_transaction_code']) || $post['current_transaction_code'] != $userinfo['pin_code']) {
                call_alert(35); // Assuming error code 35 for incorrect transaction code
                break;
            }
            // Confirm transaction code match
            if($post['new_transaction_code'] != $post['confirm_transaction_code']) {
                call_alert(11); // Assuming error code 11 for mismatched transaction codes
                break;
            }
            // Check minimum length if required
            if(strlen($post['new_transaction_code']) < 4) { // Adjust length as needed
                call_alert(7); // Assuming error code 7 for transaction code too short
                break;
            }
            $updates['pin_code'] = $post['new_transaction_code'];
        }

        // Clean up POST data
         unset($post['new_password']);
        unset($post['current_password']);
        unset($post['confirm_password']);
        unset($post['current_transaction_code']);
        unset($post['new_transaction_code']);
        unset($post['confirm_transaction_code']);
        unset($post['2fa_code']);
        unset($post['pin_code']);
        unset($post['confirm']);
        unset($post['email_code']);

        // Validate and prepare profile updates
        $allowedFields = ['fullname', 'phone', 'address', 'city', 'state', 'zip', 'country', 'question', 'answer', 'photo'];
        foreach ($allowedFields as $field) {
            if (isset($post[$field])) {
                $updates[$field] = $post[$field];
            }
        }

        // Handle email update if allowed
        if($user_settings['can_change_email'] && isset($post['email'])) {
            $updates['email'] = $post['email'];
        }

        // If no updates are needed
        if (empty($updates)) {
            call_alert(32);
            break;
        }

        // Build and execute update query
        $setClauses = array();
        foreach ($updates as $field => $value) {
            $sanitizedValue = db_filter([$field => $value])[$field];
            $setClauses[] = "`$field` = '$sanitizedValue'";
        }
        $setClause = implode(', ', $setClauses);
        
        $update_result = mysqli_query($DB_CONN, "UPDATE users SET $setClause WHERE id = '$user_id'");
        
        if($update_result) {
            sendmail("change_account", $user_id, $userinfo, $updates);
            updateuserinfo();
            call_alert(31);
            getUserProfileData($userinfo);
        } else
            call_alert(5);
        break;
    }

    // Handle unsupported methods
    call_alert(3);
    break;
    case 'transactions':
    $rows = array();
    $w = "";
    if(isset($_POST['txn_type']) && $_POST['txn_type']) {
      $w .= " and txn_type = '{$_POST['txn_type']}'";
    }
    if(isset($_POST['cid']) && $_POST['cid']) {
      $w .= " and payment_method_id = '{$_POST['cid']}'";
    }
    if(isset($_POST['from']) && $_POST['from']) {
      $from = date("Y-m-d", strtotime($_POST['from']));
      $w .= " and date(created_at) >= '{$from}'";
    }
     if(isset($_POST['search']) && $_POST['search']) {
        $_POST['search'] = str_replace(" ", "%", $_POST['search']);
        $w .= " and (amount like '%{$_POST['search']}%' or txn_id like '%{$_POST['search']}%' or address like '%{$_POST['search']}%' or id like '%{$_POST['search']}%') ";
    }
    if(isset($_POST['to']) && $_POST['to']) {
      $to = date("Y-m-d", strtotime($_POST['to']));
      $w .= " and date(created_at) <= '{$to}'";
    }
    $d = mysqli_query($DB_CONN, "SELECT * FROM `transactions` where user_id = '{$userinfo['id']}' {$w}") or die(mysqli_error($DB_CONN));
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['transactions'] ?: 20;
    $currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    unset($_POST['page']);
    $urlPattern = '?'.http_build_query($_POST).'&page=(:num)';
    // $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
   // $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT *, (select name from packages where id = transactions.package_id) as pname,
    (SELECT name from currencies where id = transactions.payment_method_id) as currency, (SELECT symbol from currencies where id = transactions.payment_method_id) as symbol FROM `transactions` where user_id = '{$userinfo['id']}' {$w} order by id desc limit {$start}, {$itemsPerPage}") or die(mysqli_error($DB_CONN));
    while($de = mysqli_fetch_assoc($d)) {
      $de['datetime'] = date("F j, Y, g:i a", strtotime($de['created_at'])); 
      $de['type'] = ucfirst($de['txn_type']);
       $de['amount'] = fiat($de['amount']);
      $de['cid'] = $de['payment_method_id'];
      $de['icon'] = $siteURL . '/images/icons/' . $de['payment_method_id'] . '.svg';
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
        $de['delay_time'] = date("F j, Y, g:i a", $next);
        $de['delay_timer'] = $next;
      }
      if($wi)
        $de['id'] = md5($de['id']);
      $rows[] = $de;
    }
    $txn_type = [];
    $ty = mysqli_query($DB_CONN, "SELECT DISTINCT txn_type FROM transactions WHERE user_id = '{$userinfo['id']}'") or die(mysqli_error($DB_CONN));
    while($txnType = mysqli_fetch_assoc($ty)){
      $txn_type[] = $txnType['txn_type'];
    }
      $data['txn_type'] = $txn_type;
      $data['data'] = $rows;
      $data['details'] = [
    'totalItems' => $totalItems, // Total number of transactions available
    'currentPage' => $currentPage, // Current page number
    'itemsPerPage' => $itemsPerPage, // Number of items per page
];
  break;
case 'investments':
  $rows = array();
  $i = 1;
  $d = mysqli_query($DB_CONN, "SELECT * FROM `package_deposits` where user_id = '{$userinfo['id']}' and status = 1 ");
  $totalItems = mysqli_num_rows($d);
  $itemsPerPage = $infobox_settings['packages'] ?: 20;
  $currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1;
  $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
  $urlPattern = '?page=(:num)';
  $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
  $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
//  $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
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
        $exclude_days[] = 'Saturday';
        $exclude_days[] = 'Sunday';
        if($last_day == 'Friday')
            $next += (86400*2);
    }
    if($package['earnings_mon_fri'] == 2) {
        $earning_days = json_decode($package['earning_days'], true);
        $days_loop = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
        foreach ($days_loop as $day) {
            if($earning_days[$day] != 'on') {
                $exclude_days[] = $day;
            }
        }
    }
    $exclude_days = array_unique($exclude_days);
    $next_day = date('l', $next);
    while(in_array($next_day, $exclude_days)) {
        $next += 86400; // Add one day
        $next_day = date('l', $next);
    }
    $current_time = time();
    if ($next <= $current_time && $package['avail'] < $package['duration']) {
        $next = $current_time + $package['diff_in_seconds'];
        $next_day = date('l', $next);
        while(in_array($next_day, $exclude_days)) {
            $next += 86400; // Add one day
            $next_day = date('l', $next);
        }
    } else if ($package['avail'] >= $package['duration']) {
        $next = 0;
    }
    if ($next > 0) {
        $dtCurrent = DateTime::createFromFormat('U', $current_time);
        $dtCreate = DateTime::createFromFormat('U', $next);
        $diff = $dtCurrent->diff($dtCreate);
        $interval = "";
        if ($diff->d > 0) $interval .= $diff->d . " days ";
        if ($diff->h > 0) $interval .= $diff->h . " hours ";
        if ($diff->i > 0) $interval .= $diff->i . " minutes ";
        if ($diff->s > 0 || empty($interval)) $interval .= $diff->s . " seconds";
        $interval = trim($interval);
    } else {
        $interval = "0";
    }
    $expiry_timestamp = addDays($de['datetime'], $package['duration'], $package['diff_in_seconds'], $exclude_days);
    $de['expiry'] = date("F j, Y, g:i a", $expiry_timestamp);
    $de['last_earning_time'] = $de['avail'] > 0 ? date("F j, Y, g:i a", strtotime($de['last_earningDateTime'])) : 'N/A';
    $de['next_earning_time'] = $next > 0 ? date("F j, Y, g:i a", $next) : 'Completed';
    $de['next_earning'] = $interval;
    $de['description'] = $package['description'] ?? null;
    $de['principal_return']  =  $package['principal_return'];
    $de['principal_hold']  =  $package['principal_hold'];
    $de['re_invest'] = $details['auto_reinvest'];
    $de['datetime'] = date("F j, Y, g:i a", strtotime($de['datetime']));
    $pro = $plan['percent_max'] / 100*$de['amount'];
    $de['profit'] = fiat($pro);
    $tpro = $pro*$package['duration'];
    $de['total_profit'] = fiat($tpro);
    $de['percentage'] = $plan['percent_max'];
    $de['total_percentage'] = $plan['percent_max']*$package['duration'];
    $de['name'] = $package['name'];
    $de['amount'] = fiat($de['amount']);
    $de['cid'] = $de['payment_method_id'];
    $de['icon'] = $siteURL . '/images/icons/' . $de['payment_method_id'] . '.svg';
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
    if ($release) {
    // Add the release_info details to each investment
    $release_calc = calc_release($de);

    $de['release_info'] = array(
        'can_release' => true, // Since $release is already true
        'fee' => $release_calc['fee'],
        'available_amount' => $release_calc['amount']
    );
    
    $de['confirm_release'] = true;
    if ($details['allowprincipalfull']) {
        $de['release_info']['full_principal_only'] = true;
        $de['release_info']['input_amount_allowed'] = false;
    } else {
        $de['release_info']['full_principal_only'] = false;
        $de['release_info']['input_amount_allowed'] = true;
        $de['release_info']['min_amount'] = 0; // Set minimum withdrawal amount if applicable
        $de['release_info']['max_amount'] = $release_calc['amount']; // Maximum is the available amount
    }
} else {
    $de['release_info'] = array(
        'can_release' => false,
        'fee' => 0,
        'available_amount' => 0
    );
    $de['confirm_release'] = false;
}
    // $de['remaining'] = secondsToWords($rem);
      $de['id'] = md5($de['id']);
    $rows[] = $de;
    $i++;
  }
  $data['investments'] = $rows;
  $data['details'] = [
    'totalItems' => $totalItems, // Total number of transactions available
    'currentPage' => $currentPage, // Current page number
    'itemsPerPage' => $itemsPerPage, // Number of items per page
];
break;
    case 'release_info':
    if (isset($_POST['id'])) {
        $chk = mysqli_query($DB_CONN, "SELECT pd.*, 
            (SELECT name from currencies where id = pd.payment_method_id) as currency,
            (SELECT sum(amount) FROM transactions WHERE txn_type = 'earning' and ref_id = pd.id) as earned 
            FROM package_deposits pd 
            WHERE md5(id) = '{$_POST['id']}' AND status = 1 AND user_id = '{$userinfo['id']}'");
            
        if (mysqli_num_rows($chk) > 0) {
            $dep = mysqli_fetch_assoc($chk);
            $release_calc = calc_release($dep, $_POST['amount']);
            
            $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$dep['package_id']}'"));
            $details = json_decode($package['details'], true);
            $data = array(
                'deposit' => array(
                    'id' => $dep['id'],
                    'amount' => $dep['amount'],
                    'currency' => $dep['currency'],
                    'earned' => $dep['earned'],
                    'datetime' => $dep['datetime']
                ),
                'release' => array(
                    'can_release' => !$release_calc['a'],
                    'fee' => $release_calc['fee'],
                    'available_amount' => $release_calc['amount']
                ),
            );
        } else {
            call_alert(65); // Some Error. Please try again
        }
    } else {
        call_alert(65); // Some Error. Please try again
    }
    break;
case 'reinvest':
  if(isset($_POST['id']) && $_POST['id']) {
    $id = $_POST['id'];
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
    }else {
    $data['result'] = false;
  }
    
    break;
  case 'release':
    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        $chk = mysqli_query($DB_CONN, "SELECT *, (SELECT name from currencies where id = package_deposits.payment_method_id) as currency from package_deposits where md5(id) = '{$id}' and status = 1 and user_id = '{$userinfo['id']}'");
        if (mysqli_num_rows($chk) > 0) {
            $dep = mysqli_fetch_assoc($chk);
            if(!isset($_POST['amount']))
                $_POST['amount'] = $dep['amount'];
            extract(calc_release($dep, $_POST['amount']));
            if (!$a) {
                if (!$details['allowprincipalfull'] && $_POST['amount'] != $dep['amount']) {
                    mysqli_query($DB_CONN, "UPDATE package_deposits set amount = amount - {$_POST['amount']} where id = '{$dep['id']}'");
                } else {
                    mysqli_query($DB_CONN, "UPDATE package_deposits set status = 0 where id = '{$dep['id']}'");
                }
                $amount = $_POST['amount'] - $fee;
                $package = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from packages where id = '{$dep['package_id']}'"));
                $details = json_decode($package['details'], true);
                
                if ($details['release_to_pending']) {
                    $field = strtolower(str_replace(' ', '', $dep['currency']));
                    $address = $userinfo['wallets'][$field];
                    $memo = str_replace(array("#address#", "#method#"), array($address, $dep['currency']), $withdraw_settings['request_memo']);
                    mysqli_query($DB_CONN, "INSERT into transactions (user_id, address, amount, fee, payment_method_id, ip, txn_type, status, am_type, detail) values('{$userinfo['id']}', '{$address}', '{$amount}', '0', '{$dep['payment_method_id']}', '{$ip}', 'withdraw', '0', 'out', '{$memo}')");
                } else {
                    add_balance($userinfo['id'], $dep['payment_method_id'], $amount);
                    $detail = str_replace(array("#amount#", "#method#", "#plan#", "#fee#"), array(fiat($amount), $dep['currency'], $package['name'], $fee), $deposit_settings['investment_released']);
                    mysqli_query($DB_CONN, "INSERT into transactions (user_id, amount, fee, payment_method_id, ip, txn_type, detail, ref_id) values('{$userinfo['id']}', '{$amount}', '{$fee}', '{$dep['payment_method_id']}', '{$ip}', 'release', '{$detail}', '{$dep['id']}')");
                }
                call_alert(41); // Deposit Amount Released Successfully
            } else
                call_alert(65); // Some Error. Please try again
        } else
            call_alert(65);
    }
    break;

case 'affiliates':
    $rows = array();
    $d = mysqli_query($DB_CONN, "SELECT fullname,email,username,country,oauth_uid, (SELECT sum(amount) from package_deposits where user_id = users.id and status = 1) as invested FROM `users` where sponsor = '{$userinfo['id']}' order by id desc");
    $totalItems = mysqli_num_rows($d);
    $itemsPerPage = $infobox_settings['affiliates'] ?: 20;
    $currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $i = ($currentPage * $itemsPerPage)-$itemsPerPage+1;
    $urlPattern = '?page=(:num)';
    $start = ($currentPage*$itemsPerPage)-$itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    $paginator = new Paginator($totalItems, $itemsPerPage, $currentPage, $urlPattern);
    $d = mysqli_query($DB_CONN, "SELECT 
    u.fullname,
    u.email,
    u.username,
    u.country,
    u.oauth_uid,
    pd.payment_method_id,
    c.name AS currency,
    c.symbol AS symbol,
    (SELECT sum(amount) 
     FROM package_deposits 
     WHERE user_id = u.id AND status = 1) as deposit
FROM users u
LEFT JOIN (
    SELECT DISTINCT user_id, payment_method_id 
    FROM package_deposits
) pd ON pd.user_id = u.id
LEFT JOIN currencies c ON c.id = pd.payment_method_id
WHERE u.sponsor = '{$userinfo['id']}' 
ORDER BY u.id DESC 
LIMIT {$start}, {$itemsPerPage}");
while($de = mysqli_fetch_assoc($d)) {
    $user = $de; 
    if($de['payment_method_id'])
    $user['icon'] = $siteURL . '/images/icons/' . $de['payment_method_id'] . '.svg';
    $user['deposit'] = fiat($de['deposit']);  
    $user['status'] = $de['deposit'] ? "1" : "0";  // Add status
    $rows[] = $user;
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
      $levels[$i]['earning'] = fiat(mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT COALESCE(sum(amount), 0) from transactions where txn_type = 'referral' and user_id = '{$userinfo['id']}' and ref_id in ($s_id)"))[0]);
      $r = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT count(DISTINCT user_id) as c, COALESCE(sum(amount), 0) as a FROM `package_deposits` WHERE user_id in ($s_id) and (status = 1 or last_earningDateTime) "));
      $levels[$i]['active_users'] = $r['c'];
      $levels[$i]['deposit'] = fiat($r['a']);
    }
    $ref_pms = array();
    $r = mysqli_query($DB_CONN, "SELECT payment_method_id, (SELECT name from currencies where id = transactions.payment_method_id) as name, sum(amount) as total_amount FROM `transactions` WHERE txn_type = 'referral' and user_id = '{$userinfo['id']}' GROUP by payment_method_id ");
    while ($rec = mysqli_fetch_assoc($r)) {
        $rec['icon'] = $siteURL . '/images/icons/' . $rec['payment_method_id'] . '.svg';
      $ref_pms[] = $rec;
    }
    $data['levels'] = $levels;
    $data['referral_pms'] = $ref_pms;
    $data['users'] = $rows;
    $data['details'] = [
      'totalItems' => $totalItems,
      'currentPage' => $currentPage,
      'itemsPerPage' => $itemsPerPage
    ];


break;

case 'claim_faucet':
if($userinfo['id'] && $_POST['faucet']) {
  $amounts = faucets_list();
  if(count($amounts)) {
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
            $data['result'] = true;
            if($value['type']=='0')
              add_balance($userinfo['id'], $package['limit_currency'], $am);
            else
              add_faucet($userinfo['id'], $package['limit_currency'], $am);
          }
        }
      }
    }
    if($alert) {
      if(!$data['result'])
        $data['result']=false;
      $data['message']=$alert_message;
    }
  } else {
    $data['result'] = false;
    $data['message'] = "No Faucet Found";
  }
}
break;

 case 'check_withdraw':
    $data = ['status' => false];
    
    if (!empty($_POST['id'])) {
        $stmt = $DB_CONN->prepare("SELECT txn_id, status, tx_url, detail 
            FROM transactions 
            WHERE id = ? AND user_id = ? 
            ORDER BY id DESC LIMIT 1");
            
        $stmt->bind_param("si", $_POST['id'], $userinfo['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction = $result->fetch_assoc();
            $data = [
                'status' => (!empty($transaction['txn_id']) && $transaction['status'] == 1),
                'tx' => $transaction['txn_id'],
                'tx_url' => $transaction['tx_url'],
                'detail' => $transaction['detail']
            ];
        }
        $stmt->close();
    }
    break;

  case 'currencies':
     $data['currencies'] = $ps;
     break;
  case 'invest':
  if ($_POST['package_id'] && is_numeric($_POST['amount']) && $_POST['payment_method_id'] && $userinfo['id'])
{
    $user_id = $userinfo['id'];
  $package_id=$_POST['package_id'];
  $deposit=$_POST['amount'];
  $credit = $deposit;
  $payment_method_id=$_POST['payment_method_id'];
  $package_= mysqli_query($DB_CONN,"SELECT * from packages where status=1 AND id='{$package_id}'") or die(mysqli_error($DB_CONN));
  if(mysqli_num_rows($package_)>0)
  {
  $package=mysqli_fetch_assoc($package_);
  $package_details = json_decode($package['details'], true);
  // Pre-process the data
$accurals = $package['frequency'];
$reinvest = isset($package_details['auto_reinvest']) ? $package_details['auto_reinvest'] : false;
$cashback_bonus_amount = isset($package_details['cashback_bonus_amount']) ? $package_details['cashback_bonus_amount'] : false;
$cashback_bonus_percentage = isset($package_details['cashback_bonus_percentage']) ? $package_details['cashback_bonus_percentage'] : false;

// Process compound details if enabled
$compound = null;
if (isset($package_details['compound_enable']) && $package_details['compound_enable'] === "true") {
    $compound = [
        'compound_end' => $package_details['compound_end'] === "true" ? "Yes" : "No",
        'compound_min' => $package_details['compound_min'],
        'compound_max' => $package_details['compound_max'],
        'compound_percent_min' => $package_details['compound_percent_min'],
        'compound_percent_max' => $package_details['compound_percent_max']
    ];
}

// Process earnings days
$days = "Mon-Sun";
if ($package['earnings_mon_fri'] == 1) {
    $days = "Mon-Fri";
} elseif ($package['earnings_mon_fri'] == 2) {
    $earning_days = json_decode($package['earning_days'], true);
    $days_array = [];
    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
        if (isset($earning_days[$day]) && $earning_days[$day] == 'on') {
            $days_array[] = substr($day, 0, 3);
        }
    }
    $days = implode(',', $days_array);
}
  $plan_name = $package['name'];
 // $days = $package['duration'];
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
  $func = check_deposit($deposit, $package, $plan, $user_id, $method, $acc_id, $package_details, $_POST['compound']);
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
          $DEPOSIT1= mysqli_query($DB_CONN,"INSERT INTO package_deposits (user_id,package_id,plan_id,amount,fee,status,payment_method_id,ip, auto_reinvest, compound) VALUES ('{$userinfo['id']}','{$package_id}','{$PLAN['id']}','{$deposit}','{$fee}',0,'{$acc_id}','{$ip}', '{$_POST['auto_reinvest']}', '{$_POST['compound']}')");
          if ($DEPOSIT1) 
           {
            $deposit = $deposit+$fee;
            $count=false;
            $ID=mysqli_insert_id($DB_CONN);
            if(stripos($payment_method_id, "account_") !== FALSE) {
              if($package_details['accept_account_balance'] == 1) {
                $bal = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT balance FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$acc_id}' limit 1"))[0];
                error_log($bal);
                if($bal < $deposit) {
                  call_alert(59); //Insufficient Balance!
                } else {
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
                    add_balance($user_id, $acc_id, -$credit);
                    updateuserinfo();
                    call_alert(44); //Your Investment from the Account Balance has been active successfully.
                }
              } else
                call_alert(57); //Account Balance for this package not allowed
            } elseif(stripos($payment_method_id, "faucet_") !== FALSE) {
               if($package_details['accept_faucet_balance']) {
                $bal = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT faucet FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$acc_id}' limit 1"))[0];
                if($bal < $deposit) {
                  call_alert(59); //Insufficient Balance!
                } else {
                    $am = $credit;
                    if($deposit_settings['deposit_from_balance_fee'])
                      $fee = ($am/100)*$deposit_settings['deposit_from_balance_fee'];
                    else
                      $fee = 0;
                    $am = $am-$fee;
                    $txnid = $currency.' Faucet Balance';
                    activate_package($ID, $am, $fee, $txnid, $userinfo['id'], $acc_id, $package_id, $PLAN['id'], $method, '', 'invest', $referral_settings['reffrom_balance']);
                    add_faucet($user_id, $acc_id, -$credit);
                    updateuserinfo();
                    call_alert(441); //Your Investment from the Faucet Balance has been active successfully.
                }
              } else
                call_alert(571); //Faucet Balance for this package not allowed 
            }else {
              if($package_details['accept_processings']) {
              //  $data = get_payment_code($pm['id'], $method['symbol'], $ID, $system, $package, $deposit, $method['address']);
                  $processor = new PaymentProcessor(
                        $pm['id'],
                        $system,
                        $DB_CONN,
                        $preferences,
                        $siteURL,
                        $g_alert,
                        $main_link
                    );
                    $data = $processor->generatePaymentCode(
                        $method['symbol'],
                        $ID,
                        $package,
                        $deposit,
                        $userinfo,
                        $method['address']
                    );
                if(!is_array($data))
                  call_alert(65); //Some Error. Please try again
                else {
                $data = [
                        'result' => true,
                        'status' => true,
                        'form' => $data['form'] ?? '',
                        'address' => $data['address'] ?? '',
                        'address_url' => $method['address_url'] . ($data['address'] ?? ''),
                        'amount' => $data['amount'] ?? '',
                        'img' => $data['img'] ?? '',
                        'tag' => $data['tag'] ?? '',
                        'package' => [
                            'id' => $package['id'],
                            'name' => $package['name'],
                            'etype' => $package['etype'],
                            'frequency' => $package['frequency'],
                            'duration' => $package['duration'],
                            'earnings_days' => $days,
                            'principal_return' => $package['principal'],
                            'features' => [
                                'reinvest' => $reinvest,
                                'cashback' => [
                                    'amount' => $cashback_bonus_amount,
                                    'percentage' => $cashback_bonus_percentage
                                ]
                            ],
                            'plans' => []
                        ],
                        'method' => [
                            'id' => $method['id'],
                            'name' => $method['name'],
                            'symbol' => $method['symbol'],
                            'rate' => $method['rate'],
                            'icon' => $siteURL . '/images/icons/' . $method['id'] . '.svg'
                        ]
                    ];
                    
                    // Add plans data
                    $plans_query = mysqli_query($DB_CONN, "SELECT * FROM package_plans WHERE package_id = '{$package['id']}'");
                    while ($plan = mysqli_fetch_assoc($plans_query)) {
                        $planData = [
                            'min_deposit' => floatval($plan['range_min']),
                            'max_deposit' => floatval($plan['range_max'])
                        ];
                        
                        // Handle percentage based on package type
                        if ($package['etype'] == 0) {
                            $planData['percent'] = floatval($plan['percent_max']);
                        } else if ($package['etype'] == 1 || $package['etype'] == 2) {
                            $planData['percent_min'] = floatval($plan['percentage_min']);
                            $planData['percent_max'] = floatval($plan['percentage_max']);
                        }
                        
                        $data['package']['plans'][] = $planData;
                    }
                }
              } else
                call_alert(58); //Payment Processor for this package not allowed
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
  break;
case 'check_invest':
    $data = ['status' => false];
    
    if (!empty($_POST['address'])) {
        $stmt = $DB_CONN->prepare("SELECT txn_id, status, amount, fee 
            FROM package_deposits 
            WHERE address = ? AND user_id = ? 
            ORDER BY id DESC LIMIT 1");
            
        $stmt->bind_param("si", $_POST['address'], $userinfo['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction = $result->fetch_assoc();
            $data = [
                'status' => (!empty($transaction['txn_id']) && $transaction['status'] == 1),
                'txn' => $transaction
            ];
        }
        $stmt->close();
    }
    break;

case 'check_deposit':
    $data = ['status' => false];
    
    if (!empty($_POST['address'])) {
        $stmt = $DB_CONN->prepare("SELECT txn_id, status, amount, fee 
            FROM transactions 
            WHERE address = ? AND user_id = ? 
            ORDER BY id DESC LIMIT 1");
            
        $stmt->bind_param("si", $_POST['address'], $userinfo['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction = $result->fetch_assoc();
            $data = [
                'status' => (!empty($transaction['txn_id']) && $transaction['status'] == 1),
                'txn' => $transaction
            ];
        }
        $stmt->close();
    }
    break;
  case 'deposit':
    if ($_POST['amount'] && is_numeric($_POST['amount']) && $_POST['payment_method_id'] && $userinfo['id']) {
        $deposit = $_POST['amount'];
        $credit = $deposit;
        $payment_method_id = $_POST['payment_method_id'];
        
        // Fetch payment method details
        $method = mysqli_query($DB_CONN, "SELECT * from currencies where id = '{$payment_method_id}'");
        if (mysqli_num_rows($method) > 0) {
            $method = mysqli_fetch_assoc($method);
            $currency = $method['name'];
            $symbol = $method['symbol'];
            
            // Get payment processor details
            $pm = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * FROM `payment_methods` where id = '{$method['de_pm_id']}'"));
            $system = json_decode($pm['currencies'], true);
            
            // Calculate deposit fee
            extract(deposit_fee($method, $deposit));
            
            if ($eligible1) {
                // Insert transaction record
                $DEPOSIT1 = mysqli_query($DB_CONN, "INSERT INTO transactions (
                    user_id, 
                    amount, 
                    fee, 
                    payment_method_id, 
                    status, 
                    txn_type, 
                    detail,
                    ip
                ) VALUES (
                    '{$userinfo['id']}',
                    '{$deposit}',
                    '{$fee}',
                    '{$payment_method_id}',
                    '0',
                    'deposit',
                    'Balance Deposit',
                    '{$ip}'
                )");
                
                if ($DEPOSIT1) {
                    $deposit = $deposit + $fee;
                    $ID = mysqli_insert_id($DB_CONN);
                    $package = ['name' => 'Account Balance'];
                    
                    // Initialize payment processor
                    $processor = new PaymentProcessor(
                        $pm['id'],
                        $system,
                        $DB_CONN,
                        $preferences,
                        $siteURL,
                        $g_alert,
                        $main_link
                    );
                    
                    // Generate payment data
                    $data = $processor->generatePaymentCode(
                        $method['symbol'],
                        "b".$ID,
                        $package,
                        $deposit,
                        $userinfo,
                        $method['address']
                    );
                    
                    if (!is_array($data)) {
                        call_alert(65); // Some Error. Please try again
                    } else {
                        $data = [
                            'result' => true,
                            'status' => true,
                            'form' => $data['form'] ?? '',
                            'address' => $data['address'] ?? '',
                            'address_url' => $method['address_url'] . ($data['address'] ?? ''),
                            'amount' => $data['amount'] ?? '',
                            'img' => $data['img'] ?? '',
                            'tag' => $data['tag'] ?? '',
                            'method' => [
                            'id' => $method['id'],
                            'name' => $method['name'],
                            'symbol' => $method['symbol'],
                            'rate' => $method['rate'],
                            'icon' => $siteURL . '/images/icons/' . $method['id'] . '.svg'
                        ]
                        ];
                    }
                } else {
                    call_alert(65); // Some Error. Please try again
                }
            } else {
                call_alert(50); // Invalid deposit amount
            }
        } else {
            call_alert(57); // Invalid payment method
        }
    }
    break;

  case 'new_ticket':
  if($userinfo['id'] && $_POST['subject'] && $_POST['message']) {
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
        $data['result'] = true;
        call_alert(100);
     }
    if($alert) {
      if(!$data['result'])
        $data['result']=false;
      $data['message']=$alert_message;
    }
  } else {
    $data['result'] = false;
    $data['message'] = "Invalid Values";
  }
  break;
  case 'tickets_list':
  if($userinfo['id']) {
    $tickets = array();
    $i =0;
    $t = mysqli_query($DB_CONN, "SELECT * from tickets where user_id = '{$userinfo['id']}' order by id desc");
    while($ticket = mysqli_fetch_assoc($t)) {
        unset($ticket['user_id']);
      $tickets[$i] = $ticket;
      $tickets[$i]['datetime'] = $ticket['datetime'];
      $tickets[$i]['id'] = md5($ticket['id']);
      $i++;
      }
    $data['data'] = $tickets;
    $total_tickets = number_format(mysqli_num_rows(mysqli_query($DB_CONN, "select id from tickets where user_id = '{$userinfo['id']}'")));
    $responded_tickets = number_format(mysqli_num_rows(mysqli_query($DB_CONN, "select id from tickets where status = 1 and user_id = '{$userinfo['id']}'")));
    $resolved_tickets = number_format(mysqli_num_rows(mysqli_query($DB_CONN, "select id from tickets where status = 2 and user_id = '{$userinfo['id']}'")));
    $pendings_tickets = number_format(mysqli_num_rows(mysqli_query($DB_CONN, "select id from tickets where status != 2 and user_id = '{$userinfo['id']}'")));
    $data['details'] = [
        'total_tickets' => $total_tickets,
        'responded_tickets' => $responded_tickets,
        'resolved_tickets' => $resolved_tickets,
        'pendings_tickets' => $pendings_tickets,
    ];
  } else {
    $data['result'] = false;
    $data['message'] = "Invalid Values";
  }
  break;
  case 'ticket_reply':
  if($userinfo['id'] && $_POST['ticket_id'] && $_POST['message']) {
    $cc = captcha_check('support');
     if($cc) {
        $id = $_POST['ticket_id'];
        $message = $_POST['message'];
        $ticket = mysqli_fetch_assoc(mysqli_query($DB_CONN, "SELECT * from tickets where md5(id) = '{$id}'"));
        $q = mysqli_query($DB_CONN, "INSERT into ticket_replies(ticket_id, user_id, message, type) values('{$ticket['id']}', '{$userinfo['id']}', '{$message}', 0)");
        $status = "Replied";
        sendadminmail("admin_ticket_reply", $userinfo['id'], $userinfo, array('id' => $id, 'msg' => $message, 'subject' => $ticket['subject'], 'status' => $status));
        call_alert(100); //Ticket Updated Successfully.
        $data['result'] = true;
     }
    if($alert) {
      if(!$data['result'])
        $data['result']=false;
      $data['message']=$alert_message;
    }
  } else {
    $data['result'] = false;
    $data['message'] = "Invalid Values";
  }
  break;
  case 'ticket_details':
    if ($userinfo['id'] && $_POST['ticket_id']) {
        $ticket_id = $_POST['ticket_id'];
        
        // Get ticket details
        $ticket = mysqli_query($DB_CONN, "SELECT * FROM tickets WHERE md5(id) = '{$ticket_id}' AND user_id = '{$userinfo['id']}'");
        
        if (mysqli_num_rows($ticket) > 0) {
            $ticketData = mysqli_fetch_assoc($ticket);
            
            // Get ticket replies
            $replies = array();
            $r = mysqli_query($DB_CONN, "SELECT * FROM ticket_replies WHERE ticket_id = '{$ticketData['id']}' ORDER BY id ASC");
            
            while ($reply = mysqli_fetch_assoc($r)) {
                $replies[] = array(
                    'message' => $reply['message'],
                    'type' => $reply['type'], // 0 for user, 1 for admin
                    'datetime' => date("F j, Y, g:i a", strtotime($reply['datetime']))
                );
            }
            
            $data['result'] = true;
            $data['ticket'] = array(
                'id' => md5($ticketData['id']),
                'subject' => $ticketData['subject'],
                'status' => $ticketData['status'],
                'datetime' => date("F j, Y, g:i a", strtotime($ticketData['datetime'])),
                'replies' => $replies
            );
        } else {
            $data['result'] = false;
            $data['message'] = 'Ticket not found';
        }
    } else {
        $data['result'] = false;
        $data['message'] = 'Invalid request';
    }
    break;
   case 'trade':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate required fields
       $required_fields = ['pair_id', 'payment_method_id', 'pair', 'trade_type', 'calc_type', 'qty'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $data['result'] = false;
                $data['message'] = ucfirst($field) . ' is required';
                break 2;
            }
        }
        
        // Validate trade parameters
        $payment_method_id = (int)$_POST['payment_method_id'];
        $pair_id = (int)$_POST['pair_id'];
        $pair = $_POST['pair'];
        $trade_type = $_POST['trade_type'];
        $calc_type = $_POST['calc_type'];
        $bchk = $qty = $_POST['qty'];
        // Validate trade type
        if (!in_array($trade_type, ['long', 'short'])) {
            $data['result'] = false;
            $data['message'] = 'Invalid trade type';
            break;
        }
        
        // Validate calculation type
        if (!in_array($calc_type, ['timer', 'option', 'spot', 'future'])) {
            $data['result'] = false;
            $data['message'] = 'Invalid calculation type';
            break;
        }
        if($qty < $trading_settings['min_trade']){
            call_alert(1002, fiat($trading_settings['min_trade'])); //Minimum amount to trade is 
            break;
        }

        if($calc_type == 'future' && $trading_settings['maker_fee']) {
            $bchk += ($bchk/100)*($trading_settings['maker_fee']*$_POST['leverage']);
        }
        $bal = mysqli_fetch_array(mysqli_query($DB_CONN, "SELECT balance FROM `user_balances` where user_id = '{$userinfo['id']}' and payment_method_id = '{$payment_method_id}' limit 1"))[0];
        if($bal < $bchk) {
            call_alert(59);
            break;
        }

        if($trading_settings['trading_limit'] && $trading_settings['trading_limit_duration']) {
            $cond = getcond($trading_settings['trading_limit_duration']);
            $p = "";
            if($trading_settings['trading_limit_for'] == 'currency')
                $p = " and payment_method_id = '{$payment_method_id}'";
            $c = mysqli_query($DB_CONN, "SELECT * FROM `trades` WHERE {$cond} and user_id = '{$userinfo['id']}' {$p}");
            if(mysqli_num_rows($c) >= $trading_settings['trading_limit']){
                $data['result'] = false;
                $data['message'] = $trading_settings['trading_limit'].' trade is allowed per '.$trading_settings['trading_limit_duration'];
                break;
            }
        }
       
        // Set default values
        $timer = isset($_POST['timer']) ? (int)$_POST['timer'] : 0;
        $leverage = isset($_POST['leverage']) ? $_POST['leverage'] : 0;
        $stoploss = isset($_POST['stoploss']) ? $_POST['stoploss'] : 0;
        $takeprofit = isset($_POST['takeprofit']) ?  $_POST['takeprofit'] : 0;
        $entry_price = isset($_POST['entry_price']) ? $_POST['entry_price'] : 0;
        
        try {
            // Insert the trade into database
            $query = "INSERT INTO trades (
                user_id, pair_id, payment_method_id, pair, trade_type, calc_type, timer,
                entry_price, leverage, qty, stoploss, takeprofit, status
            ) VALUES (
                '{$userinfo['id']}', '{$pair_id}', '{$payment_method_id}', '{$pair}', '{$trade_type}',
                '{$calc_type}', '{$timer}', '{$entry_price}', '{$leverage}',
                '{$qty}', '{$stoploss}', '{$takeprofit}', 0
            )";
            
            if (mysqli_query($DB_CONN, $query)) {
                $trade_id = mysqli_insert_id($DB_CONN);
                $am = $qty;
                
                if($calc_type == 'future' && $trading_settings['maker_fee']) {
                    $am += ($am/100)*($trading_settings['maker_fee']*$_POST['leverage']);
                } elseif($trading_settings['fee_per']) {
                    $am += ($am/100)*$trading_settings['fee_per'];
                }

                add_balance($userinfo['id'], $payment_method_id, -$am);
                // Return success response
                $data['result'] = true;
                call_alert(1001); //Trade placed successfully
                $data['trade'] = [
                    'id' => $trade_id,
                    'pair' => $pair,
                    'trade_type' => $trade_type,
                    'calc_type' => $calc_type,
                    'qty' => $qty,
                    'entry_price' => $entry_price,
                    'leverage' => $leverage,
                    'timer' => $timer,
                    'stoploss' => $stoploss,
                    'takeprofit' => $takeprofit,
                    'status' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            } else {
                throw new Exception('Failed to create trade');
            }
        } catch (Exception $e) {
            $data['result'] = false;
            $data['message'] = $e->getMessage();
        }
    } else {
        $data['result'] = false;
        $data['message'] = 'Method not allowed';
    }
     break;
     case 'update_trade':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate required fields
        $required_fields = ['trade_id'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $data['status'] = false;
                $data['message'] = ucfirst($field) . ' is required';
                break 2;
            }
        }

        // Get and validate trade ID
        $trade_id = (int)$_POST['trade_id'];
        
        // Verify trade exists and belongs to user
        $trade_query = mysqli_query($DB_CONN, "SELECT * FROM trades WHERE id = '{$trade_id}' AND user_id = '{$userinfo['id']}' AND calc_type IN ('spot', 'future')  AND trade_result = '' LIMIT 1");
        if (!mysqli_num_rows($trade_query)) {
            $data['result'] = false;
            $data['message'] = 'Trade not found or cannot be modified';
            break;
        }

        $trade = mysqli_fetch_assoc($trade_query);
        
        // Initialize update fields
        $update_fields = [];
        
        // Validate and process stop loss
        if (isset($_POST['stoploss'])) {
            $stop_loss = $_POST['stoploss'];
            if ($stop_loss > 0) {
                // Validate stop loss is below current price for safety
                if ($stop_loss >= $trade['entry_price']) {
                    $data['result'] = false;
                    $data['message'] = 'Stop loss must be below entry price';
                    break;
                }
                $update_fields[] = "stoploss = '{$stop_loss}'";
            } else {
                // If stop loss is 0 or negative, remove it
                $update_fields[] = "stoploss = '0'";
            }
        }
        
        // Validate and process take profit
        if (isset($_POST['takeprofit'])) {
            $take_profit = $_POST['takeprofit'];
            if ($take_profit > 0) {
                // Validate take profit is above current price for safety
                if ($take_profit <= $trade['entry_price']) {
                    $data['status'] = false;
                    $data['message'] = 'Take profit must be above entry price';
                    break;
                }
                $update_fields[] = "takeprofit = '{$take_profit}'";
            } else {
                // If take profit is 0 or negative, remove it
                $update_fields[] = "takeprofit = '0'";
            }
        }

        // If no valid updates, return error
        if (empty($update_fields)) {
         
            $data['result'] = false;
            $data['message'] = 'No valid updates provided';
            break;
        }

        try {
            // Update the trade in database
            $update_query = "UPDATE trades SET " . implode(', ', $update_fields) . " WHERE id = '{$trade_id}' AND user_id = '{$userinfo['id']}'";
            
            if (mysqli_query($DB_CONN, $update_query)) {
                // Return success response
                $data['result'] = true;
                call_alert(1268);
                
                // Fetch and return updated trade data
                $updated_trade_query = mysqli_query($DB_CONN, "SELECT * FROM trades WHERE id = '{$trade_id}' LIMIT 1");
                $updated_trade = mysqli_fetch_assoc($updated_trade_query);
                
                $data['trade'] = [
                    'id' => $updated_trade['id'],
                    'stoploss' => $updated_trade['stoploss'],
                    'takeprofit' => $updated_trade['takeprofit'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } else {
                throw new Exception('Failed to update trade');
            }
        } catch (Exception $e) {
            $data['status'] = false;
            $data['message'] = $e->getMessage();
        }
    } else {
        $data['status'] = false;
        $data['message'] = 'Method not allowed';
    }
    break;

    case 'close_trade':
    processTrade((int)$_POST['Pair_id'], $_POST['current_price']);
    break;
    case 'dailycheckin':
    $data['result']= true;
    mysqli_query($DB_CONN, "UPDATE users set dailycheckin = CURRENT_TIMESTAMP where id = '{$userinfo['id']}'");
    break;
case 'save_subscription':
    $data['result'] = true;
    $subscription = json_encode($_POST['subscription']);
    mysqli_query($DB_CONN, "UPDATE users SET subscription_data = '{$subscription}' WHERE id = '{$userinfo['id']}'");
    break;
case 'check_task':
    $task_id = (int)$_POST['task_id'];
    $params = null;
    
    if (isset($_POST['result']) && isset($_POST['comment'])) {
        $params = [
            'result' => $_POST['result'],
            'comment' => $_POST['comment']
        ];
    }
    
    $result = handleTasks('check', $task_id, $params);
    
    $data['result'] = ($result['status'] === 'success');
    if (isset($result['task_results'])) {
        $data['task_results'] = $result['task_results'];
    }
    break;

case 'taps':
    if (isset($userinfo['id']) && !empty($_POST['used_taps'])) {
        $taskData = handleTasks('get_data');
        
        // Get current used taps for this user using ref_id
        $used_taps_query = mysqli_query($DB_CONN, "SELECT SUM(ref_id) as total_used FROM transactions WHERE user_id = '{$userinfo['id']}' AND txn_type = 'bonus'");
        $used_taps_result = mysqli_fetch_assoc($used_taps_query);
        $total_used_taps = $taskData['total_taps']-$used_taps_result['total_used'] ?: 0;

        // Check if user has enough taps remaining
       $totaltaps = $user_settings['default_taps']+$taskData['total_taps'];
        if ($_POST['used_taps'] > ($totaltaps - $total_used_taps)) {
            $data['message'] = "You don't have enough taps available.";
            break;
        }

        $total_bonus = $_POST['used_taps'] * $user_settings['balance_bonus_per_tap'];
        add_balance($userinfo['id'], 14, $total_bonus);
          $totalf_bonus = $_POST['used_taps'] * $user_settings['faucet_bonus_per_tap'];
        add_faucet($userinfo['id'], 14, $totalf_bonus);
        $det = "Taps earning from". $_POST['used_taps'];
        
        // Store used taps in ref_id
        mysqli_query($DB_CONN, "INSERT INTO `transactions`(`detail`, `user_id`, `amount`, `txn_type`, `payment_method_id`, `ref_id`) 
            VALUES ('{$det}', '{$userinfo['id']}', '{$total_bonus}', 'tap', '14', '{$_POST['used_taps']}')");
            $data['status']=true;
        $data['message'] = "Posted Successfully";
    }
    break;
case 'trades':
    $rows = array();
    $where = " and user_id = '{$userinfo['id']}'";
    
    // Add single trade filter if ID is provided
    if (isset($_POST['id']) && $_POST['id']) {
        $trade_id = mysqli_real_escape_string($DB_CONN, $_POST['id']);
        $where .= " AND id = '{$trade_id}'";
    }
    
    // Add other filters if provided
    if (isset($_POST['status']) && $_POST['status']) {
        $status = (int)$_POST['status'];
        $where .= " AND status = '{$status}'";
    }
    if (isset($_POST['trade_type']) && $_POST['trade_type']) {
        $trade_type = mysqli_real_escape_string($DB_CONN, $_POST['trade_type']);
        $where .= " AND trade_type = '{$trade_type}'";
    }
    
    // Add date filters if provided
    if(isset($_POST['from']) && $_POST['from']) {
        $from = date("Y-m-d", strtotime($_POST['from']));
        $where .= " and date(created_at) >= '{$from}'";
    }
    if(isset($_POST['to']) && $_POST['to']) {
        $to = date("Y-m-d", strtotime($_POST['to']));
        $where .= " and date(created_at) <= '{$to}'";
    }
    
    // Add search filter if provided
    if(isset($_POST['search']) && $_POST['search']) {
        $_POST['search'] = str_replace(" ", "%", $_POST['search']);
        $where .= " and (amount like '%{$_POST['search']}%' or id like '%{$_POST['search']}%') ";
    }
    
    // Get total count for pagination
    $d = mysqli_query($DB_CONN, "SELECT * FROM trades WHERE 1=1 {$where}") or die(mysqli_error($DB_CONN));
    $totalItems = mysqli_num_rows($d);
    
    // Use the settings for itemsPerPage
    $itemsPerPage = $infobox_settings['orders'] ?: 20;
    $currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $i = ($currentPage * $itemsPerPage) - $itemsPerPage + 1;
    
    // Build URL pattern for pagination
    unset($_POST['page']);
    $urlPattern = '?' . http_build_query($_POST) . '&page=(:num)';
    
    // Calculate start and end positions
    $start = ($currentPage * $itemsPerPage) - $itemsPerPage;
    $end = ($start + $itemsPerPage) > $totalItems ? $totalItems : ($start + $itemsPerPage);
    
    // Only fetch data if not for a specific ID or if pagination is needed
    if (!isset($_POST['id']) || $totalItems > 0) {
        $d = mysqli_query($DB_CONN, "SELECT *, 
            (select name from currencies where id = trades.payment_method_id) as currency 
            FROM trades WHERE 1=1 {$where} 
            ORDER BY status ASC, timestamp DESC LIMIT {$start}, {$itemsPerPage}") or die(mysqli_error($DB_CONN));
        
        while($row = mysqli_fetch_assoc($d)) {
            $row['icon'] = $siteURL . '/images/icons/' . $row['payment_method_id'] . '.svg';
            $rows[] = $row;
        }
    }
    
    if (count($rows) > 0) {
        $data['result'] = true;
        $data['trades'] = $rows;
        
        // Add pagination details
        $data['details'] = [
            'totalItems' => $totalItems,
            'currentPage' => $currentPage,
            'itemsPerPage' => $itemsPerPage
        ];
    } else {
        $data['result'] = false;
        $data['message'] = isset($_POST['id']) ? 'Trade not found' : 'No trades found';
    }
    
    // Get unique trade types for filters
    $trade_types = [];
    $ty = mysqli_query($DB_CONN, "SELECT DISTINCT trade_type FROM trades WHERE user_id = '{$userinfo['id']}'") or die(mysqli_error($DB_CONN));
    while($tradeType = mysqli_fetch_assoc($ty)){
        $trade_types[] = $tradeType['trade_type'];
    }
    $data['trade_types'] = $trade_types;
    
    break;
  case 'tradesss':
    // Initialize WHERE clause with user_id

    $where = "WHERE user_id = '{$userinfo['id']}'";
    
    // Add single trade filter if ID is provided
    if (isset($_POST['id'])) {
        $trade_id = mysqli_real_escape_string($DB_CONN, $_POST['id']);
        $where .= " AND id = '{$trade_id}'";
    }
    
    // Add other filters if provided
    if (isset($_POST['status'])) {
        $status = (int)$_POST['status'];
        $where .= " AND status = '{$status}'";
    }
    if (isset($_POST['trade_type'])) {
        $trade_type = mysqli_real_escape_string($DB_CONN, $_POST['trade_type']);
        $where .= " AND trade_type = '{$trade_type}'";
    }
    
    // Add pagination only for list view (when no ID is provided)
    if (!isset($_POST['id'])) {
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        // Get total count for pagination
        $total_query = "SELECT COUNT(*) as total FROM trades {$where}";
        $total_result = mysqli_query($DB_CONN, $total_query);
        $total = mysqli_fetch_assoc($total_result)['total'];
        
        $query = "SELECT *,(select name from currencies where id = trades.payment_method_id ) as currency FROM trades {$where} ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
    } else {
        // For single trade, no pagination needed
        $query = "SELECT *,(select name from currencies where id = trades.payment_method_id ) as currency FROM trades {$where}";
    }
    
    $result = mysqli_query($DB_CONN, $query);
    
    $trades = [];
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['icon'] = $siteURL . '/images/icons/' . $row['payment_method_id'] . '.svg';
            $trades[] = $row;
        }
        $data['result'] = true;
        $data['trades'] = $trades;
        
        // Add pagination data only for list view
        if (!isset($_POST['id'])) {
            $data['pagination'] = [
                'total' => (int)$total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        }
    } else {
        $data['result'] = false;
        $data['message'] = isset($_POST['id']) ? 'Trade not found' : 'No trades found';
    }
    break;
    case 'veriff':
    $veriffApiKey = $kyc_settings['veriff_api'];
    $veriffApiUrl = 'https://stationapi.veriff.com/v1/sessions';
    
    // Extract user information
    $userId = $userinfo['id'];
    $email = $userinfo['email'] ?? '';
    $fullName = $userinfo['fullname'] ?? $userinfo['username'] ?? '';
    
    // Split name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    $payload = [
        'verification' => [
            'callback'   => $siteURL.'/status.php?veriff', // let json_encode escape slashes
            'person'     => [
                'firstName' => $firstName,
                'lastName'  => $lastName
            ],
            'vendorData' => "".$userId
        ]
    ];

    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $veriffApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-AUTH-CLIENT: ' . $veriffApiKey
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $error = curl_error($ch);
    // Handle response
    if (!$error) {
        $sessionData = json_decode($response, true);
        $data = [
            'url' => $sessionData['verification']['url'] ?? '',
            'sessionId' => $sessionData['verification']['id'] ?? ''
        ];
    } else {
        echo json_encode([
            'error' => 'Failed to create Veriff session',
            'details' => json_decode($response, true)
        ]);
    }
    
    curl_close($ch);
    break; 
}
} else {
    $data['message'] = 'Missing request type';
}
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}