<?php
require_once 'config.php';
session_start();
if(!empty($_POST) && $_POST['sid'] == session_id()) {
    $token_v2 = $_POST['captcha_token_v2'];
    $token_v3 = $_POST['captcha_token_v3'];
    $result = checkCaptcha($token_v2, $token_v3);
    die(json_encode($result, true));
}

function checkCaptcha($token_v2 = false, $token_v3 = false)
{
    // если не передано ни одного токена - возвращаем ошибку
    if (!$token_v3 && !$token_v2) {
        return ['error' => 'fall_captcha'];
    }
    // если дело дошло до капчи
    elseif ($token_v2) {
        // проверяем информацию по второй версии, если google ответил, что провека успешная - возвращаем успех
        $result = checkCaptchaCurl($token_v2, KEY_SECRET_V2);
        if (!$result['success']) {
            // если проверка провалилась - тоже ошибка
            return ['error' => 'fall_captcha_v2'];
        }
    }
    // если токен второй версии еще не получен, но есть 3, значит проверяем невидимую капчу
    else {
        $result = checkCaptchaCurl($token_v3, KEY_SECRET_V3);
        // проверяем количество очков от 0 до 1. Чем ближе к 1, тем больше вероятности, что это человек
        if ($result['score'] < 1) {
            return ['error' => 'fall_captcha_v3'];
        }
    }
    // возвращаем успех, если проверки пройдены
    $text = 'Your message "' . $_POST['text'] . '" was send to email "' . $_POST['email'] . '" successfully';
    return ['success' => true, 'text' => $text];
}

/**
 * Метод для отправки запроса в google через CURL
 * @param $response
 * @param $secret
 * @return mixed
 */
function checkCaptchaCurl($response, $secret)
{
    $url_data = 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $secret . '&response=' . $response . '&remoteip=' . $_SERVER['REMOTE_ADDR'];
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url_data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $captcha_res = curl_exec($curl);
    curl_close($curl);
    $captcha_res = json_decode($captcha_res, true);
    return $captcha_res;
}

