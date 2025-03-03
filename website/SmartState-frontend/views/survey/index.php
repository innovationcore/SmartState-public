<?php
/** @var UserSession $userSession */
$page = 'survey';
global $rootURL;
include_once __DIR__ . '/../_header_no_sidebar.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Health Survey - <span class="text-muted">Your Healthy Habits Matter</span></h1>
    </div>

    <div class="row">
        <div class="offset-md-1 col-md-10 offset-md-1">
            <form>
                <div class="form-group mb-2">
                    <label for="exercise">How often do you exercise per week?</label>
                    <select class="form-control" id="exercise">
                        <option value="">Select an option</option>
                        <option value="Never">Never</option>
                        <option value="1-2 times">1-2 times</option>
                        <option value="3-4 Times">3-4 times</option>
                        <option value="5+ times">5+ times</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label for="diet">How would you describe your diet?</label>
                    <select class="form-control" id="diet">
                        <option value="">Select an option</option>
                        <option value="Poor">Poor</option>
                        <option value="Average">Average</option>
                        <option value="Healthy">Healthy</option>
                        <option value="Very Healthy">Very Healthy</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label>Do you smoke?</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="smoke" id="smoke-yes" value="yes">
                        <label class="form-check-label" for="smoke-yes">Yes</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="smoke" id="smoke-no" value="no">
                        <label class="form-check-label" for="smoke-no">No</label>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <label>How many hours of sleep do you get per night?</label>
                    <select class="form-control" id="sleep">
                        <option value="">Select an option</option>
                        <option value="Less than 4 hours">Less than 4 hours</option>
                        <option value="4-6 hours">4-6 hours</option>
                        <option value="6-8 hours">6-8 hours</option>
                        <option value="More than 8 hours">More than 8 hours</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label>How much water do you drink daily?</label>
                    <select class="form-control" id="water">
                        <option value="">Select an option</option>
                        <option value="Less than 1 liter">Less than 1 liter</option>
                        <option value="1-2 liters">1-2 liters</option>
                        <option value="2-3 liters">2-3 liters</option>
                        <option value="More than 3 liters">More than 3 liters</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label for="comments">Additional Healthy Habits or Concerns</label>
                    <textarea class="form-control" id="comments" rows="3" placeholder="Enter your habits or concerns"></textarea>
                </div>
                <button id="finished-btn" type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <script type="text/javascript" src="/js/uuid4.js"></script>
    <script type="text/javascript">
        var token = "<?= $token; ?>";
        var participantUUID = "<?= $participantUUID;?>";

        $('#finished-btn').click(function(event) {
            event.preventDefault();

            let answers = {
                exercise: $('#exercise').val(),
                diet: $('#diet').val(),
                smoke: $('input[name="smoke"]:checked').val(),
                sleep: $('#sleep').val(),
                water: $('#water').val(),
                comments: $('#comments').val()
            };

            for (let key in answers) {
                if (!answers[key] && key !== 'comments') {
                    showError("Please answer all questions before submitting.");
                    return;
                }
            }

            $.ajax({
                url: '<?= $rootURL ?>/survey/done',
                type: 'POST',
                data: {
                    'answers': answers,
                    'token': token,
                    'participantUUID': participantUUID
                },
                success: function(data) {
                    if (data.success) {
                        window.location.href = "/survey/thank-you";
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

<?php
include_once __DIR__ . '/../_footer.php';
