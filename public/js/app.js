$(document).foundation();

var App = {

    updating: 0,
    ready: false,
    noAni: false,

    baseUrl: 'backend/api.php',

    handlers: function () {

        // Formats dropdown
        $('#formats').on('change', function () {
            App.callApi({'format': $(this).val()});
            $('#customSpecs').hide();
            if ($(this).val() === '0') {
                $('#customSpecs').show();
            }
        });

        // Custom Specs
        $('#customInit').on('change', function () {
            App.callApi({'customInit': $(this).val()});
        });
        $('#customSub').on('change', function () {
            App.callApi({'customSub': $(this).val()});
        });
        $('#customWidth').on('change', function () {
            App.callApi({'customWidth': $(this).val()});
        });
        $('#customHeight').on('change', function () {
            App.callApi({'customHeight': $(this).val()});
        });

        // Source
        $('#source').on('change', function () {
            App.callApi({'source': $(this).val()});
        }).on('keyup', function () {
            App.checkReady();
        })
        ;

        // Upload archive
        $('#archive').on('click', function(e){
            if (App.updating > 0) {
                e.preventDefault();
            }
        }).on('change', function () {
            App.sendFile(this.files[0]);
        });

        // Assets
        $('#assets').on('change', 'input', function () {
            var data = [];
            $.each($('#assets input'), function (k, v) {
                data.push($(v).val())
            });
            App.callApi({'assets': data});
        });
        $('#add-asset').on('click', function () {
            if ($('#assets').find('input').length < 12) {
                App.addAssetField();
            }
        });

        // IAB mode dropdown
        $('#iabMode').on('change', function () {
            App.callApi({'iabMode': $(this).val()});
        });

        // Iframe mode dropdown
        $('#iframeMode').on('change', function () {
            App.callApi({'iframeMode': $(this).val()});
        });

        // Check Button
        $('#check').on('click', function () {
            if (App.updating === 0) {
                App.callApi(
                    {'action': 'check'},
                    true,
                    true
                );
            }
        });

        // Flush/Reset Button
        $('#flush').on('click', function () {
            if (App.updating === 0) {
                App.scrollTo($('body'));
                App.callApi({'action': 'flush'}, true);
                $('#results').hide();
                $('#customSpecs').hide();
                $('#pdf').attr('disabled', true);
            }
        });
    },

    scrollTo: function($e)
    {
        $('html,body').animate({scrollTop: $e.offset().top}, 'slow');
    },

    startUpdating: function () {
        document.body.style.cursor = 'progress';
        $('.spinner').show();
        App.updating++;
        App.checkReady();
    },

    finishUpdating: function () {
        App.updating--;
        if (App.updating <= 0) {
            document.body.style.cursor = 'default';
            $('.spinner').hide();
            App.updating = 0;
        }
        App.checkReady();
    },

    checkReady: function() {
        App.ready =
            $('#formats').val()
            && (
                $('#archiveName').val().length > 0
                || $('#source').val().trim().length > 0
            )
        ;
        App.buttonsDisabledState();
    },

    buttonsDisabledState: function()
    {
        let show = true;
        if (App.updating > 0) {
            show = false;
        }
        $('#check').attr('disabled', !show || !App.ready);
        // $('#pdf').attr('disabled', !show || !App.ready);

        $('#flush').attr('disabled', !show);
    },

    addAssetField: function ()
    {
        $('#assets').append(
            '<input data-added="1" type="url" maxlength="1024" />'
        );
    },

    updateFormats: function (f)
    {
        // Prepare dropdown values
        var $dd = $('#formats');
        $dd.empty();
        $dd.append(new Option('Bitte wählen', ''));
        $.each(f, function (k, v) {
            $dd.append(new Option(v.description, v.id));
        });
    },

    updateParameters: function (p)
    {
        // Set Format
        $('#formats').val(p.format);
        if (p.format === '0') {
            $('#customSpecs').show();
        }

        // Custom specs
        $('#customInit').val(p.customInit);
        $('#customSub').val(p.customSub);
        $('#customWidth').val(p.customWidth);
        $('#customHeight').val(p.customHeight);

        // Set archive name
        $('#archiveName').val(p.archiveName);
        $('#archive').val('');

        // Set Source
        $('#source').val(p.source);

        // Set Assets
        var a = p.assets;
        var c = a.length;
        $('#assets input[data-added]').remove();
        for (var n = 1; n < c; n++) {
            App.addAssetField();
        }
        $.each($('#assets input'), function (k, v) {
            var val = a.shift();
            $(v).val(val);
        });

        // IAB Mode
        $('#iabMode').val(p.iabMode);

        // Iframe Mode
        $('#iframeMode').val(p.iframeMode);
    },

    callApi: function (data, update, results)
    {
        App.startUpdating();
        $.ajax(
            App.baseUrl,
            {
                'method': 'POST',
                'data': data,
                'error': function (e) {
                    App.finishUpdating();
                    toastr.error('Fehler! (' + e.statusText + ')<br />' + e.responseText);
                },
                'success': function (response) {
                    App.toasts(response);

                    if (update) {
                        App.updates(response);
                    }
                    App.finishUpdating();
                    App.checkReady();

                    if (update) {

                        if (response.state && response.state.results.profiling) {
                            let $r = $('#results');
                            let prof = response.state.results.profiling;
                            if (
                                !prof
                                || prof.length === 0
                                || prof.items.length === 0
                                || prof.error
                            ) {
                                if (prof.error) {
                                    if (prof.error.code) {
                                        toastr.error(prof.error.code);
                                    }
                                    else {
                                        toastr.error(prof.error);
                                    }
                                }
                                else if (prof.items && prof.items.length === 0) {
                                    toastr.error('Keine Anfragen?');
                                }
                                else {
                                    toastr.error('Fehler!');
                                }
                                $('#pdf').attr('disabled', true);
                                $r.fadeOut();
                            }
                            else { // draw all results
                                $r.fadeIn();
                                $('#pdf').attr('disabled', false);
                                App.results(
                                    response.state.results, false
                                );
                                if (results) {
                                    App.scrollTo($r);
                                }
                            }
                        }
                    }
                }
            }
        );
    },

    sendFile: function(file)
    {
        if (!file) {
            return;
        }
        if (file.size > 10000000) {
            toastr.error('Datei zu groß!');
            return;
        }

        // let valid = [
        //     'application/zip',
        //     'application/octet-stream',
        //     'application/x-zip-compressed',
        //     'multipart/x-zip'
        // ];
        // if (!valid.includes(file.type)) {
        //     toastr.error('Kein Zip-Archiv!?');
        //     return;
        // }

        App.startUpdating();
        $.ajax({
            type: 'post',
            url: App.baseUrl + '?file=' + file.name,
            data: file,
            success: function (response) {
                App.toasts(response);
                App.updates(response);
                App.finishUpdating();
                App.checkReady();
            },
            'error': function (e) {
                App.finishUpdating();
                toastr.error('Fehler! ' + e.statusText);
            },
            processData: false,
            contentType: file && file.type ? file.type : ''
        });
    },

    toasts: function (r) {
        if (r.status) {
            if (r.status.warning) {
                toastr.warning(r.status.warning);
            }
            if (r.status.error) {
                toastr.error(r.status.error);
            }
            if (r.status.info) {
                toastr.info(r.status.info);
            }
        }
        else {
            if (!r.state) {
                toastr.error('State?');
            }
        }
    },

    // update form fields
    updates: function (r) {
        if (r.formats && r.formats.list) {
            App.updateFormats(
                r.formats.list
            );
        }
        if (r.state && r.state.parameters) {
            App.updateParameters(
                r.state.parameters
            );
        }
    },

    // ---------------------------------------
    // AUSWERTUNG
    // ---------------------------------------

    results: function (
        results,
        noAni
    )
    {
        if (!results.profiling) {
            return;
        }

        App.noAni = noAni;

        // ---------------

        $('#specsFormat').html(
            results.format.description
        );
        $('#specsSizes').html(
            results.format.maxSizeInit + 'k/' + results.format.maxSizeSubload + 'k'
        );
        $('#specsDim').html(
            results.format.width + 'px * ' + results.format.height + 'px'
        );
        $('#specsInput').html(
            results.archiveName.length
                ? results.archiveName
                : 'Werbemittel Eingabe'
        );
        $('#created').html(
            results.created
        );

        $('#status').html('');

        App.resultsTable(results.profiling);

        App.initialChart(results.profiling, results.format);
        App.subloadChart(results.profiling, results.format);
        App.requestsChart(results.profiling);

        App.byTypeChart(results.profiling, 'byTypeSizeChart', 'kb', 'size');
        App.byTypeChart(results.profiling, 'byTypeRequestsChart', '%', 'req');

        App.loadTimesChart(results.profiling);

        if (!$('#status').html()) {
            $('#status').append('<span class="label success">OK</span>');
        }

        $('#preview')
            .width(results.format.width)
            .height(results.format.height)
            .attr('src', results.previewUrl)
        ;
    },

    resultsTable: function (profiling)
    {
        let $tbody = $('#resultRows');
        $tbody.empty();
        let sslErrors = 0;
        $.each(profiling.items, function (k, v) {
            let t = v.url.split('?');
            let u = t[0];

            let siz = v.encodedSize;
            let tim = App.calcLatency(v);

            let nr = App.typeMap()[v.type];
            if (nr !== 0 && !nr) {
                nr = 8;
            }

            let vendor = '';
            if (v.vendor.length > 0) {
                vendor = '<strong>' + v.vendor + '</strong><br />';
            }

            let errorText = '';
            let sslError = false;
            if (v.failed) {
                errorText = v.failed.errorText;
                if (v.failed.errorText.match('net::ERR_CERT')) {
                    sslError = true;
                    App.addErrorLabel('SSL: ' + v.failed.errorText, '');
                }
                else {
                    App.addErrorLabel('Ladefehler', '');
                }
            }
            if (v.status < 200 || v.status > 299) {
                App.addErrorLabel('HTTP: ' + v.status, '');
            }

            let sslUrl = v.url.toLowerCase().startsWith('https');
            if (!sslUrl) {
                sslError = true;
                sslErrors++;
                if (!v.failed) {
                    errorText = 'Unsicherer URL!';
                }
            }

            let isSubload = v.queueTimestamp >= profiling.complete;

            if (vendor && isSubload) {
                App.addErrorLabel('Vendor im Subload!', vendor);
            }

            $tbody.append(
                '<tr>' +

                   // number
                '  <td class="text-right">#' + v.nr + '</td>' +

                   // url
                '  <td title="' + v.url + '">' + vendor +
                '    <a href="' + v.url + '">' +
                        (u.length > 64 ? u.substr(0, 64) + '...' : u) +
                '    </a></td>' +

                   // init/subload
                '  <td>' +
                    (isSubload
                        ? (
                            vendor
                                ? '<i title="Subload" class="fa fa-clock-o" style="color:#CC4A37"></i>'
                                : '<i title="Subload" class="fa fa-clock-o"></i>'
                        )
                        : ''
                    ) +
                '  </td>' +

                   // error
                '  <td style="color:#CC4A37">' +
                    (
                        v.failed
                        || errorText
                        || (v.status < 200 || v.status > 299)
                        ? (
                            sslError
                            ? '<i title="' + errorText + '" class="fa fa-lock"></i>'
                            : '<i class="fa fa-exclamation-triangle"></i>'
                          )
                        : ''
                    ) +
                '  </td>' +

                   // type
                '  <td>' + App.typeLabels()[nr] + '</td>' +

                   // time
                '  <td class="text-right">' +
                    Math.round(tim) +
                '  </td>' +

                   // size
                '  <td class="text-right" title="' + siz + '">' +
                    (Math.round(siz / 100.24) / 10) +
                '  </td>' +

                   // http-status
                '  <td ' + (v.status != '200' ? 'style="color:#CC4A37"' : '') + '>' +
                    (v.status ? v.status : '?') +
                '  </td>' +

                '</tr>'
            );
        });

        if (sslErrors > 0) {
            App.addErrorLabel(sslErrors + ' unsichere URLs!', '');
        }
    },

    addErrorLabel: function(err, title)
    {
        $('#status').append(
            '<span class="label alert" title="' + title + '">' + err + '</span> '
        );
    },

    loadTimesChart: function (profiling)
    {
        let $c = App.newCanvas('loadingTimes');

        let colors = App.chartColors();

        let labels = [];
        let data1 = [];
        let data2 = [];
        let data3 = [];
        let bgWhite = [];

        // ------ find min/first and max/latest stamp

        let min = null;
        let max = 0;
        $.each(profiling.items, function (k, item) {
            if (min === null) {
                min = item.queueTimestamp;
            }
            else {
                min = Math.min(min, item.queueTimestamp);
            }
            max = Math.max(max, item.queueTimestamp);
            max = Math.max(max, item.requestTime + (item.receiveHeadersEnd / 1000));
            max = Math.max(max, item.lastDataTimestamp);
            if (item.failed !== null) {
                max = Math.max(max, item.failed.timestamp);
            }
        });
        max = Math.max(max, profiling.dom);
        max = Math.max(max, profiling.load);

        // ------

        let tmp = (max - min) * 1000;
        let maxScale = null;
        $.each(App.timeline(), function(k, value) {
            if (tmp < value) {
                maxScale = value;
            }
        });

        // ------ vertical annotation lines

        var annotations = [];
        annotations.push({ // DOM
            type: 'line',
            mode: 'vertical',
            scaleID: 'x-axis-0',
            value: (((profiling.dom - min) * 1000) / maxScale) * 10000,
            borderColor: 'rgb(100, 255, 100, 1)',
            borderWidth: 2,
        });
        if (profiling.load) {
            annotations.push({ // LOAD
                type: 'line',
                mode: 'vertical',
                scaleID: 'x-axis-0',
                value: (((profiling.load - min) * 1000) / maxScale) * 10000,
                borderColor: 'rgb(255, 100, 100, 0.5)',
                borderWidth: 1,
            });
        }
        if (profiling.complete) {
            annotations.push({ // COMPLETE
                type: 'line',
                mode: 'vertical',
                scaleID: 'x-axis-0',
                value: (((profiling.complete - min) * 1000) / maxScale) * 10000,
                borderColor: 'rgb(100, 100, 255, 0.5)',
                borderWidth: 4,
            });
        }

        // -----------------

        var n = 1;
        $.each(profiling.items, function (k, item) {
            labels.push('#' + n);

            let queue = item.queueTimestamp;
            let req = item.requestTime;
            let end = item.lastDataTimestamp;
            if (
                item.failed
                && item.failed.timestamp
            ) {
                end = item.failed.timestamp;
                if (req) {
                    App.loadTimeRow(req - min, end - min, maxScale, data1, data2, data3);
                }
                else if (queue) {
                    App.loadTimeRow(queue - min, end - min, maxScale, data1, data2, data3);
                }
                else {
                    data1.push(0);
                    data2.push(10000);
                    data3.push(0);
                }
            }
            else if (
                req === null
                || end === null
            ) {
                if (req) {
                    // TODO only if contentLength = 0?
                    let headers = req + (item.receiveHeadersEnd / 1000);
                    App.loadTimeRow(req - min, headers - min, maxScale, data1, data2, data3);
                }
                else if (queue) {
                    let tmp = Math.floor((queue - min) * 100) / 100;
                    data1.push(tmp);
                    data2.push(Math.floor((100 - tmp) * 100) / 100);
                    data3.push(0);
                }
                else {
                    data1.push(0);
                    data2.push(10000);
                    data3.push(0);
                }
            }
            else {
                // if (item.proxyStart > -1) {
                //     console.log(v);
                // }
                App.loadTimeRow(req - min, end - min, maxScale, data1, data2, data3);
            }

            bgWhite.push('rgba(255,255,255,0.5)');
            n++;
        });

        // Create chart
        let opt = {
            legend: {
                display: false,
            },
            tooltips: {
                enabled: false
            },
            scales: {
                xAxes: [
                    {
                        stacked: true,
                        ticks: {
                            callback: function (value) {
                                return (value * (maxScale / 10000)) + 'ms';
                            }
                        }
                    }
                ],
                    yAxes: [
                    {
                        stacked: true,
                    },
                ]
            },
            annotation: {
                annotations: annotations
            }
        };
        if (App.noAni) {
            opt.animation = false;
        }
        new Chart($c[0].getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data1,
                        backgroundColor: bgWhite,
                    },
                    {
                        data: data2,
                        backgroundColor: colors,
                    },
                    {
                        data: data3,
                        backgroundColor: bgWhite,
                    },
                ]
            },
            options: opt
        });
    },

    initialChart: function(profiling, format)
    {
        let $c = App.newCanvas('initialChart');

        let max = format.maxSizeInit;

        let val = 0;
        $.each(profiling.items, function (k, item) {
            if (item.queueTimestamp < profiling.complete) {
                val += item.encodedSize;
            }
        });
        val = Math.round(val / 1024);

        let color = '#469E72';
        if (val >= max) {
            $('#status').append('<span class="label alert">Initial K-Weight</span> ');
            color = '#CC4A37';
        }

        let opt = {
            legend: {
                display: false
            },
            rotation: Math.PI,
            circumference: Math.PI
        };
        if (App.noAni) {
            opt.animation = false;
        }
        new Chart($c[0].getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [
                        val,
                        Math.max(0, max - val)
                    ],
                    backgroundColor: [
                        color,
                        '#CCC',
                    ]
                }],
                labels: [
                    'Gewicht Initial (kb)',
                    'Spielraum (kb)',
                ]
            },
            options: opt
        });
        $('#initialChartVal').html(val + 'k');
        $('#initialChartMax').html(max + 'k');
    },
    subloadChart: function(profiling, format)
    {
        let $c = App.newCanvas('subloadChart');

        let max = format.maxSizeSubload;
        let val = 0;
        $.each(profiling.items, function (k, item) {
            if (item.queueTimestamp >= profiling.complete) {
                val += item.encodedSize;
            }
        });
        val = Math.round(val / 1024);

        let color = '#469E72';
        if (val >= max) {
            $('#status').append('<span class="label alert">Subload K-Weight</span> ');
            color = '#CC4A37';
        }

        let opt = {
            legend: {
                display: false
            },
            rotation:  Math.PI,
            circumference: Math.PI
        };
        if (App.noAni) {
            opt.animation = false;
        }
        new Chart($c[0].getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [
                        val,
                        Math.max(0, max - val)
                    ],
                    backgroundColor: [
                        color,
                        '#CCC',
                    ]
                }],
                labels: [
                    'Gewicht Subload (kb)',
                    'Spielraum (kb)',
                ]
            },
            options: opt
        });
        $('#subloadChartVal').html(val + 'k');
        $('#subloadChartMax').html(max + 'k');
    },
    requestsChart: function(profiling)
    {
        let $c = App.newCanvas('requestsChart');

        let max = 20;
        let val = profiling.reqNr;


        let color = '#469E72';
        if (val >= max)  {
            $('#status').append('<span class="label alert">Anfragen</span> ');
            color = '#CC4A37';
        }

        let opt = {
            legend: {
                display: false
            },
            rotation:  Math.PI,
            circumference: Math.PI
        };
        if (App.noAni) {
            opt.animation = false;
        }
        new Chart($c[0].getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [
                        val,
                        Math.max(0, max - val)
                    ],
                    backgroundColor: [
                        color,
                        '#CCC',
                    ]
                }],
                labels: [
                    'Anfragen',
                    'Spielraum',
                ]
            },
            options: opt
        });
        $('#requestsChartVal').html(val);
        $('#requestsChartMax').html(max);
    },
    byTypeChart: function(profiling, id, suffix, mode)
    {
        let $c = App.newCanvas(id);

        let byLabel = [];
        $.each(profiling.items, function (k, item) {
            let label = '';
            let nr = App.typeMap()[item.type];
            if (nr !== 0 && !nr) {
                nr = 8;
            }
            if (!byLabel[nr]) {
                byLabel[nr] = 0;
            }

            if (mode === 'size') {
                byLabel[nr] += item.encodedSize;
            }
            else {
                byLabel[nr] += 1;
            }
        });
        $.map(byLabel, function(val, i) {
            if (mode === 'size') {
                byLabel[i] = Math.round(val / 1024);
            }
            else {
                byLabel[i] = Math.round((val / profiling.reqNr * 1000)) / 10;
            }
        });

        let opt = {
            legend: {
                position: 'bottom',
                labels: {
                    filter: function(i, d) {
                        return !i.hidden;
                    },
                    generateLabels: function(chart)
                    {
                        let data = chart.data;
                        return data.labels.map(function(label, i) {
                            let meta = chart.getDatasetMeta(0);
                            let ds = data.datasets[0];
                            let arc = meta.data[i];
                            let f = Chart.helpers.getValueAtIndexOrDefault;
                            let hidden = isNaN(ds.data[i]) || meta.data[i].hidden;
                            let value = hidden ? 0 : chart.config.data.datasets[arc._datasetIndex].data[arc._index];
                            return {
                                text: hidden ? label : label + ': ' + value + suffix,
                                fillStyle: f(ds.backgroundColor, i, chart.options.elements.arc.backgroundColor),
                                hidden: hidden,
                                index: i
                            };
                        });
                    }
                }
            },
            tooltips: {
                callbacks: {
                    label: function (i, v) {
                        return v.labels[i.index] + ': ' + v.datasets[0].data[i.index] + suffix;
                    }
                }
            },
            rotation: Math.PI,
            circumference: Math.PI,
        };
        if (App.noAni) {
            opt.animation = false;
        }
        new Chart($c[0].getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: byLabel,
                    backgroundColor: App.typeColors(),
                }],
                labels: App.typeLabels()
            },
            options: opt
        });
    },

    chartColors: function()
    {
      return [
          '#9E4672',
          '#46729E',
          '#729E46',
          '#469E72',
          '#DBDDD1',
          '#BEA7D5',
          '#A7BED5',
          '#D7DDC5',

          '#9E4672',
          '#46729E',
          '#729E46',
          '#469E72',
          '#DBDDD1',
          '#BEA7D5',
          '#A7BED5',
          '#D7DDC5',

          '#9E4672',
          '#46729E',
          '#729E46',
          '#469E72',
          '#DBDDD1',
          '#BEA7D5',
          '#A7BED5',
          '#D7DDC5',

          '#9E4672',
          '#46729E',
          '#729E46',
          '#469E72',
          '#DBDDD1',
          '#BEA7D5',
          '#A7BED5',
          '#D7DDC5',
      ]
    },

    typeMap: function() {
      return {
          'Image': 3,
          'Document': 0,
          'Script': 1,
          'Stylesheet': 2,
          'Font': 5,
          'XHR': 8,
          'Fetch': 8, // ???
          'Media': 4,
      };
    },

    typeLabels: function()
    {
        return [
            'Html',
            'Script',
            'Styles',
            'Image',
            'Media',
            'Font',
            'Flash',
            'Ajax',
            'Unbek.',
        ];
    },

    typeColors: function()
    {
        return [
            '#9E4672',
            '#46729E',
            '#729E46',
            '#469E72',
            '#DBDDD1',
            '#BEA7D5',
            '#A7BED5',
            '#D7DDC5',
        ];
    },

    loadTimeRow: function(e, f, maxScale, data1, data2, data3)
    {
        let r = f - e;
        let x = Math.floor(((e * 1000) / maxScale) * 10000);
        let y = Math.floor(((r * 1000) / maxScale) * 10000);
        let z = 10000 - x - y;

        data1.push(x);
        data2.push(y);
        data3.push(z);
    },

    newCanvas: function(id) {
        let $container = $('#' + id);
        let $c = $('<canvas style="width:100%">');
        $container.empty();
        $c.appendTo($container);
        return $c;
    },

    timeline: function()
    {
        return [
            10,
            50,
            100,
            250,
            500,
            750,
            1000,
            1500,
            2000,
            2500,
            3000,
            4000,
            5000,
            6000,
            7500,
            8000,
            10000,
            15000,
            20000,
            25000,
            30000,
            40000,
            50000,
            100000,
            200000,
            500000,
            1000000,
        ].reverse();
    },

    calcLatency: function(item)
    {
        let queue = item.queueTimestamp;
        let req = item.requestTime;
        let end = item.lastDataTimestamp;

        if (
            item.failed
            && item.failed.timestamp
        ) {
            if (req) {
                return (item.failed.timestamp - req) * 1000;
            }
            else if (queue) {
                return (item.failed.timestamp - queue) * 1000;
            }
            else {
                return 0;
            }
        }
        else if (
            req === null
            || end === null
        ) {
            if (req) {
                let headers = req + (item.receiveHeadersEnd / 1000);
                return (headers - req) * 1000;
            }
            else {
                return 0;
            }
        }
        else {
            return (end - req) * 1000;
        }
    }
};
