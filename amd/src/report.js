define(['core/log', 'jquery', 'require', 'core/notification', 'core/ajax'], function(logger, $, require, notification, ajax) {
    var ReportRunner = function(visualize, reportid, fittocontainer) {

        var myself = this;

        var report = null;
        var inputControls = null;
        var containerid = '#block_zoola_reports-' + reportid;
        var SELECTORS = {
            reportContainer: containerid + ' > div.block_zoola_reports_report',
            controls: containerid + ' > div.block_zoola_reports_controls',
            inputControls: containerid + ' > div.block_zoola_reports_inputcontrols',
            pagingbar: containerid + ' div.block_zoola_reports_pagingbar',
            button: {
                first: containerid + ' div.block_zoola_reports_pagingbar > button.block_zoola_reports_first',
                previous: containerid + ' div.block_zoola_reports_pagingbar > button.block_zoola_reports_previous',
                next: containerid + ' div.block_zoola_reports_pagingbar > button.block_zoola_reports_next',
                last: containerid + ' div.block_zoola_reports_pagingbar > button.block_zoola_reports_last'
            },
            pageCurrent: containerid + ' div.block_zoola_reports_pagingbar > input.block_zoola_reports_pagecurrent',
            pageCount: containerid + ' div.block_zoola_reports_pagingbar > span.block_zoola_reports_pagecount',
            emptyReport: containerid + ' div.emptyreport',
            loading: containerid + ' div.block_zoola_reports_loading',
            loadingMessage: containerid + ' div.block_zoola_reports_loading span.message',
            exportpdf: containerid + ' button.block_zoola_reports_exportpdf',
            exportxlsx: containerid + ' button.block_zoola_reports_exportxlsx',
            backButton: containerid + ' button.block_zoola_reports_back',
            filterButton: containerid + ' button.block_zoola_reports_showfilters',
            filtersBody: containerid + ' div.modal-body',
            emailButton: containerid + ' button.block_zoola_reports_email'
        };

        var reportStack = []; // Used for drill through.

        function errorHandler(error) {
            var message = error.message;
            if (message.indexOf('QueryExecutionTooManyRowsException') >= 0) {
                message = "The query returned too many rows.";
            }
            $(SELECTORS.reportContainer).html(message);
            $(SELECTORS.loading).hide();
        }

        function showEmptyReport(totalPages) {
            if (totalPages === 0) {
                $(SELECTORS.reportContainer).html("Report is empty");
                $(SELECTORS.loading).hide();
            }
        }

        function updatePaging() {
            var currentPage = report.pages() || 1;
            var lastPage = report.data().totalPages;
            showEmptyReport(lastPage);
            if (lastPage > 1) {
                $(SELECTORS.pageCurrent).val(currentPage);
                $(SELECTORS.pageCount).html(' of ' + lastPage);
                $(SELECTORS.button.first).prop('disabled', currentPage <= 1);
                $(SELECTORS.button.previous).prop('disabled', currentPage <= 1);
                $(SELECTORS.button.next).prop('disabled', currentPage >= lastPage);
                $(SELECTORS.button.last).prop('disabled', lastPage === undefined || currentPage >= lastPage);
                $(SELECTORS.pagingbar).show();
            } else {
                $(SELECTORS.pagingbar).hide();
            }
        }

        function afterReportRendered() {
            var data = report.data();
            showEmptyReport(data.totalPages);
            $(SELECTORS.loading).hide();
            $(SELECTORS.controls).show();
            $([SELECTORS.exportpdf, SELECTORS.exportxlsx, SELECTORS.emailButton].join(', ')).prop('disabled', false);
            updatePaging();

            // Fix container's height.
            if (fittocontainer) {
                var tableWidth = $(SELECTORS.reportContainer + ' table.jrPage').width();
                if (tableWidth > 0) {
                    var tableHeight = $(SELECTORS.reportContainer + ' table.jrPage').height();
                    var reportWidth = $(SELECTORS.reportContainer).width();
                    $(SELECTORS.reportContainer).height(tableHeight * reportWidth / tableWidth);
                }
            } else {
                if (data.components.length > 0 && data.components[0].componentType !== 'chart') {
                    $(SELECTORS.reportContainer).height('');
                    $(SELECTORS.reportContainer).height($(SELECTORS.reportContainer).height());
                }
            }
        }

        function showLoadingIcon() {
            // If the report is visible, it will show the Loading... message.
            // If now, we should display our loading icon.
            if (!$(SELECTORS.reportContainer + ' > div.visualizejs').height()) {
                $(SELECTORS.loading).show();
            }
        }

        function refreshReport() {
            report.run().done(afterReportRendered).fail(errorHandler);
            $(SELECTORS.loadingMessage).html('Loading Report ...');
            showLoadingIcon();
        }

        function setupPaging() {
            $(SELECTORS.button.first).click(function() {
                var currentPage = report.pages() || 1;
                if (currentPage > 1) {
                    report.pages(1);
                    refreshReport();
                }
            });
            $(SELECTORS.button.previous).click(function() {
                var currentPage = report.pages() || 1;
                if (currentPage > 1) {
                    report.pages(--currentPage);
                    refreshReport();
                }
            });
            $(SELECTORS.button.next).click(function() {
                var currentPage = report.pages() || 1;
                var lastPage = report.data().totalPages;
                if (lastPage === undefined || currentPage < lastPage) {
                    report.pages(++currentPage);
                    refreshReport();
                }
            });
            $(SELECTORS.button.last).click(function() {
                var currentPage = report.pages() || 1;
                var lastPage = report.data().totalPages;
                if (lastPage > currentPage) {
                    report.pages(lastPage);
                    refreshReport();
                }
            });
            $(SELECTORS.pageCurrent).change(function() {
                var page = parseInt($(SELECTORS.pageCurrent).val(), 10);
                var currentPage = parseInt(report.pages()) || 1;
                var lastPage = report.data().totalPages;
                if (page === undefined || isNaN(page) || page < 1) {
                    page = 1;
                } else if (lastPage !== undefined && page > lastPage) {
                    page = lastPage;
                }
                if (page !== currentPage) {
                    report.pages(page);
                    refreshReport();
                }
                $(SELECTORS.pageCurrent).val(page);
            });
        }

        function setExportButtons() {
            $(SELECTORS.exportpdf).click(function() {
                report.export({
                    outputFormat: "pdf"
                }).done(function(link) {
                    var url = link.href ? link.href : link;
                    // Open new window to download report.
                    window.open(url);
                }).fail(function(err) {
                    notification.alert('Error', err.message, 'OK');
                });
            });
            $(SELECTORS.exportxlsx).click(function() {
                report.export({
                    outputFormat: "xlsx",
                    ignorePagination: true
                }).done(function(link) {
                    var url = link.href ? link.href : link;
                    // Open new window to download report.
                    window.open(url);
                }).fail(function(err) {
                    notification.alert('Error', err.message, 'OK');
                });
            });
        }

        function openDialog(selector) {
            $('body').addClass('modal-open');
            $('body').append('<div class="modal-backdrop in"></div>');

            $(selector).show();
            var header = $(selector + ' div.modal-header').outerHeight() || 0;
            var footer = $(selector + ' div.modal-footer').outerHeight() || 0;
            var margins = $(selector + ' div.modal-body').outerHeight() - $(selector + ' div.modal-body').height();
            var maxHeight = 0.8 * window.outerHeight - header - footer - margins;
            $(selector + ' div.modal-body').css('max-height', maxHeight);
        }

        function closeDialog(selector) {
            $(selector).hide();
            $('body').removeClass('modal-open');
            $('div.modal-backdrop.in').remove();
        }

        function setEmailButton() {
            $(SELECTORS.emailButton).click(function() {
                openDialog('#block_zoola_reports-emailform');
            });

            $('#block_zoola_reports-emailform input[name="cancel"]').prop('onclick', '').click(function(e) {
                e.preventDefault();
                closeDialog('#block_zoola_reports-emailform');
            });

            $('#block_zoola_reports-emailform button.close').click(function() {
                closeDialog('#block_zoola_reports-emailform');
            });

            $("#block_zoola_reports-emailform form").submit(function(e) {
                e.preventDefault();
                if (!$('#block_zoola_reports-emailform input[name="to"]').val() ||
                        !$('#block_zoola_reports-emailform input[name="subject"]').val()) {
                    return false;
                }
                var formData = $("#block_zoola_reports-emailform form").serialize();
                var args = {
                    jsonformdata: JSON.stringify(formData),
                    reportparams: JSON.stringify(inputControls.data().parameters)
                };

                $("#block_zoola_reports-emailform form").css('opacity', 0.3);
                $(".block_zoola_reports-emailwait").show();
                var promises = ajax.call([{
                    methodname: 'block_zoola_reports_email_report_form',
                    args: args,
                    done: function(result) {
                        closeDialog('#block_zoola_reports-emailform');
                        if (notification.addNotification) {
                            notification.addNotification({
                                message: result,
                                type: 'success'
                            });
                        } else {
                            notification.alert('Success', result, 'OK');
                        }
                    },
                    fail: function(err) {
                        notification.alert('Error', err.message);
                    }
                }]);
                promises[0].always(function() {
                    $(".block_zoola_reports-emailwait").hide();
                    $("#block_zoola_reports-emailform form").css('opacity', '');
                });
            });

            $('#block_zoola_reports_email').prop('disabled', false);
        }

        function setBackButton() {
            $(SELECTORS.backButton).click(myself.popReport);
        }

        function setFilterButtons() {
            function updateReportWithParameters() {
                closeDialog(SELECTORS.inputControls);
                report.params(inputControls.data().parameters).pages(1);
                refreshReport();
            }

            $(SELECTORS.inputControls + ' button.btn-apply').click(function() {
                updateReportWithParameters();
            });

            $(SELECTORS.inputControls + ' button.btn-reset').click(function() {
                inputControls.reset().then(function() {
                    if (!$.isEmptyObject(reportStack[0].defaultParams)) {
                        // Reset to report options' default filters.
                        return inputControls.params(reportStack[0].defaultParams).run();
                    }
                }).done(function() {
                    updateReportWithParameters();
                });
            });

            $(SELECTORS.inputControls + ' button.btn-cancel').click(function() {
                closeDialog(SELECTORS.inputControls);
            });

            $(SELECTORS.filterButton).click(function() {
                openDialog(SELECTORS.inputControls);
            });

        }

        function showButtons() {
            var container = SELECTORS.reportContainer;
            if ($(container).parents('#block-region-side-pre, #block-region-side-post').length >= 1) {
                // Side regions.
                $(SELECTORS.filterButton).html('&#9776;');
            } else if ($(container).parents('#block-region-content').length >= 1) {
                // Content region.
                $(SELECTORS.exportpdf + ', ' + SELECTORS.exportxlsx).show();
            } else {
                // Standalone report.
                $(SELECTORS.exportpdf + ', ' + SELECTORS.exportxlsx + ', ' + SELECTORS.emailButton).show();
            }
        }

        function showBackButton() {
            if (reportStack.length > 1) {
                $(SELECTORS.backButton).show();
            } else {
                $(SELECTORS.backButton).hide();
            }
        }

        function runReport(reporturi, defaultParams, runImmediately) {
            var container = SELECTORS.reportContainer;
            $(SELECTORS.reportContainer).height('');
            if (runImmediately) {
                $(SELECTORS.loadingMessage).html('Loading Report ...');
            } else {
                $(SELECTORS.loadingMessage).html('Loading Filters ...');
            }
            var params = {
                resource: reporturi,
                container: container,
                runImmediately: false,
                params: defaultParams,
                defaultJiveUi: {
                    enabled: true
                },
                events: {
                    changeTotalPages: function () {
                        updatePaging();
                    },
                    reportCompleted: function(status) {
                        if (status === 'ready') {
                            afterReportRendered();
                        }
                    },
                    beforeRender: function(el) {
                        $(SELECTORS.loading).hide();
                        var $table = $(el).find('table.jrPage'),
                            tableWidth = $table.width();
                        if (tableWidth > 0) {
                            // Set bottom margin (height of the last row).
                            $table.find('tr').last().height(20);

                            var $firstrow = $table.find('tr').first(), // First row contains column widths.
                                leftMargin = $firstrow.children().first().width(), // First column width is left margin.
                                rightMargin = $firstrow.children().last().width(),
                                dataColumnCount = this.data().components.length,
                                tableColumnCount = $firstrow.children().length;
                            if ((tableColumnCount === dataColumnCount + 2) && (rightMargin > leftMargin)) {
                                // Fix crosstab right margin.
                                // Table reports have dataColumnCount columns in report, plus 2 columns for margins,
                                // Crosstab reports have only left margin set, that's why we use
                                // (tableColumnCount === dataColumnCount + 2).
                                $firstrow.children().last().width(leftMargin);
                                // We reduced the right margin, now we should also reduce total table width.
                                $table.width(tableWidth - (rightMargin - leftMargin));
                            }
                        }
                    }
                },

                linkOptions: {
                    beforeRender: function (linkToElemPairs) {
                        linkToElemPairs.forEach(function (pair) {
                            var el = pair.element,
                                link = pair.data;
                            if (link.href || link.type === 'ReportExecution') {
                                el.style.cursor = "pointer";
                                el.style.textDecoration = "underline";
                            }
                        });
                    },
                    events: {
                        click: function(ev, link) {
                            if (link.href) {
                                window.open(link.href);
                                ev.stopPropagation();
                            }
                            if (link.type === 'ReportExecution') {
                                // Save current filters state, so we have them when we go back.
                                reportStack[0].currentParams = inputControls.data().parameters;

                                var newParams = {},
                                    newUri;
                                Object.keys(link.parameters).forEach(function(key) {
                                    if (key === '_report') {
                                        newUri = link.parameters[key];
                                    } else {
                                        newParams[key] = [link.parameters[key]];
                                    }
                                });
                                // When drill through allways run the report immediately.
                                myself.pushReport(newUri, newParams, true);
                            }
                        }
                    }
                }

            };
            if (fittocontainer) {
                params.scale = 'width';
            }
            report = visualize.report(params);
            inputControls = visualize.inputControls({
                resource: reporturi,
                container: SELECTORS.filtersBody,
                params: defaultParams,
                error: function(err) {
                    $(SELECTORS.filtersBody).html(err.message);
                    errorHandler(err);
                },
                success: function(data) {
                    if (data.length > 0) {
                        $(SELECTORS.filterButton).prop('disabled', false);
                        if (!runImmediately) {
                            // If the report is not to be run immediately, show input controls.
                            openDialog(SELECTORS.inputControls);
                            $(SELECTORS.loading).hide();
                        }
                    } else {
                        $(SELECTORS.filterButton).prop({
                            disabled: true,
                            title: 'The report does not have filters'
                        });
                        $(SELECTORS.filtersBody).html('The report does not have filters');
                        if (!runImmediately) {
                            // There are not input controls, run report immediately.
                            refreshReport();
                        }
                    }
                }
            });
            if (runImmediately) {
                refreshReport();
            }
        }

        this.setControls = function() {
            $(SELECTORS.controls).show();
            setupPaging();
            showButtons();
            setExportButtons();
            setEmailButton();
            setFilterButtons();
            setBackButton();
        };

        this.popReport = function() {
            reportStack.shift();
            var report = reportStack[0];
            showBackButton();
            // When we go back, always run report immediately with currentParams.
            runReport(report.reportUri, report.currentParams, true);
        };

        this.pushReport = function(reportUri, parameters, runImmediately) {
            reportStack.unshift({
                reportUri: reportUri,
                currentParams: {}, // These params will be used when we come back to this report.
                defaultParams: parameters // These params will be used for reseting filters.
            });
            showBackButton();
            runReport(reportUri, parameters, runImmediately);
        };

    };

    return {
        embedReport: function(visualizeUrl, token, reportid, reporturi, fittocontainer, runImmediately, filters) {
            require(['block_zoola_reports/visualize'], function(visualizeJS) {
                visualizeJS.getVisualize(visualizeUrl, token).done(function(visualize) {
                    var reportRunner = new ReportRunner(visualize, reportid, fittocontainer);
                    reportRunner.setControls();
                    reportRunner.pushReport(reporturi, filters, runImmediately);
                }).fail(function(error) {
                    $('#block_zoola_reports-' + reportid).html(error.message);
                });
            });
        }
    };
});
