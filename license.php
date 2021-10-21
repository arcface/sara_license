<?php
define('LICANSE_CRYPTO_KEY', '2c6326b1d378cb3555e5ee051302eb7e');

function license_cypto_enc($string, $key = '', $secret = '', $method = 'AES-256-CBC')
{
    $key = hash('sha256', $key);
    $iv = substr(hash('sha256', $secret), 0, 16);
    $output = openssl_encrypt($string, $method, $key, 0, $iv);
    return base64_encode($output);
}
function lic_post($name)
{
    return htmlspecialchars(trim($_POST[$name]));
}
function lic_get($name)
{
    return htmlspecialchars(trim($_GET[$name]));
}
$db = NEW SQLite3("license.db");

function license_check($token){ global $db;
    $statement = $db->prepare("SELECT licenses.*,cypto_keys.key_value FROM licenses INNER JOIN cypto_keys ON licenses.id = cypto_keys.license_id WHERE token = :token");
    $statement->bindValue(':token',$token);

    $result = $statement->execute();

    return $result->fetchArray(true);
}

function license_update($token){ global $db;
    $last_update_time = date('d-m-Y H:i:s');
    $query = $db->exec('UPDATE licenses SET last_update_time = "{$last_update_time}"  WHERE licenses.token = "{$token}"');
    if ($query){
        return true;
    }else{
        return false;
    }
}

if ($_POST || $_GET) {

    $error_codes = [
        0 => "License Error: NULL",
        1 => "License Error: Change detected on License File, please delete the file and access the site again.",
        2 => "License Error: Invalid Domain Address",
        3 => "License Error: Invalid IP Address",
        4 => "License Error: Your License Has Not Been Started Yet.",
        5 => "License Error: Your License Has Expired. Contact to renew.",
        6 => "License Error: Your License Code is Invalid.",
        7 => "License Error: Try again by deleting the license.json file.",
        8 => "License Error: Unable to update license please contact administrator."
    ];

    if ($_POST['command'] || $_GET['command']) {
        $command = isset($_POST['command']) ? lic_post('command') : lic_get('command');
        $domain = isset($_POST['domain']) ? lic_post('domain') : lic_get('domain');
        $ip = isset($_POST['ip']) ? lic_post('ip') : lic_get('ip');
        $token = license_cypto_enc(trim($domain ). "-" . trim($ip), LICANSE_CRYPTO_KEY);

        $license = license_check($token);

        if(empty($license)){
            echo json_encode(['error' => $error_codes[6]]);
            exit;
        }

        if ($command == 'license') {

            if (license_update($token) == false){
                echo json_encode(['error' => $error_codes[8]]);
                exit;
            }

            $license = license_check($token);

            $license_list = [];

            $license_list["domain"] = license_cypto_enc($license['domain'], $license['key_value']);
            $license_list["ip"] = license_cypto_enc($license['ip'], $license['key_value']);
            $license_list["start_time"] = license_cypto_enc(strtotime($license['start_time']), $license['key_value']);
            $license_list["finish_time"] = license_cypto_enc(strtotime($license['finish_time']), $license['key_value']);
            $license_list["last_update_time"] = license_cypto_enc(strtotime($license['last_update_time']), $license['key_value']);
            $license_list["repetition_time"] = license_cypto_enc($license['repetition_time'], $license['key_value']);
            $license_list['crypto_key'] = license_cypto_enc($license['key_value'],LICANSE_CRYPTO_KEY);

            echo json_encode($license_list);

        } elseif ($command == 'code_query') {
            echo json_encode(['token' => $token]);
        } elseif ($command == 'information') {

            $license_list = [];

            $license_list["domain"] = license_cypto_enc($license['domain'], $license['key_value']);
            $license_list["ip"] = license_cypto_enc($license['ip'], $license['key_value']);
            $license_list["start_time"] = license_cypto_enc(strtotime($license['start_time']), $license['key_value']);
            $license_list["finish_time"] = license_cypto_enc(strtotime($license['finish_time']), $license['key_value']);
            $license_list["last_update_time"] = license_cypto_enc(strtotime($license['last_update_time']), $license['key_value']);
            $license_list["repetition_time"] = license_cypto_enc($license['repetition_time'], $license['key_value']);
            $license_list['crypto_key'] = license_cypto_enc($license['key_value'],LICANSE_CRYPTO_KEY);

            echo json_encode($license_list);

        } elseif ($command == 'error') {
            $error_code = isset($_POST['code']) ? lic_post('code') : lic_get('code');
            if (!isset($error_code)) $error_code = 0;
            die(json_encode(['error' => $error_codes[$error_code]]));
        }
    }
    exit;
}
