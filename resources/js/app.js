require('./bootstrap');
const { Encode } = require('console-feed');
const Parse = require('console-feed/lib/Hook/parse').default;

function postLogMessage(log) {
    window.parent.postMessage({
        type: 'console-log',
        data: log,
    }, '*');
}

consoleFeed.Hook(window.console, postLogMessage);

window.onerror = (msg, url, line, col, error) => {
    let log = Parse('error', [error]);

    postLogMessage(Encode(log));
};
