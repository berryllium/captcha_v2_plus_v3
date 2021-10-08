<?php
require_once 'config.php';
session_start();
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <title>Captcha</title>

        <!-- подключаем скрипт капчи -->
        <script src="https://www.google.com/recaptcha/api.js?render=<?=KEY_SITE_V3?>"></script>
    </head>
    <body>
        <h1 class="text-center my-5">Send email</h1>
            <div class="wrap my-5 mx-3">
            <form action="ajax.php" id="form" class="col-6 offset-md-3">
                <!-- создаем в форме два скрытых поля - по одному для каждой версии капчи -->
                <input type="hidden" name="sid" value="<?=session_id();?>">
                <input type="hidden" name="captcha_token_v2">
                <input type="hidden" name="captcha_token_v3">
                <div class="mb-3 alert alert-success" style="display: none" data-dismiss="alert">test</div>
                <div class="mb-3">
                    <label for="exampleFormControlInput1" class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com" required>
                </div>
                <div class="mb-3">
                    <label for="exampleFormControlTextarea1" class="form-label">Example textarea</label>
                    <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" name="text" required></textarea>
                </div>
                <div class="mb-3">
                    <div id="captcha"></div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>

        <script>
            let captcha_key_site_v2 = '<?=KEY_SITE_V2?>' // значения ключей из файла config.php
            let captcha_key_site_v3 = '<?=KEY_SITE_V3?>' // значения ключей из файла config.php
            let widgetCaptcha = false // id виджета, на случай, если будет использовано несколько форм на странице
            let tokenInputV2 = $('#captcha_token_v2') // места для вставки токенов
            let tokenInputV3 = $('#captcha_token_v3') // места для вставки токенов
            let form = $('#form')

            // метод отправки формы по ajax, принимающий файл ajax.php
            function sendForm() {
                let data = form.serialize()
                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: data,
                    dataType: 'json',
                    success: function (response) {
                        if(response.success) {
                            // показываем сообщение об успехе и перезагружаем страницу
                            $('.alert').text(response.text).slideDown()
                            setTimeout(function (){location.reload()}, 4000)
                        } else if(response.error) {
                            // если была ошибка капчи, сбрасываем капчку v2 при наличии
                            if (widgetCaptcha !== false) {
                                grecaptcha.reset(widgetCaptcha)
                            }
                            // если ошибка была в версии v3, показываем видимую капчу v2
                            // widgetCaptcha - идентификатор, т.е. можно рендерить и управлять несколькими штуками
                            if (response.error === 'fall_captcha_v3' && !widgetCaptcha) {
                                widgetCaptcha = grecaptcha.render('captcha', {
                                    'sitekey': captcha_key_site_v2,
                                    'theme': 'dark',
                                    'callback': setTokenV2
                                })
                            }
                        }
                    }
                })
            }

            // функция-колбек добавляет полученный токен второй версии капчи в скрытое поле формы
            function setTokenV2(token) {
                form.find('input[name="captcha_token_v2"]').val(token)
                submitSubscribeFooterForm()
            }

            // назначаем событие на попытку отправки формы,
            // если гугловый скрипт добавляем токен версии три в скрытое поле и пробуем отправить форму
            form.submit(function (e){
                e.preventDefault()
                if (typeof grecaptcha != 'undefined' && typeof captcha_key_site_v3 != 'undefined') {
                    grecaptcha.ready(function () {
                        grecaptcha.execute(captcha_key_site_v3, {action: 'submit'}).then(function (token) {
                            if(token) {
                                form.find('input[name="captcha_token_v3"]').val(token)
                                sendForm()
                            }
                        })
                    })
                }
            })
        </script>
    </body>
</html>

