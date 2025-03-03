<?php
/** @var UserSession $userSession */
$page = 'home';
include_once __DIR__ . '/_header.php';
?>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Dashboard - This is a DEMO dashboard, specific graphs or metrics can be added here</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
                <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <a href="/participants" class="text-decoration-none">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Participants</h5>
                        <h2 id="active-part"></h2>
                        <p class="card-text" id="total-part"></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/participants" class="text-decoration-none">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Devices</h5>
                        <h2 id="active-dev"></h2>
                        <p class="card-text">&nbsp;</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/survey/view" class="text-decoration-none">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Surveys Completed</h5>
                        <h2 id="survey-complete"></h2>
                        <p id="survey-total" class="card-text"></p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Protocol Compliance</h5>
                    <div id="complianceChart"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Participant State Distribution</h5>
                    <div id="mealDistributionChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/plotly.js/1.33.1/plotly.min.js" integrity="sha512-V0j9LhrK9IMNdFYZqh+IqU4cjo7wdxyHNyH+L0td4HryBuZ7Oq6QxP2/CWr6TituX31+gv5PnolvERuTbz8UNA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<?php include_once __DIR__ . '/_footer.php'; ?>

<script>
    $(document).ready(function() {
        updateStats();
        updateCompliance();
        updateMealDist();
    });

    function updateStats() {
        $.ajax({
            url: '/participants/home-stats',
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    let patientStats = data.patientStats;
                    let surveyStats = data.surveyStats;
                    $('#active-part').text(patientStats['active']);
                    $('#total-part').text(`Total: ${patientStats['total']}`);

                    $('#active-dev').text(patientStats['devices']);

                    $('#survey-complete').text(surveyStats['completed']);
                    $('#survey-total').text(`Total: ${surveyStats['total']}`);
                } else {
                    showError(data.error_message);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                console.error(status);
                console.error(error);
                showError("Error communicating with the server.");
            }
        });
    }

    function updateCompliance() {
        // Initialize compliance data object
        let complianceData = {};

        // Make AJAX call to fetch compliance data
        $.ajax({
            url: '/metrics/compliance',
            type: 'GET',
            success: function(data) {
                if (data.success) {
                    // Full week days array
                    const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                    // Get today's day index (0 = Sun, 1 = Mon, ..., 6 = Sat)
                    const todayIndex = new Date().getDay();

                    // Rotate the weekDays array to place today last
                    let reorderedDays = [...weekDays.slice(todayIndex + 1), ...weekDays.slice(0, todayIndex + 1)];

                    // Build a lookup object for compliance rates
                    const complianceLookup = {};
                    data.values.forEach(item => {
                        complianceLookup[item.day_of_week] = ((item.total_good / item.total_total)*100);
                    });

                    // Create compliance rates for the reordered days
                    let complianceRates = reorderedDays.map(day => {
                        return complianceLookup[day] !== undefined ? complianceLookup[day] : null; // Default to 100 if missing
                    });

                    // Update compliance data for the chart
                    reorderedDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    complianceRates = [10, 78, 30, 45, 81, 80, 65];
                    complianceData = {
                        x: reorderedDays,          // Rotated days
                        y: complianceRates,        // Corresponding compliance rates
                        type: 'scatter',
                        mode: 'lines+markers',
                        name: 'Compliance Rate (%)'
                    };

                    // Define layout for the chart
                    const complianceLayout = {
                        title: 'Weekly Compliance',
                        yaxis: { range: [0, 105], title: 'Compliance Rate (%)' },
                        height: 300
                    };

                    // Render the chart
                    Plotly.newPlot('complianceChart', [complianceData], complianceLayout);
                } else {
                    console.error("Error: Data fetch unsuccessful");
                }
            },
            error: function(request, error) {
                console.error("AJAX Error:", error);
            }
        });
    }


    function updateMealDist() {
        // meal distribution
        const mealDistributionData = [{
            type: 'bar',
            x: ['Breakfast', 'Lunch', 'Dinner', 'Snack'],
            y: [45, 32, 38, 28],
            marker: {
                color: ['#FF9999', '#99FF99', '#99CCFF', '#FFCC99']
            }
        }];

        const mealDistributionLayout = {
            title: 'Current Participant Meal Distribution',
            height: 300
        };

        Plotly.newPlot('mealDistributionChart', mealDistributionData, mealDistributionLayout);
    }
</script>