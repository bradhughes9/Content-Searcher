jQuery(document).ready(function ($) {
    $('.tab').on('click', function () {
        // Remove active class from all tabs
        $('.tab').removeClass('active');

        // Add active class to the clicked tab
        $(this).addClass('active');
    });
});
