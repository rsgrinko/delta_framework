<?php

    /**
     * Copyright (c) 2022 Roman Grinko <rsgrinko@gmail.com>
     * Permission is hereby granted, free of charge, to any person obtaining
     * a copy of this software and associated documentation files (the
     * "Software"), to deal in the Software without restriction, including
     * without limitation the rights to use, copy, modify, merge, publish,
     * distribute, sublicense, and/or sell copies of the Software, and to
     * permit persons to whom the Software is furnished to do so, subject to
     * the following conditions:
     * The above copyright notice and this permission notice shall be included
     * in all copies or substantial portions of the Software.
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
     */
    require_once __DIR__ . '/inc/header.php';
?>
    <div class="pageheader">
        <div class="media">
            <div class="pageicon pull-left">
                <i class="fa fa-home"></i>
            </div>
            <div class="media-body">
                <ul class="breadcrumb">
                    <li><a href=""><i class="glyphicon glyphicon-home"></i></a></li>
                    <li>Дашюоард</li>
                </ul>
                <h4>Дашбоард</h4>
            </div>
        </div><!-- media -->
    </div><!-- pageheader -->

    <div class="contentpanel">


        типа статистика или хз

        <p id="userList"></p>
        <p id="app"></p>
        <input id="message" type="text">
        <button id="sendBtn">send</button>



        <script>
            let socket = new WebSocket('wss://dev.it-stories.ru/wss?userName=<?= $USER->getLogin()?>');

            socket.onopen = function(e) {
                /*const initMessage = {
                    method: 'registration',
                    userId: <?= $USER->getId() ?>,
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
                    $('#app').append(`<div class="messageBox"><div class="userLogin">${data.userName}</div><div class="message">${data.text}</div></div>`)
                    return;
                }

                // Обработка при подключении
                if (data.action === 'Connected') {
                    $('#app').append(`<div class="messageBox"><div class="notification">Пользователь ${data.userName} подлючился в чат</div></div>`)
                    //return;
                }

                // Обработка при подключении
                if (data.action === 'Authorized') {
                    $('#userList').html('');
                    data.users.forEach(function(element){
                        $('#userList').append(`<div class="userElement">${element.userName}</div>`)
                    });
                    return;
                }

                // Обработка при отклбчении
                if (data.action === 'Disconnected') {
                    $('#app').append(`<div class="messageBox"><div class="notification">Пользователь ${data.userName} покинул чат</div></div>`)

                    $('#userList').html('');
                    data.users.forEach(function(element){
                        $('#userList').append(`<div class="userElement">${element.userName}</div>`)
                    });
                    return;
                }



                console.log('Данные получены с сервера: ' + JSON.stringify(data))



                //$('#app').append(`<div class="messageBox"><div class="userLogin">${data.userName}</div><div class="message">${data.text}</div></div>`)


                $('#app').html($('#app').html() + "<br>" + event.data);
            };

            socket.onclose = function(event) {
                if (event.wasClean) {
                    console.log(`[close] Соединение закрыто чисто, код=${event.code} причина=${event.reason}`);
                } else {
                    // например, сервер убил процесс или сеть недоступна
                    // обычно в этом случае event.code 1006
                    console.log('[close] Соединение прервано');
                }
            };

            socket.onerror = function(error) {
                console.log(`[error]`);
            };
        </script>



        <script>
            $('#sendBtn').on('click', function(){
                const message = {
                    action: 'PublicMessage',
                    text: $('#message').val()
                };
                socket.send(JSON.stringify(message));
                $('#message').val('');
            });
        </script>
        <style>
            .messageBox {
                border: 1px solid #dbdbdb;
                margin: 5px;
                padding: 5px;
                background: #f9f9f9;
            }
            .userLogin {
                font-weight: bold;
                color: green;
            }

            .notification {
                font-style: italic;
                color: #e50000;
            }

            p#userList {
                display: flex;
                flex-wrap: wrap;
            }
            .userElement {
                border: 1px solid #687cff;
                margin: 2px;
                padding: 5px;
                background: blue;
                color: white;
            }
        </style>
    </div><!-- contentpanel -->
<?php
    require_once __DIR__ . '/inc/footer.php';
