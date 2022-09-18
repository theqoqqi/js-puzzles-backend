$(() => {
    let $numberSpan = $('#number-span');
    let $generateButton = $('#generate-button');

    $generateButton.click(e => {
        $numberSpan.text(randomInt(100000, 999999));
    });

    function randomInt(min, max) { console.error(new Error('QWER')); throw new Error('qwer');
        return min + Math.floor(Math.random() * (max - min + 1));
    }
});
