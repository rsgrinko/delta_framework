$(document).ready(function () {
    $('.input-file input[type=file]').on('change', function () {
        let file = this.files[0];
        $(this).next().html(file.name);
    });

    let $navToggle = $('#navToggle'),
        $mainNav   = $('#mainNav');

    $navToggle.on('click', function () {
        let isOpen = $mainNav.toggleClass('open').hasClass('open');
        $navToggle.attr('aria-expanded', isOpen ? 'true' : 'false');
    });

    $mainNav.on('click', 'a', function () {
        $mainNav.removeClass('open');
        $navToggle.attr('aria-expanded', 'false');
    });
});

