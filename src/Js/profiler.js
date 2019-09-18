const chromeLauncher = require('chrome-launcher');
const CDP = require('chrome-remote-interface');

// see https://github.com/cyrus-and/chrome-remote-interface
// see https://github.com/GoogleChrome/chrome-launcher#readme



// args -----------------------

let url = null;
let wait = 3000;
let maxWait = 10000;
process.argv.slice(2).forEach((val, index) => {
    if (index === 0) {
        url = '' + val;
    }
    else if (index === 1) {
        wait = Math.max(0, Math.min(30000, parseInt(val)));
    }
    else if (index === 2) {
        maxWait = Math.max(0, Math.min(30000, parseInt(val)));
    }
});


async function profiler() {

    let prof = { // result object

        'items': {},

        'wait': wait,
        'reqNr': -1,
        'dom': null,
        'load': null,
        'complete': null,
        'error': null,
        'chromePid': null,
        'chromePort': null
    };
    let dataUrls = {};

    let chrome;
    let client;

    try {

        if (!url) {
            throw 'Have no URL?';
        }


        // launch chrome on random port ----------------------
        chrome = await chromeLauncher.launch({
            chromeFlags: [
                '--headless',
                '--disable-gpu',
                '--no-sandbox'
            ],
            logLevel: 'error',
            maxConnectionRetries: 5
        });

        prof.chromePort = chrome.port;
        prof.chromePid = chrome.pid;

        // start debug interface ----------------------
        client = await CDP(
            {
                port: chrome.port
            }
        );
        const {Network, Page, Runtime} = client;

        await Promise.all([
            Runtime.enable(),
            Network.enable(),
            Network.setCacheDisabled({'cacheDisabled': true}),
            Page.enable()
        ]);


        // Install profiling hooks ------------------------------------

        Network.requestWillBeSent((params) => {

            // console.log('SEND: ' + params.requestId + ' (' + params.request.url + ')');
            // console.log(params);

            if (
                params.request.url.substr(0, 5) === 'data:'
                || params.request.url.match('/delayReady')
            ) {
                dataUrls[params.requestId] = params.request.url;
                return;
            }

            prof.reqNr++;
            prof.items[params.requestId] = prepItem(
                params,
                prof.reqNr
            );
        });

        Network.responseReceived((params) => {

            // console.log('  REC: ' + params.requestId + ' --- HTTP: ' + params.response.status);
            // console.log(params);

            if (dataUrls[params.requestId]) {
                return;
            }

            if (prof.items[params.requestId]) {

                if (!isNaN(params.response.dataLength)) {
                    prof.items[params.requestId].size += params.response.dataLength;
                }
                prof.items[params.requestId].encodedSize += params.response.encodedDataLength;

                prof.items[params.requestId].type = params.type;

                prof.items[params.requestId].contentLength = parseInt(params.response.headers['content-length']);
                prof.items[params.requestId].mime = params.response.headers['content-type']
                prof.items[params.requestId].status = params.response.status;

                let timing = params.response.timing;

                if (timing) {
                    prof.items[params.requestId].requestTime = timing.requestTime;
                    prof.items[params.requestId].proxyStart = timing.proxyStart;
                    prof.items[params.requestId].connectStart = timing.connectStart;
                    prof.items[params.requestId].receiveHeadersEnd = timing.receiveHeadersEnd;
                }
                else {
                    console.error('(!) responseReceived: no timing?');
                    console.error(params);
                    console.error("\n");
                }
            }
            else {
                console.error('(RR) Missing item for requestId: ' + params.requestId);
            }

            // console.log(params.response.securityDetails);
        });

        Network.dataReceived((params) => {

            // console.log('  DATA: ' + params.requestId + ' --- LEN: ' + params.dataLength + ' / ' + params.encodedDataLength + ' TS: ' + params.timestamp);
            // console.log(params);

            if (dataUrls[params.requestId]) {
                return;
            }

            if (prof.items[params.requestId]) {
                prof.items[params.requestId].chunks++;
                prof.items[params.requestId].size += params.dataLength;
                prof.items[params.requestId].encodedSize += params.encodedDataLength;
                prof.items[params.requestId].lastDataTimestamp = params.timestamp;
            }
            else {
                console.error('(DR) Missing item for requestId: ' + params.requestId);
            }
        });

        // --------

        Network.loadingFinished((params) => {

            // console.log('  FIN: ' + params.requestId);
            // console.log(params);

            if (dataUrls[params.requestId]) {
                return;
            }

            if (prof.items[params.requestId]) {
                prof.items[params.requestId].finished = true;
            }
            else {
                console.error('(LF) Missing item for requestId: ' + params.requestId);
            }
        });

        Network.loadingFailed((params) => {

            // console.log('  (!) FAIL: ' + params.requestId);
            // console.log(params);

            console.error('(!) loadingFailed');
            console.error(params);
            console.error("\n");

            if (prof.items[params.requestId]) {
                prof.items[params.requestId].failed = params;
                // console.log(prof.items[params.requestId]);
            }
            else {
                console.error('(LE) Missing item for requestId: ' + params.requestId);
            }
        });

        Network.requestServedFromCache((params) => {

            if (dataUrls[params.requestId]) {
                return;
            }

            // console.log('  (!) CACHE: ' + params.requestId);

            if (prof.items[params.requestId]) {
                prof.items[params.requestId].cached = params;
                // console.log(prof.items[params.requestId]);
            }
            else {
                console.error('Missing item for requestId: ' + params.requestId);
            }

            console.error('(!) requestServedFromCache');
            console.error(params);
            console.error("\n");
        });

        Network.requestIntercepted((params) => {
            // console.log('  (!) INTERCEPT: ' + params.requestId);

            console.error('(!) requestIntercepted');
            console.error(params);
            console.error("\n");

            if (prof.items[params.requestId]) {
                prof.items[params.requestId].intercepted = params;
            }
            else {
                console.error('(RI) Missing item for requestId: ' + params.requestId);
            }
        });

        Page.domContentEventFired((params) => {
            // console.log('>> DOMCONTENT ' + ' --- TS: ' + params.timestamp);
            prof.dom = params.timestamp;
        });

        Page.loadEventFired((params) => {
            // console.log('>> LOAD ' + ' --- TS: ' + params.timestamp);
            prof.load = params.timestamp;
        });


        // Start -------------------

        await Page.navigate({url: url});

        let timeout = await Promise.race([
            Page.domContentEventFired(),
            sleep(maxWait).then(function(){return 'TIMEOUT';})
        ]);
        if (!prof.dom || timeout === 'TIMEOUT') {
            throw "Timeout";
        }

        // sometimes there is no load event --- so better not wait for it??
        // await Page.loadEventFired();

        // wait some time for subseq requests
        await sleep(wait);

        if (!prof.load) {
            throw "No Load Timestamp?";
        }

        // hack for measuring readystate complete ---------
        prof.complete = prof.load;

        let pageComplete = await Promise.race([
            Runtime.evaluate({
                expression: 'window.ovkadcheckComplete'
            }),
            sleep(500).then(function(){return 'TIMEOUT';})
        ]);
        if (pageComplete !== 'TIMEOUT') {
            let pageLoaded = await Promise.race([
                Runtime.evaluate({
                    expression: 'window.ovkadcheckLoad'
                }),
                sleep(500).then(function(){return 'TIMEOUT';})
            ]);
            if (pageLoaded !== 'TIMEOUT') {
                if (
                    pageLoaded.result.value != undefined
                    && pageComplete.result.value != undefined
                ) {
                    let delta = (pageLoaded.result.value - pageComplete.result.value) / 1000;
                    if (delta > 0) {
                        prof.complete -= delta;
                    }
                }
            }
            else {
                console.error('eval pageLoaded TIMEOUT');
            }
        }
        else {
            console.error('eval pageComplete TIMEOUT');
        }

    }
    catch (err) {
        prof.error = err;
        console.error(err);
        console.error("\n");
    }
    finally {

        if (chrome) {
            chrome.kill();
        }

        if (client) {
            await client.close();
        }

        console.log( // dump results to stdout
            JSON.stringify(
                prof,
                null,
                4
            )
        );
    }
}

function sleep(ms)
{
    return new Promise(resolve => setTimeout(resolve, ms));
}

function prepItem(params, nr)
{
    return {
        'nr': nr,

        'url': params.request.url,
        'type': null,

        'status': null,
        'mime': null,

        'queueTimestamp': params.timestamp,
        'requestTime': null,
        'proxyStart': null,
        'connectStart': null,
        'receiveHeadersEnd': null,
        'lastDataTimestamp': null,

        'contentLength': null,
        'size': 0,
        'encodedSize': 0,
        'chunks': 0,

        'finished': null,
        'failed': null,
        'intercepted': null,
        'cached': null,
    };
}

profiler();