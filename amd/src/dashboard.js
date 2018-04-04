define(['core/log', 'jquery', 'require'], function(logger, $, require) {
    return {
        embedDashboard: function (visualizeUrl, token, reportid, dashboarduri) {
            var container = '#block_zoola_reports-' + reportid + ' > div.block_zoola_reports_dashboard';

            function errorHandler(error) {
                $(container).html(error.message);
                logger.error(error);
            }

            require(['block_zoola_reports/visualize'], function(visualizeJS) {
                visualizeJS.getVisualize(visualizeUrl, token).done(function(visualize) {
                    $(container + ' span.message').html("Loading Dashboard ...");
                    visualize.dashboard({
                        resource: dashboarduri,
                        container: container,
                        success: function() {
                            logger.info('Dashboard ' + dashboarduri + ' run within ' + container);
                        },
                        error: errorHandler
                    });
                }).fail(errorHandler);
            });
        }
    };
});
