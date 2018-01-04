module.exports = {
    MainNavigation: function(callback) {
        callback(require('js/mainNavigation'));
    },
    UsersController: function(callback) {
        require.ensure([], function() {
            callback(require('js/controllers/user'));
        });
    },
    LocalesController: function(callback) {
        require.ensure([], function() {
            callback(require('js/controllers/locale'));
        });
    },
    TorrentsController: function(callback) {
        require.ensure([], function() {
            callback(require('js/controllers/torrent'));
        });
    }
};
