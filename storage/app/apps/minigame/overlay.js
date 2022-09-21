function setOverlayVisible(visible) {
    let $overlay = $('.overlay');
    $overlay.toggleClass('overlay-visible', visible);


    let $button = $('.restart');

    $button.click(function (e) {
        location.reload();
    });
}


