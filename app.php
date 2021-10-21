<?php

$domain = str_replace('www.', '', $_SERVER['SERVER_NAME']);
$ip = $_SERVER['SERVER_ADDR'];

function license_control_curl($values)
{
    $get_url = 'https://www.arcface.net/dynamic_license/license.php';
    if (extension_loaded('curl') === true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $values);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (isset($result)) {
            return $result;
        } else {
            return ['error' => "CURL Response: Empty Data."];
        }
    } elseif (function_exists('file_get_contents')) {
        $result = file_get_contents($get_url . "?". $values);
        $result = json_decode($result, true);

        if (isset($result)) {
            return $result;
        } else {
            return ['error' => "CURL Response: Empty Data."];
        }
    } else {
        return ['error' => "System Error: To continue, you must activate the CURL or FILE_GET_CONTENTS Function."];
    }
}

function license_cypto_dec($string, $key = '', $secret = '', $method = 'AES-256-CBC')
{
    $key = hash('sha256', $key);
    $iv = substr(hash('sha256', $secret), 0, 16);
    $string = base64_decode($string);
    return openssl_decrypt($string, $method, $key, 0, $iv);
}

function request_values($arr = []){
    $val_string = '';

    foreach ($arr as $key => $value) {
        $val_string .= $key . '=' . $value . '&';
    }
    $val_string = rtrim($val_string, '&');

    return $val_string;
}

function print_data_file($path = "")
{
    require($path);
}

function get_data($path = "")
{
    ob_start();
    print_data_file($path);
    $data_file = ob_get_contents();
    ob_end_clean();
    return json_decode($data_file, true);
}

if (file_exists(LICANSE_DIR_PATH)) {
    $license = get_data(LICANSE_DIR_PATH);

    $license['crypto_key'] = license_cypto_dec($license['crypto_key'],"2c6326b1d378cb3555e5ee051302eb7e");

    if (empty($license['crypto_key'])){
        die(license_control_curl( "command=error&code=7")['error']);
    }

    $time = strtotime(date('d-m-Y H:i:s'));
    $file_update_time = strtotime(date("d-m-Y H:i:s", filemtime(LICANSE_DIR_PATH)));
    $license['domain'] = license_cypto_dec($license['domain'], $license['crypto_key']);
    $license['ip'] = license_cypto_dec($license['ip'], $license['crypto_key']);
    $license['start_time'] = license_cypto_dec($license['start_time'], $license['crypto_key']);
    $license['finish_time'] = license_cypto_dec($license['finish_time'], $license['crypto_key']);
    $license['last_update_time'] = license_cypto_dec($license['last_update_time'], $license['crypto_key']);
    $license['repetition_time'] = license_cypto_dec($license['repetition_time'], $license['crypto_key']);

    if (!is_numeric($license['repetition_time']) || !is_numeric($license['last_update_time'])) {
        die(license_control_curl( "command=error&code=1")['error']);
    }

    if ($time - $license['last_update_time'] > $license['repetition_time'] || $time - $file_update_time > $license['repetition_time']) {
        $domain = str_replace('www.', '', $_SERVER['SERVER_NAME']);
        $ip = $_SERVER['SERVER_ADDR'];

        $license_token = license_control_curl( "command=code_query&domain={$domain}&ip={$ip}");

        if (isset($license_token['error'])) {
            die($license_token['error']);
        }

        if (LICANSE_CODE != $license_token['token']) {
            die(license_control_curl( "command=error&code=6")['error']);
        }

        if ($domain != $license['domain']) {
            die(license_control_curl( "command=error&code=2")['error']);
        }

        if ($ip != $license['ip']) {
            die(license_control_curl( "command=error&code=3")['error']);
        }

        if ($time - $license['start_time'] < 0) {
            die(license_control_curl( "command=error&code=4")['error']);
        }

        if ($time - $license['finish_time'] > 0) {
            die(license_control_curl( "command=error&code=5")['error']);
        }

        $values = array(
            'command' => "license",
            'domain' => $domain,
            'ip' => $ip
        );

        $return_data = license_control_curl(request_values($values));

        if (isset($return_data['error'])) {
            die($return_data['error']);
        } else {
            file_put_contents(LICANSE_DIR_PATH, json_encode($return_data));
        }
    }
    unset($license);
}
else {
    $values = array(
        'command' => "information",
        'domain' => $domain,
        'ip' => $ip
    );

    $return_data = license_control_curl(request_values($values));

    if (isset($return_data['error'])) {
        die($return_data['error']);
    } else {
        touch(LICANSE_DIR_PATH);
        file_put_contents(LICANSE_DIR_PATH, json_encode($return_data));
    }
}