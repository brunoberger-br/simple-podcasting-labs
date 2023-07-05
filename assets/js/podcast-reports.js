jQuery(document).ready(
    function () {
        jQuery(".custom-date-range").hide();

        jQuery('.ga_datepicker').datepicker(
            {
                dateFormat: 'mm/dd/yy',
                changeMonth: true,
                changeYear: true
            }
        );

        jQuery('#report-date-range').on(
            'change',
            function () {
                if (jQuery(this).val() == 'custom') {
                    jQuery(".custom-date-range").show();
                } else {
                    jQuery(".custom-date-range").hide();
                }
            }
        );

        jQuery('#report_series').on(
            'change',
            function () {
                if (jQuery('#report_type').val() == 'episode') {
                    getSeriesEpisodes(jQuery(this).val());
                }
                return false;
            }
        );

        jQuery('#report_episodes').on(
            'change',
            function () {
                jQuery('#detail_id').val(jQuery(this).attr('data-podcast'));
            }
        );

        jQuery('#report-button').on(
            'click',
            function () {
                jQuery(this).prop('disabled', true);
                jQuery('#report_loading').show();
                jQuery('#report_run').submit();
            }
        );

        jQuery('#report_type').on(
            'change',
            function () {
                switch (jQuery(this).val()) {
                    case "summary":
                        jQuery('#date_selection').show();
                        jQuery('#episode_selection').hide();
                        jQuery('#series_selection').hide();
                        if (jQuery('#report-date-range').val() == 'custom') {
                            jQuery('.custom-date-range').show();
                        }
                        break;
                    case "episode":
                        jQuery('#date_selection').show();
                        jQuery('#episode_selection').show();
                        jQuery('#series_selection').show();
                        if (jQuery('#series_selection').length > 0) {
                            getSeriesEpisodes(jQuery('#report_series').val());
                        }
                        if (jQuery('#report-date-range').val() == 'custom') {
                            jQuery('.custom-date-range').show();
                        }
                        break;
                    case "series":
                        jQuery('#date_selection').show();
                        jQuery('#series_selection').show();
                        jQuery('#episode_selection').hide();
                        if (jQuery('#report-date-range').val() == 'custom') {
                            jQuery('.custom-date-range').show();
                        }
                        break;
                    case "downloads":
                        jQuery('#date_selection').hide();
                        jQuery('#episode_selection').hide();
                        jQuery('#series_selection').hide();
                        jQuery('.custom-date-range').hide();
                        break;
                    case "monthly_downloads":
                        jQuery('#date_selection').hide();
                        jQuery('#episode_selection').hide();
                        jQuery('#series_selection').hide();
                        jQuery('.custom-date-range').hide();
                        break;
                    default:
                        jQuery('#episode_selection').hide();
                        jQuery('#series_selection').hide();
                        break;
                }
            }
        );

        jQuery('body').on(
            'click',
            '.podcast_detail',
            function () {
                jQuery('#report_loading').show();
                if (jQuery('#series_selection').is(':visible')) {
                    getSeriesEpisodes(jQuery('#report_series').val(), jQuery(this).attr('data-podcast'));
                }
                jQuery('#report_type').val('episode');
                jQuery('#detail_id').val(jQuery(this).attr('data-podcast'));
                jQuery('#report_episodes').val(jQuery(this).attr('data-podcast'));
                jQuery('#episode_selection').show();
                jQuery('#report_run').submit();
                jQuery('#report_episodes').val(jQuery(this).attr('data-podcast'));

            }
        );

        jQuery('#report_run').on(
            'submit',
            function () {

                ajax_action = ajaxurl + '?action=podcast_report_run';

                if (jQuery('#report_type').val() == 'series') {
                    jQuery('#detail_id').val(jQuery('#report_series').val());
                } else if (jQuery('#report_type').val() == 'episode') {

                    jQuery('#detail_id').val(jQuery('#report_episodes').val());
                }

                formData = jQuery(this).serialize();

                jQuery.ajax(
                    {
                        type: 'POST',
                        url: ajax_action,
                        data: formData,
                        success: function (data) {
                            displayPodcastReportResults(data);
                        }, //jQuery('#report_episodes').val(jQuery(this).attr('data-podcast')); },
                        error: function (xhr, ajaxOptions, thrownError) {
                            displayPodcastReportResults(thrownError);
                        } // displayPodcastReportResults(thrownError) }
                    }
                );

                return false;
            }
        );

        if (autorun) {
            jQuery(".custom-date-range").show();
            jQuery("#report_type").val(report);
            jQuery('#report-button').trigger('click');

            if (report == 'summary') {
                jQuery('#report_product').val(0);
            }
        }

        function getSeriesEpisodes(series, selectedEpisode) {
            jQuery('#episodes_loading').show();
            jQuery('#report_episodes').hide();

            ajax_action = ajaxurl + '?action=podcast_series_episodes';
            jQuery.ajax(
                {
                    type: 'POST',
                    url: ajax_action,
                    data: "series=" + series,
                    success: function (data) {
                        jQuery('#report_episodes').html(data).show();
                        if (parseInt(selectedEpisode) > 0) {
                            jQuery('#report_episodes').val(selectedEpisode);
                        }
                        jQuery('#episodes_loading').hide();
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        displayPodcastReportResults(thrownError)
                    }
                }
            );

        }

    }
);

