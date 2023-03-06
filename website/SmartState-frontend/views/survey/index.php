<?php
/** @var UserSession $userSession */
$page = 'survey';
include_once __DIR__ . '/../_header_no_sidebar.php';
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h4">Survey</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <section class="msger">
            <header class="msger-header">
                <div class="msger-header-title">
                <i class="fas fa-comment-alt"></i> SmartState
                </div>
                <div class="msger-header-mute">
                    <span><i id="mute-sound" class="fas fa-volume-up"></i></span>
                </div>
            </header>

            <main class="msger-chat">
                <div class="msg left-msg">
                    <div class="msg-img" style="background-image: url(https://cdn-icons-png.flaticon.com/512/2021/2021646.png)"></div>

                    <div class="msg-bubble">
                        <div class="msg-info">
                        <div class="msg-info-name">SmartState</div>
                        <div class="msg-info-time"><?= date("h:i:s a"); ?></div>
                        </div>

                        <div class="msg-text">Hi! It's time for your wellness check. Tracking healthy habits is a great way to measure your progress over time. Are you ready to answer a few questions about your health?</div>
                    </div>
                </div>
            </main>

            <form class="msger-inputarea">
                <button type="button" id="chatbot-mic-input" class="mr-1 btn btn-secondary" data-toggle="tooltip" data-placement="top" title="Press and Hold"><i class="fa fa-microphone"></i></button>
                <input type="text" id="chat-text-area" class="msger-input" placeholder="Enter your message..." value=""/>
                <button type="submit" class="msger-send-btn">Send</button>
            </form>

            <button id="finished-btn" class="btn btn-primary">I'm finished!</button>
        </section>
    </div>
</div>

<script type="text/javascript" src="/js/uuid4.js"></script>
<script type="text/javascript">
    <?php $config = include CONFIG_FILE;?>
    var API_url = "<?php echo $config['API_url']; ?>";
    var token = "<?php echo $token;?>";
    var participantUUID = "<?php echo $participantUUID;?>";

    $('#finished-btn').click(function() {
        let lastMessage = $('.left-msg').find('.msg-text').last().prop('innerText');

        $.ajax({
            url: '/survey/done',
            type: 'POST',
            data: {
                'text': lastMessage,
                'token': token,
                'participantUUID': participantUUID
            },
            success: function(data) {
                if (data.success) {
                    window.location.href = "/survey/thank-you"
                } else {
                    showError(data.error_message);
                    return null;
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                console.error(status);
                console.error(error);
                showError("Error communicating with the server.");
                return null;
            }
        });
    });

    
</script>

<script type="text/javascript" src="/js/chat.js"></script>
    
<?php
    include_once __DIR__ . '/../_footer.php';
