<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>Чат для компании</title>
    <style>
        div#app {
            height: 380px;
            overflow-y: scroll;
            box-shadow: inset 0px 15px 10px -7px black;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <div id="userList"></div>


    <div id="app">


    </div>




    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">Введите сообщение</span>
        </div>
        <textarea id="message" class="form-control" aria-label="With textarea"></textarea>
    </div>
    <button id="sendBtn" type="button" class="btn btn-primary btn-lg btn-block">Отправить</button>
</div>










<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<script>
    function getRandomInt(min, max) {
        min = Math.ceil(min);
        max = Math.floor(max);
        return Math.floor(Math.random() * (max - min)) + min;
    }

    let socket = new WebSocket('wss://dev.it-stories.ru/wss?userName=Guest_' + getRandomInt(1, 9999));

    socket.onopen = function(e) {
        /*const initMessage = {
            method: 'registration',
            userId: 1,
                date: Date.now()
            };
            socket.send(JSON.stringify(initMessage));*/
    };

    socket.onmessage = function(event) {
        let data = JSON.parse(event.data);
        // Обработка пингов
        if (data.action === 'Ping') {
            const pongMessage = {action: 'Pong'};
            socket.send(JSON.stringify(pongMessage));
            return;
        }

        // Обработка подключившихся пользователей
        if (data.action === 'PublicMessage') {
             $('#app').append(`<div class="alert alert-success" role="alert"><h5 class="alert-heading" onClick="contactTo('${data.userName}')">${data.userName}</h5>${data.text}</div>`)
            //return;
        }

        // Обработка при подключении
        if (data.action === 'Connected') {
            $('#app').append(`<div class="alert alert-info" role="alert">Пользователь <b>${data.userName}</b> подлючился в чат</div>`)
            //return;
        }

        // Обработка при подключении
        if (data.action === 'Authorized') {
            $('#userList').html('');
            data.users.forEach(function(element){
                $('#userList').append(`<span class="badge badge-secondary m-2">${element.userName}</span>`)
            });
            //return;
        }

        // Обработка при отклбчении
        if (data.action === 'Disconnected') {
            $('#app').append(`<div class="alert alert-info" role="alert">Пользователь <b>${data.userName}</b> покинул чат</div>`)

            $('#userList').html('');
            data.users.forEach(function(element){
                $('#userList').append(`<span class="badge badge-secondary">${element.userName}</span>`)
            });
            //return;
        }

        // Обработка при отклбчении
        if (data.action === 'Information') {
            $('#app').append(`<div class="alert alert-warning" role="alert">${data.text}</div>`)
            //return;
        }

        window.document.getElementById('app').scrollTop = window.document.getElementById('app').scrollHeight
    };

    socket.onclose = function(event) {
        if (event.wasClean) {
            console.log(`[close] Соединение закрыто чисто, код=${event.code} причина=${event.reason}`);
        } else {
            console.log('[close] Соединение прервано');
        }
    };

    socket.onerror = function(error) {
        console.log(`[error]`);
    };

    $('#sendBtn').on('click', function(){
        let messageText = $('#message').val();
        if (messageText === "") {
            return;
        }

        const message = {
            action: 'PublicMessage',
            text: $('#message').val()
        };
        socket.send(JSON.stringify(message));
        $('#message').val('');
    });

    function contactTo(name){
        $('#message').val($('#message').val() + " [b]" + name + "[/b] ");
    }
</script>


</body>
</html>