function displayPodcastReportResults(data) {
    jQuery('#report_loading').hide();
    jQuery('#report-button').prop('disabled', false);
    jQuery('#podcast-report-display').html(data);
}


function sortTable(n) {

    const tableItems = document.querySelectorAll(".analytics-table-heading");

    tableItems.forEach(e => {

        if (parseInt(e.getAttribute('data-order')) === n) {
            e.classList.toggle("down-arrow");
        }
        if (parseInt(e.getAttribute('data-order')) != n) {
            e.classList.remove("down-arrow");
        }
    });

    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById('custom-table-report');

    if (table === null) {
        return;
    }

    switching = true;
    // Set the sorting direction to ascending:
    dir = 'asc';
    /*
    Make a loop that will continue until
    no switching has been done: */
    while (switching) {
        // Start by saying: no switching is done:
        switching = false;
        rows = table.rows;
        /*
        Loop through all table rows (except the
        first, which contains table headers): */
        for (i = 1; i < (rows.length - 2); i++) {
            // Start by saying there should be no switching:
            shouldSwitch = false;
            /*
            Get the two elements you want to compare,
            one from current row and one from the next: */
            x = rows[i].getElementsByTagName('TD')[n];
            y = rows[i + 1].getElementsByTagName('TD')[n];
            /*
            Check if the two rows should switch place,
            based on the direction, asc or desc: */
            if (dir == 'asc') {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            } else if (dir == 'desc') {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    // If so, mark as a switch and break the loop:
                    shouldSwitch = true;
                    break;
                }
            }
        }
        if (shouldSwitch) {
            /*
            If a switch has been marked, make the switch
            and mark that a switch has been done: */
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            // Each time a switch is done, increase this count by 1:
            ++switchcount;
        } else {
            /*
            If no switching has been done AND the direction is "asc",
            set the direction to "desc" and run the while loop again. */
            if (switchcount == 0 && dir == 'asc') {
                dir = 'desc';
                switching = true;
            }
        }
    }
}

// Pagination code
$(document).ready(function () {
    $('#pagination').on('click', 'a', function (e) {
        e.preventDefault();
        var page = $(this).attr('data-page');
        loadPodcastReports(page);
    });
});

function loadPodcastReports(page) {
    // Make an AJAX request to fetch the data for the selected page
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'load_podcast_reports',
            page: page
        },
        success: function (response) {
            // Update the table with the new data
            $('#custom-table-report tbody').html(response);
        }
    });
}

