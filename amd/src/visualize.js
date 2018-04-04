// This settings already exists in Moodle 3.0 and later.
require.config({
    waitSeconds: 0
});

define(['core/log', 'require', 'core/notification', 'jquery'], function(logger, require, notification, $) {

    var deferred = null;

    function VisualizeJS() {
    }

    function errorHandler(error) {
        notification.alert('Error', 'Failed to initialize Zoola report: ' + error.message, 'OK');
        logger.error('block/zoola_reports getVisualize: ' + error);
        deferred.reject(error);
    }

    VisualizeJS.prototype.getVisualize = function(url, token) {
        if (deferred === null) {
            deferred = $.Deferred();
            if (url.charAt(url.length - 1) === "/") {
                // Remove trailing slash.
                url = url.substr(0, url.length - 1);
            }
            require([url + '/client/visualize.js?_opt=true'], function(visualize) {
                visualize({
                    server: url,
                    auth: {
                        token: token,
                        preAuth: true,
                        tokenName: 'pp'
                    }
                }, function(v) {
                    deferred.resolve(v);
                }, errorHandler);
            }, errorHandler);
        }
        return deferred.promise();
    };

    return new VisualizeJS();
});
